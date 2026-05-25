<?php

namespace Alves\NfseBrasil\Provider\Adapter;

use Alves\NfseBrasil\Provider\Contract\ProviderAdapterInterface;
use Alves\NfseBrasil\Provider\Contract\ProviderRuntimeAdapterInterface;
use Alves\NfseBrasil\Provider\ProviderProfile;

final class Smart4Adapter implements ProviderAdapterInterface, ProviderRuntimeAdapterInterface
{
    use ProviderRuntimeByConventionTrait;

    public function providerName(): string
    {
        return 'Smart4';
    }

    public function supportedServices(): array
    {
        return [
            'cancelar_nf_se',
            'consultar_lote',
            'consultar_nf_se',
            'consultar_nf_se_rps',
            'consultar_situacao',
            'gerar_nf_se',
            'link_url',
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
