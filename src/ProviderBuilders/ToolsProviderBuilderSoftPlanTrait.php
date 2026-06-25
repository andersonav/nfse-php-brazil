<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderSoftPlanTrait
{
    /**
     * @param array<string,mixed> $auth
     */
    private function softPlanToken(string $baseUrl, array $auth): string
    {
        $accessToken = trim((string) ($auth['access_token'] ?? $auth['token'] ?? ''));
        if ($accessToken !== '') {
            return $accessToken;
        }

        $username = (string) ($auth['username'] ?? '');
        $password = (string) ($auth['password'] ?? '');
        $clientId = (string) ($auth['client_id'] ?? '');
        $clientSecret = (string) ($auth['client_secret'] ?? '');
        if ($username === '' || $password === '' || $clientId === '' || $clientSecret === '') {
            throw new RuntimeException('SoftPlan requer auth.username, auth.password, auth.client_id e auth.client_secret.');
        }

        $url = $this->joinBasePath($baseUrl, '/autenticacao/oauth/token');
        $query = http_build_query([
            'grant_type' => 'password',
            'username' => $username,
            'password' => $password,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);
        $basic = base64_encode($clientId . ':' . $clientSecret);
        $response = $this->postFormToUrl($url . '?' . $query, '', [
            'Authorization: Basic ' . $basic,
        ]);

        $token = trim((string) (($response['access_token'] ?? '') ?: ($response['token'] ?? '')));
        if ($token === '') {
            throw new RuntimeException('Nao foi possivel obter token SoftPlan.');
        }
        return $token;
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function softPlanXmlForEmitir(array $payload): string
    {
        $xml = trim((string) ($payload['dados_xml'] ?? $payload['xml_processamento_nfpse'] ?? ''));
        if ($xml !== '') {
            return $xml;
        }
        return '<xmlProcessamentoNfpse>' . $this->buildAbrasfGerarNfseEnvioXml($payload) . '</xmlProcessamentoNfpse>';
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function softPlanXmlForCancelar(array $payload): string
    {
        $xml = trim((string) ($payload['dados_xml'] ?? ''));
        if ($xml !== '') {
            return $xml;
        }

        $numeroNfse = trim((string) ($payload['numero_nfse'] ?? ''));
        $motivo = trim((string) ($payload['motivo'] ?? ''));
        $codigoVerificacao = trim((string) ($payload['codigo_verificacao'] ?? ''));
        $nuAedf = trim((string) ($payload['nu_aedf'] ?? $payload['chave_autorizacao'] ?? ''));
        if ($numeroNfse === '' || $motivo === '' || $codigoVerificacao === '' || $nuAedf === '') {
            throw new RuntimeException('SoftPlan cancelamento requer numero_nfse, motivo, codigo_verificacao e nu_aedf/chave_autorizacao.');
        }

        return '<xmlCancelamentoNfpse>'
            . '<motivoCancelamento>' . $this->xmlValue($motivo) . '</motivoCancelamento>'
            . '<nuAedf>' . $this->xmlValue($nuAedf) . '</nuAedf>'
            . '<nuNotaFiscal>' . $this->xmlValue($numeroNfse) . '</nuNotaFiscal>'
            . '<codigoVerificacao>' . $this->xmlValue($codigoVerificacao) . '</codigoVerificacao>'
            . '</xmlCancelamentoNfpse>';
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function softPlanXmlForSubstituir(array $payload): string
    {
        $xml = trim((string) ($payload['dados_xml'] ?? ''));
        if ($xml !== '') {
            return $xml;
        }
        return '<xmlProcessamentoNfpseSubstituta>' . $this->buildAbrasfSubstituirNfseEnvioXml($payload) . '</xmlProcessamentoNfpseSubstituta>';
    }

    private function emitirSoftPlan(string $baseUrl, array|string $payload): mixed
    {
        $data = $this->normalizePayload($payload);
        $auth = $this->payloadAuth($data);
        $token = $this->softPlanToken($baseUrl, $auth);
        return $this->postXmlToUrl(
            $this->joinBasePath($baseUrl, '/processamento/notas/processa'),
            $this->softPlanXmlForEmitir($data),
            ['Authorization: Bearer ' . $token, 'Accept: */*']
        );
    }

    private function cancelarSoftPlan(string $baseUrl, array|string $payload): mixed
    {
        $data = $this->normalizePayload($payload);
        $auth = $this->payloadAuth($data);
        $token = $this->softPlanToken($baseUrl, $auth);
        return $this->postXmlToUrl(
            $this->joinBasePath($baseUrl, '/cancelamento/notas/cancela'),
            $this->softPlanXmlForCancelar($data),
            ['Authorization: Bearer ' . $token, 'Accept: */*']
        );
    }

    private function substituirSoftPlan(string $baseUrl, array|string $payload): mixed
    {
        $data = $this->normalizePayload($payload);
        $auth = $this->payloadAuth($data);
        $token = $this->softPlanToken($baseUrl, $auth);
        return $this->postXmlToUrl(
            $this->joinBasePath($baseUrl, '/processamento/notas/processa-substituta'),
            $this->softPlanXmlForSubstituir($data),
            ['Authorization: Bearer ' . $token, 'Accept: */*']
        );
    }
}
