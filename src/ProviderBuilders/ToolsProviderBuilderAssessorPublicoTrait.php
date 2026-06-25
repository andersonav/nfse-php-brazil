<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderAssessorPublicoTrait
{
    /**
     * @param array|string $payload
     */
    private function buildAssessorPublicoEnvelope(array|string $payload, string $service): string
    {
        $data = $this->normalizePayload($payload);
        $normalized = strtolower(trim($service));
        $operacao = match ($normalized) {
            'cancelar_nfse', 'cancelar_nf_se' => '2',
            'consultar_lote' => '3',
            'consultar_nfse', 'consultar_nf_se', 'consultar_nfse_rps', 'consultar_nf_se_rps' => '4',
            default => '1',
        };
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $auth = $this->payloadAuth($data);
        $user = trim((string) ($auth['username'] ?? $auth['ws_user'] ?? ''));
        $pass = trim((string) ($auth['password'] ?? $auth['ws_senha'] ?? ''));
        if ($user === '' || $pass === '') {
            throw new RuntimeException('AssessorPublico requer auth.username e auth.password.');
        }

        $dadosUsuario = '<nfse:Usuario>' . $this->xmlValue($user) . '</nfse:Usuario>'
            . '<nfse:Senha>' . md5($pass) . '</nfse:Senha>';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfse="nfse">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<nfse:Nfse.Execute>'
            . '<nfse:Operacao>' . $operacao . '</nfse:Operacao>'
            . $dadosUsuario
            . '<nfse:Webxml>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfse:Webxml>'
            . '</nfse:Nfse.Execute>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }
}
