<?php

namespace Alves\NfseBrasil\Provider\Adapter;

use Alves\NfseBrasil\Provider\Contract\ProviderAdapterInterface;
use Alves\NfseBrasil\Provider\Contract\ProviderRuntimeAdapterInterface;
use Alves\NfseBrasil\Provider\ProviderProfile;

final class ISSDSFAdapter implements ProviderAdapterInterface, ProviderRuntimeAdapterInterface
{
    use ProviderRuntimeByConventionTrait;

    public function providerName(): string
    {
        return 'ISSDSF';
    }

    public function supportedServices(): array
    {
        return [
            'cancelar_nf_se',
            'consultar_lote',
            'consultar_nf_se',
            'consultar_nf_se_rps',
            'consultar_nfse_faixa',
            'consultar_nfse_servico_prestado',
            'consultar_nfse_servico_tomado',
            'consultar_seq_rps',
            'gerar_nf_se',
            'recepcionar',
            'recepcionar_sincrono',
            'substituir_nf_se'
        ];
    }

    public function buildServiceUrl(ProviderProfile $profile, string $service, int $tpAmb): ?string
    {
        if (!in_array($service, $this->supportedServices(), true)) {
            return null;
        }
        return $profile->serviceUrl($service, $tpAmb);
    }
}
