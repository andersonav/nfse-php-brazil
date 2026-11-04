<?php
declare(strict_types=1);
namespace Alves\NfseBrasil\Danfse;
use Alves\NfseBrasil\Utils\NfseCodigoPadrao;
use DateTimeImmutable;
use DOMDocument;
use DOMXPath;
use RuntimeException;
/** Compatibilidade com XML municipal. Não reinterpreta códigos municipais como nacionais. */
final class MunicipalDanfseParser
{
    private static $municipiosCache = null;
    public function parse(string $xml): array
    {
        $dom = new DOMDocument();
        if (!@$dom->loadXML($xml, LIBXML_NONET) || $dom->doctype !== null) {
            throw new RuntimeException('Não foi possivel interpretar o XML da NFS-e.');
        }
        $xp = new DOMXPath($dom);
        $nodes = $xp->query('//*[local-name()="Nfse"]/*[local-name()="InfNfse"]');
        if ($nodes->length !== 1) { throw new RuntimeException('Informe o XML autorizado de uma única NFS-e municipal.'); }
        $scoped = new DOMDocument();
        $scoped->appendChild($scoped->importNode($nodes->item(0), true));
        $xp = new DOMXPath($scoped);
        if (trim($xp->evaluate('string(/*/*[local-name()="Numero"])')) === '') { throw new RuntimeException('Número autorizado da NFS-e não encontrado.'); }
        $v = static function (array $queries) use ($xp): string {
            foreach ($queries as $query) {
                $raw = @$xp->evaluate("string({$query})");
                $value = trim((string) $raw);
                if ($value !== '') {
                    return $value;
                }
            }
            return '';
        };

        $munIbge = $v(["//*[local-name()='emit']//*[local-name()='cMun']", "//*[local-name()='CodigoMunicipio']"]);
        $munInfo = $this->resolveMunicipioByIbge($munIbge);

        $d = [
            'chave' => $v(["//*[local-name()='InfNfse']/@Id", "//*[local-name()='Nfse']/@Id"]),
            'numero_nfse' => $v(["//*[local-name()='nNFSe']", "//*[local-name()='Numero']"]),
            'competencia' => $this->formatDate($v(["//*[local-name()='DataCompetencia']", "//*[local-name()='Competencia']"])),
            'data_emissao_nfse' => $this->formatDateTime($v(["//*[local-name()='dhProc']", "//*[local-name()='dhEmi']", "//*[local-name()='DataEmissao']"])),
            'numero_rps' => $v(["//*[local-name()='IdentificacaoRps']/*[local-name()='Numero']"]),
            'serie_rps' => $v(["//*[local-name()='IdentificacaoRps']/*[local-name()='Serie']"]),
            'data_emissao_dps' => $this->formatDateTime($v(["//*[local-name()='DhEmi']", "//*[local-name()='DataEmissao']"])),
            'qrcode_payload' => '',
            'ambiente_gerador_desc' => $this->mapByCode($v(["//*[local-name()='ambGer']"]), NfseCodigoPadrao::ambiente()),
            'ambiente_desc' => $this->mapByCode($v(["//*[local-name()='TpAmb']"]), NfseCodigoPadrao::ambiente()),
            'municipio_label' => $munInfo['nome'] !== '' ? ($munInfo['nome'] . ' - ' . $munInfo['uf']) : $munIbge,

            'situacao_nfse_desc' => $this->mapByCode($v(["//*[local-name()='cStat']", "//*[local-name()='Situacao']"]), NfseCodigoPadrao::situacaoNfse()),
            'finalidade_desc' => $this->mapByCode($v(["//*[local-name()='finNfse']", "//*[local-name()='Finalidade']"]), NfseCodigoPadrao::finalidadeNfse()),

            'emit_nome' => $v(["//*[local-name()='emit']/*[local-name()='xNome']", "//*[local-name()='PrestadorServico']//*[local-name()='RazaoSocial']"]),
            'emit_doc' => $v([
                "//*[local-name()='emit']/*[local-name()='CNPJ']",
                "//*[local-name()='PrestadorServico']/*[local-name()='IdentificacaoPrestador']/*[local-name()='Cnpj']",
                "//*[local-name()='PrestadorServico']//*[local-name()='Cnpj']"
            ]),
            'emit_im' => $v([
                "//*[local-name()='emit']/*[local-name()='IM']",
                "//*[local-name()='PrestadorServico']/*[local-name()='IdentificacaoPrestador']/*[local-name()='InscricaoMunicipal']",
                "//*[local-name()='PrestadorServico']//*[local-name()='InscricaoMunicipal']"
            ]),
            'emit_tel' => $v([
                "//*[local-name()='emit']//*[local-name()='Telefone']",
                "//*[local-name()='PrestadorServico']/*[local-name()='Contato']/*[local-name()='Telefone']"
            ]),
            'emit_email' => $v([
                "//*[local-name()='emit']//*[local-name()='email']",
                "//*[local-name()='PrestadorServico']/*[local-name()='Contato']/*[local-name()='Email']",
                "//*[local-name()='PrestadorServico']//*[local-name()='Email']"
            ]),
            'emit_mun_uf' => trim(
                $v([
                    "//*[local-name()='emit']//*[local-name()='cMun']",
                    "//*[local-name()='PrestadorServico']/*[local-name()='Endereco']/*[local-name()='CodigoMunicipio']"
                ]) . ' / ' .
                $v([
                    "//*[local-name()='emit']//*[local-name()='UF']",
                    "//*[local-name()='PrestadorServico']/*[local-name()='Endereco']/*[local-name()='Uf']"
                ])
            ),
            'emit_ibge_cep' => trim(
                $v([
                    "//*[local-name()='emit']//*[local-name()='cMun']",
                    "//*[local-name()='PrestadorServico']/*[local-name()='Endereco']/*[local-name()='CodigoMunicipio']"
                ]) . ' / ' .
                $v([
                    "//*[local-name()='emit']//*[local-name()='CEP']",
                    "//*[local-name()='PrestadorServico']/*[local-name()='Endereco']/*[local-name()='Cep']"
                ])
            ),
            'emit_end' => $this->joinAddressParts([
                $v([
                    "//*[local-name()='emit']//*[local-name()='xLgr']",
                    "//*[local-name()='PrestadorServico']/*[local-name()='Endereco']/*[local-name()='Endereco']"
                ]),
                $v([
                    "//*[local-name()='emit']//*[local-name()='nro']",
                    "//*[local-name()='PrestadorServico']/*[local-name()='Endereco']/*[local-name()='Numero']"
                ]),
                $v([
                    "//*[local-name()='emit']//*[local-name()='xBairro']",
                    "//*[local-name()='PrestadorServico']/*[local-name()='Endereco']/*[local-name()='Bairro']"
                ]),
            ]),
            'sn_comp_desc' => $this->mapByCode($v(["//*[local-name()='OptanteSimplesNacional']", "//*[local-name()='OpSimpNac']"]), NfseCodigoPadrao::simplesNacionalCompetencia()),
            'reg_ap_sn_desc' => $this->mapByCode($v(["//*[local-name()='RegApTribSN']"]), NfseCodigoPadrao::regimeApuracaoSn()),

            'toma_nome' => $v(["//*[local-name()='Tomador']//*[local-name()='RazaoSocial']", "//*[local-name()='TomadorServico']//*[local-name()='RazaoSocial']"]),
            'toma_doc' => $v([
                "//*[local-name()='Tomador']//*[local-name()='Cpf']",
                "//*[local-name()='Tomador']//*[local-name()='Cnpj']",
                "//*[local-name()='TomadorServico']/*[local-name()='IdentificacaoTomador']/*[local-name()='CpfCnpj']/*[local-name()='Cpf']",
                "//*[local-name()='TomadorServico']/*[local-name()='IdentificacaoTomador']/*[local-name()='CpfCnpj']/*[local-name()='Cnpj']"
            ]),
            'toma_im' => $v([
                "//*[local-name()='Tomador']//*[local-name()='InscricaoMunicipal']",
                "//*[local-name()='TomadorServico']/*[local-name()='IdentificacaoTomador']/*[local-name()='InscricaoMunicipal']"
            ]),
            'toma_tel' => $v([
                "//*[local-name()='Tomador']//*[local-name()='Telefone']",
                "//*[local-name()='TomadorServico']/*[local-name()='Contato']/*[local-name()='Telefone']"
            ]),
            'toma_email' => $v([
                "//*[local-name()='Tomador']//*[local-name()='Email']",
                "//*[local-name()='TomadorServico']/*[local-name()='Contato']/*[local-name()='Email']"
            ]),
            'toma_mun_uf' => trim(
                $v([
                    "//*[local-name()='Tomador']//*[local-name()='CodigoMunicipio']",
                    "//*[local-name()='TomadorServico']/*[local-name()='Endereco']/*[local-name()='CodigoMunicipio']"
                ]) . ' / ' .
                $v([
                    "//*[local-name()='Tomador']//*[local-name()='Uf']",
                    "//*[local-name()='TomadorServico']/*[local-name()='Endereco']/*[local-name()='Uf']"
                ])
            ),
            'toma_ibge_cep' => trim(
                $v([
                    "//*[local-name()='Tomador']//*[local-name()='CodigoMunicipio']",
                    "//*[local-name()='TomadorServico']/*[local-name()='Endereco']/*[local-name()='CodigoMunicipio']"
                ]) . ' / ' .
                $v([
                    "//*[local-name()='Tomador']//*[local-name()='Cep']",
                    "//*[local-name()='TomadorServico']/*[local-name()='Endereco']/*[local-name()='Cep']"
                ])
            ),
            'toma_end' => $this->joinAddressParts([
                $v([
                    "//*[local-name()='Tomador']//*[local-name()='Endereco']/*[local-name()='Endereco']",
                    "//*[local-name()='TomadorServico']/*[local-name()='Endereco']/*[local-name()='Endereco']"
                ]),
                $v([
                    "//*[local-name()='Tomador']//*[local-name()='Endereco']/*[local-name()='Numero']",
                    "//*[local-name()='TomadorServico']/*[local-name()='Endereco']/*[local-name()='Numero']"
                ]),
                $v([
                    "//*[local-name()='Tomador']//*[local-name()='Endereco']/*[local-name()='Bairro']",
                    "//*[local-name()='TomadorServico']/*[local-name()='Endereco']/*[local-name()='Bairro']"
                ]),
            ]),

            'trib_nac' => $v(["//*[local-name()='cTribNac']", "//*[local-name()='CTribNac']", "//*[local-name()='cTribNacional']", "//*[local-name()='CodigoTributacaoNacional']", "//*[local-name()='ItemListaServico']"]),
            'trib_mun' => $v(["//*[local-name()='xTribMun']", "//*[local-name()='cTribMun']", "//*[local-name()='CodigoTributacaoMunicipio']"]),
            'nbs' => $v(["//*[local-name()='cNBS']"]),
            'local_prest' => $v(["//*[local-name()='xLocPrestacao']", "//*[local-name()='CodigoMunicipio']"]),
            'serv_desc' => $v(["//*[local-name()='xDescServ']", "//*[local-name()='Discriminacao']"]),
            'item_lista' => $v(["//*[local-name()='ItemListaServico']"]),
            'cnae' => $v(["//*[local-name()='CodigoCnae']"]),
            'cod_trib_mun' => $v(["//*[local-name()='CodigoTributacaoMunicipio']"]),

            'reg_esp_issqn_desc' => $this->mapByCode($v(["//*[local-name()='RegimeEspecialTributacao']", "//*[local-name()='RegEspTrib']"]), NfseCodigoPadrao::regimeEspecialIssqn()),
            'trib_issqn_desc' => $this->mapByCode($v(["//*[local-name()='NaturezaOperacao']", "//*[local-name()='TribIssqn']"]), NfseCodigoPadrao::tipoTributacaoIssqn()),
            'mun_incid' => $v(["//*[local-name()='MunicipioIncidencia']", "//*[local-name()='cLocIncid']"]),
            'proc_susp' => $v(["//*[local-name()='NProcesso']"]),
            'benef_mun' => $v(["//*[local-name()='BeneficioMunicipal']//*[local-name()='TpBM']"]),
            'calc_bm' => $this->money($v(["//*[local-name()='BeneficioMunicipal']//*[local-name()='VRedBCBM']"])),
            'ded_red' => $this->money($v(["//*[local-name()='ValorDeducoes']"])),
            'desc_incond' => $this->money($v(["//*[local-name()='DescontoIncondicionado']"])),
            'bc_issqn' => $this->money($v(["//*[local-name()='BaseCalculo']", "//*[local-name()='vBC']"])),
            'aliq' => $this->percent($v(["//*[local-name()='Aliquota']", "//*[local-name()='pAliqAplic']"])),
            'ret_issqn_desc' => $this->mapByCode($v(["//*[local-name()='IssRetido']", "//*[local-name()='TpRetIssqn']"]), NfseCodigoPadrao::retencaoIssqn()),
            'v_issqn' => $this->money($v(["//*[local-name()='ValorIss']", "//*[local-name()='vISSQN']"])),
            'v_irrf' => $this->money($v(["//*[local-name()='ValorIr']"])),
            'v_prev' => $this->money($v(["//*[local-name()='ValorInss']"])),
            'v_soc' => $this->money($v(["//*[local-name()='ValorCsll']"])),
            'v_pis' => $this->money($v(["//*[local-name()='ValorPis']"])),
            'v_cofins' => $this->money($v(["//*[local-name()='ValorCofins']"])),

            'ibs_exclusoes' => $this->money($v(["//*[local-name()='VRedBC']"])),
            'ibs_base_apos' => $this->money($v(["//*[local-name()='VBCPosReducao']"])),
            'ibs_red_aliq' => $this->fitPair($this->percent($v(["//*[local-name()='pRedAliqIBS']"])), $this->percent($v(["//*[local-name()='pRedAliqCBS']"]))),
            'ibs_aliq_uf_mun' => $this->fitPair($this->percent($v(["//*[local-name()='pAliqIBSUF']"])), $this->percent($v(["//*[local-name()='pAliqIBSMun']"]))),
            'ibs_aliq_mun' => $this->percent($v(["//*[local-name()='pAliqEfetMun']"])),
            'ibs_valor_mun' => $this->money($v(["//*[local-name()='vIBSMun']"])),
            'ibs_aliq_est' => $this->percent($v(["//*[local-name()='pAliqEfetUF']"])),
            'ibs_valor_est' => $this->money($v(["//*[local-name()='vIBSUF']"])),

            'v_serv' => $this->money($v(["//*[local-name()='ValorServicos']", "//*[local-name()='vServ']"])),
            'desc_cond' => $this->money($v(["//*[local-name()='DescontoCondicionado']"])),
            'ret_total' => $this->money($v(["//*[local-name()='ValorIssRetido']"])),
            'v_liq' => $this->money($v(["//*[local-name()='ValorLiquidoNfse']", "//*[local-name()='vLiq']"])),
            'v_ibscbs' => $this->money($v(["//*[local-name()='IBSValorTotal']"])),
            'v_liq_ibscbs' => $this->money($v(["//*[local-name()='ValorTotalComTributos']"])),
            'info_comp' => $v(["//*[local-name()='InformacoesComplementares']", "//*[local-name()='xInfComp']"]),
            'tot_aprox_fed_v' => $this->money($v(["//*[local-name()='vTotTribFed']", "//*[local-name()='vTribFed']"])),
            'tot_aprox_est_v' => $this->money($v(["//*[local-name()='vTotTribEst']", "//*[local-name()='vTribEst']"])),
            'tot_aprox_mun_v' => $this->money($v(["//*[local-name()='vTotTribMun']", "//*[local-name()='vTribMun']"])),
            'tot_aprox_fed_p' => $this->percent($v(["//*[local-name()='pTotTribFed']", "//*[local-name()='pTribFed']"])),
            'tot_aprox_est_p' => $this->percent($v(["//*[local-name()='pTotTribEst']", "//*[local-name()='pTribEst']"])),
            'tot_aprox_mun_p' => $this->percent($v(["//*[local-name()='pTotTribMun']", "//*[local-name()='pTribMun']"])),
        ];

        $get = fn(string $path): string => trim((string) $xp->evaluate('string(/*/' . $path . ')'));
        $el = fn(string $name): string => '*[local-name()="' . $name . '"]';
        $rtc = fn(string $name): string => $get($el('IBSCBS') . '/' . $el($name));
        $ctrl = function(string $name) use ($get, $el): string {
            $value = $get($el('ControleIBSCBS') . '/' . $el($name));
            return $value !== '' ? $value : $get($el('ControleIBSCBS') . '/' . $el(lcfirst($name)));
        };
        $d['chave'] = $get('@Id') ?: $get('@id') ?: $get($el('CodigoVerificacao'));
        $chaveNacional = preg_match('/^NFS(\d{50})$/', $d['chave'], $match) === 1;
        if ($chaveNacional) {
            $d['chave'] = $match[1];
            // A identificação do RPS municipal não substitui a identificação da DPS.
            $d['numero_rps'] = $get($el('DadosDPS') . '/' . $el('nDPS')) ?: '-';
            $d['serie_rps'] = $get($el('DadosDPS') . '/' . $el('serie')) ?: '-';
        }
        $d['nacional'] = false;
        $d['tp_amb'] = $get($el('DadosDPS') . '/' . $el('TpAmb'));
        $d['reg_ap_sn_desc'] = [
            '1' => 'Tributos federais e municipal pelo SN',
            '2' => 'Tributos federais pelo SN e ISS pela NFS-e',
            '3' => 'Tributos federais e ISS pela NFS-e',
        ][$get($el('DadosDPS') . '/' . $el('RegApTribSN'))] ?? '-';
        $d['ambiente_gerador_desc'] = 'Municipal';
        $d['emitente_desc'] = ['1' => 'Prestador', '2' => 'Tomador', '3' => 'Intermediário'][$get($el('DadosDPS') . '/' . $el('TpEmit'))] ?? 'Prestador';
        $d['situacao_nfse_desc'] = 'NFS-e Gerada';
        $d['finalidade_desc'] = ['0' => 'NFS-e Regular', '1' => 'NFS-e de Crédito', '2' => 'NFS-e de Débito'][$ctrl('FinNFSe')] ?? '-';
        $d['sn_comp_desc'] = ['1' => 'Optante', '2' => 'Não Optante'][$get($el('OptanteSimplesNacional'))] ?? '-';
        $sn = $get($el('DadosDPS') . '/' . $el('OpSimpNac'));
        if ($sn !== '') { $d['sn_comp_desc'] = ['1' => 'Não Optante', '2' => 'Optante - ME/EPP', '3' => 'Optante - MEI'][$sn] ?? $sn; }
        $d['prestador'] = $this->person($xp, 'PrestadorServico');
        $d['tomador'] = $this->person($xp, 'TomadorServico');
        $d['destinatario'] = $this->person($xp, 'Destinatario');
        $d['intermediario'] = $this->person($xp, 'IntermediarioServico');
        $d['ind_dest'] = $ctrl('IndDest');
        $d['ano'] = (int) substr($get($el('Competencia')), 0, 4);
        $d['trib_nac'] = $get($el('DadosDPS') . '/' . $el('CTribNac')) ?: '-';
        $d['trib_mun'] = $get($el('Servico') . '/' . $el('CodigoTributacaoMunicipio')) ?: '-';
        $d['trib_desc'] = NfseCodigoPadrao::descricaoTributacaoNacional($d['trib_nac']);
        $codigoLocal = $get($el('OrgaoGerador') . '/' . $el('CodigoMunicipio'));
        $mun = $this->resolveMunicipioByIbge($codigoLocal);
        $d['municipio_label'] = trim($mun['nome'] . ' / ' . $mun['uf'], ' /');
        $tipoTrib = $get($el('DadosDPS') . '/' . $el('TribIssqn'));
        $d['incide_iss'] = $tipoTrib !== '4';
        $d['trib_issqn_desc'] = ['1' => 'Operação Tributável', '2' => 'Imunidade', '3' => 'Exportação de Serviço', '4' => 'Não Incidência'][$tipoTrib] ?? ['1' => 'Tributação no Município', '2' => 'Tributação Fora do Município', '3' => 'Isenção', '4' => 'Imunidade', '5' => 'Exigibilidade Suspensa por Decisão Judicial', '6' => 'Exigibilidade Suspensa por Processo Administrativo'][$get($el('NaturezaOperacao'))] ?? '-';
        $d['imunidade_desc'] = '-'; $d['suspensao_desc'] = '-';
        $d['ret_fed_desc'] = ['0' => 'Não Retidos', '1' => 'PIS/COFINS Retidos', '2' => 'PIS/COFINS Não Retidos', '3' => 'PIS/COFINS/CSLL Retidos', '4' => 'PIS/COFINS Retidos, CSLL Não Retido', '5' => 'PIS Retido', '6' => 'COFINS Retido', '7' => 'COFINS/CSLL Retidos', '8' => 'CSLL Retido', '9' => 'PIS/CSLL Retidos'][$get($el('Servico') . '/' . $el('Valores') . '/' . $el('TipoRetencaoPisCofins'))] ?? '-';
        $d['ret_issqn_desc'] = ['1' => 'Retido', '2' => 'Não Retido'][$get($el('Servico') . '/' . $el('Valores') . '/' . $el('IssRetido'))] ?? '-';
        $d['local_prest_desc'] = $this->resolveLocalPrestacao($get($el('DadosDPS') . '/' . $el('CLocPrestacao')) ?: $d['local_prest']);
        $d['mun_incid'] = $this->resolveLocalPrestacao($d['mun_incid']);
        $d['cst_class'] = trim($ctrl('CST') . ' / ' . $ctrl('cClassTrib'), ' /') ?: '-';
        $d['ibs_local'] = trim($ctrl('CIndOp') . ' / ' . $rtc('LocalidadeIncidenciaCod') . ' / ' . $rtc('LocalidadeIncidenciaNome') . ' / ' . ($this->resolveMunicipioByIbge($rtc('LocalidadeIncidenciaCod'))['uf'] ?? ''), ' /') ?: '-';
        foreach (['ibs_base_apos' => 'IBSCBSBaseCalculo', 'ibs_valor_mun' => 'IBSMunValor', 'ibs_valor_est' => 'IBSUFValor', 'ibs_total' => 'IBSValorTotal', 'cbs_valor' => 'CBSValor', 'v_ibscbs' => 'IBSValorTotal', 'v_liq_ibscbs' => 'ValorTotalComTributos'] as $key => $tag) { $d[$key] = $this->money($rtc($tag)); }
        foreach (['ibs_aliq_mun' => 'IBSMunAliquotaEfetiva', 'ibs_aliq_est' => 'IBSUFAliquotaEfetiva', 'cbs_aliquota' => 'CBSAliquota', 'cbs_aliquota_efetiva' => 'CBSAliquotaEfetiva'] as $key => $tag) { $d[$key] = $this->percent($rtc($tag)); }
        $d['ibs_aliq_uf_mun'] = $this->percent($rtc('IBSUFAliquota')) . ' / ' . $this->percent($rtc('IBSMunAliquota'));
        $d['ibs_red_aliq'] = implode(' / ', array_map(fn($tag) => $this->percent($rtc($tag)), ['IBSUFPercReducao','IBSMunPercReducao','CBSPercReducao']));
        $d['v_ibscbs'] = $rtc('IBSValorTotal') !== '' || $rtc('CBSValor') !== '' ? $this->money((string) ((float) $rtc('IBSValorTotal') + (float) $rtc('CBSValor'))) : '-';
        $exclusoes = (float) $get($el('Servico') . '/' . $el('Valores') . '/' . $el('DescontoIncondicionado')) + (float) $rtc('IBSValorReembolso');
        if ($d['ano'] <= 2032) { $exclusoes += (float) $get($el('Servico') . '/' . $el('Valores') . '/' . $el('ValorIss')); }
        if ($d['ano'] <= 2026) { foreach (['ValorPis','ValorCofins'] as $tag) { $exclusoes += (float) $get($el('Servico') . '/' . $el('Valores') . '/' . $el($tag)); } }
        $d['ibs_exclusoes'] = $rtc('IBSCBSBaseCalculo') !== '' ? $this->money((string) $exclusoes) : '-';
        $retencoes = 0;
        $val = fn($tag): float => (float) $get($el('Servico') . '/' . $el('Valores') . '/' . $el($tag));
        foreach (['ValorInss','ValorIr','OutrasRetencoes'] as $tag) { $retencoes += $val($tag); }
        if ($get($el('Servico') . '/' . $el('Valores') . '/' . $el('IssRetido')) === '1') { $retencoes += $val('ValorIssRetido'); }
        $tipoRet = (int) $get($el('Servico') . '/' . $el('Valores') . '/' . $el('TipoRetencaoPisCofins'));
        if (in_array($tipoRet, [1,3,4,5,9], true)) { $retencoes += $val('ValorPis'); }
        if (in_array($tipoRet, [1,3,4,6,7], true)) { $retencoes += $val('ValorCofins'); }
        if (in_array($tipoRet, [3,7,8,9], true)) { $retencoes += $val('ValorCsll'); }
        $d['ret_total'] = $this->money((string) $retencoes);
        $d['info_comp'] = $get($el('OutrasInformacoes')) ?: $d['info_comp'];
        $d['totais_aproximados'] = 'Totais Aproximados dos Tributos cfe. Lei nº 12.741/2012: Federais: -; Estaduais: -; Municipais: -';
        $d['marca_agua'] = '';
        // Só gera o QR nacional quando o próprio XML autorizado contém a chave nacional e o ambiente.
        $d['qrcode_payload'] = '';
        if ($chaveNacional && in_array($d['tp_amb'], ['1', '2'], true)) {
            $host = $d['tp_amb'] === '1' ? 'www.nfse.gov.br' : 'www.producaorestrita.nfse.gov.br';
            $d['qrcode_payload'] = 'https://' . $host . '/ConsultaPublica/?tpc=1&chave=' . $d['chave'];
        }

        return $d;
    }

