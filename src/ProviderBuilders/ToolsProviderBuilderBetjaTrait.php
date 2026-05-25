<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderBetjaTrait
{
    /**
     * Betja: fallback ABRASF request estilo padrão iiBrasil.
     *
     * @param array|string $payload
     */
    private function buildBetjaEnvelope(array|string $payload, string $service, ?string $versao): string
    {
        return $this->buildIiBrasilEnvelope($payload, $service, $versao);
    }

    private function buildBetjaSoapAction(string $service): string
    {
        return $this->buildIiBrasilSoapAction($service);
    }
}
