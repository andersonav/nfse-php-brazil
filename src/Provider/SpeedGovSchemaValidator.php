<?php
declare(strict_types=1);
namespace Alves\NfseBrasil\Provider;

use DOMDocument;
use RuntimeException;

/** Contrato fornecido pelo SpeedGov: documentacao WS/xsd. Ver storage/schemes/speedgov/origem.txt. */
final class SpeedGovSchemaValidator
{
    public static function validarEnvio(string $xml): void
    {
        self::validar($xml, 'enviar_lote_rps_envio_v1.xsd');
    }

    public static function validarCancelamento(string $xml): void
    {
        self::validar($xml, 'cancelar_nfse_envio_v1.xsd');
    }

    private static function validar(string $xml, string $schema): void
    {
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            if (!$dom->loadXML($xml, LIBXML_NONET) || $dom->doctype !== null) {
                throw new RuntimeException('XML de envio SpeedGov inválido.');
            }
            if (!$dom->schemaValidate(dirname(__DIR__, 2) . '/storage/schemes/speedgov/' . $schema, LIBXML_NONET)) {
                $errors = array_map(fn($e) => trim($e->message), libxml_get_errors());
                throw new RuntimeException('XML incompatível com o XSD SpeedGov: ' . implode(' | ', array_slice($errors, 0, 5)));
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
