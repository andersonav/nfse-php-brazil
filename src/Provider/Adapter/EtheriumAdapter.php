<?php

namespace Alves\NfseBrasil\Provider\Adapter;

use Alves\NfseBrasil\Provider\Contract\ProviderAdapterInterface;
use Alves\NfseBrasil\Provider\Contract\ProviderRuntimeAdapterInterface;
use Alves\NfseBrasil\Provider\ProviderProfile;

final class EtheriumAdapter implements ProviderAdapterInterface, ProviderRuntimeAdapterInterface
{
    public function providerName(): string
    {
        return 'Etherium';
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

    public function buildRuntimePlan(
        string $operation,
        string $service,
        ProviderProfile $profile,
        int $tpAmb,
        string $url,
        array|string $payload
    ): ?array {
        if (!in_array($operation, ['emitir', 'cancelar', 'substituir'], true)) {
            return null;
        }

        return [
            'transport' => 'xml',
            'envelope' => [
                'method' => 'buildEtheriumEnvelope',
                'args' => ['payload', 'service', 'profile.versao', 'tpAmb'],
            ],
            'soap_action' => [
                'method' => 'buildEtheriumSoapAction',
                'args' => ['service', 'profile.versao', 'url'],
            ],
        ];
    }
}
