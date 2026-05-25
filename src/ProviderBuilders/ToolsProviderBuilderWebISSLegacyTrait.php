<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderWebISSLegacyTrait
{
    /**
     * @return array{0:string,1:string}
     */
    private function mapWebISSLegacyMethod(string $service): array
    {
        return match ($service) {
            'consultar_situacao' => ['ConsultarSituacaoLoteRps', 'ConsultarSituacaoLoteRps'],
            'consultar_lote' => ['ConsultarLoteRps', 'ConsultarLoteRps'],
            'consultar_nfse_rps', 'consultar_nf_se_rps' => ['ConsultarNfsePorRps', 'ConsultarNfsePorRps'],
            'consultar_nfse', 'consultar_nf_se' => ['ConsultarNfse', 'ConsultarNfse'],
            'cancelar_nfse', 'cancelar_nf_se' => ['CancelarNfse', 'CancelarNfse'],
            default => ['RecepcionarLoteRps', 'RecepcionarLoteRps'],
        };
    }
}
