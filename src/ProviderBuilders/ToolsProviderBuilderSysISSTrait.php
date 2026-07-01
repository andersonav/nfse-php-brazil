<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderSysISSTrait
{
    /**
     * Envelopes SysISS em SOAP 1.1 com wrappers ws:ws.*.
     *
     * @param array|string $payload
     */
    private function buildSysISSEnvelope(array|string $payload, string $service): string
    {
        $data = $this->normalizePayload($payload);
        [$outerMethod, $innerTag] = $this->mapSysISSMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="ws">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<ws:ws.' . $outerMethod . '>'
            . '<ws:' . $innerTag . '>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</ws:' . $innerTag . '>'
            . '</ws:ws.' . $outerMethod . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function buildSysISSSoapAction(string $service): string
    {
        [$outerMethod] = $this->mapSysISSMethod($service);
        return 'wsaction/AWS.' . $outerMethod;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function mapSysISSMethod(string $service): array
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'recepcionar_sincrono' => ['ENVIARLOTERPSSINCRONO', 'Enviarloterpssincronoin'],
            'gerar_nfse', 'gerar_nf_se', 'emitir_nfse' => ['GERARNFSE', 'Gerarnfsein'],
            'consultar_lote' => ['CONSULTARLOTERPS', 'Consultarloterpsin'],
            'consultar_nfse_faixa', 'consultar_nf_se_faixa' => ['CONSULTARNFSEFAIXA', 'Consultarnfsefaixain'],
            'consultar_nfse_rps', 'consultar_nf_se_rps' => ['CONSULTARNFSERPS', 'Consultarnfserpsin'],
            'consultar_nfse_servico_prestado', 'consultar_nf_se_servico_prestado' => ['CONSULTARNFSESERVICOPRESTADO', 'Consultarnfseservicoprestadoin'],
            'consultar_nfse_servico_tomado', 'consultar_nf_se_servico_tomado' => ['CONSULTARNFSESERVICOTOMADO', 'Consultarnfseservicotomadoin'],
            'cancelar_nfse', 'cancelar_nf_se' => ['CANCELARNFSE', 'Cancelarnfsein'],
            'substituir_nfse', 'substituir_nf_se' => ['SUBSTITUIRNFSE', 'Substituirnfsein'],
            default => ['ENVIARLOTERPS', 'Enviarloterpsin'],
        };
    }
}
