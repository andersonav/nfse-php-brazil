<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderCTAConsultTrait
{
    /**
     * @param array|string $payload
     */
    private function buildCTAConsultEnvelope(array|string $payload, string $service, int $tpAmb): string
    {
        $data = $this->normalizePayload($payload);
        $normalized = strtolower(trim($service));
        $isCancel = in_array($normalized, ['cancelar_nfse', 'cancelar_nf_se'], true);

        $cabecalho = $this->buildCTAConsultCabecalhoXml($tpAmb, $isCancel);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $isCancel
                ? $this->buildCTAConsultCancelXml($data)
                : $this->buildCTAConsultEmitXml($data);
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsn="http://wsnfselote.ctaconsult.com.br/">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<wsn:executar>'
            . '<arg0>' . htmlspecialchars($cabecalho, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</arg0>'
            . '<arg1>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</arg1>'
            . '</wsn:executar>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }
}
