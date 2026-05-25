<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderEReceitaTrait
{
    /**
     * @param array|string $payload
     */
    private function buildEReceitaEnvelope(array|string $payload, string $service, string $url): string
    {
        $data = $this->normalizePayload($payload);
        [$method, $requestTag] = $this->mapEReceitaMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $method);
        }
        $cabecalho = $this->buildAbrasfCabecalhoXml((string) ($data['versao_dados'] ?? '2.02'));
        $namespace = $this->eReceitaNamespace($url);

        $body = '<nfs:' . $requestTag . '>'
            . '<nfs:nfseCabecMsg>' . htmlspecialchars($cabecalho, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfs:nfseCabecMsg>'
            . '<nfs:nfseDadosMsg>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfs:nfseDadosMsg>'
            . '</nfs:' . $requestTag . '>';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfs="' . $this->xmlAttr($namespace) . '">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>' . $body . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    /**
     * @return array{0:string,1:string}
     */
    private function mapEReceitaMethod(string $service): array
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'gerar_nfse', 'gerar_nf_se', 'emitir_nfse' => ['GerarNfse', 'GerarNfseRequest'],
            'recepcionar_sincrono' => ['RecepcionarLoteRpsSincrono', 'RecepcionarLoteRpsSincronoRequest'],
            'consultar_lote' => ['ConsultarLoteRps', 'ConsultarLoteRpsRequest'],
            'consultar_nfse_faixa', 'consultar_nf_se_faixa' => ['ConsultarNfseFaixa', 'ConsultarNfseFaixaRequest'],
            'consultar_nfse_rps', 'consultar_nf_se_rps' => ['ConsultarNfsePorRps', 'ConsultarNfsePorRpsRequest'],
            'cancelar_nfse', 'cancelar_nf_se' => ['CancelarNfse', 'CancelarNfseRequest'],
            'substituir_nfse', 'substituir_nf_se' => ['SubstituirNfse', 'SubstituirNfseRequest'],
            default => ['RecepcionarLoteRps', 'RecepcionarLoteRpsRequest'],
        };
    }

    private function eReceitaNamespace(string $url): string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if (str_contains($host, 'www3')) {
            return 'http://www3.ereceita.net.br/soap/NfseWebService';
        }
        return 'http://webservice.ereceita.net.br/soap/NfseWebService';
    }

    private function buildEReceitaSoapAction(string $service, string $url): string
    {
        [$method] = $this->mapEReceitaMethod($service);
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $base = str_contains($host, 'www3') ? 'https://www3.ereceita.net.br/' : 'https://www.ereceita.net.br/';
        return $base . $method;
    }
}
