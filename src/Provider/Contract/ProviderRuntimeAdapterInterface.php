<?php

namespace Alves\NfseBrasil\Provider\Contract;

use Alves\NfseBrasil\Provider\ProviderProfile;

interface ProviderRuntimeAdapterInterface
{
    /**
     * Retorna um plano de execucao runtime para a operacao no provedor.
     *
     * @param string $operation emitir|cancelar|substituir
     * @param string $service nome canonico do servico
     * @param array|string $payload payload unificado
     * @return array<string,mixed>|null
     */
    public function buildRuntimePlan(
        string $operation,
        string $service,
        ProviderProfile $profile,
        int $tpAmb,
        string $url,
        array|string $payload
    ): ?array;
}

