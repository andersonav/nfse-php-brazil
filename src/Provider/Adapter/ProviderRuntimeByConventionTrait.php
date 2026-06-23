<?php

namespace Alves\NfseBrasil\Provider\Adapter;

use Alves\NfseBrasil\Provider\ProviderProfile;

trait ProviderRuntimeByConventionTrait
{
    public function buildRuntimePlan(
        string $operation,
        string $service,
        ProviderProfile $profile,
        int $tpAmb,
        string $url,
        array|string $payload
    ): ?array {
        $op = strtolower(trim($operation));
        if (!in_array($op, ['emitir', 'cancelar', 'substituir'], true)) {
            return null;
        }

        $provider = strtolower((string) $this->providerName());
        if ($provider === 'padraonacional') {
            return [
                'direct_candidates' => [
                    match ($op) {
                        'emitir' => 'emitirPadraoNacional',
                        'cancelar' => 'cancelarPadraoNacional',
                        default => 'substituirPadraoNacional',
                    },
                ],
            ];
        }
        if ($provider === 'eiss') {
            return ['transport' => 'json'];
        }

        $serviceKey = strtolower(trim($service));
        if ($op === 'emitir' && in_array($serviceKey, ['emitir_nfse', 'gerar_nf_se', 'gerar_nfse'], true)) {
            $serviceKey = 'recepcionar';
        }

        $suffix = match ($op) {
            'emitir' => 'Emitir',
            'cancelar' => 'Cancelar',
            default => 'Substituir',
        };

        $tokens = $this->runtimeProviderTokens();
        $direct = [];
        $envelopes = [];
        $soapActions = [];
        $headers = [];

        $allowDirectByConvention = in_array($provider, ['softplan', 'ipm'], true);
        foreach ($tokens as $token) {
            if ($allowDirectByConvention) {
                $direct[] = $op . $token;
            }

            if ($op === 'emitir' && $serviceKey === 'recepcionar') {
                $envelopes[] = 'build' . $token . 'RecepcionarEnvelope';
            }
            $envelopes[] = 'build' . $token . $suffix . 'Envelope';
            $envelopes[] = 'build' . $token . 'Envelope';

            $soapActions[] = 'build' . $token . $suffix . 'SoapAction';
            $soapActions[] = 'build' . $token . 'SoapAction';

            $headers[] = 'build' . $token . $suffix . 'Headers';
            $headers[] = 'build' . $token . 'Headers';
        }

        return [
            'direct_candidates' => array_values(array_unique($direct)),
            'transport' => 'xml',
            'envelope' => [
                'candidates' => array_values(array_unique($envelopes)),
            ],
            'soap_action' => [
                'candidates' => array_values(array_unique($soapActions)),
            ],
            'headers' => [
                'candidates' => array_values(array_unique($headers)),
            ],
        ];
    }

    /**
     * @return string[]
     */
    private function runtimeProviderTokens(): array
    {
        $raw = preg_replace('/[^a-zA-Z0-9]/', '', (string) $this->providerName());
        $tokens = [];
        if ($raw !== '') {
            $tokens[] = $raw;
            $tokens[] = ucfirst(strtolower($raw));
        }

        $tokens = array_values(array_unique(array_filter($tokens, static fn (string $token): bool => $token !== '')));
        return $tokens;
    }
}
