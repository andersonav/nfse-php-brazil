<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderDSFTrait
{
    /**
     * Monta envelope SOAP DSF, cobrindo legado V3 (ABRASF 1) e versões 2.x.
     *
     * @param array|string $payload
     */
    private function buildDSFEnvelope(array|string $payload, string $service, ?string $versao): string
    {
        $data = $this->normalizePayload($payload);
        $normalized = strtolower(trim($service));
        $is2x = str_starts_with((string) $versao, '2.');

        if ($is2x) {
            $method = $this->mapDSF2xMethod($normalized);
            $dados = trim((string) ($data['dados_xml'] ?? ''));
            if ($dados === '') {
                if ($method === 'RecepcionarLoteRps') {
                    $dados = $this->buildISSNetDadosLoteXml($data);
                } else {
                    $dados = $this->buildAbrasfDataForMethod($data, $service);
                }
            }

            return '<?xml version="1.0" encoding="UTF-8"?>'
                . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfse="http://nfse.abrasf.org.br">'
                . '<soapenv:Header/>'
                . '<soapenv:Body>'
                . '<nfse:' . $method . '>'
                . $dados
                . '</nfse:' . $method . '>'
                . '</soapenv:Body>'
                . '</soapenv:Envelope>';
        }

        $methodV3 = $this->mapDSFV3Method($normalized);
        if ($methodV3 === null) {
            throw new RuntimeException("Servico '{$service}' nao suportado em DSF legado V3.");
        }

        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            if ($normalized === 'recepcionar') {
                $dados = $this->buildISSNetDadosLoteXml($data);
            } else {
                $dados = $this->buildAbrasfDataForMethod($data, $service);
            }
        }

        $cabecalho = '<ns2:cabecalho versao="3" xmlns:ns2="http:/www.abrasf.org.br/nfse.xsd"><versaoDados>3</versaoDados></ns2:cabecalho>';
        $dadosLegacy = str_replace('http://www.abrasf.org.br/nfse.xsd', 'http:/www.abrasf.org.br/nfse.xsd', $dados);

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfse="http://www.abrasf.org.br/nfse.xsd">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<nfse:' . $methodV3 . '>'
            . '<arg0><![CDATA[' . $cabecalho . ']]></arg0>'
            . '<arg1><![CDATA[' . $dadosLegacy . ']]></arg1>'
            . '</nfse:' . $methodV3 . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }
}
