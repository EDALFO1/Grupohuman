<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class DianApiService
{
    public function enviar($signedZipPath, $softwareId, $pin, $ambiente = 'HABILITACION')
    {
        try {
            $zipFullPath = realpath($signedZipPath) ?: storage_path('app/' . ltrim($signedZipPath, '/'));
            if (!file_exists($zipFullPath)) {
                throw new Exception("No se encontró el archivo ZIP firmado: {$zipFullPath}");
            }

            $zipContent = file_get_contents($zipFullPath);
            if (!$zipContent) {
                throw new Exception("No se pudo leer el archivo ZIP firmado.");
            }

            $encodedZip = base64_encode($zipContent);
            $testSetId = env('DIAN_TESTSET_ID');

            // ✅ Endpoint correcto
            $endpoint = $ambiente === 'PRODUCCION'
                ? 'https://vpfe.dian.gov.co/UBL2.1/WSFESetTestService.svc'
                : 'https://vpfe-hab.dian.gov.co/UBL2.1/WSFESetTestService.svc';

            // ✅ SOAP Envelope correcto (namespace: `tem`)
            $soapEnvelope = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                 xmlns:xsd="http://www.w3.org/2001/XMLSchema"
                 xmlns:soap12="http://www.w3.org/2003/05/soap-envelope"
                 xmlns:tem="http://wcf.dian.colombia">
  <soap12:Body>
    <tem:SendTestSetAsync>
      <tem:fileName>{$softwareId}.zip</tem:fileName>
      <tem:contentFile>{$encodedZip}</tem:contentFile>
      <tem:testSetId>{$testSetId}</tem:testSetId>
    </tem:SendTestSetAsync>
  </soap12:Body>
</soap12:Envelope>
XML;

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $soapEnvelope,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/soap+xml; charset=utf-8; action="http://wcf.dian.colombia/IWSFESetTestService/SendTestSetAsync"',
                    'Accept: application/soap+xml',
                ],
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_TIMEOUT => 180,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new Exception("Error cURL: {$error}");
            }

            if ($httpCode >= 400) {
                throw new Exception("Error HTTP {$httpCode}: {$response}");
            }

            Log::info("📩 Respuesta SOAP DIAN:\n" . $response);

            // Parsear el TrackID (SendTestSetAsyncResult)
            preg_match('/<SendTestSetAsyncResult>(.*?)<\/SendTestSetAsyncResult>/i', $response, $match);
            $trackId = $match[1] ?? null;

            return [
                'success' => true,
                'endpoint' => $endpoint,
                'httpCode' => $httpCode,
                'trackId' => $trackId,
                'rawResponse' => $response,
            ];

        } catch (Exception $e) {
            Log::error("❌ Error al enviar ZIP a la DIAN: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
