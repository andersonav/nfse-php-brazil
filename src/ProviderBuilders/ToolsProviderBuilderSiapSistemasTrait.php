<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderSiapSistemasTrait
{
    /**
     * @param array|string $payload
     * @param array<string,mixed> $params
     */
    private function buildSiapSistemasEnvelope(array|string $payload, string $service, array $params): string
    {
        $data = $this->normalizePayload($payload);
        $method = $this->mapSiapSistemasMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $cabecalho = trim((string) ($data['cabecalho_xml'] ?? '<nfse:Cabecalho xmlns="http://www.abrasf.org.br/nfse.xsd"><Versao>1.0</Versao><versaoDados>2.03</versaoDados></nfse:Cabecalho>'));
        $aliasCidade = (string) ($params['AliasCidade'] ?? $params['aliascidade'] ?? 'Cidade');
        $namespace = $aliasCidade . 'RPS';
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfse="' . $this->xmlAttr($namespace) . '">'
            . '<soapenv:Header/><soapenv:Body>'
            . '<nfse:' . $method . '.Execute>'
            . $cabecalho
            . $dados
            . '</nfse:' . $method . '.Execute>'
            . '</soapenv:Body></soapenv:Envelope>';
    }

    private function mapSiapSistemasMethod(string $service): string
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'recepcionar_sincrono' => 'RecepcionarLoteRpsSincrono',
            'gerar_nfse', 'gerar_nf_se', 'emitir_nfse' => 'GerarNfse',
            'consultar_lote' => 'ConsultarLoteRps',
            'cancelar_nfse', 'cancelar_nf_se' => 'CancelarNfse',
            default => 'RecepcionarLoteRps',
        };
    }

    /**
     * @param array<string,mixed> $params
     */
    private function buildSiapSistemasSoapAction(string $service, array $params): string
    {
        $aliasCidade = (string) ($params['AliasCidade'] ?? $params['aliascidade'] ?? 'Cidade');
        $method = strtoupper($this->mapSiapSistemasMethod($service));
        return $aliasCidade . 'RPSaction/A' . $method . '.Execute';
    }
}
