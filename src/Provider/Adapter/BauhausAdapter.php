<?php

namespace Alves\NfseBrasil\Provider\Adapter;

use Alves\NfseBrasil\Provider\Contract\ProviderAdapterInterface;
use Alves\NfseBrasil\Provider\Contract\ProviderRuntimeAdapterInterface;
use Alves\NfseBrasil\Provider\ProviderProfile;

final class BauhausAdapter implements ProviderAdapterInterface, ProviderRuntimeAdapterInterface
{
    use ProviderRuntimeByConventionTrait;

    public function providerName(): string
    {
        return 'Bauhaus';
    }

    public function supportedServices(): array
    {
        return [
            'recepcionar',
            'consultar_situacao',
            'consultar_lote',
            'consultar_nf_se_rps',
            'consultar_nf_se',
            'cancelar_nf_se',
            'gerar_nf_se',
            'substituir_nf_se',
            'link_url'
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
