<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderSimplISS203Trait
{
    /**
     * @return array{0:string,1:string}
     */
    private function mapSimplISS203Method(string $service): array
    {
        return match ($service) {
            'gerar_nfse', 'gerar_nf_se', 'emitir_nfse' => ['GerarNfse', 'GerarNfseRequest'],
            'consultar_lote' => ['ConsultarLoteRps', 'ConsultarLoteRpsRequest'],
            'consultar_nfse_faixa', 'consultar_nf_se_faixa' => ['ConsultarNfseFaixa', 'ConsultarNfseFaixaRequest'],
            'consultar_nfse_rps', 'consultar_nf_se_rps' => ['ConsultarNfseRps', 'ConsultarNfseRpsRequest'],
            'consultar_nfse_servico_prestado', 'consultar_nf_se_servico_prestado' => ['ConsultarNfseServicoPrestado', 'ConsultarNfseServicoPrestadoRequest'],
            'consultar_nfse_servico_tomado', 'consultar_nf_se_servico_tomado' => ['ConsultarNfseServicoTomado', 'ConsultarNfseServicoTomadoRequest'],
            'cancelar_nfse', 'cancelar_nf_se' => ['CancelarNfse', 'CancelarNfseRequest'],
            'substituir_nfse', 'substituir_nf_se' => ['SubstituirNfse', 'SubstituirNfseRequest'],
            default => ['RecepcionarLoteRps', 'RecepcionarLoteRpsRequest'],
        };
    }
}
