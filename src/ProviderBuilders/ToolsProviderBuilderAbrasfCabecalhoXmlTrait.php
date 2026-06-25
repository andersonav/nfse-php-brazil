<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderAbrasfCabecalhoXmlTrait
{
    private function buildAbrasfCabecalhoXml(string $versaoDados): string
    {
        return '<cabecalho versao="' . $this->xmlAttr($versaoDados) . '" xmlns="http://www.abrasf.org.br/nfse.xsd">'
            . '<versaoDados>' . $this->xmlValue($versaoDados) . '</versaoDados>'
            . '</cabecalho>';
    }
}
