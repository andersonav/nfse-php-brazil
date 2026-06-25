<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderSiappaEmitBodyTrait
{
    /**
     * @param array<string,mixed> $data
     */
    private function buildSiappaEmitBody(array $data, string $user, string $senha, string $token, string $cnpj, string $execucao): string
    {
        $xmlRps = trim((string) ($data['rps_xml'] ?? $data['xml_rps'] ?? $data['nfse_xml'] ?? ''));
        if ($xmlRps === '') {
            $xmlRps = $this->buildAbrasfDataForMethod($data, 'gerar_nfse');
        }
        return '<Sdt_ws_001_in_gera_nfse_token>'
            . '<ws_001_in_prest_insc_seq>' . $this->xmlValue($user) . '</ws_001_in_prest_insc_seq>'
            . '<ws_001_in_prest_cnpj>' . $this->xmlValue($cnpj) . '</ws_001_in_prest_cnpj>'
            . '<ws_001_in_prest_ws_senha>' . $this->xmlValue($senha) . '</ws_001_in_prest_ws_senha>'
            . '<ws_001_in_prest_ws_token>' . $this->xmlValue($token) . '</ws_001_in_prest_ws_token>'
            . '<ws_001_in_opcao_execucao>' . $this->xmlValue($execucao) . '</ws_001_in_opcao_execucao>'
            . '<ws_001_in_xml_nfse><![CDATA[' . $xmlRps . ']]></ws_001_in_xml_nfse>'
            . '</Sdt_ws_001_in_gera_nfse_token>';
    }
}
