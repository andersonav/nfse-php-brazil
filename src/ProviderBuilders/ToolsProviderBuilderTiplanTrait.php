<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderTiplanTrait
{
    /**
     * @param array|string $payload
     */
    private function buildTiplanEnvelope(array|string $payload, string $service, ?string $versao): string
    {
        $data = $this->normalizePayload($payload);
        $isClassic = str_starts_with((string) $versao, '1.');
        if ($isClassic) {
            return $this->buildTiplanClassicEnvelope($data, $service);
        }

        $method = $this->mapTiplanAbrasfMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $method);
        }
        $cabecalho = $this->buildAbrasfCabecalhoXml((string) ($data['versao_dados'] ?? $versao ?? '2.03'));

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfse="http://nfse.abrasf.org.br/">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<nfse:' . $method . 'Request>'
            . '<nfseCabecMsg>' . htmlspecialchars($cabecalho, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseCabecMsg>'
            . '<nfseDadosMsg>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseDadosMsg>'
            . '</nfse:' . $method . 'Request>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function buildTiplanSoapAction(string $service, ?string $versao): string
    {
        if (str_starts_with((string) $versao, '1.')) {
            return 'http://www.nfe.com.br/' . $this->mapTiplanClassicMethod($service);
        }
        return 'http://nfse.abrasf.org.br/' . $this->mapTiplanAbrasfMethod($service);
    }
}
