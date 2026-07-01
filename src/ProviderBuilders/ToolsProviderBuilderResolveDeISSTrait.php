<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderResolveDeISSTrait
{
    /**
     * @param array<string,mixed> $params
     */
    private function resolveDeISSNamespace(array $params, string $url): string
    {
        $namespace = trim((string) ($params['NameSpace'] ?? $params['Namespace'] ?? $params['namespace'] ?? ''));
        if ($namespace !== '') {
            return $namespace;
        }
        return rtrim(strtok($url, '?') ?: $url, '/');
    }
}
