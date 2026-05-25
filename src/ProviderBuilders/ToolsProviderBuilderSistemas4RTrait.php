<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderSistemas4RTrait
{
    /**
     * Envelopes Sistemas4R com tags .Execute + Entrada.
     *
     * @param array|string $payload
     */
    private function buildSistemas4REnvelope(array|string $payload, string $service, int $tpAmb): string
    {
        $data = is_array($payload) ? $payload : json_decode((string) $payload, true);
        if (!is_array($data)) {
            throw new RuntimeException('Payload invalido para Sistemas4R.');
        }

        $normalized = strtolower(trim($service));
        if ($normalized === 'recepcionar') {
            $normalized = 'recepcionar_sincrono';
        }
        [$tag, $namespace] = $this->buildSistemas4RTagAndNamespace($normalized, $tpAmb);
        $dados = $this->buildAbrasfDataForMethod($data, $tag);

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<' . $tag . ' xmlns="' . $this->xmlAttr($namespace) . '">'
            . '<Entrada>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</Entrada>'
            . '</' . $tag . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function buildSistemas4RSoapAction(string $service, int $tpAmb): string
    {
        $normalized = strtolower(trim($service));
        if ($normalized === 'recepcionar') {
            $normalized = 'recepcionar_sincrono';
        }
        $isProd = $tpAmb === 1;
        return match ($normalized) {
            'consultar_lote' => $isProd ? 'AbrasfNFSeactionACONSULTARLOTERPS.Execute' : 'AbrasfNFSeactionAHCONSULTARLOTERPS.Execute',
            'consultar_nfse_rps', 'consultar_nf_se_rps' => $isProd ? 'Abrasf2action/ACONSULTARNFSEPORRPS.Execute' : 'Abrasf2action/AHCONSULTARNFSEPORRPS.Execute',
            'cancelar_nfse', 'cancelar_nf_se' => $isProd ? 'Abrasf2action/ACANCELARNFSE.Execute' : 'Abrasf2action/AHCANCELARNFSE.Execute',
            default => $isProd ? 'Abrasf2action/ARECEPCIONARLOTERPSSINCRONO.Execute' : 'Abrasf2action/AHRECEPCIONARLOTERPSSINCRONO.Execute',
        };
    }
}
