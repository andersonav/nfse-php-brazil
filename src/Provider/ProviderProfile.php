<?php

namespace Alves\NfseBrasil\Provider;

final class ProviderProfile
{
    private array $context;

    public function __construct(array $municipioContext)
    {
        $this->context = $municipioContext;
    }

    public function ibge(): ?string
    {
        return $this->context['ibge'] ?? null;
    }

    public function nomeMunicipio(): ?string
    {
        return $this->context['nome'] ?? null;
    }

    public function uf(): ?string
    {
        return $this->context['uf'] ?? null;
    }

    public function provedor(): ?string
    {
        return $this->context['provedor'] ?? null;
    }

    public function versao(): ?string
    {
        return $this->context['versao'] ?? null;
    }

    public function params(): array
    {
        $params = $this->context['params_map'] ?? [];
        return is_array($params) ? $params : [];
    }

    public function services(): array
    {
        $services = $this->context['services'] ?? [];
        return is_array($services) ? $services : [];
    }

    public function hasService(string $service): bool
    {
        return in_array($service, $this->services(), true);
    }

    public function serviceUrl(string $service, int $tpAmb = 2): ?string
    {
        $urls = $this->context['urls'] ?? [];
        if (!is_array($urls)) {
            return null;
        }

        $amb = $tpAmb === 1 ? 'producao' : 'homologacao';
        $envUrls = $urls[$amb] ?? [];
        if (!is_array($envUrls)) {
            return null;
        }

        foreach ($this->candidateServiceKeys($service) as $candidate) {
            if (isset($envUrls[$candidate]) && $envUrls[$candidate] !== '') {
                return $envUrls[$candidate];
            }
        }

        return null;
    }

    public function isPadraoNacionalLike(): bool
    {
        $provider = strtolower((string) $this->provedor());
        return in_array($provider, ['padraonacional', 'nfsebrasil'], true);
    }

    /**
     * Resolve aliases de nomes de servico entre variantes canonicas e legadas.
     *
     * @return string[]
     */
    private function candidateServiceKeys(string $service): array
    {
        $service = strtolower(trim($service));
        if ($service === '') {
            return [];
        }

        $candidates = [$service];
        $queue = [$service];
        while ($queue !== []) {
            $current = array_shift($queue);
            foreach ($this->expandAliases($current) as $alias) {
                if (!in_array($alias, $candidates, true)) {
                    $candidates[] = $alias;
                    $queue[] = $alias;
                }
            }
        }

        return $candidates;
    }

    /**
     * @return string[]
     */
    private function expandAliases(string $service): array
    {
        $aliases = [];

        if (str_contains($service, 'nf_se')) {
            $aliases[] = str_replace('nf_se', 'nfse', $service);
        }
        if (str_contains($service, 'nfse')) {
            $aliases[] = str_replace('nfse', 'nf_se', $service);
        }

        if (str_contains($service, 'd_fe')) {
            $aliases[] = str_replace('d_fe', 'dfe', $service);
        }
        if (str_contains($service, 'dfe')) {
            $aliases[] = str_replace('dfe', 'd_fe', $service);
        }

        if ($service === 'gerar_nf_se') {
            $aliases[] = 'emitir_nfse';
            $aliases[] = 'gerar_nfse';
        }
        if ($service === 'gerar_nfse') {
            $aliases[] = 'emitir_nfse';
            $aliases[] = 'gerar_nf_se';
        }
        if ($service === 'emitir_nfse') {
            $aliases[] = 'gerar_nf_se';
            $aliases[] = 'gerar_nfse';
        }

        if ($service === 'cancelar_nfse') {
            $aliases[] = 'cancelar_nf_se';
        }
        if ($service === 'cancelar_nf_se') {
            $aliases[] = 'cancelar_nfse';
        }

        if ($service === 'consultar_evento') {
            $aliases[] = 'consultar_eventos';
        }
        if ($service === 'consultar_eventos') {
            $aliases[] = 'consultar_evento';
        }

        if ($service === 'enviar_evento') {
            $aliases[] = 'consultar_eventos';
        }

        return $aliases;
    }
}
