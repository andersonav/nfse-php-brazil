<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderTiplanAbrasfTrait
{
    private function mapTiplanAbrasfMethod(string $service): string
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'gerar_nfse', 'gerar_nf_se', 'emitir_nfse' => 'GerarNfse',
            'recepcionar_sincrono' => 'RecepcionarLoteRpsSincrono',
            'consultar_lote' => 'ConsultarLoteRps',
            'consultar_nfse_rps', 'consultar_nf_se_rps' => 'ConsultarNfsePorRps',
            'consultar_nfse_faixa', 'consultar_nf_se_faixa' => 'ConsultarNfsePorFaixa',
            'consultar_nfse_servico_prestado', 'consultar_nf_se_servico_prestado' => 'ConsultarNfseServicoPrestado',
            'consultar_nfse_servico_tomado', 'consultar_nf_se_servico_tomado' => 'ConsultarNfseServicoTomado',
            'cancelar_nfse', 'cancelar_nf_se' => 'CancelarNfse',
            'substituir_nfse', 'substituir_nf_se' => 'SubstituirNfse',
            default => 'RecepcionarLoteRps',
        };
    }
}
