<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderCTACancelXmlTrait
{
    /**
     * @param array<string,mixed> $data
     */
    private function buildCTACancelXml(array $data): string
    {
        $auth = $this->payloadAuth($data);
        $chave = trim((string) ($auth['ws_chave_acesso'] ?? $auth['token'] ?? ''));
        $numero = trim((string) ($data['numero_nfse'] ?? $data['numero'] ?? ''));
        $motivo = trim((string) ($data['motivo'] ?? 'Cancelamento solicitado.'));
        return '<Informacoes><chave_acesso>' . $this->xmlValue($chave) . '</chave_acesso>'
            . '<numero_nfse>' . $this->xmlValue($numero) . '</numero_nfse>'
            . '<motivo>' . $this->xmlValue($motivo) . '</motivo></Informacoes>';
    }
}
