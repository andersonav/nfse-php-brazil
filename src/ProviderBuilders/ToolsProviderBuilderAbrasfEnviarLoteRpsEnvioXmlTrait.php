<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderAbrasfEnviarLoteRpsEnvioXmlTrait
{
    /**
     * @param array<string,mixed> $data
     */
    private function buildAbrasfEnviarLoteRpsEnvioXml(array $data): string
    {
        $loteNumero = (string) ($data['lote']['numero_lote'] ?? '1');
        $prestador = is_array(($data['rps'][0]['prestador'] ?? null)) ? $data['rps'][0]['prestador'] : [];
        $prestadorCnpj = preg_replace('/\D+/', '', (string) ($prestador['cnpj'] ?? ''));
        $prestadorIm = trim((string) ($prestador['inscricao_municipal'] ?? ''));
        $rpsXml = $this->buildAbrasfRpsXml($data);

        return '<EnviarLoteRpsEnvio xmlns="http://www.abrasf.org.br/nfse.xsd">'
            . '<LoteRps Id="' . $this->xmlAttr('lote' . $loteNumero) . '" versao="2.04">'
            . '<NumeroLote>' . $this->xmlValue($loteNumero) . '</NumeroLote>'
            . ($prestadorCnpj !== '' ? '<CpfCnpj><Cnpj>' . $this->xmlValue($prestadorCnpj) . '</Cnpj></CpfCnpj>' : '')
            . ($prestadorIm !== '' ? '<InscricaoMunicipal>' . $this->xmlValue($prestadorIm) . '</InscricaoMunicipal>' : '')
            . '<QuantidadeRps>1</QuantidadeRps>'
            . '<ListaRps>' . $rpsXml . '</ListaRps>'
            . '</LoteRps>'
            . '</EnviarLoteRpsEnvio>';
    }
}
