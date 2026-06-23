<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderCTAConsultCabecalhoXmlTrait
{
    private function buildCTAConsultCabecalhoXml(int $tpAmb, bool $cancel): string
    {
        $tag = $cancel ? 'cabecalhoCancelamentoNfseLote' : 'cabecalhoNfseLote';
        $amb = $tpAmb === 1 ? '1' : '2';
        return '<' . $tag . ' xmlns="http://www.ctaconsult.com/nfse"><versao>1.00</versao><ambiente>'
            . $amb
            . '</ambiente></' . $tag . '>';
    }
}
