<?php

namespace App\Services;

use DOMDocument;
use Exception;
use Illuminate\Support\Facades\Log;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

class DianSignatureService
{
    /**
     * Firma el XML UBL 2.1 con estándar XAdES-BES usando el certificado PFX de la DIAN.
     *
     * @param string $xmlPath Ruta relativa del XML a firmar (dentro de storage/app/)
     * @param string $certPath Ruta completa al certificado PFX
     * @param string $certPassword Clave del certificado PFX
     * @return string Ruta completa del archivo XML firmado
     */
    public function firmar($xmlPath, $certPath, $certPassword)
    {
        try {
            // ==============================
            // 📄 Cargar XML
            // ==============================
            $xmlFullPath = storage_path('app/' . ltrim($xmlPath, '/'));

            if (!file_exists($xmlFullPath)) {
                throw new Exception("No se encontró el XML a firmar: {$xmlFullPath}");
            }

            $doc = new DOMDocument();
            $doc->preserveWhiteSpace = false;
            $doc->formatOutput = true;

            if (!$doc->load($xmlFullPath)) {
                throw new Exception("No se pudo cargar el XML desde: {$xmlFullPath}");
            }

            // ==============================
            // 🔐 Cargar certificado PFX
            // ==============================
            if (!file_exists($certPath)) {
                throw new Exception("No se encontró el certificado PFX: {$certPath}");
            }

            $pfxContent = file_get_contents($certPath);
            if (!openssl_pkcs12_read($pfxContent, $certs, $certPassword)) {
                throw new Exception("No se pudo abrir el archivo PFX. Verifique la clave.");
            }

            $privateKey = $certs['pkey'];
            $publicCert = $certs['cert'];

            // ==============================
            // 🧾 Crear objeto de firma
            // ==============================
            $objDSig = new XMLSecurityDSig();
            $objDSig->setCanonicalMethod(XMLSecurityDSig::C14N);
            $objDSig->addReference(
                $doc,
                XMLSecurityDSig::SHA256,
                ['http://www.w3.org/2000/09/xmldsig#enveloped-signature'],
                ['force_uri' => true]
            );

            // ==============================
            // 🔑 Crear clave de firma
            // ==============================
            $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
            $objKey->loadKey($privateKey, false);

            // ==============================
            // ✍️ Aplicar firma al XML
            // ==============================
            $objDSig->sign($objKey);
            $objDSig->add509Cert($publicCert);

            // Insertar firma dentro del nodo <ext:ExtensionContent> existente
            $xpath = new \DOMXPath($doc);
            $xpath->registerNamespace('ext', 'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2');
            $signatureParent = $xpath->query('//ext:ExtensionContent')->item(0);

            if (!$signatureParent) {
                throw new Exception('No se encontró el nodo <ext:ExtensionContent> en el XML.');
            }

            $signatureNode = $objDSig->sigNode;
            $imported = $doc->importNode($signatureNode, true);
            $signatureParent->appendChild($imported);

            // ==============================
            // 💾 Guardar XML firmado
            // ==============================
            $signedDir = storage_path('app/facturas/xml_signed');
            if (!is_dir($signedDir)) {
                mkdir($signedDir, 0775, true);
                Log::info("📁 Carpeta creada: {$signedDir}");
            }

            $signedPath = $signedDir . '/' . basename($xmlFullPath);
            $doc->save($signedPath);

            Log::info("✅ XML firmado exitosamente: {$signedPath}");

            return $signedPath;
        } catch (Exception $e) {
            Log::error('❌ Error al firmar XML DIAN: ' . $e->getMessage());
            throw $e;
        }
    }
}
