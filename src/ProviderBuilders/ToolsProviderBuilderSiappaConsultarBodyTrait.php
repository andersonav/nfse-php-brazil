<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderSiappaConsultarBodyTrait
{
    /**
     * @param array<string,mixed> $data
     */
    private function buildSiappaConsultarBody(array $data, string $user, string $senha, string $token, string $cnpj, string $execucao): string
    {
        $dataRef = trim((string) ($data['data_inicial'] ?? $data['data_emissao'] ?? date('Y-m-d')));
        $numero = trim((string) ($data['numero_nfse'] ?? $data['numero'] ?? ''));
        $codServ = trim((string) ($data['cod_serv'] ?? $data['codigo_servico'] ?? ''));
        $codVerificacao = trim((string) ($data['cod_verificacao'] ?? $data['codigo_verificacao'] ?? ''));
        return '<Sdt_ws_003_in_cons_nfse_token>'
            . '<ws_003_in_prest_insc_seq>' . $this->xmlValue($user) . '</ws_003_in_prest_insc_seq>'
            . '<ws_003_in_prest_cnpj>' . $this->xmlValue($cnpj) . '</ws_003_in_prest_cnpj>'
            . '<ws_003_in_prest_ws_senha>' . $this->xmlValue($senha) . '</ws_003_in_prest_ws_senha>'
            . '<ws_003_in_prest_ws_token>' . $this->xmlValue($token) . '</ws_003_in_prest_ws_token>'
            . '<ws_003_in_nfse_ano>' . $this->xmlValue(substr($dataRef, 0, 4)) . '</ws_003_in_nfse_ano>'
            . '<ws_003_in_nfse_mes>' . $this->xmlValue(substr($dataRef, 5, 2)) . '</ws_003_in_nfse_mes>'
            . '<ws_003_in_nfse_numero>' . $this->xmlValue($numero) . '</ws_003_in_nfse_numero>'
            . '<ws_003_in_nfse_cod_especie>10</ws_003_in_nfse_cod_especie>'
            . '<ws_003_in_nfse_cod_atividade>' . $this->xmlValue($codServ) . '</ws_003_in_nfse_cod_atividade>'
            . '<ws_003_in_nfse_cod_validacao>' . $this->xmlValue($codVerificacao) . '</ws_003_in_nfse_cod_validacao>'
            . '<ws_003_in_nfse_opcao_envio_e_mail>N</ws_003_in_nfse_opcao_envio_e_mail>'
            . '<ws_003_in_opcao_execucao>' . $this->xmlValue($execucao) . '</ws_003_in_opcao_execucao>'
            . '</Sdt_ws_003_in_cons_nfse_token>';
    }
}
