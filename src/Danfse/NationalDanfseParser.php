<?php
declare(strict_types=1);

namespace Alves\NfseBrasil\Danfse;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use RuntimeException;

/** Leitura do XML autorizado. Caminhos relativos evitam misturar bases de ISS e IBS/CBS. */
final class NationalDanfseParser
{
    private DOMXPath $xp;
    private DOMElement $nf;
    private DOMElement $dps;
    private static ?array $municipios = null;

    public function parse(string $xml): array
    {
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            if (!$dom->loadXML($xml, LIBXML_NONET) || $dom->doctype !== null) {
                throw new RuntimeException('XML de NFS-e inválido.');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $this->xp = new DOMXPath($dom);
        $this->xp->registerNamespace('n', 'http://www.sped.fazenda.gov.br/nfse');
        $nf = $this->xp->query('//n:NFSe/n:infNFSe');
        if ($nf === false || $nf->length !== 1) {
            throw new RuntimeException('Informe o XML autorizado de uma única NFS-e nacional.');
        }
        $this->nf = $nf->item(0);
        $dps = $this->xp->query('n:DPS/n:infDPS', $this->nf)->item(0);
        if (!$dps instanceof DOMElement) {
            throw new RuntimeException('DPS não encontrada no XML autorizado.');
        }
        $this->dps = $dps;
        $chave = preg_replace('/^NFS/', '', $this->nf->getAttribute('Id') ?: $this->nf->getAttribute('id'));
        if (!preg_match('/^\d{50}$/', $chave)) {
            throw new RuntimeException('A chave da NFS-e nacional deve conter 50 dígitos.');
        }
        $v = fn(string $path): string => $this->value($path, $this->nf);
        $p = fn(string $path): string => $this->value($path, $this->dps);
        $prest = $this->person('n:prest', $this->dps);
        // Endereço/contato do emitente autorizado complementa o cadastro da DPS somente com dados do XML.
        $emit = $this->person('n:emit', $this->nf);
        foreach ($prest as $key => $value) {
            if ($value === '') { $prest[$key] = $emit[$key] ?? ''; }
        }
        $ibs = 'n:IBSCBS/';
        $reg = 'n:prest/n:regTrib/';
        $iss = 'n:valores/n:trib/n:tribMun/';
        $fed = 'n:valores/n:trib/n:tribFed/';
        $cls = 'n:IBSCBS/n:valores/n:trib/n:gIBSCBS/';
        $ano = (int) substr($p('n:dCompet'), 0, 4);
        $parts = [];
        foreach ([
            'Inf. Cont.' => 'n:serv/n:infoCompl/n:xInfComp', 'NFS-e Subst.' => 'n:subst/n:chSubstda',
            'Doc. Ref.' => 'n:serv/n:infoCompl/n:docRef', 'Cod. Obra' => 'n:serv/n:obra/n:cObra',
            'Insc. Imob.' => 'n:IBSCBS/n:imovel/n:inscImobFisc', 'Cod. Evt.' => 'n:serv/n:atvEvento/n:idAtvEvt',
            'Doc. Tec.' => 'n:serv/n:infoCompl/n:idDocTec', 'Núm. Ped.' => 'n:serv/n:infoCompl/n:xPed',
        ] as $label => $path) {
            if (($value = $p($path)) !== '') { $parts[] = $label . ': ' . $value; }
        }
        foreach ($this->xp->query('n:serv/n:infoCompl/n:gItemPed/n:xItemPed', $this->dps) as $item) {
            $parts[] = 'Item Ped.: ' . trim($item->textContent);
        }
        if (($value = $v('n:valores/n:xOutInf')) !== '') { $parts[] = 'Inf. A. T. Mun.: ' . $value; }
        $totTrib = [];
        foreach (['Fed' => 'Federais', 'Est' => 'Estaduais', 'Mun' => 'Municipais'] as $suffix => $label) {
            $monetario = $p('n:valores/n:trib/n:totTrib/n:vTotTrib/n:vTotTrib' . $suffix);
            if ($suffix === 'Mun' && $monetario === '') { $monetario = $p('n:valores/n:trib/n:totTrib/n:vTotTrib/n:vTotTribMu'); }
            $percentual = $p('n:valores/n:trib/n:totTrib/n:pTotTrib/n:pTotTrib' . $suffix);
            if ($suffix === 'Mun' && $percentual === '') { $percentual = $p('n:valores/n:trib/n:totTrib/n:pTotTrib/n:pTotTribMu'); }
            $totTrib[] = $label . ': ' . ($monetario !== '' ? $this->money($monetario) : $this->percent($percentual));
        }
        if (isset($parts[0]) && str_starts_with($parts[0], 'Inf. Cont.: ') && mb_strlen(implode(' | ', $parts)) > 1997) {
            $reserved = mb_strlen(implode(' | ', array_slice($parts, 1)));
            $available = max(0, 1997 - $reserved - ($reserved > 0 ? 3 : 0) - 3);
            $parts[0] = mb_substr($parts[0], 0, $available) . '...';
        }
        $totalIbs = $v($ibs . 'n:totCIBS/n:gIBS/n:vIBSTot');
        $totalCbs = $v($ibs . 'n:totCIBS/n:gCBS/n:vCBS');
        $exclusoes = [$p('n:valores/n:vDescCondIncond/n:vDescIncond'), $v($ibs . 'n:valores/n:vCalcReeRepRes'),
            $v($ibs . 'n:valores/n:vCalcAjusteBCIBSCBS'), $v($ibs . 'n:valores/n:vCalcAjusteBCLocImoveis')];
        if ($ano <= 2032) { $exclusoes[] = $v('n:valores/n:vISSQN'); }
        if ($ano <= 2026) { $exclusoes[] = $p($fed . 'n:piscofins/n:vPis'); $exclusoes[] = $p($fed . 'n:piscofins/n:vCofins'); }
        return [
            'nacional' => true, 'chave' => $chave, 'numero_nfse' => $v('n:nNFSe'),
            'competencia' => $this->date($p('n:dCompet')), 'data_emissao_nfse' => $this->date($v('n:dhProc'), true),
            'numero_rps' => $p('n:nDPS'), 'serie_rps' => $p('n:serie'), 'data_emissao_dps' => $this->date($p('n:dhEmi'), true),
            'tp_amb' => $p('n:tpAmb'), 'ambiente_desc' => $this->domain($p('n:tpAmb'), ['1' => 'Produção', '2' => 'Homologação']),
            'ambiente_gerador_desc' => $this->domain($v('n:ambGer'), ['1' => 'Nacional', '2' => 'Municipal']),
            'municipio_label' => trim($v('n:xLocEmi') . ' / ' . $emit['uf'], ' /'),
            'emitente_desc' => $this->domain($p('n:tpEmit'), ['1' => 'Prestador', '2' => 'Tomador', '3' => 'Intermediário']),
            'situacao_nfse_desc' => $this->domain($v('n:cStat'), ['100' => 'NFS-e Gerada', '102' => 'NFS-e de Decisão Judicial', '103' => 'NFS-e Avulsa', '107' => 'NFS-e MEI']),
            'finalidade_desc' => $this->domain($p('n:IBSCBS/n:finNFSe'), ['0' => 'NFS-e Regular']),
            'prestador' => $prest, 'tomador' => $this->person('n:toma', $this->dps),
            'destinatario' => $this->person('n:IBSCBS/n:dest', $this->dps), 'intermediario' => $this->person('n:interm', $this->dps),
            'ind_dest' => $p('n:IBSCBS/n:indDest'),
            'sn_comp_desc' => $this->domain($p($reg . 'n:opSimpNac'), ['1' => 'Não Optante', '2' => 'Optante - MEI', '3' => 'Optante - ME/EPP']),
            'reg_ap_sn_desc' => $this->domain($p($reg . 'n:regApTribSN'), ['1' => 'Tributos federais e municipal pelo SN', '2' => 'Tributos federais pelo SN e ISS pela NFS-e', '3' => 'Tributos federais e ISS pela NFS-e']),
            'trib_nac' => $p('n:serv/n:cServ/n:cTribNac'), 'trib_mun' => $p('n:serv/n:cServ/n:cTribMun'),
            'trib_desc' => $v('n:xTribMun') ?: $v('n:xTribNac'), 'nbs' => $p('n:serv/n:cServ/n:cNBS'),
            'local_prest_desc' => $this->local($p('n:serv/n:locPrest/n:cLocPrestacao'), $v('n:xLocPrestacao'), $p('n:serv/n:locPrest/n:cPaisPrestacao')),
            'serv_desc' => $p('n:serv/n:cServ/n:xDescServ'), 'ano' => $ano,
            'trib_issqn_desc' => $this->domain($p($iss . 'n:tribISSQN'), ['1' => 'Operação Tributável', '2' => 'Imunidade', '3' => 'Exportação de Serviço', '4' => 'Não Incidência']),
            'incide_iss' => $p($iss . 'n:tribISSQN') !== '' && $p($iss . 'n:tribISSQN') !== '4',
            'reg_esp_issqn_desc' => $this->domain($p($reg . 'n:regEspTrib'), ['0' => 'Nenhum', '1' => 'Ato Cooperado', '2' => 'Estimativa', '3' => 'Microempresa Municipal', '4' => 'Notário ou Registrador', '5' => 'Profissional Autônomo', '6' => 'Sociedade de Profissionais', '9' => 'Outros']),
            'imunidade_desc' => $this->domain($p($iss . 'n:tpImunidade'), ['0' => 'Imunidade (tipo não informado)', '1' => 'Patrimônio, renda ou serviços recíprocos', '2' => 'Templos de qualquer culto', '3' => 'Partidos políticos e entidades', '4' => 'Livros, jornais e periódicos', '5' => 'Fonogramas e videofonogramas']),
            'mun_incid' => $this->local($v('n:cLocIncid'), $v('n:xLocIncid'), $p($iss . 'n:cPaisResult')),
            'suspensao_desc' => $this->domain($p($iss . 'n:exigSusp/n:tpSusp'), ['1' => 'Exigibilidade Suspensa por Decisão Judicial', '2' => 'Exigibilidade Suspensa por Processo Administrativo']),
            'proc_susp' => $p($iss . 'n:exigSusp/n:nProcesso'),
            'benef_mun' => $this->domain($v('n:valores/n:tpBM'), ['1' => 'Isenção', '2' => 'Redução da Base de Cálculo', '3' => 'Redução da BC em R$', '4' => 'Alíquota Diferenciada']),
            'calc_bm' => $this->money($v('n:valores/n:vCalcBM') ?: $p($iss . 'n:BM/n:vRedBCBM')),
            'ded_red' => $this->sum([$p('n:valores/n:vDedRed/n:vDR') ?: $v('n:valores/n:vCalcDR'), $v($ibs . 'n:valores/n:vCalcReeRepRes'), $p('n:valores/n:vAjusteBC/n:vAjusteBCISSQN') ?: $v('n:valores/n:vCalcAjusteBCISSQN')]),
            'bc_issqn' => $this->money($v('n:valores/n:vBC')), 'aliq' => $this->percent($v('n:valores/n:pAliqAplic')),
            'ret_issqn_desc' => $this->domain($p($iss . 'n:tpRetISSQN'), ['1' => 'Não Retido', '2' => 'Retido pelo Tomador', '3' => 'Retido pelo Intermediário']),
            'v_issqn' => $this->money($v('n:valores/n:vISSQN')), 'v_irrf' => $this->money($p($fed . 'n:vRetIRRF')),
            'v_prev' => $this->money($p($fed . 'n:vRetCP')), 'v_soc' => $this->money($p($fed . 'n:vRetCSLL')),
            'v_pis' => $this->money($p($fed . 'n:piscofins/n:vPis')), 'v_cofins' => $this->money($p($fed . 'n:piscofins/n:vCofins')),
            'ret_fed_desc' => $this->domain($p($fed . 'n:piscofins/n:tpRetPisCofins'), ['0' => 'PIS/COFINS/CSLL Não Retidos', '1' => 'PIS/COFINS Retidos', '2' => 'PIS/COFINS Não Retidos', '3' => 'PIS/COFINS/CSLL Retidos', '4' => 'PIS/COFINS Retidos, CSLL Não Retido', '5' => 'PIS Retido, COFINS/CSLL Não Retidos', '6' => 'COFINS Retido, PIS/CSLL Não Retidos', '7' => 'PIS Não Retido, COFINS/CSLL Retidos', '8' => 'PIS/COFINS Não Retidos, CSLL Retido', '9' => 'COFINS Não Retido, PIS/CSLL Retidos']),
            'cst_class' => trim(($p($cls . 'n:CST') ?: $p('n:IBSCBS/n:CST')) . ' / ' . ($p($cls . 'n:cClassTrib') ?: $p('n:IBSCBS/n:cClassTrib')), ' /'),
            'ibs_local' => trim($p('n:IBSCBS/n:cIndOp') . ' / ' . $v($ibs . 'n:cLocalidadeIncid') . ' / ' . $this->local($v($ibs . 'n:cLocalidadeIncid'), $v($ibs . 'n:xLocalidadeIncid')), ' /'),
            'ibs_exclusoes' => $this->sum($exclusoes), 'ibs_base_apos' => $this->money($v($ibs . 'n:valores/n:vBC')),
            'ibs_red_aliq' => implode(' / ', [$this->percent($v($ibs . 'n:valores/n:uf/n:pRedAliqUF')), $this->percent($v($ibs . 'n:valores/n:mun/n:pRedAliqMun')), $this->percent($v($ibs . 'n:valores/n:fed/n:pRedAliqCBS'))]),
            'ibs_aliq_uf_mun' => $this->percent($v($ibs . 'n:valores/n:uf/n:pIBSUF')) . ' / ' . $this->percent($v($ibs . 'n:valores/n:mun/n:pIBSMun')),
            'ibs_aliq_mun' => $this->percent($v($ibs . 'n:valores/n:mun/n:pAliqEfetMun')),
            'ibs_valor_mun' => $this->money($v($ibs . 'n:totCIBS/n:gIBS/n:gIBSMunTot/n:vIBSMun')),
            'ibs_aliq_est' => $this->percent($v($ibs . 'n:valores/n:uf/n:pAliqEfetUF')),
            'ibs_valor_est' => $this->money($v($ibs . 'n:totCIBS/n:gIBS/n:gIBSUFTot/n:vIBSUF')),
            'ibs_total' => $this->money($totalIbs), 'cbs_aliquota' => $this->percent($v($ibs . 'n:valores/n:fed/n:pCBS')),
            'cbs_aliquota_efetiva' => $this->percent($v($ibs . 'n:valores/n:fed/n:pAliqEfetCBS')), 'cbs_valor' => $this->money($totalCbs),
            'v_serv' => $this->money($p('n:valores/n:vServPrest/n:vServ')),
            'desc_incond' => $this->money($p('n:valores/n:vDescCondIncond/n:vDescIncond')), 'desc_cond' => $this->money($p('n:valores/n:vDescCondIncond/n:vDescCond')),
            'ret_total' => $this->money($v('n:valores/n:vTotalRet')), 'v_liq' => $this->money($v('n:valores/n:vLiq')),
            'v_ibscbs' => $this->sum([$totalIbs, $totalCbs]), 'v_liq_ibscbs' => $this->money($v($ibs . 'n:totCIBS/n:vTotNF')),
            'info_comp' => implode(' | ', $parts),
            'totais_aproximados' => 'Totais Aproximados dos Tributos cfe. Lei nº 12.741/2012: ' . implode('; ', $totTrib),
            'qrcode_payload' => 'https://www.nfse.gov.br/ConsultaPublica/?tpc=1&chave=' . $chave,
            'marca_agua' => $this->watermark(),
        ];
    }

    private function value(string $path, DOMNode $context): string
    {
        return trim((string) $this->xp->evaluate('string(' . $path . ')', $context));
    }

    private function person(string $path, DOMNode $parent): array
    {
        $node = $this->xp->query($path, $parent)->item(0);
        $keys = ['nome', 'doc', 'im', 'fone', 'email', 'endereco', 'municipio', 'ibge_cep', 'uf'];
        if (!$node) { return array_fill_keys($keys, ''); }
        $v = fn(string $p): string => $this->value($p, $node);
        $cMun = $v('n:end/n:endNac/n:cMun') ?: $v('n:enderNac/n:cMun');
        $info = $this->municipio($cMun);
        $doc = $v('n:CNPJ') ?: $v('n:CPF') ?: $v('n:NIF');
        if (preg_match('/^\d{14}$/', $doc)) { $doc = preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $doc); }
        elseif (preg_match('/^\d{11}$/', $doc)) { $doc = preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $doc); }
        $addr = [];
        foreach (['xLgr', 'nro', 'xCpl', 'xBairro'] as $field) { $addr[] = $v('n:end/n:' . $field) ?: $v('n:enderNac/n:' . $field); }
        $cep = $v('n:end/n:endNac/n:CEP') ?: $v('n:end/n:CEP') ?: $v('n:enderNac/n:CEP') ?: $v('n:end/n:endExt/n:cEndPost');
        if (preg_match('/^\d{8}$/', $cep)) { $cep = substr($cep, 0, 5) . '-' . substr($cep, 5); }
        return [
            'nome' => $v('n:xNome'), 'doc' => $doc, 'im' => $v('n:IM'), 'fone' => $v('n:fone'), 'email' => $v('n:email'),
            'endereco' => implode(', ', array_filter($addr, fn($s) => $s !== '')),
            'municipio' => $info['nome'] !== '' ? $info['nome'] . ' / ' . $info['uf'] : $v('n:end/n:endExt/n:xCidade'),
            'ibge_cep' => trim($cMun . ' / ' . $cep, ' /'), 'uf' => $info['uf'] ?: $v('n:enderNac/n:UF'),
        ];
    }

    private function municipio(string $codigo): array
    {
        if (self::$municipios === null) {
            $catalogo = require dirname(__DIR__, 2) . '/storage/municipios-catalog.php';
            self::$municipios = $catalogo['municipios'] ?? [];
        }
        $item = self::$municipios[(int) $codigo] ?? [];
        return ['nome' => (string) ($item['nome'] ?? ''), 'uf' => (string) ($item['uf'] ?? '')];
    }

    private function local(string $codigo, string $nome = '', string $pais = ''): string
    {
        $info = $this->municipio($codigo);
        return implode(' / ', array_filter([$nome ?: $info['nome'], $info['uf'], $pais ?: ($codigo !== '' && $info['nome'] !== '' ? 'BR' : '')], fn($s) => $s !== ''));
    }

    private function domain(string $codigo, array $map): string { return $codigo === '' ? '-' : ($map[$codigo] ?? $codigo); }
    private function date(string $v, bool $hora = false): string
    {
        if ($v === '') { return ''; }
        try { return (new \DateTimeImmutable($v))->format($hora ? 'd/m/Y H:i:s' : 'd/m/Y'); }
        catch (\Exception $e) { throw new RuntimeException('Data inválida no XML autorizado: ' . $v); }
    }
    private function money(string $v): string { return $v === '' ? '-' : 'R$ ' . number_format((float) $v, 2, ',', '.'); }
    private function percent(string $v): string { return $v === '' ? '-' : number_format((float) $v, 2, ',', '.') . '%'; }
    private function sum(array $values): string
    {
        $values = array_values(array_filter($values, fn($s) => $s !== ''));
        return $values === [] ? '-' : $this->money((string) array_sum(array_map('floatval', $values)));
    }
    private function watermark(): string
    {
        // Eventos externos exigem contexto fiscal correlacionado à chave pela aplicação.
        if ($this->value('n:subst/n:chSubstda', $this->dps) !== '') { return ''; } // A substituta continua válida.
        return '';
    }
}
