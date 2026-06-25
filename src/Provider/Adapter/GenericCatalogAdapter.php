<?php

namespace Alves\NfseBrasil\Provider\Adapter;

use Alves\NfseBrasil\Provider\Contract\ProviderAdapterInterface;
use Alves\NfseBrasil\Provider\Contract\ProviderRuntimeAdapterInterface;
use Alves\NfseBrasil\Provider\ProviderProfile;

final class GenericCatalogAdapter implements ProviderAdapterInterface, ProviderRuntimeAdapterInterface
{
    use ProviderRuntimeByConventionTrait;

    private string $providerName;

    public function __construct(string $providerName)
    {
        $this->providerName = $providerName;
    }

    public function providerName(): string
    {
        return $this->providerName;
    }

    public function supportedServices(): array
    {
        return [];
    }

    public function buildServiceUrl(ProviderProfile $profile, string $service, int $tpAmb): ?string
    {
        return $profile->serviceUrl($service, $tpAmb);
    }
}
