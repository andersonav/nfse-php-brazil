<?php

namespace Alves\NfseBrasil\Provider\Contract;

use Alves\NfseBrasil\Provider\ProviderProfile;

interface ProviderAdapterInterface
{
    public function providerName(): string;

    /**
     * Retorna os nomes canonicos de servico suportados pelo adapter.
     * Ex.: recepcionar, consultar_nf_se, cancelar_nf_se.
     *
     * @return string[]
     */
    public function supportedServices(): array;

    public function buildServiceUrl(ProviderProfile $profile, string $service, int $tpAmb): ?string;
}
