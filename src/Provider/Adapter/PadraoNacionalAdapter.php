<?php

namespace Alves\NfseBrasil\Provider\Adapter;

use Alves\NfseBrasil\Provider\Contract\ProviderAdapterInterface;
use Alves\NfseBrasil\Provider\Contract\ProviderRuntimeAdapterInterface;
use Alves\NfseBrasil\Provider\ProviderProfile;

final class PadraoNacionalAdapter implements ProviderAdapterInterface, ProviderRuntimeAdapterInterface
{
    use ProviderRuntimeByConventionTrait;

    public function providerName(): string
    {
        return 'PadraoNacional';
    }

    public function supportedServices(): array
    {
        return [
            'cancelar_nfse',
            'consultar_d_fe',
            'consultar_dfe',
            'consultar_danfse',
            'obterdanfse',
            'consultar_dps',
            'consultar_eventos',
            'consultar_nf_se',
            'consultar_nfse_rps',
            'consultar_param',
            'emitir_nfse',
            'enviar_evento',
            'link_url',
            'recepcionar'
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
