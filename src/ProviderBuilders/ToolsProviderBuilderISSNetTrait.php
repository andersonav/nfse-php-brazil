<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderISSNetTrait
{
    /**
     * Monta envelope SOAP ISSNet para RecepcionarLoteRps.
     * Compatível com variante legado e 2.04 do provedor.
     *
     * @param array|string $payload
     */
    private function buildISSNetRecepcionarEnvelope(array|string $payload, ?string $versao): string
    {
        $data = is_array($payload) ? $payload : json_decode((string) $payload, true);
        if (!is_array($data)) {
            throw new RuntimeException('Payload de emissao invalido para ISSNet.');
        }

        $dados = $this->buildISSNetDadosLoteXml($data);
        $is204 = str_starts_with((string) $versao, '2.');

        if ($is204) {
            $cabecalho = '<cabecalho versao="2.04" xmlns="http://www.abrasf.org.br/nfse.xsd"><versaoDados>2.04</versaoDados></cabecalho>';

            return '<?xml version="1.0" encoding="UTF-8"?>'
                . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfse="http://nfse.abrasf.org.br">'
                . '<soapenv:Header/>'
                . '<soapenv:Body>'
                . '<nfse:RecepcionarLoteRps>'
                . '<nfseCabecMsg>' . htmlspecialchars($cabecalho, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseCabecMsg>'
                . '<nfseDadosMsg>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseDadosMsg>'
                . '</nfse:RecepcionarLoteRps>'
                . '</soapenv:Body>'
                . '</soapenv:Envelope>';
        }

        // Legado ISSNet: XML vai dentro da tag <xml> (escaped) no método RecepcionarLoteRps.
        $wrapped = '<?xml version="1.0" encoding="UTF-8"?>' . $dados;
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfd="http://www.issnetonline.com.br/webservice/nfd">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<nfd:RecepcionarLoteRps>'
            . '<xml>' . htmlspecialchars($wrapped, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</xml>'
            . '</nfd:RecepcionarLoteRps>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    /**
     * @param array|string $payload
     */
    private function buildISSNetEnvelope(array|string $payload, string $service, ?string $versao): string
    {
        $normalized = strtolower(trim($service));
        if (in_array($normalized, ['recepcionar', 'emitir_nfse', 'gerar_nfse', 'gerar_nf_se'], true)) {
            return $this->buildISSNetRecepcionarEnvelope($payload, $versao);
        }

        $data = $this->normalizePayload($payload);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $is204 = str_starts_with((string) $versao, '2.');

        if ($is204) {
            $map = [
                'cancelar_nfse' => 'CancelarNfse',
                'cancelar_nf_se' => 'CancelarNfse',
                'substituir_nfse' => 'SubstituirNfse',
                'substituir_nf_se' => 'SubstituirNfse',
            ];
            $method = $map[$normalized] ?? 'ConsultarNfse';
            $cabecalho = $this->buildAbrasfCabecalhoXml((string) ($data['versao_dados'] ?? '2.04'));

            return '<?xml version="1.0" encoding="UTF-8"?>'
                . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfse="http://nfse.abrasf.org.br">'
                . '<soapenv:Header/>'
                . '<soapenv:Body>'
                . '<nfse:' . $method . '>'
                . '<nfseCabecMsg>' . htmlspecialchars($cabecalho, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseCabecMsg>'
                . '<nfseDadosMsg>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseDadosMsg>'
                . '</nfse:' . $method . '>'
                . '</soapenv:Body>'
                . '</soapenv:Envelope>';
        }

        $method = $normalized === 'cancelar_nfse' || $normalized === 'cancelar_nf_se' ? 'CancelarNfse' : 'ConsultarNfse';
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<' . $method . ' xmlns="http://www.issnetonline.com.br/webservice/nfd">'
            . '<xml>' . htmlspecialchars('<?xml version="1.0" encoding="UTF-8"?>' . $dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</xml>'
            . '</' . $method . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    /**
     * @param array<string,mixed> $data
     */
    private function buildISSNetDadosLoteXml(array $data): string
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
            throw new RuntimeException('RPS numero obrigatorio para emissao ISSNet.');
        }
        $rpsSerie = trim((string) ($ident['serie'] ?? 'UNICA'));
        $rpsTipo = (string) ($ident['tipo'] ?? '1');
        $dataEmissao = trim((string) ($first['data_emissao'] ?? date('Y-m-d\TH:i:s')));

        $prestadorCnpj = preg_replace('/\D+/', '', (string) ($prestador['cnpj'] ?? ''));
        $prestadorIm = trim((string) ($prestador['inscricao_municipal'] ?? ''));
        if ($prestadorCnpj === '' || $prestadorIm === '') {
            throw new RuntimeException('Prestador (CNPJ e IM) obrigatorio para emissao ISSNet.');
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
            . '<Rps><IdentificacaoRps><Numero>' . $this->xmlValue($rpsNumero) . '</Numero><Serie>' . $this->xmlValue($rpsSerie) . '</Serie><Tipo>' . $this->xmlValue($rpsTipo) . '</Tipo></IdentificacaoRps><DataEmissao>' . $this->xmlValue($dataEmissao) . '</DataEmissao><Status>1</Status></Rps>'
            . '<Competencia>' . $this->xmlValue(substr($dataEmissao, 0, 10)) . '</Competencia>'
            . '<Servico><Valores><ValorServicos>' . number_format($servicoValor, 2, '.', '') . '</ValorServicos><ValorIss>' . number_format($servicoValor * ($servicoAliquota > 0 ? $servicoAliquota : 0), 2, '.', '') . '</ValorIss><Aliquota>' . number_format($servicoAliquota, 4, '.', '') . '</Aliquota></Valores><IssRetido>' . $this->xmlValue((string) $issRetido) . '</IssRetido><ItemListaServico>' . $this->xmlValue($itemListaServico) . '</ItemListaServico><CodigoCnae>' . $this->xmlValue($codigoCnae) . '</CodigoCnae><Discriminacao>' . $this->xmlValue($servicoDesc) . '</Discriminacao><CodigoMunicipio>' . $this->xmlValue($codigoMunicipio) . '</CodigoMunicipio></Servico>'
            . '<Prestador><Cnpj>' . $this->xmlValue($prestadorCnpj) . '</Cnpj><InscricaoMunicipal>' . $this->xmlValue($prestadorIm) . '</InscricaoMunicipal></Prestador>'
            . '<TomadorServico><IdentificacaoTomador><CpfCnpj>' . $tomadorDocXml . '</CpfCnpj></IdentificacaoTomador><RazaoSocial>' . $this->xmlValue($tomadorNome) . '</RazaoSocial><Contato><Email>' . $this->xmlValue($tomadorEmail) . '</Email></Contato></TomadorServico>'
            . '</InfDeclaracaoPrestacaoServico>'
            . '</Rps>';

        return ''
            . '<EnviarLoteRpsEnvio xmlns="http://www.issnetonline.com.br/webserviceabrasf/vsd/servico_enviar_lote_rps_envio.xsd">'
            . '<LoteRps Id="' . $this->xmlAttr('lote' . $loteNumero) . '" versao="2.04">'
            . '<NumeroLote>' . $this->xmlValue($loteNumero) . '</NumeroLote>'
            . '<CpfCnpj><Cnpj>' . $this->xmlValue($prestadorCnpj) . '</Cnpj></CpfCnpj>'
            . '<InscricaoMunicipal>' . $this->xmlValue($prestadorIm) . '</InscricaoMunicipal>'
            . '<QuantidadeRps>1</QuantidadeRps>'
            . '<ListaRps xmlns="http://www.issnetonline.com.br/webserviceabrasf/vsd/tipos_complexos.xsd">' . $rpsXml . '</ListaRps>'
            . '</LoteRps>'
            . '</EnviarLoteRpsEnvio>';
    }
}
