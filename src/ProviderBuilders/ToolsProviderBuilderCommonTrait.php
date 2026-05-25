<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderCommonTrait
{
    private function xmlValue(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function xmlAttr(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * @param array|string $payload
     * @return array<string,mixed>
     */
    private function normalizePayload(array|string $payload): array
    {
        $data = is_array($payload) ? $payload : json_decode((string) $payload, true);
        if (!is_array($data)) {
            throw new RuntimeException('Payload invalido.');
        }
        return $data;
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private function payloadAuth(array $payload): array
    {
        $providerExtras = is_array($payload['provider_extras'] ?? null) ? $payload['provider_extras'] : [];
        $auth = $payload['auth']
            ?? $payload['softplan_auth']
            ?? ($providerExtras['auth'] ?? []);
        return is_array($auth) ? $auth : [];
    }

    private function joinBasePath(string $baseUrl, string $path): string
    {
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

    private function extractInnerXmlByTagName(string $xml, string $tag): string
    {
        $tagNoNs = preg_quote($tag, '/');
        if (preg_match('/<' . $tagNoNs . '(?:\\s[^>]*)?>(.*)<\\/' . $tagNoNs . '>/is', $xml, $m) === 1) {
            return (string) $m[1];
        }
        if (preg_match('/<[^:>]+:' . $tagNoNs . '(?:\\s[^>]*)?>(.*)<\\/[^:>]+:' . $tagNoNs . '>/is', $xml, $m) === 1) {
            return (string) $m[1];
        }
        return $xml;
    }

    private function asCdata(string $content): string
    {
        return '<![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', $content) . ']]>';
    }
}
