<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderTcheInfoTrait
{
    /**
     * @param array|string $payload
     */
    private function buildTcheInfoEnvelope(array|string $payload, string $service): string
    {
        $data = $this->normalizePayload($payload);
        $method = $this->mapTcheInfoMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $cabecalho = $this->buildAbrasfCabecalhoXml((string) ($data['versao_dados'] ?? '2.04'));

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfse="http://www.abrasf.org.br/nfse.xsd">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<nfse:NfseWebService.' . $method . '>'
            . '<nfse:Nfsecabecmsg>' . htmlspecialchars($cabecalho, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfse:Nfsecabecmsg>'
            . '<nfse:Nfsedadosmsg>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfse:Nfsedadosmsg>'
            . '</nfse:NfseWebService.' . $method . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function mapTcheInfoMethod(string $service): string
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'consultar_nfse_rps', 'consultar_nf_se_rps' => 'CONSULTARNFSERPS',
            'cancelar_nfse', 'cancelar_nf_se' => 'CANCELARNFSE',
            default => 'GERARNFSE',
        };
    }

    private function buildTcheInfoSoapAction(string $service): string
    {
        return 'http://www.abrasf.org.br/nfse.xsdaction/ANFSEWEBSERVICE.' . $this->mapTcheInfoMethod($service);
    }
}
