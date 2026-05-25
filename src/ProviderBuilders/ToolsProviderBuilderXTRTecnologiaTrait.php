<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderXTRTecnologiaTrait
{
    /**
     * @param array|string $payload
     * @return array<string,mixed>|string
     */
    private function buildXTRTecnologiaPayload(array|string $payload, string $service): array|string
    {
        $data = $this->normalizePayload($payload);
        if (isset($data['dados_json']) && $data['dados_json'] !== '') {
            return is_string($data['dados_json']) ? $data['dados_json'] : $data['dados_json'];
        }
        if (isset($data['dados_xml']) && is_string($data['dados_xml']) && $data['dados_xml'] !== '') {
            return $data['dados_xml'];
        }
        $normalized = strtolower(trim($service));
        if (in_array($normalized, ['cancelar_nfse', 'cancelar_nf_se'], true)) {
            $numero = (int) ($data['numero_nfse'] ?? $data['numero'] ?? 0);
            $motivo = (string) ($data['motivo'] ?? '');
            $auth = $this->payloadAuth($data);
            $insc = (int) preg_replace('/\D+/', '', (string) ($auth['inscricao_municipal'] ?? $auth['ccm'] ?? $data['prestador_im'] ?? '0'));
            return [
                'DadosNota' => [
                    'Numero' => $numero,
                    'Cancelamento' => ['Motivo' => $motivo],
                    'Prestador' => ['InscricaoMunicipal' => $insc],
                ],
            ];
        }
        return $data;
    }

    /**
     * @param array|string $payload
     * @return array<int,string>
     */
    private function buildXTRTecnologiaHeaders(array|string $payload): array
    {
        $data = $this->normalizePayload($payload);
        $auth = $this->payloadAuth($data);
        $authorization = trim((string) ($auth['ws_chave_acesso'] ?? $auth['authorization'] ?? $auth['token'] ?? ''));
        if ($authorization === '') {
            throw new RuntimeException('XTRTecnologia requer auth.ws_chave_acesso (ou auth.authorization/auth.token).');
        }
        return ['Authorization: ' . $authorization];
    }
}
