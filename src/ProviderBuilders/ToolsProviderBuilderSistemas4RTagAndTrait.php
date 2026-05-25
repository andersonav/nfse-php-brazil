<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderSistemas4RTagAndTrait
{
    /**
     * @return array{0:string,1:string}
     */
    private function buildSistemas4RTagAndNamespace(string $service, int $tpAmb): array
    {
        $isProd = $tpAmb === 1;
        return match ($service) {
            'consultar_lote' => [$isProd ? 'ConsultarLoteRps.Execute' : 'hConsultarLoteRps.Execute', 'AbrasfNFSe'],
            'consultar_nfse_rps', 'consultar_nf_se_rps' => [$isProd ? 'ConsultarNfsePorRps.Execute' : 'hConsultarNfsePorRps.Execute', 'Abrasf2'],
            'cancelar_nfse', 'cancelar_nf_se' => [$isProd ? 'CancelarNfse.Execute' : 'hCancelarNfse.Execute', 'Abrasf2'],
            default => [$isProd ? 'RecepcionarLoteRpsSincrono.Execute' : 'hRecepcionarLoteRpsSincrono.Execute', 'Abrasf2'],
        };
    }
}
