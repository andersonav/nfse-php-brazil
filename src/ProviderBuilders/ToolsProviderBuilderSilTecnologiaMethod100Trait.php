<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderSilTecnologiaMethod100Trait
{
    private function mapSilTecnologiaMethod100(string $service): string
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'consultar_lote' => 'consultarLoteRps',
            'consultar_situacao' => 'consultarSituacaoLoteRPS',
            'consultar_nfse_rps', 'consultar_nf_se_rps' => 'consultarNFSePorRPS',
            'consultar_nfse', 'consultar_nf_se' => 'consultarNfse',
            'cancelar_nfse', 'cancelar_nf_se' => 'cancelarNfse',
            default => 'recepcionarLoteRps',
        };
    }
}
