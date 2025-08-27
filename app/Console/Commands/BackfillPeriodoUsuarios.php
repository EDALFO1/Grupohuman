<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Recibo;
use App\Models\PeriodoUsuario;
use App\Models\UsuarioExterno;
use Carbon\Carbon;

class BackfillPeriodoUsuarios extends Command
{
    /**
     * Nombre/llave del comando para Artisan.
     *
     * Uso:
     *   php artisan periodos:backfill --empresa=1 --usuario=123
     */
    protected $signature = 'periodos:backfill {--empresa=} {--usuario=}';

    protected $description = 'Reconstruye marcas de período (Activo/Retirado) a partir de recibos existentes y de la fecha de afiliación de usuarios.';

    public function handle()
    {
        $empresaOpt = $this->option('empresa');
        $usuarioOpt = $this->option('usuario');

        $total = 0;
        $totalRecibos = 0;
        $totalUsuarios = 0;

        // ------------------------------------------------------------
        // 1) Backfill desde RECIBOS → marca período (fecha + 1 mes)
        // ------------------------------------------------------------
        $this->info('Procesando recibos...');
        $query = Recibo::query()
            ->when($empresaOpt, fn($q) => $q->where('empresa_local_id', (int) $empresaOpt))
            ->when($usuarioOpt, fn($q) => $q->where('usuario_externo_id', (int) $usuarioOpt))
            ->orderBy('id');

        $query->chunkById(500, function ($recibos) use (&$total, &$totalRecibos) {
            foreach ($recibos as $r) {
                $periodo = Carbon::parse($r->fecha)
                    ->addMonthNoOverflow() // Y-m del "siguiente período"
                    ->format('Y-m');

                $estado = ($r->novedad === 'Retiro') ? 'Retirado' : 'Activo';

                PeriodoUsuario::updateOrCreate(
                    [
                        'empresa_local_id'   => (int) $r->empresa_local_id,
                        'usuario_externo_id' => (int) $r->usuario_externo_id,
                        'periodo'            => $periodo,
                    ],
                    [
                        'estado'    => $estado,
                        'recibo_id' => (int) $r->id,
                    ]
                );

                $total++;
                $totalRecibos++;
            }
        });

        // -------------------------------------------------------------------------
        // 2) Backfill desde USUARIOS → marca ACTIVO en período siguiente a afiliación
        //    (ingreso en agosto ⇒ activo en septiembre), solo si NO existe la marca.
        // -------------------------------------------------------------------------
        $this->info('Procesando usuarios por fecha de afiliación...');
        $uQuery = UsuarioExterno::query()
            ->when($empresaOpt, fn($q) => $q->where('empresa_local_id', (int) $empresaOpt))
            ->when($usuarioOpt, fn($q) => $q->where('id', (int) $usuarioOpt))
            ->orderBy('id');

        $uQuery->chunkById(500, function ($usuarios) use (&$total, &$totalUsuarios) {
            foreach ($usuarios as $u) {
                if (!$u->fecha_afiliacion) {
                    continue;
                }

                $periodo = Carbon::parse($u->fecha_afiliacion)
                    ->addMonthNoOverflow()
                    ->format('Y-m');

                // Si ya existe cualquier marca (Activo/Retirado) para ese usuario/empresa/período, no crear otra.
                $exists = PeriodoUsuario::where([
                    'empresa_local_id'   => (int) $u->empresa_local_id,
                    'usuario_externo_id' => (int) $u->id,
                    'periodo'            => $periodo,
                ])->exists();

                if (!$exists) {
                    PeriodoUsuario::create([
                        'empresa_local_id'   => (int) $u->empresa_local_id,
                        'usuario_externo_id' => (int) $u->id,
                        'periodo'            => $periodo,
                        'estado'             => 'Activo',
                        'recibo_id'          => null,
                    ]);

                    $total++;
                    $totalUsuarios++;
                }
            }
        });

        $this->info("Backfill completado.");
        $this->info(" - Registros (recibos):  {$totalRecibos}");
        $this->info(" - Registros (usuarios): {$totalUsuarios}");
        $this->info(" - TOTAL procesados:     {$total}");

        return self::SUCCESS;
    }
}
