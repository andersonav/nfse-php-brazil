<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderFiorilliTrait
{
    /**
     * @param array|string $payload
     */
    private function buildFiorilliEnvelope(array|string $payload, string $service): string
    {
        $data = $this->normalizePayload($payload);
        $method = $this->mapFiorilliMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $method);
        }
        $auth = $this->payloadAuth($data);
        $user = (string) ($auth['username'] ?? '');
        $pass = (string) ($auth['password'] ?? '');
        if ($user === '' || $pass === '') {
            throw new RuntimeException('Fiorilli requer auth.username e auth.password.');
        }

        $inner = '<ws:' . $method . '>'
            . $dados
            . '<username>' . $this->xmlValue($user) . '</username>'
            . '<password>' . $this->xmlValue($pass) . '</password>'
            . '</ws:' . $method . '>';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.issweb.fiorilli.com.br/">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . $inner
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function mapFiorilliMethod(string $service): string
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'gerar_nfse', 'gerar_nf_se', 'emitir_nfse' => 'gerarNfse',
            'recepcionar_sincrono' => 'recepcionarLoteRpsSincrono',
            'consultar_lote' => 'consultarLoteRps',
            'consultar_nfse_rps', 'consultar_nf_se_rps' => 'consultarNfsePorRps',
            'consultar_nfse_faixa', 'consultar_nf_se_faixa' => 'consultarNfsePorFaixa',
            'consultar_nfse_servico_prestado', 'consultar_nf_se_servico_prestado' => 'consultarNfseServicoPrestado',
            'consultar_nfse_servico_tomado', 'consultar_nf_se_servico_tomado' => 'consultarNfseServicoTomado',
            'cancelar_nfse', 'cancelar_nf_se' => 'cancelarNfse',
            'substituir_nfse', 'substituir_nf_se' => 'substituirNfse',
            default => 'recepcionarLoteRps',
        };
    }

    private function buildFiorilliSoapAction(string $service): string
    {
        return $this->mapFiorilliMethod($service);
    }
}
