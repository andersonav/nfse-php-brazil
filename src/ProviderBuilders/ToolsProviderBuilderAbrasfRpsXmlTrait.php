<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderAbrasfRpsXmlTrait
{
    /**
     * @param array<string,mixed> $data
     */
    private function buildAbrasfRpsXml(array $data): string
    {
        $rpsList = $data['rps'] ?? [];
        $first = is_array($rpsList) && isset($rpsList[0]) && is_array($rpsList[0]) ? $rpsList[0] : [];
        $ident = is_array($first['identificacao'] ?? null) ? $first['identificacao'] : [];
        $prestador = is_array($first['prestador'] ?? null) ? $first['prestador'] : [];
        $tomador = is_array($first['tomador'] ?? null) ? $first['tomador'] : [];
        $servico = is_array($first['servico'] ?? null) ? $first['servico'] : [];

        $rpsNumero = trim((string) ($ident['numero'] ?? ''));
        if ($rpsNumero === '') {
            throw new RuntimeException('RPS numero obrigatorio.');
        }
        $rpsSerie = trim((string) ($ident['serie'] ?? 'UNICA'));
        $rpsTipo = (string) ($ident['tipo'] ?? '1');
        $dataEmissao = trim((string) ($first['data_emissao'] ?? date('Y-m-d\TH:i:s')));

        $prestadorCnpj = preg_replace('/\D+/', '', (string) ($prestador['cnpj'] ?? ''));
        $prestadorIm = trim((string) ($prestador['inscricao_municipal'] ?? ''));
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
            $tomadorDocXml = strlen($tomadorDoc) > 11
                ? '<Cnpj>' . $this->xmlValue($tomadorDoc) . '</Cnpj>'
                : '<Cpf>' . $this->xmlValue($tomadorDoc) . '</Cpf>';
        }

        return '<Rps>'
            . '<InfDeclaracaoPrestacaoServico Id="' . $this->xmlAttr('rps' . $rpsNumero) . '">'
            . '<Rps><IdentificacaoRps><Numero>' . $this->xmlValue($rpsNumero) . '</Numero><Serie>' . $this->xmlValue($rpsSerie) . '</Serie><Tipo>' . $this->xmlValue($rpsTipo) . '</Tipo></IdentificacaoRps><DataEmissao>' . $this->xmlValue($dataEmissao) . '</DataEmissao><Status>1</Status></Rps>'
            . '<Competencia>' . $this->xmlValue(substr($dataEmissao, 0, 10)) . '</Competencia>'
            . '<Servico><Valores><ValorServicos>' . number_format($servicoValor, 2, '.', '') . '</ValorServicos><ValorIss>' . number_format($servicoValor * ($servicoAliquota > 0 ? $servicoAliquota : 0), 2, '.', '') . '</ValorIss><Aliquota>' . number_format($servicoAliquota, 4, '.', '') . '</Aliquota></Valores><IssRetido>' . $this->xmlValue((string) $issRetido) . '</IssRetido><ItemListaServico>' . $this->xmlValue($itemListaServico) . '</ItemListaServico><CodigoCnae>' . $this->xmlValue($codigoCnae) . '</CodigoCnae><Discriminacao>' . $this->xmlValue($servicoDesc) . '</Discriminacao><CodigoMunicipio>' . $this->xmlValue($codigoMunicipio) . '</CodigoMunicipio></Servico>'
            . ($prestadorCnpj !== '' || $prestadorIm !== '' ? '<Prestador>' . ($prestadorCnpj !== '' ? '<Cnpj>' . $this->xmlValue($prestadorCnpj) . '</Cnpj>' : '') . ($prestadorIm !== '' ? '<InscricaoMunicipal>' . $this->xmlValue($prestadorIm) . '</InscricaoMunicipal>' : '') . '</Prestador>' : '')
            . '<TomadorServico><IdentificacaoTomador><CpfCnpj>' . $tomadorDocXml . '</CpfCnpj></IdentificacaoTomador><RazaoSocial>' . $this->xmlValue($tomadorNome) . '</RazaoSocial><Contato><Email>' . $this->xmlValue($tomadorEmail) . '</Email></Contato></TomadorServico>'
            . '</InfDeclaracaoPrestacaoServico>'
            . '</Rps>';
    }
}
