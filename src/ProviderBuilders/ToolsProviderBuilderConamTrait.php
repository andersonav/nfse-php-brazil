<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderConamTrait
{
    /**
     * @param array|string $payload
     */
    private function buildConamEnvelope(array|string $payload, string $service): string
    {
        $data = $this->normalizePayload($payload);
        [$method] = $this->mapConamMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $inner = $method === 'IMPRESSAOLINKNFSE'
            ? '<Xml_entrada>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</Xml_entrada>'
            : $dados;

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfe="NFe">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<nfe:ws_nfe.' . $method . '>' . $inner . '</nfe:ws_nfe.' . $method . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    /**
     * @return array{0:string}
     */
    private function mapConamMethod(string $service): array
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'consultar_situacao' => ['CONSULTAPROTOCOLO'],
            'consultar_lote' => ['CONSULTANOTASPROTOCOLO'],
            'consultar_danfse', 'link_url' => ['IMPRESSAOLINKNFSE'],
            'cancelar_nfse', 'cancelar_nf_se' => ['CANCELANOTAELETRONICA'],
            default => ['PROCESSARPS'],
        };
    }

    private function buildConamSoapAction(string $service): string
    {
        [$method] = $this->mapConamMethod($service);
        return 'NFeaction/AWS_NFE.' . $method;
    }
}
