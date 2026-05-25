<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderSilTecnologiaMethod203Trait
{
    private function mapSilTecnologiaMethod203(string $service): string
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'recepcionar_sincrono' => 'recepcionarLoteRpsSincrono',
            'gerar_nfse', 'gerar_nf_se', 'emitir_nfse' => 'gerarNfse',
            'consultar_lote' => 'consultarLoteRps',
            'consultar_nfse_rps', 'consultar_nf_se_rps' => 'consultarNfsePorRps',
            'consultar_nfse_faixa', 'consultar_nf_se_faixa' => 'consultarNfsePorFaixa',
            'consultar_nfse_servico_prestado', 'consultar_nf_se_servico_prestado' => 'consultarNfseServicoPrestado',
            'consultar_nfse_servico_tomado', 'consultar_nf_se_servico_tomado' => 'consultarNfseServicoTomado',
            'cancelar_nfse', 'cancelar_nf_se' => 'cancelarNfse',
            'substituir_nfse', 'substituir_nf_se' => 'substituirNfse',
            default => 'recepcionarLoteRps',
        };
    }
}
