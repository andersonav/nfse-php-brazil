<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderGiapTrait
{
    /**
     * @param array|string $payload
     */
    private function buildGiapEnvelope(array|string $payload, string $service): string
    {
        $data = $this->normalizePayload($payload);
        $normalized = strtolower(trim($service));
        $xml = trim((string) ($data['dados_xml'] ?? ''));
        if ($xml !== '') {
            return $xml;
        }

        if (in_array($normalized, ['cancelar_nfse', 'cancelar_nf_se'], true)) {
            $codigoMotivo = trim((string) ($data['codigo_cancelamento'] ?? $data['cod_cancelamento'] ?? ''));
            $numeroNota = trim((string) ($data['numero_nfse'] ?? $data['numero'] ?? ''));
            if ($codigoMotivo === '' || $numeroNota === '') {
                throw new RuntimeException('Giap cancelamento requer codigo_cancelamento e numero_nfse.');
            }
            return '<nfe><cancelaNota><codigoMotivo>' . $this->xmlValue($codigoMotivo)
                . '</codigoMotivo><numeroNota>' . $this->xmlValue($numeroNota)
                . '</numeroNota></cancelaNota></nfe>';
        }

        if (in_array($normalized, ['consultar_nfse_rps', 'consultar_nf_se_rps', 'consultar_nfse', 'consultar_nf_se'], true)) {
            $auth = $this->payloadAuth($data);
            $inscricao = preg_replace('/\D+/', '', (string) ($auth['inscricao_municipal'] ?? $auth['ccm'] ?? $data['prestador_im'] ?? ''));
            $codigoVerificacao = trim((string) ($data['codigo_verificacao'] ?? $data['cod_verificacao'] ?? ''));
            if ($inscricao === '' || $codigoVerificacao === '') {
                throw new RuntimeException('Giap consulta requer inscricao_municipal e codigo_verificacao.');
            }
            return '<consulta><inscricaoMunicipal>' . $this->xmlValue($inscricao)
                . '</inscricaoMunicipal><codigoVerificacao>' . $this->xmlValue($codigoVerificacao)
                . '</codigoVerificacao></consulta>';
        }

        $conteudo = trim((string) ($data['nfse_xml'] ?? ''));
        if ($conteudo === '') {
            $conteudo = $this->buildAbrasfDataForMethod($data, 'gerar_nfse');
        }
        return '<nfe>' . $conteudo . '</nfe>';
    }

    /**
     * @param array|string $payload
     * @return array<int,string>
     */
    private function buildGiapHeaders(array|string $payload): array
    {
        $data = $this->normalizePayload($payload);
        $auth = $this->payloadAuth($data);
        $token = trim((string) ($auth['ws_chave_autorizacao'] ?? $auth['token'] ?? ''));
        $inscricao = preg_replace('/\D+/', '', (string) ($auth['inscricao_municipal'] ?? $auth['ccm'] ?? $data['prestador_im'] ?? ''));
        if ($token === '' || $inscricao === '') {
            throw new RuntimeException('Giap requer auth.ws_chave_autorizacao e auth.inscricao_municipal.');
        }
        return [
            'Authorization: ' . $inscricao . '-' . $token,
            'postman-token: ' . $token,
        ];
    }
}
