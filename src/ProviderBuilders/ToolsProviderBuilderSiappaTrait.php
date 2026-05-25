<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderSiappaTrait
{
    /**
     * Envelopes Siappa (operações *.Execute em namespace issqnwebev3v2).
     *
     * @param array|string $payload
     */
    private function buildSiappaEnvelope(array|string $payload, string $service, int $tpAmb): string
    {
        $data = $this->normalizePayload($payload);
        [$method, $body] = $this->buildSiappaMethodAndBody($data, $service, $tpAmb);
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<' . $method . ' xmlns="issqnwebev3v2">' . $body . '</' . $method . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }
}
