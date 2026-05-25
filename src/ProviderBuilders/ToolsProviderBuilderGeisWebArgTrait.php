<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderGeisWebArgTrait
{
    private function mapGeisWebArg(string $service): string
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'consultar_lote' => 'ConsultaLoteRps',
            'consultar_nfse', 'consultar_nf_se', 'consultar_nfse_rps', 'consultar_nf_se_rps' => 'ConsultaNfse',
            'cancelar_nfse', 'cancelar_nf_se' => 'CancelaNfse',
            default => 'EnviaLoteRps',
        };
    }
}
