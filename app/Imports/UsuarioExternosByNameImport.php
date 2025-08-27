<?php

namespace App\Imports;

use App\Models\UsuarioExterno;
use App\Models\{Documento, Asesor, Eps, Arl, Pension, Caja, SubtipoCotizante, EmpresaLocal, EmpresaExterna};
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Validators\Failure;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;
use Throwable;

class UsuarioExternosByNameImport implements
    OnEachRow, WithHeadingRow,
    SkipsOnError, SkipsOnFailure,
    WithBatchInserts, WithChunkReading
{
    use Importable;

    /** fallos acumulados que el controlador puede leer */
    public array $failuresList = [];

    /** contadores para diagnóstico */
    public int $processed = 0;
    public int $created   = 0;
    public int $skipped   = 0;

    /** caches de catálogos */
    protected array $map = [];

    /** Encabezados esperados (minúsculas) */
    protected array $expectedHeaders = [
        'documento','asesor','numero','fecha_expedicion',
        'primer_apellido','segundo_apellido','primer_nombre','segundo_nombre',
        'fecha_nacimiento','correo_electronico','direccion','telefono',
        'fecha_afiliacion','sexo','eps','arl','pension','caja',
        'subtipo_cotizante','empresa_local','empresa_externa',
        'sueldo','admon','seg_exequial','mora','otros_servicios',
        'cargo','estado','novedad','fecha_retiro'
    ];

    /** Control del chequeo de encabezados */
    protected bool $headersChecked = false;
    protected bool $abortImport = false;

    /**
     * @param int|null $empresaLocalIdForzada  Si viene, se usará SIEMPRE como empresa_local_id
     *                                         ignorando lo que venga en la columna "empresa_local".
     *                                         Si no viene, se intentará: columna -> sesión.
     */
    public function __construct(private ?int $empresaLocalIdForzada = null)
    {
        // Mapeos según tus migraciones
        $this->map['documentos'] = $this->buildMap(Documento::query(), ['nombre']);
        $this->map['asesores']   = $this->buildMap(Asesor::query(),   ['nombre','numero_documento']);
        $this->map['eps']        = $this->buildMap(Eps::query(),      ['nombre','codigo']);
        $this->map['pensions']   = $this->buildMap(Pension::query(),  ['nombre','codigo']);
        $this->map['cajas']      = $this->buildMap(Caja::query(),     ['nombre','codigo']);
        $this->map['subtipo_cotizantes'] = $this->buildMap(SubtipoCotizante::query(), ['codigo','nombre']);
        $this->map['empresa_local']      = $this->buildMap(EmpresaLocal::query(),     ['nombre','numero_documento']);
        $this->map['empresa_externas']   = $this->buildMap(EmpresaExterna::query(),   ['nombre','numero']);
        // ARL por nivel (riesgo 1..5) y/o nombre
        $this->map['arls'] = $this->buildArlLevelMap('nivel', 'nombre');
    }

    public function batchSize(): int { return 500; }
    public function chunkSize(): int { return 500; }

    public function onError(Throwable $e) { /* opcional log */ }

    /** requerido por SkipsOnFailure; además guardamos en $failuresList */
    public function onFailure(Failure ...$failures)
    {
        $this->failuresList = array_merge($this->failuresList, $failures);
    }

    public function onRow(Row $row)
    {
        $this->processed++;

        $r = $this->normalize($row->toArray());
        $rowIndex = $row->getIndex();
        $errors = [];

        /* ✅ Validar encabezados (solo 1 vez). Si no coinciden, aborta el archivo. */
        $this->validateHeadersOrAbort($r, $rowIndex);
        if ($this->abortImport) {
            $this->skipped++;
            return;
        }

        // No procesar filas “en blanco” (plantilla)
        if ($this->isRowEffectivelyEmpty($r)) {
            $this->skipped++;
            return;
        }

        // Resolver por nombres/niveles (tolerante)
        $documento_id = $this->resolve('documentos', $r['documento'] ?? null);
        if (!$documento_id) { $this->addFailure($errors, $rowIndex, 'documento', 'No se encontró el tipo de documento', $r); }

        $asesor_id = $this->resolve('asesores', $r['asesor'] ?? null);
        if (!$asesor_id) { $this->addFailure($errors, $rowIndex, 'asesor', 'No se encontró el asesor', $r); }

        $eps_id = $this->resolve('eps', $r['eps'] ?? null);
        if (!$eps_id) { $this->addFailure($errors, $rowIndex, 'eps', 'No se encontró la EPS', $r); }

        $arl_id = $this->resolveArlByLevel($r['arl'] ?? null);
        if (!$arl_id) { $this->addFailure($errors, $rowIndex, 'arl', 'No se encontró ARL por nivel (usa "riesgo 1..5")', $r); }

        $pension_id = $this->resolve('pensions', $r['pension'] ?? null);
        if (!$pension_id) { $this->addFailure($errors, $rowIndex, 'pension', 'No se encontró la administradora de pensión', $r); }

        $caja_id = $this->resolve('cajas', $r['caja'] ?? null);
        if (!$caja_id) { $this->addFailure($errors, $rowIndex, 'caja', 'No se encontró la caja de compensación', $r); }

        $subtipo_id = $this->resolve('subtipo_cotizantes', $r['subtipo_cotizante'] ?? null);
        if (!$subtipo_id) { $this->addFailure($errors, $rowIndex, 'subtipo_cotizante', 'No se encontró el subtipo de cotizante', $r); }

        /**
         * ✅ Empresa Local:
         *  - Si el importador se creó con empresaLocalIdForzada → usarla SIEMPRE.
         *  - Si no, intentar resolver "empresa_local" por nombre/código.
         *  - Si no viene o no se resuelve, intentar desde la sesión.
         */
        if ($this->empresaLocalIdForzada) {
            $empresa_local_id = $this->empresaLocalIdForzada;
        } else {
            if (!empty($r['empresa_local'])) {
                $empresa_local_id = $this->resolve('empresa_local', $r['empresa_local']);
            } else {
                $empresa_local_id = session('empresa_local_id');
            }
        }

        if (empty($empresa_local_id)) {
            $this->addFailure($errors, $rowIndex, 'empresa_local', 'No hay empresa activa para asignar (ni en Excel ni en sesión).', $r);
        }

        // ✅ Empresa Externa (sí se resuelve por nombre o id texto)
        $empresa_externa_id = $this->resolve('empresa_externas', $r['empresa_externa'] ?? null);
        if (!$empresa_externa_id) {
            $this->addFailure($errors, $rowIndex, 'empresa_externa', 'No se encontró la empresa externa', $r);
        }

        if (!empty($errors)) {
            $this->onFailure(...$errors);
            $this->skipped++;
            return;
        }

        // Datos ya resueltos a IDs
        $data = [
            'documento_id'          => $documento_id,
            'asesor_id'             => $asesor_id,
            'numero'                => (string)($r['numero'] ?? ''),
            'fecha_expedicion'      => $this->toDate($r['fecha_expedicion'] ?? null),
            'primer_apellido'       => $r['primer_apellido'] ?? null,
            'segundo_apellido'      => $r['segundo_apellido'] ?? null,
            'primer_nombre'         => $r['primer_nombre'] ?? null,
            'segundo_nombre'        => $r['segundo_nombre'] ?? null,
            'fecha_nacimiento'      => $this->toDate($r['fecha_nacimiento'] ?? null),
            'correo_electronico'    => $r['correo_electronico'] ?? null,
            'direccion'             => $r['direccion'] ?? null,
            'telefono'              => $r['telefono'] ?? null,
            'fecha_afiliacion'      => $this->toDate($r['fecha_afiliacion'] ?? null),
            'sexo'                  => $r['sexo'] ?? null,
            'eps_id'                => $eps_id,
            'arl_id'                => $arl_id,
            'pension_id'            => $pension_id,
            'caja_id'               => $caja_id,
            'subtipo_cotizantes_id' => $subtipo_id,
            'empresa_local_id'      => $empresa_local_id,
            'empresa_externa_id'    => $empresa_externa_id,
            'sueldo'                => $this->toNumber($r['sueldo'] ?? 0),
            'admon'                 => $this->toNumber($r['admon'] ?? 0),
            'seg_exequial'          => $this->toNumber($r['seg_exequial'] ?? 0),
            'mora'                  => $this->toNumber($r['mora'] ?? 0),
            'otros_servicios'       => $this->toNumber($r['otros_servicios'] ?? 0),
            'cargo'                 => $r['cargo'] ?? null,
            'estado'                => in_array(($r['estado'] ?? 1), [1,'1',true,'true'], true) ? 1 : 0,
            'novedad'               => $r['novedad'] ?? 'Ingreso',
            'fecha_retiro'          => $this->toDate($r['fecha_retiro'] ?? null),
        ];

        // Validación final (misma que tu controlador)
        $validator = Validator::make($data, [
            'documento_id'           => ['required','exists:documentos,id'],
            'asesor_id'              => ['required','exists:asesores,id'],
            'numero'                 => ['required','string','unique:usuario_externos,numero'],
            'fecha_expedicion'       => ['required','date'],
            'primer_apellido'        => ['required','string'],
            'segundo_apellido'       => ['nullable','string'],
            'primer_nombre'          => ['required','string'],
            'segundo_nombre'         => ['nullable','string'],
            'fecha_nacimiento'       => ['required','date'],
            'correo_electronico'     => ['nullable','email'],
            'direccion'              => ['required','string'],
            'telefono'               => ['required','string'],
            'fecha_afiliacion'       => ['required','date'],
            'sexo'                   => ['required', Rule::in(['M','F','Otro'])],
            'eps_id'                 => ['required','exists:eps,id'],
            'arl_id'                 => ['required','exists:arls,id'],
            'pension_id'             => ['required','exists:pensions,id'],
            'caja_id'                => ['required','exists:cajas,id'],
            'subtipo_cotizantes_id'  => ['required','exists:subtipo_cotizantes,id'],
            'empresa_local_id'       => ['required','exists:empresa_local,id'],
            'empresa_externa_id'     => ['required','exists:empresa_externas,id'],
            'sueldo'                 => ['required','numeric','min:0'],
            'admon'                  => ['required','numeric','min:0'],
            'seg_exequial'           => ['nullable','numeric','min:0'],
            'mora'                   => ['nullable','numeric','min:0'],
            'otros_servicios'        => ['nullable','numeric','min:0'],
            'cargo'                  => ['required','string'],
            'estado'                 => ['required', Rule::in([0,1])],
            'novedad'                => ['required', Rule::in(['Ingreso','Retiro'])],
            'fecha_retiro'           => ['nullable','date','after_or_equal:fecha_afiliacion','required_if:novedad,Retiro'],
        ]);

        if ($validator->fails()) {
            // Mostrar en pantalla las causas exactas
            $fails = [];
            foreach ($validator->errors()->toArray() as $attr => $msgs) {
                foreach ($msgs as $msg) {
                    $fails[] = $this->makeFailure($rowIndex, $attr, $msg, $r);
                }
            }
            $this->onFailure(...$fails);
            $this->skipped++;
            return;
        }

        UsuarioExterno::create($data);
        $this->created++;
    }

    /** ================== Helpers ================== */

    /** Valida encabezados; si difieren, crea un Failure y aborta todo el archivo */
    protected function validateHeadersOrAbort(array $rowAssoc, int $rowIndex): void
    {
        if ($this->headersChecked) return;
        $this->headersChecked = true;

        $norm = function($v){
            $s = mb_strtolower(trim((string)$v));
            $t = @iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s); if($t!==false && $t!==null) $s = $t;
            return preg_replace('/\s+/u',' ', $s);
        };

        $present  = array_map($norm, array_keys($rowAssoc));
        $expected = array_map($norm, $this->expectedHeaders);

        $missing = array_values(array_diff($expected, $present));
        $extra   = array_values(array_diff($present, $expected));

        if (!empty($missing) || !empty($extra)) {
            $msgs = [];
            if (!empty($missing)) $msgs[] = 'Faltan columnas: '.implode(', ', $missing);
            if (!empty($extra))   $msgs[] = 'Columnas desconocidas o con nombre distinto: '.implode(', ', $extra);

            $failure = new Failure($rowIndex, '_headers', $msgs, $rowAssoc);
            $this->onFailure($failure);
            $this->abortImport = true;
        }
    }

    // Clave: trim, minúsculas, sin tildes, espacios colapsados
    protected function key($v): string
    {
        $s = mb_strtolower(trim((string)$v));
        $s = preg_replace('/\s+/u', ' ', $s);
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if ($t !== false && $t !== null) $s = $t;
        $s = preg_replace('/[^a-z0-9 \-._#@]/i', '', $s);
        return $s;
    }

    protected function buildMap($query, array $keys): array
    {
        $map = [];
        foreach ($query->get(array_unique(array_merge(['id'], $keys))) as $item) {
            foreach ($keys as $k) {
                if (!isset($item->{$k})) continue;
                $v = $item->{$k};
                if ($v === null || $v === '') continue;
                $map[$this->key($v)] = $item->id;
            }
            $map[(string)$item->id] = $item->id; // id directo
        }
        return $map;
    }

    protected function resolve(string $table, $value): ?int
    {
        if ($value === null || $value === '') return null;
        $key = $this->key($value);

        if (isset($this->map[$table][$key])) return $this->map[$table][$key];

        $candidates = $this->map[$table] ?? [];
        $keys = array_keys($candidates);

        foreach ($keys as $k) {
            if (str_starts_with($k, $key) || str_starts_with($key, $k)) {
                return $candidates[$k];
            }
        }
        foreach ($keys as $k) {
            if (str_contains($k, $key)) return $candidates[$k];
        }
        return null;
    }

    protected function resolveArlByLevel($value): ?int
    {
        if ($value === null || $value === '') return null;
        $key = $this->normalizeRiskLabel($value);
        if (!$key) return null;

        if (isset($this->map['arls'][$key])) return $this->map['arls'][$key];

        foreach (array_keys($this->map['arls']) as $k) {
            if (str_starts_with($k, $key) || str_contains($k, $key)) {
                return $this->map['arls'][$k];
            }
        }
        return null;
    }

    protected function buildArlLevelMap(string $campoNivel = 'nivel', string $campoNombre = 'nombre'): array
    {
        $map = [];
        $rows = Arl::query()->get(['id', $campoNivel, $campoNombre]);

        foreach ($rows as $row) {
            if (isset($row->{$campoNivel}) && $row->{$campoNivel} !== '') {
                $k = $this->normalizeRiskLabel($row->{$campoNivel});
                if ($k) $map[$k] = $row->id;
            }
            if (!empty($row->{$campoNombre})) {
                $k = $this->normalizeRiskLabel($row->{$campoNombre});
                if ($k) $map[$k] = $row->id;
            }
            $map[(string)$row->id] = $row->id;
        }
        return $map;
    }

    protected function normalizeRiskLabel($value): ?string
    {
        if ($value === null) return null;
        $s = $this->key($value);
        if (preg_match('/\b([1-5])\b/', $s, $m)) return 'riesgo '.$m[1];
        if (preg_match('/\b(riesgo|nivel|clase)\s*(i{1,3}|iv|v)\b/', $s, $m)) {
            $roman = ['i'=>1,'ii'=>2,'iii'=>3,'iv'=>4,'v'=>5];
            $n = $roman[$m[2]] ?? null;
            return $n ? 'riesgo '.$n : null;
        }
        return null;
    }

    /** Crea y agrega un Failure incluyendo el valor original de la fila */
    protected function addFailure(array &$errors, int $rowIndex, string $attr, string $msg, array $rowValues): void
    {
        $errors[] = $this->makeFailure($rowIndex, $attr, $msg, $rowValues);
    }

    /** Helper para crear Failure con valores originales */
    protected function makeFailure(int $rowIndex, string $attr, string $msg, array $rowValues): Failure
    {
        return new Failure($rowIndex, $attr, [$msg], $rowValues);
    }

    protected function isRowEffectivelyEmpty(array $r): bool
    {
        $important = [
            'documento','asesor','numero','primer_nombre','primer_apellido',
            'eps','arl','pension','caja','empresa_externa'
        ];
        foreach ($important as $f) {
            if (!empty($r[$f])) return false;
        }
        return true;
    }

    private function normalize(array $row): array
    {
        $clean = [];
        foreach ($row as $k => $v) {
            $key = is_string($k) ? trim(mb_strtolower($k)) : $k;
            $clean[$key] = is_string($v) ? trim($v) : $v;
        }
        return $clean;
    }

    private function toDate($value)
    {
        if ($value === null || $value === '') return null;
        if (is_numeric($value)) {
            try { return Carbon::instance(ExcelDate::excelToDateTimeObject($value))->format('Y-m-d'); } catch (Throwable) {}
        }
        try { return Carbon::parse($value)->format('Y-m-d'); } catch (Throwable) { return null; }
    }

    private function toNumber($value)
    {
        if ($value === null || $value === '') return 0;
        $s = str_replace([' ', '$'], '', (string)$value);
        if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } elseif (strpos($s, ',') !== false) {
            $s = str_replace(',', '.', $s);
        }
        return (float)$s;
    }
}