    private function person(DOMXPath $xp, string $tag): array
    {
        $node = $xp->query('/*/*[local-name()="' . $tag . '"]')->item(0);
        $keys = ['nome','doc','im','fone','email','endereco','municipio','ibge_cep','uf'];
        if (!$node) { return array_fill_keys($keys, ''); }
        $v = function(array $names) use ($xp, $node): string {
            foreach ($names as $name) {
                $value = trim((string) $xp->evaluate('string(.//*[local-name()="' . $name . '"])', $node));
                if ($value !== '') { return $value; }
            }
            return '';
        };
        $codigo = $v(['CodigoMunicipio','CodMunicipio']); $mun = $this->resolveMunicipioByIbge($codigo);
        $doc = $v(['Cnpj','Cpf','CnpjCpf','NIF']);
        if (preg_match('/^\d{14}$/', $doc)) { $doc = preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $doc); }
        elseif (preg_match('/^\d{11}$/', $doc)) { $doc = preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $doc); }
        $end = trim((string) $xp->evaluate('string(.//*[local-name()="Endereco"]/*[local-name()="Endereco"])', $node));
        if ($end === '') { $end = $v(['Logradouro']); }
        if ($end === '' && $tag === 'IntermediarioServico') { $end = trim((string) $xp->evaluate('string(*[local-name()="Endereco"])', $node)); }
        return ['nome' => $v(['RazaoSocial','Nome']), 'doc' => $doc, 'im' => $v(['InscricaoMunicipal']), 'fone' => $v(['Telefone']), 'email' => $v(['Email']),
            'endereco' => implode(', ', array_filter([$end, $v(['Numero']), $v(['Complemento']), $v(['Bairro'])])),
            'municipio' => trim(($mun['nome'] ?: $v(['Cidade'])) . ' / ' . ($mun['uf'] ?: $v(['Uf','UF'])), ' /'),
            'ibge_cep' => trim($codigo . ' / ' . $v(['Cep','CEP','CodPostalExt']), ' /'), 'uf' => $mun['uf'] ?: $v(['Uf','UF'])];
    }

    private function resolveLocalPrestacao(string $local): string
    {
        $local = trim($local);
        if ($local === '') {
            return '-';
        }
        if (preg_match('/^\d{7}$/', $local) === 1) {
            $info = $this->resolveMunicipioByIbge($local);
            if ($info['nome'] !== '') {
                return $local . ' - ' . $info['nome'] . ' / ' . $info['uf'] . ' / Brasil';
            }
        }
        return $local;
    }

    private function resolveMunicipioByIbge(string $ibge): array
    {
        $ibgeInt = (int) preg_replace('/\D+/', '', $ibge);
        if ($ibgeInt <= 0) {
            return ['nome' => '', 'uf' => ''];
        }
        if (self::$municipiosCache === null) {
            $catalogPath = dirname(__DIR__, 2) . '/storage/municipios-catalog.php';
            $cache = [];
            if (is_file($catalogPath)) {
                $catalog = require $catalogPath;
                if (is_array($catalog) && isset($catalog['municipios']) && is_array($catalog['municipios'])) {
                    foreach ($catalog['municipios'] as $key => $item) {
                        if (is_array($item) && isset($item['nome'], $item['uf'])) {
                            $cache[(int) $key] = ['nome' => (string) $item['nome'], 'uf' => (string) $item['uf']];
                        }
                    }
                }
            }
            self::$municipiosCache = $cache;
        }
        return self::$municipiosCache[$ibgeInt] ?? ['nome' => '', 'uf' => ''];
    }

    private function joinAddressParts(array $parts): string
    {
        $clean = [];
        foreach ($parts as $part) {
            $value = trim((string) $part);
            if ($value === '') {
                continue;
            }

            if (preg_match('/^null\b/i', $value) === 1) {
                $value = trim(preg_replace('/^null\b\s*/i', '', $value) ?? '');
            }

            if ($value === '' || strcasecmp($value, 'null') === 0) {
                continue;
            }

            $clean[] = $value;
        }

        return trim(implode(' ', $clean));
    }

    private function formatDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        try {
            return (new DateTimeImmutable($value))->format('d/m/Y');
        } catch (\Throwable $e) {
            $ts = strtotime($value);
            return $ts === false ? $value : date('d/m/Y', $ts);
        }
    }

    private function formatDateTime(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        try {
            return (new DateTimeImmutable($value))->format('d/m/Y H:i:s');
        } catch (\Throwable $e) {
            $ts = strtotime($value);
            return $ts === false ? $value : date('d/m/Y H:i:s', $ts);
        }
    }

    private function mapByCode(string $code, array $map): string
    {
        $code = trim($code);
        if ($code === '') {
            return '-';
        }
        return isset($map[$code]) ? ($code . ' - ' . $map[$code]) : $code;
    }

    private function money(string $value): string
    {
        $num = $this->toFloat($value);
        if ($num === null) {
            return $this->safe($value);
        }
        return 'R$ ' . number_format($num, 2, ',', '.');
    }

    private function percent(string $value): string
    {
        $num = $this->toFloat($value);
        if ($num === null) {
            return $this->safe($value);
        }
        return number_format($num, 2, ',', '.') . '%';
    }

    private function toFloat(string $value)
    {
        $value = trim(str_replace(['R$', '%', ' '], '', $value));
        if ($value === '') {
            return null;
        }
        if (strpos($value, ',') !== false && strpos($value, '.') !== false) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif (strpos($value, ',') !== false) {
            $value = str_replace(',', '.', $value);
        }
        return is_numeric($value) ? (float) $value : null;
    }

    private function safe(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '-';
        }

        return $value;
    }

    private function fitPair(string $a, string $b): string
    {
        $a = trim($a);
        $b = trim($b);
        if ($a === '' && $b === '') {
            return '-';
        }
        if ($b === '' || $b === '-') {
            return $a;
        }
        if ($a === '' || $a === '-') {
            return $b;
        }
        return $a . ' / ' . $b;
    }
}
