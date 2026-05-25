<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderDSFV3Trait
{
    private function mapDSFV3Method(string $service): ?string
    {
        return match ($service) {
            'recepcionar' => 'RecepcionarLoteRpsV3',
            'consultar_lote' => 'ConsultarLoteRpsV3',
            'consultar_situacao' => 'ConsultarSituacaoLoteRpsV3',
            'consultar_nfse_rps', 'consultar_nf_se_rps' => 'ConsultarNfsePorRpsV3',
            'consultar_nfse', 'consultar_nf_se' => 'ConsultarNfseV3',
            'cancelar_nfse', 'cancelar_nf_se' => 'CancelarNfseV3',
            default => null,
        };
    }
}
