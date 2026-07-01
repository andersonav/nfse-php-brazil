<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderSiappaMethodAndBodyTrait
{
    /**
     * @param array<string,mixed> $data
     * @return array{0:string,1:string}
     */
    private function buildSiappaMethodAndBody(array $data, string $service, int $tpAmb): array
    {
        $normalized = strtolower(trim($service));
        $body = trim((string) ($data['dados_xml'] ?? ''));
        if ($body !== '') {
            return match ($normalized) {
                'cancelar_nfse', 'cancelar_nf_se' => ['ws_cancela_nfse_token.Execute', $body],
                'consultar_nfse', 'consultar_nf_se', 'consultar_nfse_rps', 'consultar_nf_se_rps' => ['ws_consulta_nfse_token.Execute', $body],
                'gerar_token' => ['ws_gera_token.Execute', $body],
                default => ['ws_gera_nfse_token.Execute', $body],
            };
        }

        $auth = $this->payloadAuth($data);
        $user = trim((string) ($auth['ws_user'] ?? $auth['username'] ?? ''));
        $senha = trim((string) ($auth['ws_senha'] ?? $auth['password'] ?? ''));
        $token = trim((string) ($auth['ws_chave_autorizacao'] ?? $auth['token'] ?? ''));
        $cnpj = preg_replace('/\D+/', '', (string) ($auth['cnpj'] ?? $data['prestador_cnpj'] ?? ($data['rps'][0]['prestador']['cnpj'] ?? '')));
        $execucao = $tpAmb === 1 ? 'D' : 'T';

        return match ($normalized) {
            'cancelar_nfse', 'cancelar_nf_se' => [
                'ws_cancela_nfse_token.Execute',
                $this->buildSiappaCancelarBody($data, $user, $senha, $token, $cnpj, $execucao),
            ],
            'consultar_nfse', 'consultar_nf_se', 'consultar_nfse_rps', 'consultar_nf_se_rps' => [
                'ws_consulta_nfse_token.Execute',
                $this->buildSiappaConsultarBody($data, $user, $senha, $token, $cnpj, $execucao),
            ],
            'gerar_token' => [
                'ws_gera_token.Execute',
                $this->buildSiappaTokenBody($user, $senha, $cnpj, $execucao),
            ],
            default => [
                'ws_gera_nfse_token.Execute',
                $this->buildSiappaEmitBody($data, $user, $senha, $token, $cnpj, $execucao),
            ],
        };
    }
}
