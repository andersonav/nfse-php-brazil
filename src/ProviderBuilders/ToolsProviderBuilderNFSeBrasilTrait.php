<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderNFSeBrasilTrait
{
    /**
     * @param array|string $payload
     */
    private function buildNFSeBrasilEnvelope(array|string $payload, string $service): string
    {
        $data = $this->normalizePayload($payload);
        [$method, $xmlTag] = $this->mapNFSeBrasilMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }

        $auth = $this->payloadAuth($data);
        $codMunicipio = (string) ($data['codigo_municipio'] ?? ($data['rps'][0]['servico']['codigo_municipio'] ?? $data['ibge'] ?? ''));
        $prestadorCnpj = preg_replace('/\\D+/', '', (string) ($data['prestador_cnpj'] ?? ($data['rps'][0]['prestador']['cnpj'] ?? '')));
        $hashValidador = strtolower(trim((string) ($auth['hash_validador'] ?? $data['hash_validador'] ?? '')));
        if ($codMunicipio === '' || $prestadorCnpj === '' || $hashValidador === '') {
            throw new RuntimeException('NFSeBrasil requer codigo_municipio, prestador_cnpj e hash_validador (auth.hash_validador).');
        }

        $body = '<urn:' . $method . ' soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">'
            . '<' . $xmlTag . ' xsi:type="xsd:string">' . $this->asCdata($dados) . '</' . $xmlTag . '>'
            . '<codMunicipio xsi:type="xsd:string">' . $this->xmlValue($codMunicipio) . '</codMunicipio>'
            . '<cnpjPrestador xsi:type="xsd:string">' . $this->xmlValue($prestadorCnpj) . '</cnpjPrestador>'
            . '<hashValidador xsi:type="xsd:string">' . $this->xmlValue($hashValidador) . '</hashValidador>'
            . '</urn:' . $method . '>';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:loterpswsdl" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>' . $body . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    /**
     * @return array{0:string,1:string}
     */
    private function mapNFSeBrasilMethod(string $service): array
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'consultar_lote' => ['tm_lote_rps_service.consultarLoteRPS', 'protocolo'],
            'consultar_nfse_rps', 'consultar_nf_se_rps' => ['tm_lote_rps_service.consultarRPS', 'numeroRPS'],
            'consultar_nfse', 'consultar_nf_se' => ['tm_lote_rps_service.consultarNFSE', 'numeroNFSE'],
            'cancelar_nfse', 'cancelar_nf_se' => ['tm_lote_rps_service.cancelarNFSE', 'numeroNFSE'],
            default => ['tm_lote_rps_service.importarLoteRPS', 'xml'],
        };
    }

    private function buildNFSeBrasilSoapAction(string $service): string
    {
        [$method] = $this->mapNFSeBrasilMethod($service);
        return 'urn:loterpswsdl#' . $method;
    }
}
