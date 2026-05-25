<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderCoplanTrait
{
    /**
     * @param array|string $payload
     */
    private function buildCoplanEnvelope(array|string $payload, string $service, int $tpAmb): string
    {
        $data = $this->normalizePayload($payload);
        [$methodOuter, $methodInner] = $this->mapCoplanMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $cabecalho = $this->buildAbrasfCabecalhoXml((string) ($data['versao_dados'] ?? '2.01'));
        $namespace = $tpAmb === 1 ? 'Tributario_PRODUCAO_FULL' : 'TributarioGx16New';

        $body = '<trib:nfse_web_service.' . $methodOuter . '>'
            . '<trib:' . $methodInner . '>'
            . '<trib1:nfseCabecMsg>' . $this->asCdata($cabecalho) . '</trib1:nfseCabecMsg>'
            . '<trib1:nfseDadosMsg>' . $this->asCdata($dados) . '</trib1:nfseDadosMsg>'
            . '</trib:' . $methodInner . '>'
            . '</trib:nfse_web_service.' . $methodOuter . '>';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:trib="Tributario" xmlns:trib1="' . $this->xmlAttr($namespace) . '">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>' . $body . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    /**
     * @return array{0:string,1:string}
     */
    private function mapCoplanMethod(string $service): array
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'recepcionar_sincrono' => ['RECEPCIONARLOTERPSSINCRONO', 'Recepcionarloterpssincronorequest'],
            'gerar_nfse', 'gerar_nf_se', 'emitir_nfse' => ['GERARNFSE', 'Gerarnfserequest'],
            'consultar_lote' => ['CONSULTARLOTERPS', 'Consultarloterpsrequest'],
            'consultar_nfse_faixa', 'consultar_nf_se_faixa' => ['CONSULTARNFSEFAIXA', 'Consultarnfseporfaixarequest'],
            'consultar_nfse_rps', 'consultar_nf_se_rps' => ['CONSULTARNFSEPORRPS', 'Consultarnfseporrpsrequest'],
            'consultar_nfse_servico_prestado', 'consultar_nf_se_servico_prestado' => ['CONSULTARNFSESERVICOPRESTADO', 'Consultarnfseservicoprestadorequest'],
            'consultar_nfse_servico_tomado', 'consultar_nf_se_servico_tomado' => ['CONSULTARNFSESERVICOTOMADO', 'Consultarnfseservicotomadorequest'],
            'cancelar_nfse', 'cancelar_nf_se' => ['CANCELARNFSE', 'Cancelarnfserequest'],
            'substituir_nfse', 'substituir_nf_se' => ['SUBSTITUIRNFSE', 'Substituirnfserequest'],
            default => ['RECEPCIONARLOTERPS', 'Recepcionarloterpsrequest'],
        };
    }

    private function buildCoplanSoapAction(string $service): string
    {
        [$outer] = $this->mapCoplanMethod($service);
        return 'Tributarioaction/ANFSE_WEB_SERVICE.' . $outer;
    }
}
