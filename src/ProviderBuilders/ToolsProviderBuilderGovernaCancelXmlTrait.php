<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderGovernaCancelXmlTrait
{
    /**
     * @param array<string,mixed> $data
     * @param array<string,mixed> $params
     */
    private function buildGovernaCancelXml(array $data, array $params): string
    {
        $auth = $this->payloadAuth($data);
        $inscricao = preg_replace('/\D+/', '', (string) ($auth['inscricao_municipal'] ?? $auth['ccm'] ?? $data['prestador_im'] ?? ''));
        $chave = trim((string) ($auth['ws_chave_acesso'] ?? $auth['chave_acesso'] ?? ''));
        $numero = trim((string) ($data['numero_nfse'] ?? $data['numero'] ?? ''));
        $codVer = trim((string) ($data['codigo_verificacao'] ?? $data['cod_verificacao'] ?? ''));
        $motivo = trim((string) ($data['motivo'] ?? 'Cancelamento solicitado.'));
        if ($inscricao === '' || $chave === '' || $numero === '' || $codVer === '') {
            throw new RuntimeException('Governa cancelamento requer inscricao_municipal, ws_chave_acesso, numero_nfse e codigo_verificacao.');
        }
        $versaoArquivo = (string) ($data['versao_arquivo'] ?? $params['VersaoArquivo'] ?? $params['versao_arquivo'] ?? '4');

        return '<tcLoteCancelamento>'
            . '<tsCodCadBic>' . $this->xmlValue($inscricao) . '</tsCodCadBic>'
            . '<tsVrsArq>' . $this->xmlValue($versaoArquivo) . '</tsVrsArq>'
            . '<tsChvAcs>' . $this->xmlValue($chave) . '</tsChvAcs>'
            . '<tcNotCan><tcInfNotCan>'
            . '<tsNumNot>' . $this->xmlValue($numero) . '</tsNumNot>'
            . '<tsCodVer>' . $this->xmlValue($codVer) . '</tsCodVer>'
            . '<tsDesMotCan>' . $this->xmlValue($motivo) . '</tsDesMotCan>'
            . '</tcInfNotCan></tcNotCan>'
            . '</tcLoteCancelamento>';
    }
}
