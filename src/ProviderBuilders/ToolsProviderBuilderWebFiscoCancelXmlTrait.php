<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderWebFiscoCancelXmlTrait
{
    /**
     * @param array<string,mixed> $data
     */
    private function buildWebFiscoCancelXml(array $data): string
    {
        $auth = $this->payloadAuth($data);
        $usuario = trim((string) ($auth['usuario'] ?? $auth['username'] ?? ''));
        $senha = trim((string) ($auth['senha'] ?? $auth['password'] ?? ''));
        $cnpjPrefeitura = trim((string) ($data['cnpj_prefeitura'] ?? $auth['cnpj_prefeitura'] ?? ''));
        $cnpjPrestador = preg_replace('/\D+/', '', (string) ($data['prestador_cnpj'] ?? ''));
        $numero = trim((string) ($data['numero_nfse'] ?? $data['numero'] ?? ''));
        $tipo = trim((string) ($data['tipo_consulta'] ?? '1'));
        $obs = trim((string) ($data['motivo'] ?? 'Cancelamento solicitado.'));

        return '<CancelaNfe>'
            . '<usuario xsi:type="xsd:string">' . $this->xmlValue($usuario) . '</usuario>'
            . '<pass xsi:type="xsd:string">' . $this->xmlValue($senha) . '</pass>'
            . '<prf xsi:type="xsd:string">' . $this->xmlValue($cnpjPrefeitura) . '</prf>'
            . '<usr xsi:type="xsd:string">' . $this->xmlValue($cnpjPrestador) . '</usr>'
            . '<ctr xsi:type="xsd:string">' . $this->xmlValue($numero) . '</ctr>'
            . '<tipo xsi:type="xsd:string">' . $this->xmlValue($tipo) . '</tipo>'
            . '<obs xsi:type="xsd:string">' . $this->xmlValue($obs) . '</obs>'
            . '</CancelaNfe>';
    }
}
