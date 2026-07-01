<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderCTAConsultCancelXmlTrait
{
    /**
     * @param array<string,mixed> $data
     */
    private function buildCTAConsultCancelXml(array $data): string
    {
        $auth = $this->payloadAuth($data);
        $token = trim((string) ($auth['ws_chave_autorizacao'] ?? $auth['token'] ?? ''));
        $numeroNota = trim((string) ($data['numero_nfse'] ?? $data['numero'] ?? ''));
        $chaveSeguranca = trim((string) ($data['chave_nfse'] ?? $data['codigo_verificacao'] ?? $data['chave_seguranca'] ?? ''));
        if ($token === '' || $numeroNota === '' || $chaveSeguranca === '') {
            throw new RuntimeException('CTAConsult cancelamento requer token, numero_nfse e chave_nfse/codigo_verificacao.');
        }

        $codigoMunicipio = (string) ($data['codigo_municipio'] ?? ($this->getMunicipioContext()['ibge'] ?? ''));
        $dataEmissao = trim((string) ($data['data_emissao_nfse'] ?? $data['data_emissao'] ?? date('Y-m-d\TH:i:s')));

        return '<cancelamentoNfseLote xmlns="http://www.ctaconsult.com/nfse">'
            . '<codigoMunicipio>' . $this->xmlValue((string) $codigoMunicipio) . '</codigoMunicipio>'
            . '<dtEmissao>' . $this->xmlValue($dataEmissao) . '</dtEmissao>'
            . '<autenticacao><token>' . $this->xmlValue($token) . '</token></autenticacao>'
            . '<numeroNota>' . $this->xmlValue($numeroNota) . '</numeroNota>'
            . '<chaveSeguranca>' . $this->xmlValue($chaveSeguranca) . '</chaveSeguranca>'
            . '</cancelamentoNfseLote>';
    }
}
