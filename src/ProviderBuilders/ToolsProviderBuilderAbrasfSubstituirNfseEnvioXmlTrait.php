<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderAbrasfSubstituirNfseEnvioXmlTrait
{
    /**
     * @param array<string,mixed> $data
     */
    private function buildAbrasfSubstituirNfseEnvioXml(array $data): string
    {
        $dadosXmlRaw = trim((string) ($data['dados_xml'] ?? ''));
        if ($dadosXmlRaw !== '') {
            return $dadosXmlRaw;
        }

        $rpsNumero = trim((string) ($data['rps_numero'] ?? $data['substituicao']['rps_numero'] ?? ''));
        $rpsSerie = trim((string) ($data['rps_serie'] ?? $data['substituicao']['rps_serie'] ?? 'UNICA'));
        $rpsTipo = trim((string) ($data['rps_tipo'] ?? $data['substituicao']['rps_tipo'] ?? '1'));
        $cancelXml = $this->buildAbrasfCancelarNfseEnvioXml($data);
        if ($rpsNumero === '') {
            throw new RuntimeException('dados_xml ou rps_numero obrigatorio para substituicao.');
        }

        return '<SubstituirNfseEnvio xmlns="http://www.abrasf.org.br/nfse.xsd">'
            . '<Pedido>' . $cancelXml . '</Pedido>'
            . '<SubstituicaoNfse>'
            . '<IdentificacaoRps><Numero>' . $this->xmlValue($rpsNumero) . '</Numero><Serie>' . $this->xmlValue($rpsSerie) . '</Serie><Tipo>' . $this->xmlValue($rpsTipo) . '</Tipo></IdentificacaoRps>'
            . '</SubstituicaoNfse>'
            . '</SubstituirNfseEnvio>';
    }
}
