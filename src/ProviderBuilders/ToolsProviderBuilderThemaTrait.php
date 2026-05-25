<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderThemaTrait
{
    /**
     * @param array|string $payload
     */
    private function buildThemaEnvelope(array|string $payload, string $service): string
    {
        $data = $this->normalizePayload($payload);
        $method = $this->mapThemaMethod($data, $service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<' . $method . ' xmlns="http://server.nfse.thema.inf.br">'
            . '<xml>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</xml>'
            . '</' . $method . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    /**
     * @param array<string,mixed> $data
     */
    private function mapThemaMethod(array $data, string $service): string
    {
        $normalized = strtolower(trim($service));
        if (in_array($normalized, ['recepcionar', 'recepcionar_sincrono'], true)) {
            $qty = (int) ($data['lote']['quantidade_rps'] ?? count((array) ($data['rps'] ?? [])));
            if ($qty <= 0) {
                $qty = 1;
            }
            return $qty <= 3 ? 'recepcionarLoteRpsLimitado' : 'recepcionarLoteRps';
        }
        return match ($normalized) {
            'consultar_lote' => 'consultarLoteRps',
            'consultar_situacao' => 'consultarSituacaoLoteRps',
            'consultar_nfse_rps', 'consultar_nf_se_rps' => 'consultarNfsePorRps',
            'consultar_nfse', 'consultar_nf_se' => 'consultarNfse',
            'cancelar_nfse', 'cancelar_nf_se' => 'cancelarNfse',
            default => 'recepcionarLoteRps',
        };
    }

    /**
     * @param array|string $payload
     */
    private function buildThemaSoapAction(array|string $payload, string $service): string
    {
        $data = $this->normalizePayload($payload);
        return 'urn:' . $this->mapThemaMethod($data, $service);
    }
}
