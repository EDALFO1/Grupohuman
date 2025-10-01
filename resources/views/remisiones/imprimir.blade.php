<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Remisión #{{ $remision->numero }}</title>
  <style>
    :root{
      --b:#000;
      --fs:13px;

      /* Variables globales para márgenes */
      --margin-left-screen: 0.5in;
      --margin-right-screen: 0.5in;
      --margin-top-screen: 0.5in;
      --margin-bottom-screen: 0.5in;

      --margin-left-print: 0.5in;
      --margin-right-print: 0.3in; /* Ajusta este valor para mover más a la izquierda */
      --margin-top-print: 0.5in;
      --margin-bottom-print: 0.5in;
    }

    *{ box-sizing: border-box; }
    html, body{ height:100%; }
    body{
      font-family: Arial, Helvetica, sans-serif;
      color:#000;
      font-size: var(--fs);
      margin: 0;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

    /* Media carta vertical (en pantalla) */
    @page {
      size: 5.5in 8.5in;
      margin-top: var(--margin-top-screen);
      margin-bottom: var(--margin-bottom-screen);
      margin-left: var(--margin-left-screen);
      margin-right: var(--margin-right-screen);
    }

    /* Ajustes solo al imprimir */
    @media print {
      @page {
        size: 5.5in 8.5in;
        margin-top: var(--margin-top-print);
        margin-bottom: var(--margin-bottom-print);
        margin-left: var(--margin-left-print);
        margin-right: var(--margin-right-print);
      }

      /* Respaldo para navegadores que ignoran @page */
      body {
        padding-top: var(--margin-top-print);
        padding-bottom: var(--margin-bottom-print);
        padding-left: var(--margin-left-print);
        padding-right: var(--margin-right-print);
      }

      .d-print-none{ display:none !important; }
      body{ margin:0; }
    }

    .sheet{ padding: 0; }

    /* Botón moderno */
    .actions{
      text-align: right;
      margin: 15px 0;
    }
    .btn{
      background: linear-gradient(135deg, #0C28A8, #2237A3); /* degradado elegante */
      color: #fff;
      font-size: 15px;
      font-weight: 600;
      border: none;
      border-radius: 12px;
      padding: 10px 18px;
      cursor: pointer;
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .btn:hover{
      background: linear-gradient(135deg, #0C28A8, #2237A3);
      transform: translateY(-2px);
      box-shadow: 0 6px 12px rgba(0,0,0,0.25);
    }
    .btn:active{
      transform: translateY(0);
      box-shadow: 0 3px 6px rgba(0,0,0,0.2);
    }

    .header{
      text-align:center;
      margin-bottom: 6px;
    }
    .empresa{
      font-size: 20px;
      font-weight: 700;
      letter-spacing:.2px;
    }
    .nit{
      font-size: 12px;
      margin-top: 2px;
    }
    .nit small{
      display:block;
      font-size: 11px;
      color:#333;
    }

    .meta{
      display:flex;
      justify-content: space-between;
      gap: 8px;
      margin: 10px 0 6px;
    }
    .meta .left, .meta .right{
      font-size: 12px;
      line-height: 1.3;
    }

    .linea{
      display:flex;
      justify-content: space-between;
      align-items: baseline;
      gap: 10px;
      margin: 6px 0;
    }
    .left-col, .right-col{ flex:1; }
    .label{ font-weight:700; }

    table.cuadro{
      width: 100%;
      border-collapse: collapse;
      margin-top: 8px;
      table-layout: fixed;
    }
    table.cuadro th, table.cuadro td{
      border: 1px solid var(--b);
      padding: 6px 8px;
      vertical-align: middle;
    }
    .bold{ font-weight:700; }
    .right{ text-align:right; }
    .center{ text-align:center; }

    /* Fila Total (resalta) */
    .fila-total td{
      font-size: 16px;
      padding: 8px 10px;
    }
    .total-text{ font-weight:700; }
    .total-num{ font-weight:800; }

    /* Nota editable */
    .nota{
      border:1px dashed #666;
      padding:8px;
      min-height: 60px;
      margin-top: 12px;
    }
    .nota[contenteditable="true"]:empty:before{
      content: "Escribe aquí instrucciones/notas para el cliente…";
      color:#888;
    }
  </style>
</head>
<body>

  <div class="sheet">
    <div class="actions d-print-none">
      <button class="btn" onclick="window.print()">🖨️ Imprimir</button>
    </div>

    @php
      use Carbon\Carbon;

      $u = $remision->usuarioExterno;
      $empresa = $u?->empresaLocal;

      $empresaNombre = data_get($empresa, 'nombre', '');

      // 1) Intentar varias llaves para el NIT
      $posiblesNIT = ['nit','NIT','numero_documento','num_documento','documento','identificacion','rut','tax_id'];
      $nitRaw = '';
      foreach ($posiblesNIT as $k) {
          $val = data_get($empresa, $k);
          if (filled($val)) { $nitRaw = (string)$val; break; }
      }

      // 2) Agregar DV si existe separado
      $dv = data_get($empresa, 'dv') ?? data_get($empresa, 'digito_verificacion');
      $nitShown = $nitRaw;
      if ($dv && strpos($nitRaw, '-') === false) {
          $nitShown = trim($nitRaw).'-'.trim($dv);
      }

      // 3) NIT en letras (solo dígitos)
      $nitDigits = preg_replace('/\D+/', '', $nitRaw);
      try {
        $nf = new \NumberFormatter('es', \NumberFormatter::SPELLOUT);
        $nitLiteral =  null;
      } catch (\Throwable $e) { $nitLiteral = null; }

      $fmt = fn($n) => '$'.number_format((float)$n, 0, ',', '.');

      $fecha = $remision->fecha instanceof Carbon ? $remision->fecha : Carbon::parse($remision->fecha);
      $periodo = $fecha->copy()->subMonthNoOverflow()->format('m/Y');

      $docTipo = $u?->documento?->nombre ?? 'Documento';
      $docNum  = $u?->numero ?? '';

      $nombreCompleto = trim(($u?->primer_nombre.' '.($u?->segundo_nombre ?? '').' '.$u?->primer_apellido.' '.($u?->segundo_apellido ?? '')));

      $vEPS      = $remision->valor_eps ?? 0;
      $vARL      = $remision->valor_arl ?? 0;
      $vAFP      = $remision->valor_pension ?? 0;
      $vCaja     = $remision->valor_caja ?? 0;
      $vIVA      = 0;

      $vMora       = $remision->valor_mora ?? 0;
      $vSegVida    = 0;
      $vExequial   = $remision->valor_exequial ?? 0;
      $vAdmon      = $remision->valor_admon ?? 0;
      $vMensajeria = 0;

      $total = $remision->total ?? ($vEPS + $vARL + $vAFP + $vCaja + $vIVA + $vMora + $vSegVida + $vExequial + $vAdmon + $vMensajeria + (int)($remision->otros_servicios ?? 0));

      $epsNombre   = $u?->eps?->nombre ?? '';
      $arlNombre   = $u?->arl?->nombre ?? '';
      $arlNivel    = $u?->arl?->nivel ?? '';
      $afpNombre   = $u?->pension?->nombre ?? '';
      $cajaNombre  = $u?->caja?->nombre ?? '';
    @endphp

    <!-- Encabezado -->
    <div class="header">
      @if($empresaNombre)
        <div class="empresa">{{ $empresaNombre }}</div>
      @endif
      @if($nitShown)
        <div class="nit">
          NIT: {{ $nitShown }}
          @if($nitLiteral)
            <small>({{ $nitLiteral }})</small>
          @endif
        </div>
      @endif
    </div>

    <!-- Meta -->
    <div class="meta">
      <div class="left">
        <div><span class="label">Fecha:</span> {{ $fecha->format('d/m/Y') }}</div>
        <div><span class="label">Período:</span> {{ $periodo }}</div>
      </div>
      <div class="right">
        <div><span class="label">Remisión N°:</span> {{ $remision->numero }}</div>
      </div>
    </div>

    <!-- Persona -->
    <div class="linea">
      <div class="left-col">
        <span class="label">Nombre:</span> {{ $nombreCompleto }}
      </div>
      <div class="right-col" style="text-align:right;">
        <span class="label">{{ $docTipo }}:</span> {{ $docNum }}
      </div>
    </div>
    <div class="linea">
      <div class="left-col">
        <span class="label">Dirección:</span> {{ $u?->direccion ?? '' }}
      </div>
      <div class="right-col" style="text-align:right;">
        <span class="label">Teléfono:</span> {{ $u?->telefono ?? '' }}
      </div>
    </div>

    <!-- Cuadro -->
    <table class="cuadro">
      <colgroup>
        <col style="width:35%">
        <col style="width:15%">
        <col style="width:18%">
        <col style="width:32%">
      </colgroup>
      <tbody>
        <tr>
          <td><span class="bold">EPS</span> {{ $epsNombre }}</td>
          <td class="right">{{ $fmt($vEPS) }}</td>
          <td class="bold">MORA</td>
          <td class="right">{{ $fmt($vMora) }}</td>
        </tr>
        <tr>
          <td><span class="bold">NIVEL ARL:</span> {{ $arlNivel ?: $arlNombre }}</td>
          <td class="right">{{ $fmt($vARL) }}</td>
          <td class="bold">Seg. vida</td>
          <td class="right">{{ $fmt($vSegVida) }}</td>
        </tr>
        <tr>
          <td><span class="bold">AFP</span> {{ $afpNombre }}</td>
          <td class="right">{{ $fmt($vAFP) }}</td>
          <td class="bold">Exequial</td>
          <td class="right">{{ $fmt($vExequial) }}</td>
        </tr>
        <tr>
          <td><span class="bold">CAJA</span> {{ $cajaNombre }}</td>
          <td class="right">{{ $fmt($vCaja) }}</td>
          <td class="bold">Admon</td>
          <td class="right">{{ $fmt($vAdmon) }}</td>
        </tr>
        <tr>
          <td><span class="bold">IVA</span></td>
          <td class="right">{{ $fmt($vIVA) }}</td>
          <td class="bold">Mensajería</td>
          <td class="right">{{ $fmt($vMensajeria) }}</td>
        </tr>
        <tr class="fila-total">
          <td class="total-text">TOTAL</td>
          <td class="right total-num" colspan="3">{{ $fmt($total) }}</td>
        </tr>
      </tbody>
    </table>

    <!-- Nota editable -->
    <div class="nota" contenteditable="true">
      CRA 9 # 9 - 49 Tel:8818282, No.cuenta:017070235944 del banco DAVIVIENDA CTA AHORROS, enviar consignación por WHATSAPP al 3152041979, 3183375879, sin soporte no se efectúa pago.
      <strong>Por favor cancelar los primeros días hábiles de cada mes para evitar inconsitencias en el servicio y negación de incapacidades</strong>
    </div>

  </div>
</body>
</html>
