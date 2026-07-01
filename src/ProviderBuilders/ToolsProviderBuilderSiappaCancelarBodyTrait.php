<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderSiappaCancelarBodyTrait
{
    /**
     * @param array<string,mixed> $data
     */
    private function buildSiappaCancelarBody(array $data, string $user, string $senha, string $token, string $cnpj, string $execucao): string
    {
        $dataEmissao = trim((string) ($data['data_emissao_nfse'] ?? $data['data_emissao'] ?? date('Y-m-d')));
        $numero = trim((string) ($data['numero_nfse'] ?? $data['numero'] ?? ''));
        $codServ = trim((string) ($data['cod_serv'] ?? $data['codigo_servico'] ?? ''));
        $codVerificacao = trim((string) ($data['cod_verificacao'] ?? $data['codigo_verificacao'] ?? ''));
        return '<Sdt_ws_002_in_canc_nfse_token>'
            . '<ws_002_in_prest_insc_seq>' . $this->xmlValue($user) . '</ws_002_in_prest_insc_seq>'
            . '<ws_002_in_prest_cnpj>' . $this->xmlValue($cnpj) . '</ws_002_in_prest_cnpj>'
            . '<ws_002_in_prest_ws_senha>' . $this->xmlValue($senha) . '</ws_002_in_prest_ws_senha>'
            . '<ws_002_in_prest_ws_token>' . $this->xmlValue($token) . '</ws_002_in_prest_ws_token>'
            . '<ws_002_in_nfse_ano>' . $this->xmlValue(substr($dataEmissao, 0, 4)) . '</ws_002_in_nfse_ano>'
            . '<ws_002_in_nfse_mes>' . $this->xmlValue(substr($dataEmissao, 5, 2)) . '</ws_002_in_nfse_mes>'
            . '<ws_002_in_nfse_numero>' . $this->xmlValue($numero) . '</ws_002_in_nfse_numero>'
            . '<ws_002_in_nfse_cod_especie>10</ws_002_in_nfse_cod_especie>'
            . '<ws_002_in_nfse_cod_atividade>' . $this->xmlValue($codServ) . '</ws_002_in_nfse_cod_atividade>'
            . '<ws_002_in_nfse_cod_validacao>' . $this->xmlValue($codVerificacao) . '</ws_002_in_nfse_cod_validacao>'
            . '<ws_002_in_opcao_execucao>' . $this->xmlValue($execucao) . '</ws_002_in_opcao_execucao>'
            . '</Sdt_ws_002_in_canc_nfse_token>';
    }
}
