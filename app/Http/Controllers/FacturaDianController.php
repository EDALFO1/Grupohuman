<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Services\UBLGeneratorService;
use App\Services\DianSignatureService;
use App\Services\DianApiService;
use Illuminate\Support\Facades\Log;
use Exception;

class FacturaDianController extends Controller
{
    /**
     * Envía una factura electrónica a la DIAN:
     * 1️⃣ Genera el XML UBL 2.1
     * 2️⃣ Firma el XML con el certificado PFX
     * 3️⃣ Envía a la DIAN (habilitación o producción)
     */
    public function enviar(Factura $factura)
    {
        try {
            // ==============================
            // INSTANCIAS DE LOS SERVICIOS
            // ==============================
            $ubl = new UBLGeneratorService();
            $signer = new DianSignatureService();
            $api = new DianApiService();

            // ==============================
            // 1️⃣ GENERAR XML
            // ==============================
            $xmlPath = $ubl->generar($factura); // ruta relativa (storage/app/)
            Log::info("XML generado correctamente en: {$xmlPath}");

            // ==============================
            // 2️⃣ FIRMAR XML
            // ==============================
            $certPath = storage_path('certificados/certificado.pfx');
            $certPassword = env('CERT_PASSWORD');

            $signedXmlPath = $signer->firmar($xmlPath, $certPath, $certPassword);
            Log::info("XML firmado correctamente: {$signedXmlPath}");

            // ==============================
            // 3️⃣ ENVIAR A LA DIAN
            // ==============================
            $respuesta = $api->enviar(
                $signedXmlPath,
                env('DIAN_SOFTWARE_ID'),
                env('DIAN_PIN'),
                env('DIAN_AMBIENTE', 'HABILITACION')
            );

            Log::info('Respuesta DIAN:', $respuesta);

            // ==============================
            // 4️⃣ ACTUALIZAR FACTURA EN BD
            // ==============================
            $factura->update([
                'xml_ubl' => $signedXmlPath,
                'respuesta_dian' => json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                'estado_envio' => isset($respuesta['isValid']) && $respuesta['isValid'] ? 'Aceptado' : 'Rechazado',
            ]);

            return back()->with('success', '✅ Factura enviada exitosamente a la DIAN.');

        } catch (Exception $e) {
            Log::error('❌ Error al enviar factura a la DIAN: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors([
                'error' => 'Error al enviar la factura: ' . $e->getMessage()
            ]);
        }
    }
}
