<?php

namespace Alves\NfseBrasil\Provider;

use Alves\NfseBrasil\Provider\Contract\ProviderAdapterInterface;

final class ProviderAdapterRegistry
{
    /** @var array<string, ProviderAdapterInterface> */
    private array $adapters = [];

    public function register(ProviderAdapterInterface $adapter): void
    {
        $this->adapters[strtolower($adapter->providerName())] = $adapter;
    }

    public function resolve(?string $providerName): ?ProviderAdapterInterface
    {
        if (!$providerName) {
            return null;
        }
        return $this->adapters[strtolower($providerName)] ?? null;
    }

    /**
     * @return string[]
     */
    public function allProviderNames(): array
    {
        return array_map(
            static fn (ProviderAdapterInterface $adapter): string => $adapter->providerName(),
            array_values($this->adapters)
        );
    }
}
