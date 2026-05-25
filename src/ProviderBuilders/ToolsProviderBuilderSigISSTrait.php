<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderSigISSTrait
{
    /**
     * @param array|string $payload
     */
    private function buildSigISSEnvelope(array|string $payload, string $service): string
    {
        $data = $this->normalizePayload($payload);
        $xml = trim((string) ($data['dados_xml'] ?? ''));
        if ($xml !== '') {
            return $xml;
        }

        // SigISS usa mensagens proprietarias; sem dados_xml, gera fallback minimo.
        $method = $this->mapSigISSMethod($service);
        if ($method === 'CancelarNota') {
            $auth = $this->payloadAuth($data);
            $ccm = trim((string) ($auth['ccm'] ?? $auth['inscricao_municipal'] ?? $data['prestador_im'] ?? ''));
            $cnpj = preg_replace('/\D+/', '', (string) ($auth['cnpj'] ?? $data['prestador_cnpj'] ?? ''));
            $senha = trim((string) ($auth['senha'] ?? $auth['password'] ?? ''));
            $nota = trim((string) ($data['numero_nfse'] ?? $data['numero'] ?? ''));
            $motivo = trim((string) ($data['motivo'] ?? 'Cancelamento solicitado.'));
            $email = trim((string) ($data['email'] ?? ''));
            if ($ccm === '' || $cnpj === '' || $senha === '' || $nota === '') {
                throw new RuntimeException('SigISS requer payload[dados_xml] ou dados minimos de cancelamento (ccm/cnpj/senha/numero_nfse).');
            }

            return '<CancelarNota xmlns="urn:sigiss_ws">'
                . '<DadosPrestador><ccm>' . $this->xmlValue($ccm) . '</ccm><cnpj>' . $this->xmlValue($cnpj) . '</cnpj><senha>' . $this->xmlValue($senha) . '</senha></DadosPrestador>'
                . '<DescricaoCancelaNota><nota>' . $this->xmlValue($nota) . '</nota><motivo>' . $this->xmlValue($motivo) . '</motivo>'
                . ($email !== '' ? '<email>' . $this->xmlValue($email) . '</email>' : '')
                . '</DescricaoCancelaNota>'
                . '</CancelarNota>';
        }

        $dados = $this->buildAbrasfDataForMethod($data, $service);
        return '<' . $method . ' xmlns="urn:sigiss_ws"><inputXML>'
            . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8')
            . '</inputXML></' . $method . '>';
    }

    /**
     * @param array|string $payload
     */
    private function buildSigISSSoapAction(array|string $payload, string $service): string
    {
        $data = $this->normalizePayload($payload);
        $providerExtras = is_array($data['provider_extras'] ?? null) ? $data['provider_extras'] : [];
        $base = trim((string) ($providerExtras['soap_action'] ?? $this->getMunicipioServiceUrl('soap_action') ?? ''));
        if ($base === '') {
            $base = 'urn:sigiss_ws';
        }
        return $base . '#' . $this->mapSigISSMethod($service);
    }

    private function mapSigISSMethod(string $service): string
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'consultar_nfse', 'consultar_nf_se' => 'ConsultarNotaPrestador',
            'cancelar_nfse', 'cancelar_nf_se' => 'CancelarNota',
            default => 'GerarNota',
        };
    }
}
