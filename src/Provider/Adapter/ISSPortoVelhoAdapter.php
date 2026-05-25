<?php

namespace Alves\NfseBrasil\Provider\Adapter;

use Alves\NfseBrasil\Provider\Contract\ProviderAdapterInterface;
use Alves\NfseBrasil\Provider\Contract\ProviderRuntimeAdapterInterface;
use Alves\NfseBrasil\Provider\ProviderProfile;

final class ISSPortoVelhoAdapter implements ProviderAdapterInterface, ProviderRuntimeAdapterInterface
{
    use ProviderRuntimeByConventionTrait;

    public function providerName(): string
    {
        return 'ISSPortoVelho';
    }

    public function supportedServices(): array
    {
        return [
            'cancelar_nfse',
            'consultar_lote',
            'consultar_nfse_faixa',
            'consultar_nfse_rps',
            'consultar_nfse_servico_prestado',
            'consultar_nfse_servico_tomado',
            'emitir_nfse',
            'recepcionar',
            'recepcionar_sincrono',
            'substituir_nfse'
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
