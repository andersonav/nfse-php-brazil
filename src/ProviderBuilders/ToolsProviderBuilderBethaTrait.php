<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderBethaTrait
{
    /**
     * Monta envelope SOAP de emissão Betha conforme as variantes do provedor.
     *
     * @param array|string $payload
     * @param array<string,mixed> $params
     */
    private function buildBethaEmitirEnvelope(array|string $payload, string $service, ?string $versao, array $params): string
    {
        $data = is_array($payload) ? $payload : json_decode((string) $payload, true);
        if (!is_array($data)) {
            throw new RuntimeException('Payload de emissao invalido para Betha.');
        }

        $dadosXml = $this->buildBethaDadosEmissaoXml($data, $service);
        $subVersao = (int) ($params['subversao'] ?? $params['SubVersao'] ?? 0);
        $isV202 = str_starts_with((string) $versao, '2.') || $subVersao === 0;

        if ($isV202) {
            $method = in_array($service, ['gerar_nf_se', 'gerar_nfse', 'emitir_nfse'], true)
                ? 'GerarNfse'
                : 'RecepcionarLoteRps';

            $cabecalho = '<cabecalho versao="2.02" xmlns="http://www.abrasf.org.br/nfse.xsd"><versaoDados>2.02</versaoDados></cabecalho>';

            return '<?xml version="1.0" encoding="UTF-8"?>'
                . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tns="http://www.betha.com.br/e-nota-contribuinte-ws">'
                . '<soapenv:Header/>'
                . '<soapenv:Body>'
                . '<tns:' . $method . '>'
                . '<nfseCabecMsg>' . htmlspecialchars($cabecalho, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseCabecMsg>'
                . '<nfseDadosMsg>' . htmlspecialchars($dadosXml, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseDadosMsg>'
                . '</tns:' . $method . '>'
                . '</soapenv:Body>'
                . '</soapenv:Envelope>';
        }

        // Compatibilidade Betha legado (SubVersao 1) mantendo corpo XML direto no SOAP.
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . $dadosXml
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    /**
     * @param array<string,mixed> $data
     */
    private function buildBethaDadosEmissaoXml(array $data, string $service): string
    {
        $loteNumero = (string) ($data['lote']['numero_lote'] ?? '1');
        $rpsList = $data['rps'] ?? [];
        $first = is_array($rpsList) && isset($rpsList[0]) && is_array($rpsList[0]) ? $rpsList[0] : [];
        $ident = is_array($first['identificacao'] ?? null) ? $first['identificacao'] : [];
        $prestador = is_array($first['prestador'] ?? null) ? $first['prestador'] : [];
        $tomador = is_array($first['tomador'] ?? null) ? $first['tomador'] : [];
        $servico = is_array($first['servico'] ?? null) ? $first['servico'] : [];

        $rpsNumero = trim((string) ($ident['numero'] ?? ''));
        if ($rpsNumero === '') {
            throw new RuntimeException('RPS numero obrigatorio para emissao Betha.');
        }
        $rpsSerie = trim((string) ($ident['serie'] ?? 'UNICA'));
        $rpsTipo = (string) ($ident['tipo'] ?? '1');
        $dataEmissao = trim((string) ($first['data_emissao'] ?? date('Y-m-d\TH:i:s')));

        $prestadorCnpj = preg_replace('/\D+/', '', (string) ($prestador['cnpj'] ?? ''));
        $prestadorIm = trim((string) ($prestador['inscricao_municipal'] ?? ''));
        if ($prestadorCnpj === '' || $prestadorIm === '') {
            throw new RuntimeException('Prestador (CNPJ e IM) obrigatorio para emissao Betha.');
        }

        $tomadorDoc = preg_replace('/\D+/', '', (string) ($tomador['documento'] ?? ''));
        $tomadorNome = trim((string) ($tomador['nome_razao_social'] ?? ''));
        $tomadorEmail = trim((string) ($tomador['email'] ?? ''));

        $servicoDesc = trim((string) ($servico['discriminacao'] ?? ''));
        $servicoValor = (float) ($servico['valor_servicos'] ?? 0);
        $servicoAliquota = (float) ($servico['aliquota'] ?? 0);
        $issRetido = (int) ($servico['iss_retido'] ?? 2);
        $codigoMunicipio = trim((string) ($servico['codigo_municipio'] ?? ''));
        $itemListaServico = trim((string) ($servico['item_lista_servico'] ?? ''));
        $codigoCnae = trim((string) ($servico['codigo_cnae'] ?? ''));

        $tomadorDocXml = '';
        if ($tomadorDoc !== '') {
            if (strlen($tomadorDoc) > 11) {
                $tomadorDocXml = '<Cnpj>' . $this->xmlValue($tomadorDoc) . '</Cnpj>';
            } else {
                $tomadorDocXml = '<Cpf>' . $this->xmlValue($tomadorDoc) . '</Cpf>';
            }
        }

        $rpsXml = ''
            . '<Rps>'
            . '<InfDeclaracaoPrestacaoServico Id="' . $this->xmlAttr('rps' . $rpsNumero) . '">'
            . '<Rps>'
            . '<IdentificacaoRps>'
            . '<Numero>' . $this->xmlValue($rpsNumero) . '</Numero>'
            . '<Serie>' . $this->xmlValue($rpsSerie) . '</Serie>'
            . '<Tipo>' . $this->xmlValue($rpsTipo) . '</Tipo>'
            . '</IdentificacaoRps>'
            . '<DataEmissao>' . $this->xmlValue($dataEmissao) . '</DataEmissao>'
            . '<Status>1</Status>'
            . '</Rps>'
            . '<Competencia>' . $this->xmlValue(substr($dataEmissao, 0, 10)) . '</Competencia>'
            . '<Servico>'
            . '<Valores>'
            . '<ValorServicos>' . number_format($servicoValor, 2, '.', '') . '</ValorServicos>'
            . '<ValorIss>' . number_format($servicoValor * ($servicoAliquota > 0 ? $servicoAliquota : 0), 2, '.', '') . '</ValorIss>'
            . '<Aliquota>' . number_format($servicoAliquota, 4, '.', '') . '</Aliquota>'
            . '</Valores>'
            . '<IssRetido>' . $this->xmlValue((string) $issRetido) . '</IssRetido>'
            . '<ItemListaServico>' . $this->xmlValue($itemListaServico) . '</ItemListaServico>'
            . '<CodigoCnae>' . $this->xmlValue($codigoCnae) . '</CodigoCnae>'
            . '<Discriminacao>' . $this->xmlValue($servicoDesc) . '</Discriminacao>'
            . '<CodigoMunicipio>' . $this->xmlValue($codigoMunicipio) . '</CodigoMunicipio>'
            . '</Servico>'
            . '<Prestador>'
            . '<Cnpj>' . $this->xmlValue($prestadorCnpj) . '</Cnpj>'
            . '<InscricaoMunicipal>' . $this->xmlValue($prestadorIm) . '</InscricaoMunicipal>'
            . '</Prestador>'
            . '<TomadorServico>'
            . '<IdentificacaoTomador><CpfCnpj>' . $tomadorDocXml . '</CpfCnpj></IdentificacaoTomador>'
            . '<RazaoSocial>' . $this->xmlValue($tomadorNome) . '</RazaoSocial>'
            . '<Contato><Email>' . $this->xmlValue($tomadorEmail) . '</Email></Contato>'
            . '</TomadorServico>'
            . '</InfDeclaracaoPrestacaoServico>'
            . '</Rps>';

        if (in_array($service, ['gerar_nf_se', 'gerar_nfse', 'emitir_nfse'], true)) {
            return '<GerarNfseEnvio xmlns="http://www.betha.com.br/e-nota-contribuinte-ws">' . $rpsXml . '</GerarNfseEnvio>';
        }

        return ''
            . '<EnviarLoteRpsEnvio xmlns="http://www.betha.com.br/e-nota-contribuinte-ws">'
            . '<LoteRps Id="' . $this->xmlAttr('lote' . $loteNumero) . '" versao="2.02">'
            . '<NumeroLote>' . $this->xmlValue($loteNumero) . '</NumeroLote>'
            . '<Cnpj>' . $this->xmlValue($prestadorCnpj) . '</Cnpj>'
            . '<InscricaoMunicipal>' . $this->xmlValue($prestadorIm) . '</InscricaoMunicipal>'
            . '<QuantidadeRps>1</QuantidadeRps>'
            . '<ListaRps>' . $rpsXml . '</ListaRps>'
            . '</LoteRps>'
            . '</EnviarLoteRpsEnvio>';
    }

    /**
     * @param array|string $payload
     * @param array<string,mixed> $params
     */
    private function buildBethaCancelarEnvelope(array|string $payload, ?string $versao, array $params): string
    {
        $data = is_array($payload) ? $payload : json_decode((string) $payload, true);
        if (!is_array($data)) {
            throw new RuntimeException('Payload de cancelamento invalido para Betha.');
        }

        $numeroNfse = trim((string) ($data['numero_nfse'] ?? $data['numero'] ?? ''));
        $codigoMunicipio = trim((string) ($data['codigo_municipio'] ?? ''));
        $codigoCancelamento = trim((string) ($data['codigo_cancelamento'] ?? '1'));
        $motivo = trim((string) ($data['motivo'] ?? ''));
        $prestadorCnpj = preg_replace('/\D+/', '', (string) ($data['prestador_cnpj'] ?? ''));
        $prestadorIm = trim((string) ($data['prestador_im'] ?? ''));

        if ($numeroNfse === '' || $prestadorCnpj === '' || $prestadorIm === '') {
            throw new RuntimeException('Para Betha cancelamento informe numero_nfse, prestador_cnpj e prestador_im.');
        }

        $dadosXml = '<CancelarNfseEnvio xmlns="http://www.betha.com.br/e-nota-contribuinte-ws">'
            . '<Pedido>'
            . '<InfPedidoCancelamento>'
            . '<IdentificacaoNfse>'
            . '<Numero>' . $this->xmlValue($numeroNfse) . '</Numero>'
            . '<CpfCnpj><Cnpj>' . $this->xmlValue($prestadorCnpj) . '</Cnpj></CpfCnpj>'
            . '<InscricaoMunicipal>' . $this->xmlValue($prestadorIm) . '</InscricaoMunicipal>'
            . ($codigoMunicipio !== '' ? '<CodigoMunicipio>' . $this->xmlValue($codigoMunicipio) . '</CodigoMunicipio>' : '')
            . '</IdentificacaoNfse>'
            . '<CodigoCancelamento>' . $this->xmlValue($codigoCancelamento) . '</CodigoCancelamento>'
            . ($motivo !== '' ? '<MotivoCancelamento>' . $this->xmlValue($motivo) . '</MotivoCancelamento>' : '')
            . '</InfPedidoCancelamento>'
            . '</Pedido>'
            . '</CancelarNfseEnvio>';

        $subVersao = (int) ($params['subversao'] ?? $params['SubVersao'] ?? 0);
        $isV202 = str_starts_with((string) $versao, '2.') || $subVersao === 0;
        if ($isV202) {
            $cabecalho = '<cabecalho versao="2.02" xmlns="http://www.abrasf.org.br/nfse.xsd"><versaoDados>2.02</versaoDados></cabecalho>';
            return '<?xml version="1.0" encoding="UTF-8"?>'
                . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tns="http://www.betha.com.br/e-nota-contribuinte-ws">'
                . '<soapenv:Header/>'
                . '<soapenv:Body>'
                . '<tns:CancelarNfse>'
                . '<nfseCabecMsg>' . htmlspecialchars($cabecalho, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseCabecMsg>'
                . '<nfseDadosMsg>' . htmlspecialchars($dadosXml, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseDadosMsg>'
                . '</tns:CancelarNfse>'
                . '</soapenv:Body>'
                . '</soapenv:Envelope>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . $dadosXml
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    /**
     * @param array|string $payload
     * @param array<string,mixed> $params
     */
    private function buildBethaSubstituirEnvelope(array|string $payload, ?string $versao, array $params): string
    {
        $data = is_array($payload) ? $payload : json_decode((string) $payload, true);
        if (!is_array($data)) {
            throw new RuntimeException('Payload de substituicao invalido para Betha.');
        }

        $dadosXmlRaw = trim((string) ($data['dados_xml'] ?? ''));
        if ($dadosXmlRaw === '') {
            throw new RuntimeException('Para substituicao Betha informe payload[dados_xml] com o XML SubstituirNfseEnvio.');
        }

        $subVersao = (int) ($params['subversao'] ?? $params['SubVersao'] ?? 0);
        $isV202 = str_starts_with((string) $versao, '2.') || $subVersao === 0;
        if ($isV202) {
            $cabecalho = '<cabecalho versao="2.02" xmlns="http://www.abrasf.org.br/nfse.xsd"><versaoDados>2.02</versaoDados></cabecalho>';
            return '<?xml version="1.0" encoding="UTF-8"?>'
                . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tns="http://www.betha.com.br/e-nota-contribuinte-ws">'
                . '<soapenv:Header/>'
                . '<soapenv:Body>'
                . '<tns:SubstituirNfse>'
                . '<nfseCabecMsg>' . htmlspecialchars($cabecalho, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseCabecMsg>'
                . '<nfseDadosMsg>' . htmlspecialchars($dadosXmlRaw, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseDadosMsg>'
                . '</tns:SubstituirNfse>'
                . '</soapenv:Body>'
                . '</soapenv:Envelope>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . $dadosXmlRaw
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }
}
