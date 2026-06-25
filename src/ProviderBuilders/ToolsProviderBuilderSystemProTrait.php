<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderSystemProTrait
{
    /**
     * @param array|string $payload
     */
    private function buildSystemProEnvelope(array|string $payload, string $service): string
    {
        $data = $this->normalizePayload($payload);
        $method = $this->mapSystemProMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $cabecalho = $this->buildAbrasfCabecalhoXml((string) ($data['versao_dados'] ?? '2.01'));
        $cabecCdata = in_array(strtolower(trim($service)), ['recepcionar_sincrono', 'gerar_nfse', 'gerar_nf_se', 'emitir_nfse', 'substituir_nfse', 'substituir_nf_se'], true);

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns2="http://NFSe.wsservices.systempro.com.br/">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<ns2:' . $method . '>'
            . '<nfseCabecMsg>' . ($cabecCdata ? $this->asCdata($cabecalho) : htmlspecialchars($cabecalho, ENT_XML1 | ENT_QUOTES, 'UTF-8')) . '</nfseCabecMsg>'
            . '<nfseDadosMsg>' . ($cabecCdata ? $this->asCdata($dados) : htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8')) . '</nfseDadosMsg>'
            . '</ns2:' . $method . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function mapSystemProMethod(string $service): string
    {
        return match (strtolower(trim($service))) {
            'recepcionar_sincrono', 'recepcionar' => 'EnviarLoteRpsSincrono',
            'gerar_nfse', 'gerar_nf_se', 'emitir_nfse' => 'GerarNfse',
            'consultar_nfse_faixa', 'consultar_nf_se_faixa' => 'ConsultarNfseFaixa',
            'cancelar_nfse', 'cancelar_nf_se' => 'CancelarNfse',
            'substituir_nfse', 'substituir_nf_se' => 'SubstituirNfse',
            default => 'EnviarLoteRpsSincrono',
        };
    }
}
