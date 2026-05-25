<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderCTAConsultEmitXmlTrait
{
    /**
     * @param array<string,mixed> $data
     */
    private function buildCTAConsultEmitXml(array $data): string
    {
        $auth = $this->payloadAuth($data);
        $token = trim((string) ($auth['ws_chave_autorizacao'] ?? $auth['token'] ?? ''));
        if ($token === '') {
            throw new RuntimeException('CTAConsult requer auth.ws_chave_autorizacao (ou auth.token).');
        }
        $codigoMunicipio = (string) ($data['codigo_municipio'] ?? ($data['rps'][0]['servico']['codigo_municipio'] ?? ($this->getMunicipioContext()['ibge'] ?? '')));
        $dataEmissao = trim((string) ($data['data_emissao'] ?? date('Y-m-d\TH:i:s')));
        $notaIntermediada = trim((string) ($data['nota_intermediada'] ?? '2'));
        $conteudo = trim((string) ($data['nfse_xml'] ?? ''));
        if ($conteudo === '') {
            $conteudo = $this->buildAbrasfDataForMethod($data, 'gerar_nfse');
        }

        return '<nfseLote xmlns="http://www.ctaconsult.com/nfse">'
            . '<codigoMunicipio>' . $this->xmlValue((string) $codigoMunicipio) . '</codigoMunicipio>'
            . '<dtEmissao>' . $this->xmlValue($dataEmissao) . '</dtEmissao>'
            . '<notaIntermediada>' . $this->xmlValue($notaIntermediada) . '</notaIntermediada>'
            . '<autenticacao><token>' . $this->xmlValue($token) . '</token></autenticacao>'
            . $conteudo
            . '</nfseLote>';
    }
}
