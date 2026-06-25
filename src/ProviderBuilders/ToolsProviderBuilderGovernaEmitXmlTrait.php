<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderGovernaEmitXmlTrait
{
    /**
     * @param array<string,mixed> $data
     * @param array<string,mixed> $params
     */
    private function buildGovernaEmitXml(array $data, array $params): string
    {
        $auth = $this->payloadAuth($data);
        $inscricao = preg_replace('/\D+/', '', (string) ($auth['inscricao_municipal'] ?? $auth['ccm'] ?? $data['prestador_im'] ?? ''));
        $chave = trim((string) ($auth['ws_chave_acesso'] ?? $auth['chave_acesso'] ?? ''));
        if ($inscricao === '' || $chave === '') {
            throw new RuntimeException('Governa requer auth.inscricao_municipal e auth.ws_chave_acesso.');
        }
        $versaoArquivo = (string) ($data['versao_arquivo'] ?? $params['VersaoArquivo'] ?? $params['versao_arquivo'] ?? '4');
        $xml = trim((string) ($data['rps_xml'] ?? ''));
        if ($xml === '') {
            $xml = $this->buildAbrasfDataForMethod($data, 'recepcionar');
        }

        return '<tcLoteRps>'
            . '<tsCodCadBic>' . $this->xmlValue($inscricao) . '</tsCodCadBic>'
            . '<tsVrsArq>' . $this->xmlValue($versaoArquivo) . '</tsVrsArq>'
            . '<tsChvAcs>' . $this->xmlValue($chave) . '</tsChvAcs>'
            . $xml
            . '</tcLoteRps>';
    }
}
