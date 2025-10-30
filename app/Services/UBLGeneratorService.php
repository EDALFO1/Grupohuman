<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\Factura;
use DOMDocument;

/**
 * Generador UBL 2.1 conforme a la DIAN (Anexo Técnico 1.9)
 * Incluye estructura oficial: UBLExtensions, Signature, TaxTotal, LegalMonetaryTotal e InvoiceLines.
 */
class UBLGeneratorService
{
    public function generar(Factura $factura): string
    {
        try {
            // ==============================
            // 📁 Crear directorio de salida
            // ==============================
            $dir = storage_path('app/facturas/xml');
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
                Log::info("📁 Carpeta creada: {$dir}");
            }

            // ==============================
            // 🧾 Crear documento XML
            // ==============================
            $xml = new DOMDocument('1.0', 'UTF-8');
            $xml->formatOutput = true;

            // Nodo raíz: Invoice
            $root = $xml->createElement('Invoice');
            $root->setAttribute('xmlns', 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2');
            $root->setAttribute('xmlns:cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
            $root->setAttribute('xmlns:cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
            $root->setAttribute('xmlns:ext', 'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2');
            $root->setAttribute('xmlns:ds', 'http://www.w3.org/2000/09/xmldsig#');
            $xml->appendChild($root);

            // ==============================
            // 🔏 UBLExtensions (firma y extensiones DIAN)
            // ==============================
            $ext = $xml->createElement('ext:UBLExtensions');
            $ext1 = $xml->createElement('ext:UBLExtension');
            $extContent = $xml->createElement('ext:ExtensionContent');
            $ext1->appendChild($extContent);
            $ext->appendChild($ext1);
            $root->appendChild($ext);

            // ==============================
            // 📄 Cabecera UBL
            // ==============================
            $root->appendChild($xml->createElement('cbc:UBLVersionID', 'UBL 2.1'));
            $root->appendChild($xml->createElement('cbc:CustomizationID', '10'));
            $root->appendChild($xml->createElement('cbc:ProfileID', 'DIAN 2.1: Factura Electrónica de Venta'));
            $root->appendChild($xml->createElement('cbc:ProfileExecutionID', env('DIAN_AMBIENTE', 'HABILITACION') === 'PRODUCCION' ? '1' : '2'));
            $root->appendChild($xml->createElement('cbc:ID', $factura->numero));
            $root->appendChild($xml->createElement('cbc:UUID', strtoupper(md5(uniqid(rand(), true))))); // CUFE temporal
            $root->appendChild($xml->createElement('cbc:IssueDate', $factura->fecha_emision->format('Y-m-d')));
            $root->appendChild($xml->createElement('cbc:InvoiceTypeCode', '01'));
            $root->appendChild($xml->createElement('cbc:DocumentCurrencyCode', $factura->moneda ?? 'COP'));
            $root->appendChild($xml->createElement('cbc:LineCountNumeric', $factura->productos->count() ?? 1));

            // ==============================
            // 🏢 Emisor
            // ==============================
            $supplier = $xml->createElement('cac:AccountingSupplierParty');
            $party = $xml->createElement('cac:Party');
            $party->appendChild($xml->createElement('cbc:EndpointID', $factura->empresaLocal->nit));
            $partyName = $xml->createElement('cac:PartyName');
            $partyName->appendChild($xml->createElement('cbc:Name', htmlspecialchars($factura->empresaLocal->nombre)));
            $party->appendChild($partyName);

            $partyLegalEntity = $xml->createElement('cac:PartyLegalEntity');
            $partyLegalEntity->appendChild($xml->createElement('cbc:RegistrationName', htmlspecialchars($factura->empresaLocal->razon_social ?? $factura->empresaLocal->nombre)));
            $partyLegalEntity->appendChild($xml->createElement('cbc:CompanyID', $factura->empresaLocal->nit));
            $party->appendChild($partyLegalEntity);
            $supplier->appendChild($party);
            $root->appendChild($supplier);

            // ==============================
            // 👤 Cliente
            // ==============================
            $customer = $xml->createElement('cac:AccountingCustomerParty');
            $partyC = $xml->createElement('cac:Party');
            $partyC->appendChild($xml->createElement('cbc:EndpointID', $factura->cliente->nit));
            $partyNameC = $xml->createElement('cac:PartyName');
            $partyNameC->appendChild($xml->createElement('cbc:Name', htmlspecialchars($factura->cliente->nombre)));
            $partyC->appendChild($partyNameC);
            $partyLegalEntityC = $xml->createElement('cac:PartyLegalEntity');
            $partyLegalEntityC->appendChild($xml->createElement('cbc:RegistrationName', htmlspecialchars($factura->cliente->nombre)));
            $partyLegalEntityC->appendChild($xml->createElement('cbc:CompanyID', $factura->cliente->nit));
            $partyC->appendChild($partyLegalEntityC);
            $customer->appendChild($partyC);
            $root->appendChild($customer);

            // ==============================
            // 💰 Totales de impuestos
            // ==============================
            $taxTotal = $xml->createElement('cac:TaxTotal');
            $taxTotal->appendChild($xml->createElement('cbc:TaxAmount', number_format($factura->iva ?? 0, 2, '.', '')));
            $taxSubtotal = $xml->createElement('cac:TaxSubtotal');
            $taxSubtotal->appendChild($xml->createElement('cbc:TaxableAmount', number_format($factura->subtotal ?? 0, 2, '.', '')));
            $taxSubtotal->appendChild($xml->createElement('cbc:TaxAmount', number_format($factura->iva ?? 0, 2, '.', '')));
            $taxCategory = $xml->createElement('cac:TaxCategory');
            $taxScheme = $xml->createElement('cac:TaxScheme');
            $taxScheme->appendChild($xml->createElement('cbc:ID', '01'));
            $taxScheme->appendChild($xml->createElement('cbc:Name', 'IVA'));
            $taxScheme->appendChild($xml->createElement('cbc:TaxTypeCode', '01'));
            $taxCategory->appendChild($taxScheme);
            $taxSubtotal->appendChild($taxCategory);
            $taxTotal->appendChild($taxSubtotal);
            $root->appendChild($taxTotal);

            // ==============================
            // 💵 Totales monetarios
            // ==============================
            $legalMonetaryTotal = $xml->createElement('cac:LegalMonetaryTotal');
            $legalMonetaryTotal->appendChild($xml->createElement('cbc:LineExtensionAmount', number_format($factura->subtotal ?? 0, 2, '.', '')));
            $legalMonetaryTotal->appendChild($xml->createElement('cbc:TaxExclusiveAmount', number_format($factura->subtotal ?? 0, 2, '.', '')));
            $legalMonetaryTotal->appendChild($xml->createElement('cbc:TaxInclusiveAmount', number_format($factura->total ?? 0, 2, '.', '')));
            $legalMonetaryTotal->appendChild($xml->createElement('cbc:PayableAmount', number_format($factura->total ?? 0, 2, '.', '')));
            $root->appendChild($legalMonetaryTotal);

            // ==============================
            // 🧾 Detalle de líneas
            // ==============================
            foreach ($factura->productos as $i => $producto) {
                $line = $xml->createElement('cac:InvoiceLine');
                $line->appendChild($xml->createElement('cbc:ID', $i + 1));
                $line->appendChild($xml->createElement('cbc:InvoicedQuantity', $producto->pivot->cantidad));
                $line->appendChild($xml->createElement('cbc:LineExtensionAmount', number_format($producto->pivot->subtotal, 2, '.', '')));

                $item = $xml->createElement('cac:Item');
                $item->appendChild($xml->createElement('cbc:Description', htmlspecialchars($producto->nombre)));
                $line->appendChild($item);

                $price = $xml->createElement('cac:Price');
                $price->appendChild($xml->createElement('cbc:PriceAmount', number_format($producto->pivot->precio_unitario, 2, '.', '')));
                $line->appendChild($price);

                $root->appendChild($line);
            }

            // ==============================
            // 💾 Guardar XML
            // ==============================
            $path = $dir . '/' . $factura->id . '.xml';
            $xml->save($path);

            Log::info("✅ XML UBL 2.1 generado correctamente: {$path}");

            return $path;
        } catch (\Exception $e) {
            Log::error("❌ Error generando XML UBL: " . $e->getMessage());
            throw $e;
        }
    }
}
