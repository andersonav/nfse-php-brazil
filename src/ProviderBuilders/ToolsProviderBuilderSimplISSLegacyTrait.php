<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderSimplISSLegacyTrait
{
    /**
     * @return array{0:string}
     */
    private function mapSimplISSLegacyMethod(string $service): array
    {
        return match ($service) {
            'consultar_situacao' => ['ConsultarSituacaoLoteRps'],
            'consultar_lote' => ['ConsultarLoteRps'],
            'consultar_nfse_rps', 'consultar_nf_se_rps' => ['ConsultarNfsePorRps'],
            'consultar_nfse', 'consultar_nf_se' => ['ConsultarNfse'],
            'cancelar_nfse', 'cancelar_nf_se' => ['CancelarNfse'],
            default => ['RecepcionarLoteRps'],
        };
    }
}
