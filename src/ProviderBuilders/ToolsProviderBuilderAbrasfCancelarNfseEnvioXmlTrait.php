<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderAbrasfCancelarNfseEnvioXmlTrait
{
    /**
     * @param array<string,mixed> $data
     */
    private function buildAbrasfCancelarNfseEnvioXml(array $data): string
    {
        $numeroNfse = trim((string) ($data['numero_nfse'] ?? $data['numero'] ?? ''));
        $prestadorCnpj = preg_replace('/\D+/', '', (string) ($data['prestador_cnpj'] ?? ''));
        $prestadorIm = trim((string) ($data['prestador_im'] ?? ''));
        $codigoMunicipio = trim((string) ($data['codigo_municipio'] ?? ''));
        $codigoCancelamento = trim((string) ($data['codigo_cancelamento'] ?? '1'));
        $motivo = trim((string) ($data['motivo'] ?? ''));
        if ($numeroNfse === '') {
            throw new RuntimeException('numero_nfse obrigatorio para cancelamento.');
        }

        return '<CancelarNfseEnvio xmlns="http://www.abrasf.org.br/nfse.xsd">'
            . '<Pedido><InfPedidoCancelamento>'
            . '<IdentificacaoNfse>'
            . '<Numero>' . $this->xmlValue($numeroNfse) . '</Numero>'
            . ($prestadorCnpj !== '' ? '<CpfCnpj><Cnpj>' . $this->xmlValue($prestadorCnpj) . '</Cnpj></CpfCnpj>' : '')
            . ($prestadorIm !== '' ? '<InscricaoMunicipal>' . $this->xmlValue($prestadorIm) . '</InscricaoMunicipal>' : '')
            . ($codigoMunicipio !== '' ? '<CodigoMunicipio>' . $this->xmlValue($codigoMunicipio) . '</CodigoMunicipio>' : '')
            . '</IdentificacaoNfse>'
            . '<CodigoCancelamento>' . $this->xmlValue($codigoCancelamento) . '</CodigoCancelamento>'
            . ($motivo !== '' ? '<MotivoCancelamento>' . $this->xmlValue($motivo) . '</MotivoCancelamento>' : '')
            . '</InfPedidoCancelamento></Pedido>'
            . '</CancelarNfseEnvio>';
    }
}
