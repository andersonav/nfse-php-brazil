<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderGinfesTrait
{
    /**
     * Monta envelope SOAP Ginfes V3 para RecepcionarLoteRps.
     *
     * @param array|string $payload
     */
    private function buildGinfesRecepcionarEnvelope(array|string $payload, string $url): string
    {
        $data = is_array($payload) ? $payload : json_decode((string) $payload, true);
        if (!is_array($data)) {
            throw new RuntimeException('Payload de emissao invalido para Ginfes.');
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $targetNamespace = str_contains($host, 'homolog')
            ? 'http://homologacao.ginfes.com.br'
            : 'http://producao.ginfes.com.br';

        $cabecalho = '<ns2:cabecalho versao="3" xmlns:ns2="http://www.ginfes.com.br/cabecalho_v03.xsd">'
            . '<versaoDados>3</versaoDados>'
            . '</ns2:cabecalho>';

        $dados = $this->buildGinfesDadosLoteXml($data);

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="' . $this->xmlAttr($targetNamespace) . '">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<ns1:RecepcionarLoteRpsV3>'
            . '<arg0>' . htmlspecialchars($cabecalho, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</arg0>'
            . '<arg1>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</arg1>'
            . '</ns1:RecepcionarLoteRpsV3>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    /**
     * @param array|string $payload
     */
    private function buildGinfesEnvelope(array|string $payload, string $service, string $url): string
    {
        $data = $this->normalizePayload($payload);
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $targetNamespace = str_contains($host, 'homolog')
            ? 'http://homologacao.ginfes.com.br'
            : 'http://producao.ginfes.com.br';

        [$method, $respTag] = $this->mapGinfesMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $cabecalho = $this->buildAbrasfCabecalhoXml((string) ($data['versao_dados'] ?? '3'));

        $arg0 = $respTag === 'CancelarNfseResposta' ? $cabecalho : $cabecalho;
        $arg1 = $dados;

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="' . $this->xmlAttr($targetNamespace) . '">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<ns1:' . $method . '>'
            . '<arg0>' . htmlspecialchars($arg0, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</arg0>'
            . '<arg1>' . htmlspecialchars($arg1, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</arg1>'
            . '</ns1:' . $method . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    /**
     * @return array{0:string,1:string}
     */
    private function mapGinfesMethod(string $service): array
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'consultar_lote' => ['ConsultarLoteRpsV3', 'ConsultarLoteRpsResposta'],
            'consultar_situacao' => ['ConsultarSituacaoLoteRpsV3', 'ConsultarSituacaoLoteRpsResposta'],
            'consultar_nfse_rps', 'consultar_nf_se_rps' => ['ConsultarNfsePorRpsV3', 'ConsultarNfseRpsResposta'],
            'consultar_nfse', 'consultar_nf_se' => ['ConsultarNfseV3', 'ConsultarNfseResposta'],
            'cancelar_nfse', 'cancelar_nf_se' => ['CancelarNfseV3', 'CancelarNfseResposta'],
            default => ['RecepcionarLoteRpsV3', 'EnviarLoteRpsResposta'],
        };
    }

    /**
     * @param array<string,mixed> $data
     */
    private function buildGinfesDadosLoteXml(array $data): string
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
            throw new RuntimeException('RPS numero obrigatorio para emissao Ginfes.');
        }
        $rpsSerie = trim((string) ($ident['serie'] ?? 'UNICA'));
        $rpsTipo = (string) ($ident['tipo'] ?? '1');
        $dataEmissao = trim((string) ($first['data_emissao'] ?? date('Y-m-d\TH:i:s')));

        $prestadorCnpj = preg_replace('/\D+/', '', (string) ($prestador['cnpj'] ?? ''));
        $prestadorIm = trim((string) ($prestador['inscricao_municipal'] ?? ''));
        if ($prestadorCnpj === '' || $prestadorIm === '') {
            throw new RuntimeException('Prestador (CNPJ e IM) obrigatorio para emissao Ginfes.');
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

        return ''
            . '<EnviarLoteRpsEnvio xmlns="http://www.ginfes.com.br/servico_enviar_lote_rps_envio_v03.xsd">'
            . '<LoteRps Id="' . $this->xmlAttr('lote' . $loteNumero) . '" versao="3">'
            . '<NumeroLote>' . $this->xmlValue($loteNumero) . '</NumeroLote>'
            . '<Cnpj>' . $this->xmlValue($prestadorCnpj) . '</Cnpj>'
            . '<InscricaoMunicipal>' . $this->xmlValue($prestadorIm) . '</InscricaoMunicipal>'
            . '<QuantidadeRps>1</QuantidadeRps>'
            . '<ListaRps xmlns="http://www.ginfes.com.br/tipos_v03.xsd">' . $rpsXml . '</ListaRps>'
            . '</LoteRps>'
            . '</EnviarLoteRpsEnvio>';
    }
}
