<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderIPMTrait
{
    private function emitirIpm(string $url, array|string $payload, string $service, ?string $versao): mixed
    {
        return $this->requestIpm($url, $payload, $service, $versao);
    }

    private function cancelarIpm(string $url, array|string $payload, string $service, ?string $versao): mixed
    {
        return $this->requestIpm($url, $payload, $service, $versao);
    }

    private function substituirIpm(string $url, array|string $payload, string $service, ?string $versao): mixed
    {
        return $this->requestIpm($url, $payload, $service, $versao);
    }

    private function requestIpm(string $url, array|string $payload, string $service, ?string $versao): mixed
    {
        $data = $this->normalizePayload($payload);
        $normalized = strtolower(trim($service));
        $authHeaders = [];
        $auth = $this->payloadAuth($data);
        $user = (string) ($auth['username'] ?? '');
        $pass = (string) ($auth['password'] ?? '');
        if ($user !== '' || $pass !== '') {
            $authHeaders[] = 'Authorization: Basic ' . base64_encode($user . ':' . $pass);
        }

        $is204 = str_starts_with((string) $versao, '2.04') || str_starts_with((string) $versao, '2.4');
        if ($is204) {
            $xml = $this->buildIpm204Envelope($data, $normalized);
            $soapAction = $this->buildIpm204SoapAction($normalized);
            return $this->postXmlToUrl($url, $xml, array_merge($authHeaders, [
                'SOAPAction: "' . $soapAction . '"',
            ]));
        }

        $body = trim((string) ($data['dados_xml'] ?? ''));
        if ($body === '') {
            $body = $this->buildAbrasfDataForMethod($data, $normalized);
        }
        return $this->postXmlToUrl($url, $body, $authHeaders);
    }
}
