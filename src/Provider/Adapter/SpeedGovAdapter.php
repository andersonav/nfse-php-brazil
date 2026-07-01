<?php

namespace Alves\NfseBrasil\Provider\Adapter;

use Alves\NfseBrasil\Provider\Contract\ProviderAdapterInterface;
use Alves\NfseBrasil\Provider\Contract\ProviderRuntimeAdapterInterface;
use Alves\NfseBrasil\Provider\ProviderProfile;

final class SpeedGovAdapter implements ProviderAdapterInterface, ProviderRuntimeAdapterInterface
{
    use ProviderRuntimeByConventionTrait;

    public function providerName(): string
    {
        return 'SpeedGov';
    }

    public function supportedServices(): array
    {
        return [
            'recepcionar',
            'consultar_situacao',
            'consultar_lote',
            'consultar_nfse_rps',
            'consultar_nf_se_rps',
            'consultar_nfse',
            'consultar_nf_se',
            'cancelar_nf_se',
            'cancelar_nfse',
            'gerar_nf_se',
            'gerar_nfse',
            'emitir_nfse',
            'substituir_nf_se',
            'substituir_nfse',
            'link_url'
        ];
    }

    public function buildServiceUrl(ProviderProfile $profile, string $service, int $tpAmb): ?string
    {
        $normalized = strtolower(trim($service));
        if ($normalized === '') {
            return null;
        }

        $supported = $this->supportedServices();
        if (!in_array($normalized, $supported, true)) {
            return null;
        }

        $direct = $profile->serviceUrl($normalized, $tpAmb);
        if (is_string($direct) && $direct !== '') {
            return $direct;
        }

        $fallbacks = match ($normalized) {
            'consultar_lote',
            'consultar_situacao',
            'consultar_nfse_rps',
            'consultar_nf_se_rps',
            'consultar_nfse',
            'consultar_nf_se',
            'cancelar_nfse',
            'cancelar_nf_se',
            'substituir_nfse',
            'substituir_nf_se',
            'emitir_nfse',
            'gerar_nfse',
            'gerar_nf_se' => ['recepcionar', 'consultar_nf_se', 'consultar_nf_se_rps', 'consultar_lote'],
            default => [],
        };

        foreach ($fallbacks as $candidate) {
            $url = $profile->serviceUrl($candidate, $tpAmb);
            if (is_string($url) && $url !== '') {
                return $url;
            }
        }

        return null;
    }
}
