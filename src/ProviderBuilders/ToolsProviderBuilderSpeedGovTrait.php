<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderSpeedGovTrait
{
    /**
     * Mapeia payload unificado do formulário para envelope SOAP esperado pelo SpeedGov.
     *
     * @param array|string $payload
     */
    private function buildSpeedGovRecepcionarEnvelope(array|string $payload): string
    {
        $data = is_array($payload) ? $payload : json_decode((string) $payload, true);
        if (!is_array($data)) {
            throw new RuntimeException('Payload de emissao invalido para SpeedGov.');
        }

        $loteNumero = (string) ($data['lote']['numero_lote'] ?? '1');
        $rpsList = $data['rps'] ?? [];
        $first = is_array($rpsList) && isset($rpsList[0]) && is_array($rpsList[0]) ? $rpsList[0] : [];
        $ident = is_array($first['identificacao'] ?? null) ? $first['identificacao'] : [];
        $prestador = is_array($first['prestador'] ?? null) ? $first['prestador'] : [];
        $tomador = is_array($first['tomador'] ?? null) ? $first['tomador'] : [];
        $intermediario = is_array($first['intermediario'] ?? null) ? $first['intermediario'] : [];
        $servico = is_array($first['servico'] ?? null) ? $first['servico'] : [];
        $controleIbscbs = is_array($first['controle_ibscbs'] ?? null) ? $first['controle_ibscbs'] : [];
        $ibscbs = is_array($first['ibscbs'] ?? null) ? $first['ibscbs'] : [];
        $dadosDps = is_array($first['dados_dps'] ?? null) ? $first['dados_dps'] : [];
        $dadosObra = is_array($first['dados_obra'] ?? null) ? $first['dados_obra'] : [];
        $comercioExterior = is_array($first['comercio_exterior'] ?? null) ? $first['comercio_exterior'] : [];
        $exigibilidadeSuspensa = is_array($first['exigibilidade_suspensa'] ?? null) ? $first['exigibilidade_suspensa'] : [];
        $beneficioMunicipal = is_array($first['beneficio_municipal'] ?? null) ? $first['beneficio_municipal'] : [];
        $reembolsoRepasse = is_array($first['reembolso_repasse'] ?? null) ? $first['reembolso_repasse'] : [];
        $destinatario = is_array($first['destinatario'] ?? null) ? $first['destinatario'] : [];
        $dataCompetencia = trim((string) ($first['data_competencia'] ?? ''));

        $sanitizeText = static function ($value): string {
            $text = trim((string) $value);
            if ($text === '') {
                return '';
            }

            if (preg_match('/^null\b/i', $text) === 1) {
                $text = trim((string) preg_replace('/^null\b\s*/i', '', $text));
            }

            return strcasecmp($text, 'null') === 0 ? '' : $text;
        };

        $rpsNumero = (string) ($ident['numero'] ?? '');
        if ($rpsNumero === '') {
            throw new RuntimeException('RPS numero obrigatorio para emissao SpeedGov.');
        }

        $rpsSerie = (string) ($ident['serie'] ?? 'UNICA');
        $rpsTipo = (string) ($ident['tipo'] ?? '1');
        $dataEmissao = (string) ($first['data_emissao'] ?? date('Y-m-d\TH:i:s'));
        $prestadorCnpj = preg_replace('/\D+/', '', (string) ($prestador['cnpj'] ?? ''));
        $prestadorIm = trim((string) ($prestador['inscricao_municipal'] ?? ''));
        if ($prestadorCnpj === '' || $prestadorIm === '') {
            throw new RuntimeException('Prestador (CNPJ e IM) obrigatorio para emissao SpeedGov.');
        }
        if (preg_match('/[A-Za-z]/', $prestadorIm) === 1) {
            throw new RuntimeException('Prestador IM invalido para SpeedGov: informe a Inscricao Municipal, nao a Razao Social.');
        }

        $tomadorDoc = preg_replace('/\D+/', '', (string) ($tomador['documento'] ?? ''));
        $tomadorNome = $sanitizeText($tomador['nome_razao_social'] ?? '');
        $tomadorEmail = $sanitizeText($tomador['email'] ?? '');
        $tomadorIm = $sanitizeText($tomador['inscricao_municipal'] ?? '');
        $tomadorEndereco = $sanitizeText($tomador['endereco'] ?? '');
        $tomadorNumero = $sanitizeText($tomador['numero'] ?? '');
        $tomadorComplemento = $sanitizeText($tomador['complemento'] ?? '');
        $tomadorBairro = $sanitizeText($tomador['bairro'] ?? '');
        $tomadorCodigoMunicipio = preg_replace('/\D+/', '', (string) ($tomador['codigo_municipio'] ?? '')) ?: '';
        $tomadorUf = strtoupper($sanitizeText($tomador['uf'] ?? ''));
        $tomadorCep = preg_replace('/\D+/', '', (string) ($tomador['cep'] ?? '')) ?: '';
        $tomadorTelefone = preg_replace('/\D+/', '', (string) ($tomador['telefone'] ?? '')) ?: '';
        $servicoDesc = $sanitizeText($servico['discriminacao'] ?? '');
        $servicoValor = (float) ($servico['valor_servicos'] ?? 0);
        $servicoAliquota = (float) ($servico['aliquota'] ?? 0);
        $issRetido = (int) ($servico['iss_retido'] ?? 2);
        $codigoMunicipio = trim((string) ($servico['codigo_municipio'] ?? ''));
        $codigoTributacaoMunicipio = trim((string) ($servico['codigo_tributacao_municipio'] ?? ''));
        $itemListaServico = trim((string) ($servico['item_lista_servico'] ?? ''));
        $codigoCnae = trim((string) ($servico['codigo_cnae'] ?? ''));
        $naturezaOperacao = (int) ($first['natureza_operacao'] ?? 1);
        $regimeEspecialTributacaoRaw = $first['regime_especial_tributacao'] ?? null;
        $regimeEspecialTributacao = (int) ($regimeEspecialTributacaoRaw ?? 0);
        $hasRegimeEspecialTributacao = $regimeEspecialTributacaoRaw !== null && trim((string) $regimeEspecialTributacaoRaw) !== '';
        $optanteSimples = (int) ($first['optante_simples_nacional'] ?? 2);
        $incentivadorCultural = (int) ($first['incentivador_cultural'] ?? 2);
        $status = (int) ($first['status'] ?? 1);

        $valor = static function (array $source, string $key, float $default = 0): float {
            $raw = str_replace(',', '.', trim((string) ($source[$key] ?? '')));
            return $raw === '' || !is_numeric($raw) ? $default : (float) $raw;
        };

        // SpeedGov costuma validar schema/campos com codigos apenas numericos.
        $itemListaServico = preg_replace('/\D+/', '', $itemListaServico) ?: '';
        $codigoCnae = preg_replace('/\D+/', '', $codigoCnae) ?: '';
        $codigoTributacaoMunicipio = preg_replace('/\D+/', '', $codigoTributacaoMunicipio) ?: '';
        if ($itemListaServico !== '' && strlen($itemListaServico) > 5) {
            $itemListaServico = substr($itemListaServico, 0, 5);
        }
        if ($codigoCnae !== '' && strlen($codigoCnae) > 7) {
            $codigoCnae = substr($codigoCnae, 0, 7);
        }

        // Aceita aliquota informada em decimal (0.03) ou percentual (3.00).
        $aliquotaNormalizada = $servicoAliquota > 1 ? ($servicoAliquota / 100) : $servicoAliquota;
        $values = [
            'servico_valor' => number_format($servicoValor, 2, '.', ''),
            'deducoes' => number_format($valor($servico, 'valor_deducoes'), 2, '.', ''),
            'pis' => number_format($valor($servico, 'valor_pis'), 2, '.', ''),
            'cofins' => number_format($valor($servico, 'valor_cofins'), 2, '.', ''),
            'inss' => number_format($valor($servico, 'valor_inss'), 2, '.', ''),
            'ir' => number_format($valor($servico, 'valor_ir'), 2, '.', ''),
            'csll' => number_format($valor($servico, 'valor_csll'), 2, '.', ''),
            'iss_valor' => number_format($valor($servico, 'valor_iss', $servicoValor * max(0, $aliquotaNormalizada)), 2, '.', ''),
            'aliquota' => number_format($servicoAliquota, 4, '.', ''),
            'base_calculo' => number_format($valor($servico, 'base_calculo', $servicoValor), 2, '.', ''),
            'valor_iss_retido' => number_format($valor($servico, 'valor_iss_retido', $issRetido === 1 ? $servicoValor * max(0, $aliquotaNormalizada) : 0), 2, '.', ''),
            'outras_retencoes' => number_format($valor($servico, 'outras_retencoes'), 2, '.', ''),
            'valor_liquido_nfse' => number_format($valor($servico, 'valor_liquido_nfse', $servicoValor), 2, '.', ''),
            'desconto_condicionado' => number_format($valor($servico, 'desconto_condicionado'), 2, '.', ''),
            'desconto_incondicionado' => number_format($valor($servico, 'desconto_incondicionado'), 2, '.', ''),
        ];

        $cabecalhoXml = '<p:cabecalho versao="1" xmlns:p="http://ws.speedgov.com.br/cabecalho_v1.xsd">'
            . '<versaoDados>1</versaoDados>'
            . '</p:cabecalho>';

        $tomadorDocXml = '';
        if ($tomadorDoc !== '') {
            if (strlen($tomadorDoc) > 11) {
                $tomadorDocXml = '<Cnpj>' . $this->xmlValue($tomadorDoc) . '</Cnpj>';
            } else {
                $tomadorDocXml = '<Cpf>' . $this->xmlValue($tomadorDoc) . '</Cpf>';
            }
        }

        $tomadorEnderecoXml = '';
        if (
            $tomadorEndereco !== '' || $tomadorNumero !== '' || $tomadorBairro !== '' ||
            $tomadorCodigoMunicipio !== '' || $tomadorUf !== '' || $tomadorCep !== ''
        ) {
            $tomadorEnderecoXml = '<Endereco>'
                . '<Endereco>' . $this->xmlValue($tomadorEndereco) . '</Endereco>'
                . '<Numero>' . $this->xmlValue($tomadorNumero) . '</Numero>'
                . ($tomadorComplemento !== '' ? '<Complemento>' . $this->xmlValue($tomadorComplemento) . '</Complemento>' : '')
                . '<Bairro>' . $this->xmlValue($tomadorBairro) . '</Bairro>'
                . '<CodigoMunicipio>' . $this->xmlValue($tomadorCodigoMunicipio) . '</CodigoMunicipio>'
                . '<Uf>' . $this->xmlValue($tomadorUf) . '</Uf>'
                . '<Cep>' . $this->xmlValue($tomadorCep) . '</Cep>'
                . '</Endereco>';
        }

        $tomadorContatoXml = '';
        if ($tomadorTelefone !== '' || $tomadorEmail !== '') {
            $tomadorContatoXml = '<Contato>'
                . ($tomadorTelefone !== '' ? '<Telefone>' . $this->xmlValue($tomadorTelefone) . '</Telefone>' : '')
                . ($tomadorEmail !== '' ? '<Email>' . $this->xmlValue($tomadorEmail) . '</Email>' : '')
                . '</Contato>';
        }

        $hasAny = static function (array $values) use (&$hasAny): bool {
            foreach ($values as $value) {
                if (is_array($value)) {
                    if ($hasAny($value)) {
                        return true;
                    }
                    continue;
                }
                if (trim((string) $value) !== '') {
                    return true;
                }
            }
            return false;
        };

        $onlyDigits = static function (string $value, int $max = 0): string {
            $v = preg_replace('/\D+/', '', $value) ?? '';
            if ($max > 0 && strlen($v) > $max) {
                return substr($v, 0, $max);
            }
            return $v;
        };
        $onlyDecimal = static function (string $value, int $scale = 2): string {
            $v = str_replace(',', '.', trim($value));
            if ($v === '' || !preg_match('/^-?\d+(\.\d+)?$/', $v)) {
                return '';
            }
            return number_format((float) $v, $scale, '.', '');
        };
        $limitText = static function (string $value, int $max): string {
            $v = trim($value);
            if ($v === '') {
                return '';
            }
            return mb_substr($v, 0, $max);
        };

        $controleIbscbsXml = '';
        if ($hasAny($controleIbscbs)) {
            $controleIbscbsXml = '<ControleIBSCBS>'
                . (trim((string) ($controleIbscbs['fin_nfse'] ?? '')) !== '' ? '<finNFSe>' . $this->xmlValue((string) $controleIbscbs['fin_nfse']) . '</finNFSe>' : '')
                . (trim((string) ($controleIbscbs['ind_final'] ?? '')) !== '' ? '<indFinal>' . $this->xmlValue((string) $controleIbscbs['ind_final']) . '</indFinal>' : '')
                . (trim((string) ($controleIbscbs['tp_oper'] ?? '')) !== '' ? '<tpOper>' . $this->xmlValue((string) $controleIbscbs['tp_oper']) . '</tpOper>' : '')
                . (trim((string) ($controleIbscbs['tp_ente_gov'] ?? '')) !== '' ? '<tpEnteGov>' . $this->xmlValue((string) $controleIbscbs['tp_ente_gov']) . '</tpEnteGov>' : '')
                . (trim((string) ($controleIbscbs['ind_dest'] ?? '')) !== '' ? '<indDest>' . $this->xmlValue((string) $controleIbscbs['ind_dest']) . '</indDest>' : '')
                . (trim((string) ($controleIbscbs['c_ind_op'] ?? '')) !== '' ? '<cIndOp>' . $this->xmlValue((string) $controleIbscbs['c_ind_op']) . '</cIndOp>' : '')
                . (($v = $limitText((string) ($controleIbscbs['x_tp_ente_gov'] ?? ''), 2000)) !== '' ? '<XTpEnteGov>' . $this->xmlValue($v) . '</XTpEnteGov>' : '')
                . (($v = $limitText((string) ($controleIbscbs['cst'] ?? ''), 3)) !== '' ? '<CST>' . $this->xmlValue($v) . '</CST>' : '')
                . (($v = $limitText((string) ($controleIbscbs['c_class_trib'] ?? ''), 6)) !== '' ? '<cClassTrib>' . $this->xmlValue($v) . '</cClassTrib>' : '')
                . '</ControleIBSCBS>';
        }

        $ibscbsXml = '';
        if ($hasAny($ibscbs)) {
            $ibscbsXml = '<IBSCBS>'
                . (($v = $onlyDecimal((string) ($ibscbs['base_calculo'] ?? ''), 2)) !== '' ? '<IBSCBSBaseCalculo>' . $this->xmlValue($v) . '</IBSCBSBaseCalculo>' : '')
                . (($v = $onlyDecimal((string) ($ibscbs['ibs_uf_aliquota'] ?? ''), 2)) !== '' ? '<IBSUFAliquota>' . $this->xmlValue($v) . '</IBSUFAliquota>' : '')
                . (($v = $onlyDecimal((string) ($ibscbs['ibs_mun_aliquota'] ?? ''), 2)) !== '' ? '<IBSMunAliquota>' . $this->xmlValue($v) . '</IBSMunAliquota>' : '')
                . (($v = $onlyDecimal((string) ($ibscbs['cbs_aliquota'] ?? ''), 2)) !== '' ? '<CBSAliquota>' . $this->xmlValue($v) . '</CBSAliquota>' : '')
                . (($v = $onlyDecimal((string) ($ibscbs['ibs_uf_valor'] ?? ''), 2)) !== '' ? '<IBSUFValor>' . $this->xmlValue($v) . '</IBSUFValor>' : '')
                . (($v = $onlyDecimal((string) ($ibscbs['ibs_mun_valor'] ?? ''), 2)) !== '' ? '<IBSMunValor>' . $this->xmlValue($v) . '</IBSMunValor>' : '')
                . (($v = $onlyDecimal((string) ($ibscbs['cbs_valor'] ?? ''), 2)) !== '' ? '<CBSValor>' . $this->xmlValue($v) . '</CBSValor>' : '')
                . $this->buildSpeedGovDecimalElements($ibscbs, [
                    'ibs_uf_perc_reducao' => 'IBSUFPercReducao', 'ibs_mun_perc_reducao' => 'IBSMunPercReducao',
                    'cbs_perc_reducao' => 'CBSPercReducao', 'ibs_uf_aliquota_efetiva' => 'IBSUFAliquotaEfetiva',
                    'ibs_mun_aliquota_efetiva' => 'IBSMunAliquotaEfetiva', 'cbs_aliquota_efetiva' => 'CBSAliquotaEfetiva',
                    'ibs_uf_perc_diferimento' => 'IBSUFPercDiferimento', 'ibs_mun_perc_diferimento' => 'IBSMunPercDiferimento',
                    'cbs_perc_diferimento' => 'CBSPercDiferimento', 'ibs_uf_valor_diferido' => 'IBSUFValorDiferido',
                    'ibs_mun_valor_diferido' => 'IBSMunValorDiferido', 'cbs_valor_diferido' => 'CBSValorDiferido',
                    'ibs_credito_presumido_aliq' => 'IBSCreditoPresumidoAliq', 'ibs_credito_presumido_valor' => 'IBSCreditoPresumidoValor',
                    'cbs_credito_presumido_aliq' => 'CBSCreditoPresumidoAliq', 'cbs_credito_presumido_valor' => 'CBSCreditoPresumidoValor',
                ])
                . (($v = $onlyDecimal((string) ($ibscbs['ibs_valor_total'] ?? ''), 2)) !== '' ? '<IBSValorTotal>' . $this->xmlValue($v) . '</IBSValorTotal>' : '')
                . (($v = $onlyDecimal((string) ($ibscbs['valor_total_com_tributos'] ?? ''), 2)) !== '' ? '<ValorTotalComTributos>' . $this->xmlValue($v) . '</ValorTotalComTributos>' : '')
                . (($v = $onlyDecimal((string) ($ibscbs['ibs_valor_reembolso'] ?? ''), 2)) !== '' ? '<IBSValorReembolso>' . $this->xmlValue($v) . '</IBSValorReembolso>' : '')
                . (($v = $onlyDigits((string) ($ibscbs['localidade_incidencia_cod'] ?? ''), 7)) !== '' ? '<LocalidadeIncidenciaCod>' . $this->xmlValue($v) . '</LocalidadeIncidenciaCod>' : '')
                . (($v = $limitText((string) ($ibscbs['localidade_incidencia_nome'] ?? ''), 2000)) !== '' ? '<LocalidadeIncidenciaNome>' . $this->xmlValue($v) . '</LocalidadeIncidenciaNome>' : '')
                . (($v = $onlyDecimal((string) ($ibscbs['perc_redutor_compra_gov'] ?? ''), 2)) !== '' ? '<PercRedutorCompraGov>' . $this->xmlValue($v) . '</PercRedutorCompraGov>' : '')
                . '</IBSCBS>';
        }

        $dadosDpsXml = '';
        if ($hasAny($dadosDps)) {
            $dadosDpsXml = '<DadosDPS>'
                . (($v = $onlyDigits((string) ($dadosDps['tp_emit'] ?? ''), 1)) !== '' ? '<TpEmit>' . $this->xmlValue($v) . '</TpEmit>' : '')
                . (($v = $onlyDigits((string) ($dadosDps['tp_amb'] ?? ''), 1)) !== '' ? '<TpAmb>' . $this->xmlValue($v) . '</TpAmb>' : '')
                . (($v = $limitText((string) ($dadosDps['dh_emi'] ?? ''), 25)) !== '' ? '<DhEmi>' . $this->xmlValue($v) . '</DhEmi>' : '')
                . (($v = $limitText((string) ($dadosDps['ver_aplic'] ?? ''), 50)) !== '' ? '<VerAplic>' . $this->xmlValue($v) . '</VerAplic>' : '')
                . (($v = $onlyDigits((string) ($dadosDps['c_loc_emi'] ?? ''), 7)) !== '' ? '<CLocEmi>' . $this->xmlValue($v) . '</CLocEmi>' : '')
                . (($v = $onlyDigits((string) ($dadosDps['c_loc_prestacao'] ?? ''), 7)) !== '' ? '<CLocPrestacao>' . $this->xmlValue($v) . '</CLocPrestacao>' : '')
                . (($v = $limitText((string) ($dadosDps['c_trib_nac'] ?? ''), 6)) !== '' ? '<CTribNac>' . $this->xmlValue($v) . '</CTribNac>' : '')
                . (($v = $onlyDigits((string) ($dadosDps['trib_issqn'] ?? ''), 1)) !== '' ? '<TribIssqn>' . $this->xmlValue($v) . '</TribIssqn>' : '')
                . (($v = $onlyDigits((string) ($dadosDps['tp_ret_issqn'] ?? ''), 1)) !== '' ? '<TpRetIssqn>' . $this->xmlValue($v) . '</TpRetIssqn>' : '')
                . (($v = $onlyDigits((string) ($dadosDps['op_simp_nac'] ?? ''), 1)) !== '' ? '<OpSimpNac>' . $this->xmlValue($v) . '</OpSimpNac>' : '')
                . (($v = $onlyDigits((string) ($dadosDps['reg_esp_trib'] ?? ''), 1)) !== '' ? '<RegEspTrib>' . $this->xmlValue($v) . '</RegEspTrib>' : '')
                . (($v = $onlyDigits((string) ($dadosDps['reg_ap_trib_sn'] ?? ''), 1)) !== '' ? '<RegApTribSN>' . $this->xmlValue($v) . '</RegApTribSN>' : '')
                . (($v = $onlyDigits((string) ($dadosDps['serie'] ?? ''), 0)) !== '' ? '<serie>' . $this->xmlValue($v) . '</serie>' : '')
                . (($v = $onlyDigits((string) ($dadosDps['numero'] ?? $dadosDps['n_dps'] ?? ''), 0)) !== '' ? '<nDPS>' . $this->xmlValue($v) . '</nDPS>' : '')
                . (($v = $limitText((string) ($dadosDps['data_competencia'] ?? $dadosDps['d_compet'] ?? ''), 10)) !== '' ? '<dCompet>' . $this->xmlValue($v) . '</dCompet>' : '')
                . '</DadosDPS>';
        }

        $dadosObraXml = '';
        if ($hasAny($dadosObra)) {
            $endObra = is_array($dadosObra['endereco_obra'] ?? null) ? $dadosObra['endereco_obra'] : [];
            $endObraXml = '';
            if ($hasAny($endObra)) {
                $endObraXml = '<EnderecoObra>'
                    . (($v = $onlyDigits((string) ($endObra['cep'] ?? ''), 10)) !== '' ? '<Cep>' . $this->xmlValue($v) . '</Cep>' : '')
                    . (($v = $limitText((string) ($endObra['logradouro'] ?? ''), 125)) !== '' ? '<Logradouro>' . $this->xmlValue($v) . '</Logradouro>' : '')
                    . (($v = $limitText((string) ($endObra['numero'] ?? ''), 10)) !== '' ? '<Numero>' . $this->xmlValue($v) . '</Numero>' : '')
                    . (($v = $limitText((string) ($endObra['complemento'] ?? ''), 60)) !== '' ? '<Complemento>' . $this->xmlValue($v) . '</Complemento>' : '')
                    . (($v = $limitText((string) ($endObra['bairro'] ?? ''), 60)) !== '' ? '<Bairro>' . $this->xmlValue($v) . '</Bairro>' : '')
                    . '</EnderecoObra>';
            }
            $dadosObraXml = '<DadosObra>'
                . (($v = $limitText((string) ($dadosObra['codigo_obra'] ?? ''), 30)) !== '' ? '<CodigoObra>' . $this->xmlValue($v) . '</CodigoObra>' : '')
                . (($v = $limitText((string) ($dadosObra['insc_imob_fisc'] ?? ''), 30)) !== '' ? '<InscImobFisc>' . $this->xmlValue($v) . '</InscImobFisc>' : '')
                . $endObraXml
                . '</DadosObra>';
        }

        $comercioExteriorXml = '';
        if ($hasAny($comercioExterior)) {
            $comercioExteriorXml = '<ComercioExterior>'
                . (($v = $onlyDigits((string) ($comercioExterior['md_prestacao'] ?? ''), 1)) !== '' ? '<MdPrestacao>' . $this->xmlValue($v) . '</MdPrestacao>' : '')
                . (($v = $onlyDigits((string) ($comercioExterior['vinc_prest'] ?? ''), 1)) !== '' ? '<VincPrest>' . $this->xmlValue($v) . '</VincPrest>' : '')
                . (($v = $onlyDigits((string) ($comercioExterior['tp_moeda'] ?? ''), 3)) !== '' ? '<TpMoeda>' . $this->xmlValue($v) . '</TpMoeda>' : '')
                . (($v = $onlyDecimal((string) ($comercioExterior['v_serv_moeda'] ?? ''), 2)) !== '' ? '<VServMoeda>' . $this->xmlValue($v) . '</VServMoeda>' : '')
                . (($v = $limitText((string) ($comercioExterior['mec_af_comex_p'] ?? ''), 10)) !== '' ? '<MecAFComexP>' . $this->xmlValue($v) . '</MecAFComexP>' : '')
                . (($v = $limitText((string) ($comercioExterior['mec_af_comex_t'] ?? ''), 10)) !== '' ? '<MecAFComexT>' . $this->xmlValue($v) . '</MecAFComexT>' : '')
                . (($v = $onlyDigits((string) ($comercioExterior['mov_temp_bens'] ?? ''), 1)) !== '' ? '<MovTempBens>' . $this->xmlValue($v) . '</MovTempBens>' : '')
                . (($v = $limitText((string) ($comercioExterior['ndi'] ?? ''), 12)) !== '' ? '<NDI>' . $this->xmlValue($v) . '</NDI>' : '')
                . (($v = $limitText((string) ($comercioExterior['nre'] ?? ''), 12)) !== '' ? '<NRE>' . $this->xmlValue($v) . '</NRE>' : '')
                . (($v = $onlyDigits((string) ($comercioExterior['mdic'] ?? ''), 1)) !== '' ? '<MDIC>' . $this->xmlValue($v) . '</MDIC>' : '')
                . (($v = $onlyDigits((string) ($comercioExterior['c_pais_result'] ?? ''), 4)) !== '' ? '<CPaisResult>' . $this->xmlValue($v) . '</CPaisResult>' : '')
                . '</ComercioExterior>';
        }

        $exigibilidadeSuspensaXml = '';
        if ($hasAny($exigibilidadeSuspensa)) {
            $exigibilidadeSuspensaXml = '<ExigibilidadeSuspensa>'
                . (($v = $onlyDigits((string) ($exigibilidadeSuspensa['tp_susp'] ?? ''), 1)) !== '' ? '<TpSusp>' . $this->xmlValue($v) . '</TpSusp>' : '')
                . (($v = $limitText((string) ($exigibilidadeSuspensa['n_processo'] ?? ''), 30)) !== '' ? '<NProcesso>' . $this->xmlValue($v) . '</NProcesso>' : '')
                . '</ExigibilidadeSuspensa>';
        }

        $beneficioMunicipalXml = '';
        if ($hasAny($beneficioMunicipal)) {
            $beneficioMunicipalXml = '<BeneficioMunicipal>'
                . (($v = $onlyDigits((string) ($beneficioMunicipal['tp_bm'] ?? $beneficioMunicipal['tp_beneficio'] ?? ''), 1)) !== '' ? '<TpBM>' . $this->xmlValue($v) . '</TpBM>' : '')
                . (($v = $limitText((string) ($beneficioMunicipal['n_bm'] ?? $beneficioMunicipal['n_beneficio'] ?? ''), 14)) !== '' ? '<NBM>' . $this->xmlValue($v) . '</NBM>' : '')
                . (($v = $onlyDecimal((string) ($beneficioMunicipal['v_red_bcbm'] ?? $beneficioMunicipal['v_red_bc_beneficio'] ?? ''), 2)) !== '' ? '<VRedBCBM>' . $this->xmlValue($v) . '</VRedBCBM>' : '')
                . (($v = $onlyDecimal((string) ($beneficioMunicipal['p_red_bcbm'] ?? $beneficioMunicipal['p_red_bc_beneficio'] ?? ''), 2)) !== '' ? '<PRedBCBM>' . $this->xmlValue($v) . '</PRedBCBM>' : '')
                . '</BeneficioMunicipal>';
        }

        $reembolsoRepasseXml = '';
        if ($hasAny($reembolsoRepasse)) {
            $reembolsoRepasseXml = '<ReembolsoRepasse>'
                . (($v = $onlyDigits((string) ($reembolsoRepasse['tp_reemb_rep_res'] ?? ''), 1)) !== '' ? '<TpReembRepRes>' . $this->xmlValue($v) . '</TpReembRepRes>' : '')
                . (($v = $limitText((string) ($reembolsoRepasse['x_tp_reemb_rep_res'] ?? ''), 2000)) !== '' ? '<XTpReembRepRes>' . $this->xmlValue($v) . '</XTpReembRepRes>' : '')
                . (($v = $onlyDecimal((string) ($reembolsoRepasse['v_reemb_rep_res'] ?? ''), 2)) !== '' ? '<VReembRepRes>' . $this->xmlValue($v) . '</VReembRepRes>' : '')
                . '</ReembolsoRepasse>';
        }

        $destinatarioXml = '';
        if ($hasAny($destinatario)) {
            $destinatarioXml = '<Destinatario>'
                . (($v = $limitText((string) ($destinatario['cnpj_cpf'] ?? ''), 14)) !== '' ? '<CnpjCpf>' . $this->xmlValue($v) . '</CnpjCpf>' : '')
                . (($v = $limitText((string) ($destinatario['nome'] ?? ''), 115)) !== '' ? '<Nome>' . $this->xmlValue($v) . '</Nome>' : '')
                . (($v = $limitText((string) ($destinatario['logradouro'] ?? ''), 125)) !== '' ? '<Logradouro>' . $this->xmlValue($v) . '</Logradouro>' : '')
                . (($v = $limitText((string) ($destinatario['numero'] ?? ''), 10)) !== '' ? '<Numero>' . $this->xmlValue($v) . '</Numero>' : '')
                . (($v = $limitText((string) ($destinatario['complemento'] ?? ''), 60)) !== '' ? '<Complemento>' . $this->xmlValue($v) . '</Complemento>' : '')
                . (($v = $limitText((string) ($destinatario['bairro'] ?? ''), 60)) !== '' ? '<Bairro>' . $this->xmlValue($v) . '</Bairro>' : '')
                . (($v = $limitText((string) ($destinatario['cidade'] ?? ''), 60)) !== '' ? '<Cidade>' . $this->xmlValue($v) . '</Cidade>' : '')
                . (($v = $limitText((string) ($destinatario['uf'] ?? ''), 2)) !== '' ? '<UF>' . $this->xmlValue(strtoupper($v)) . '</UF>' : '')
                . (($v = $onlyDigits((string) ($destinatario['cep'] ?? ''), 10)) !== '' ? '<CEP>' . $this->xmlValue($v) . '</CEP>' : '')
                . (($v = $onlyDigits((string) ($destinatario['cod_municipio'] ?? ''), 7)) !== '' ? '<CodMunicipio>' . $this->xmlValue($v) . '</CodMunicipio>' : '')
                . (($v = $onlyDigits((string) ($destinatario['cod_pais'] ?? ''), 4)) !== '' ? '<CodPais>' . $this->xmlValue($v) . '</CodPais>' : '')
                . (($v = $limitText((string) ($destinatario['cod_postal_ext'] ?? ''), 10)) !== '' ? '<CodPostalExt>' . $this->xmlValue($v) . '</CodPostalExt>' : '')
                . (($v = $limitText((string) ($destinatario['nif'] ?? ''), 40)) !== '' ? '<NIF>' . $this->xmlValue($v) . '</NIF>' : '')
                . (($v = $limitText((string) ($destinatario['email'] ?? ''), 120)) !== '' ? '<Email>' . $this->xmlValue($v) . '</Email>' : '')
                . (($v = $limitText((string) ($destinatario['telefone'] ?? ''), 20)) !== '' ? '<Telefone>' . $this->xmlValue($v) . '</Telefone>' : '')
                . '</Destinatario>';
        }

        $intermediarioXml = '';
        if ($hasAny($intermediario)) {
            $documento = $onlyDigits((string) ($intermediario['documento'] ?? ''), 14);
            $documentoXml = $documento === '' ? '' : (strlen($documento) <= 11
                ? '<Cpf>' . $this->xmlValue($documento) . '</Cpf>'
                : '<Cnpj>' . $this->xmlValue($documento) . '</Cnpj>');
            $intermediarioXml = '<IntermediarioServico>'
                . (($v = $limitText((string) ($intermediario['nome_razao_social'] ?? ''), 115)) !== '' ? '<RazaoSocial>' . $this->xmlValue($v) . '</RazaoSocial>' : '')
                . ($documentoXml !== '' ? '<CpfCnpj>' . $documentoXml . '</CpfCnpj>' : '')
                . (($v = $limitText((string) ($intermediario['inscricao_municipal'] ?? ''), 15)) !== '' ? '<InscricaoMunicipal>' . $this->xmlValue($v) . '</InscricaoMunicipal>' : '')
                . '</IntermediarioServico>';
        }

        $dadosXml = ''
            . '<EnviarLoteRpsEnvio xmlns="http://ws.speedgov.com.br/enviar_lote_rps_envio_v1.xsd">'
            . '<LoteRps xmlns="" Id="' . $this->xmlAttr('LOTE' . $loteNumero) . '">'
            . '<NumeroLote>' . $this->xmlValue($loteNumero) . '</NumeroLote>'
            . '<Cnpj>' . $this->xmlValue($prestadorCnpj) . '</Cnpj>'
            . '<InscricaoMunicipal>' . $this->xmlValue($prestadorIm) . '</InscricaoMunicipal>'
            . '<QuantidadeRps>1</QuantidadeRps>'
            . '<ListaRps>'
            . '<Rps>'
            . '<InfRps>'
            . '<IdentificacaoRps>'
            . '<Numero>' . $this->xmlValue($rpsNumero) . '</Numero>'
            . '<Serie>' . $this->xmlValue($rpsSerie) . '</Serie>'
            . '<Tipo>' . $this->xmlValue($rpsTipo) . '</Tipo>'
            . '</IdentificacaoRps>'
            . '<DataEmissao>' . $this->xmlValue($dataEmissao) . '</DataEmissao>'
            . '<NaturezaOperacao>' . $this->xmlValue((string) $naturezaOperacao) . '</NaturezaOperacao>'
            . ($hasRegimeEspecialTributacao ? '<RegimeEspecialTributacao>' . $this->xmlValue((string) $regimeEspecialTributacao) . '</RegimeEspecialTributacao>' : '')
            . '<OptanteSimplesNacional>' . $this->xmlValue((string) $optanteSimples) . '</OptanteSimplesNacional>'
            . '<IncentivadorCultural>' . $this->xmlValue((string) $incentivadorCultural) . '</IncentivadorCultural>'
            . '<Status>' . $this->xmlValue((string) $status) . '</Status>'
            . '<Servico>'
            . '<Valores>'
            . '<ValorServicos>' . $values['servico_valor'] . '</ValorServicos>'
            . '<ValorDeducoes>' . $values['deducoes'] . '</ValorDeducoes>'
            . '<ValorPis>' . $values['pis'] . '</ValorPis>'
            . '<ValorCofins>' . $values['cofins'] . '</ValorCofins>'
            . '<ValorInss>' . $values['inss'] . '</ValorInss>'
            . '<ValorIr>' . $values['ir'] . '</ValorIr>'
            . '<ValorCsll>' . $values['csll'] . '</ValorCsll>'
            . '<IssRetido>' . $this->xmlValue((string) $issRetido) . '</IssRetido>'
            . '<ValorIss>' . $values['iss_valor'] . '</ValorIss>'
            . '<ValorIssRetido>' . $values['valor_iss_retido'] . '</ValorIssRetido>'
            . '<OutrasRetencoes>' . $values['outras_retencoes'] . '</OutrasRetencoes>'
            . '<BaseCalculo>' . $values['base_calculo'] . '</BaseCalculo>'
            . '<Aliquota>' . $values['aliquota'] . '</Aliquota>'
            . '<ValorLiquidoNfse>' . $values['valor_liquido_nfse'] . '</ValorLiquidoNfse>'
            . '<DescontoCondicionado>' . $values['desconto_condicionado'] . '</DescontoCondicionado>'
            . '<DescontoIncondicionado>' . $values['desconto_incondicionado'] . '</DescontoIncondicionado>'
            . $this->buildSpeedGovOptionalValues($servico)
            . '</Valores>'
            . '<ItemListaServico>' . $this->xmlValue($itemListaServico) . '</ItemListaServico>'
            . '<CodigoCnae>' . $this->xmlValue($codigoCnae) . '</CodigoCnae>'
            . ($codigoTributacaoMunicipio !== '' ? '<CodigoTributacaoMunicipio>' . $this->xmlValue($codigoTributacaoMunicipio) . '</CodigoTributacaoMunicipio>' : '')
            . '<Discriminacao>' . $this->xmlValue($servicoDesc) . '</Discriminacao>'
            . '<CodigoMunicipio>' . $this->xmlValue($codigoMunicipio) . '</CodigoMunicipio>'
            . (($v = $limitText((string) ($servico['codigo_nbs'] ?? $servico['c_nbs'] ?? ''), 30)) !== '' ? '<cNBS>' . $this->xmlValue($v) . '</cNBS>' : '')
            . (($v = $limitText((string) ($servico['descricao_servico'] ?? $servico['x_desc_serv'] ?? ''), 2000)) !== '' ? '<xDescServ>' . $this->xmlValue($v) . '</xDescServ>' : '')
            . (($v = $limitText((string) ($servico['codigo_interno'] ?? $servico['c_int_contrib'] ?? ''), 255)) !== '' ? '<cIntContrib>' . $this->xmlValue($v) . '</cIntContrib>' : '')
            . '</Servico>'
            . '<Prestador>'
            . '<Cnpj>' . $this->xmlValue($prestadorCnpj) . '</Cnpj>'
            . '<InscricaoMunicipal>' . $this->xmlValue($prestadorIm) . '</InscricaoMunicipal>'
            . '</Prestador>'
            . '<Tomador>'
            . '<IdentificacaoTomador>'
            . '<CpfCnpj>'
            . $tomadorDocXml
            . '</CpfCnpj>'
            . ($tomadorIm !== '' ? '<InscricaoMunicipal>' . $this->xmlValue($tomadorIm) . '</InscricaoMunicipal>' : '')
            . '</IdentificacaoTomador>'
            . '<RazaoSocial>' . $this->xmlValue($tomadorNome) . '</RazaoSocial>'
            . $tomadorEnderecoXml
            . $tomadorContatoXml
            . '</Tomador>'
            . $intermediarioXml
            . $dadosDpsXml
            . $dadosObraXml
            . $comercioExteriorXml
            . $exigibilidadeSuspensaXml
            . $beneficioMunicipalXml
            . $reembolsoRepasseXml
            . $destinatarioXml
            . $controleIbscbsXml
            . $ibscbsXml
            . ($dataCompetencia !== '' ? '<DataCompetencia>' . $this->xmlValue($limitText($dataCompetencia, 10)) . '</DataCompetencia>' : '')
            . '</InfRps>'
            . '</Rps>'
            . '</ListaRps>'
            . '</LoteRps>'
            . '</EnviarLoteRpsEnvio>';

        // No SpeedGov, a assinatura do lote (LoteRps) é obrigatória.
        $dadosXml = $this->sign($dadosXml, 'Rps', null, 'Rps');
        $dadosXml = $this->sign($dadosXml, 'LoteRps', 'Id', 'LoteRps');

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfse="http://www.abrasf.org.br/ABRASF/arquivos/nfse.xsd">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<nfse:RecepcionarLoteRps>'
            . '<header>' . $this->asCdata($cabecalhoXml) . '</header>'
            . '<parameters>' . $this->asCdata($dadosXml) . '</parameters>'
            . '</nfse:RecepcionarLoteRps>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    /** @param array<string,mixed> $values @param array<string,string> $mapping */
    private function buildSpeedGovDecimalElements(array $values, array $mapping): string
    {
        $xml = '';
        foreach ($mapping as $key => $tag) {
            $value = str_replace(',', '.', trim((string) ($values[$key] ?? '')));
            if ($value !== '' && preg_match('/^-?\d+(\.\d+)?$/', $value)) {
                $xml .= '<' . $tag . '>' . $this->xmlValue(number_format((float) $value, 2, '.', '')) . '</' . $tag . '>';
            }
        }
        return $xml;
    }

    /** @param array<string,mixed> $servico */
    private function buildSpeedGovOptionalValues(array $servico): string
    {
        $element = function (string $tag, mixed $value, bool $decimal = false): string {
            $value = str_replace(',', '.', trim((string) $value));
            if ($value === '' || ($decimal && !preg_match('/^-?\d+(\.\d+)?$/', $value))) {
                return '';
            }
            if ($decimal) {
                $value = number_format((float) $value, 4, '.', '');
            }
            return '<' . $tag . '>' . $this->xmlValue($value) . '</' . $tag . '>';
        };
        $first = static fn (array $source, array $keys): mixed => array_reduce(
            $keys,
            static fn ($carry, $key) => $carry !== null && trim((string) $carry) !== '' ? $carry : ($source[$key] ?? null),
            null
        );

        // A ordem é significativa no xsd:sequence de tcValores.
        return $element('CSTPisCofins', $servico['cst_pis_cofins'] ?? '')
            . $element('BaseCalculoPisCofins', $servico['base_calculo_pis_cofins'] ?? '', true)
            . $element('TipoRetencaoPisCofins', $servico['tipo_retencao_pis_cofins'] ?? '')
            . $element('AliqPis', $first($servico, ['aliquota_pis', 'aliq_pis']), true)
            . $element('AliqCofins', $first($servico, ['aliquota_cofins', 'aliq_cofins']), true)
            . $element('pTotTribFed', $first($servico, ['percentual_total_tributos_federais', 'p_tot_trib_fed']), true)
            . $element('pTotTribEst', $first($servico, ['percentual_total_tributos_estaduais', 'p_tot_trib_est']), true)
            . $element('pTotTribMun', $first($servico, ['percentual_total_tributos_municipais', 'p_tot_trib_mun']), true);
    }

    /**
     * @param array|string $payload
     */
    private function buildSpeedGovEnvelope(array|string $payload, string $service): string
    {
        $data = $this->normalizePayload($payload);
        $method = $this->mapSpeedGovMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildSpeedGovDataForService($data, $service);
        }

        $cabecalho = '<p:cabecalho versao="1" xmlns:p="http://ws.speedgov.com.br/cabecalho_v1.xsd">'
            . '<versaoDados>1</versaoDados>'
            . '</p:cabecalho>';

        $body = '<nfse:' . $method . '>'
            . '<header>' . $this->asCdata($cabecalho) . '</header>'
            . '<parameters>' . $this->asCdata($dados) . '</parameters>'
            . '</nfse:' . $method . '>';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfse="http://www.abrasf.org.br/ABRASF/arquivos/nfse.xsd">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>' . $body . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    /**
     * @param array<string,mixed> $data
     */
    private function buildSpeedGovDataForService(array $data, string $service): string
    {
        $normalized = strtolower(trim($service));
        $cnpj = preg_replace('/\D+/', '', (string) ($data['prestador_cnpj'] ?? $data['cnpj'] ?? '13268582000133'));
        $im = trim((string) ($data['prestador_im'] ?? $data['inscricao_municipal'] ?? '1820893'));
        $protocolo = trim((string) ($data['protocolo'] ?? $data['numero_protocolo'] ?? ''));
        $rpsNumero = trim((string) ($data['rps_numero'] ?? $data['numero_rps'] ?? '10000'));
        $rpsSerie = trim((string) ($data['rps_serie'] ?? $data['serie_rps'] ?? '1'));
        $rpsTipo = trim((string) ($data['rps_tipo'] ?? $data['tipo_rps'] ?? '1'));
        $numeroNfse = trim((string) ($data['numero_nfse'] ?? $data['nfse_numero'] ?? ''));
        $codigoMunicipio = preg_replace('/\D+/', '', (string) ($data['codigo_municipio'] ?? '')) ?: '';
        $codigoCancelamento = mb_substr(trim((string) ($data['codigo_cancelamento'] ?? '1')), 0, 4);
        if ($codigoCancelamento === '') {
            $codigoCancelamento = '1';
        }

        if (in_array($normalized, ['consultar_nfse_rps', 'consultar_nf_se_rps'], true)) {
            return '<ConsultarNfseRpsEnvio xmlns="http://ws.speedgov.com.br/consultar_nfse_rps_envio_v1.xsd">'
                . '<IdentificacaoRps xmlns="">'
                . '<Numero>' . $this->xmlValue($rpsNumero) . '</Numero>'
                . '<Serie>' . $this->xmlValue($rpsSerie) . '</Serie>'
                . '<Tipo>' . $this->xmlValue($rpsTipo) . '</Tipo>'
                . '</IdentificacaoRps>'
                . '<Prestador xmlns="">'
                . '<Cnpj>' . $this->xmlValue($cnpj) . '</Cnpj>'
                . '<InscricaoMunicipal>' . $this->xmlValue($im) . '</InscricaoMunicipal>'
                . '</Prestador>'
                . '</ConsultarNfseRpsEnvio>';
        }

        if ($normalized === 'consultar_lote') {
            return '<ConsultarLoteRpsEnvio xmlns="http://ws.speedgov.com.br/consultar_lote_rps_envio_v1.xsd">'
                . '<Prestador xmlns="">'
                . '<Cnpj>' . $this->xmlValue($cnpj) . '</Cnpj>'
                . '<InscricaoMunicipal>' . $this->xmlValue($im) . '</InscricaoMunicipal>'
                . '</Prestador>'
                . '<Protocolo xmlns="">' . $this->xmlValue($protocolo) . '</Protocolo>'
                . '</ConsultarLoteRpsEnvio>';
        }

        if ($normalized === 'consultar_situacao') {
            return '<ConsultarSituacaoLoteRpsEnvio xmlns="http://ws.speedgov.com.br/consultar_situacao_lote_rps_envio_v1.xsd">'
                . '<Prestador xmlns="">'
                . '<Cnpj>' . $this->xmlValue($cnpj) . '</Cnpj>'
                . '<InscricaoMunicipal>' . $this->xmlValue($im) . '</InscricaoMunicipal>'
                . '</Prestador>'
                . '<Protocolo xmlns="">' . $this->xmlValue($protocolo) . '</Protocolo>'
                . '</ConsultarSituacaoLoteRpsEnvio>';
        }

        if (in_array($normalized, ['cancelar_nfse', 'cancelar_nf_se'], true)) {
            if ($numeroNfse === '' || $cnpj === '' || $codigoMunicipio === '') {
                throw new RuntimeException('Cancelamento SpeedGov requer numero_nfse, prestador_cnpj e codigo_municipio.');
            }
            $id = $numeroNfse !== '' ? ('cancel' . preg_replace('/\D+/', '', $numeroNfse)) : ('cancel' . date('YmdHis'));
            $cancelamentoXml = '<CancelarNfseEnvio xmlns="http://ws.speedgov.com.br/cancelar_nfse_envio_v1.xsd">'
                . '<Pedido xmlns="">'
                . '<InfPedidoCancelamento Id="' . $this->xmlAttr($id) . '">'
                . '<IdentificacaoNfse>'
                . '<Numero>' . $this->xmlValue($numeroNfse) . '</Numero>'
                . '<Cnpj>' . $this->xmlValue($cnpj) . '</Cnpj>'
                . '<InscricaoMunicipal>' . $this->xmlValue($im) . '</InscricaoMunicipal>'
                . '<CodigoMunicipio>' . $this->xmlValue($codigoMunicipio) . '</CodigoMunicipio>'
                . '</IdentificacaoNfse>'
                . '<CodigoCancelamento>' . $this->xmlValue($codigoCancelamento) . '</CodigoCancelamento>'
                . '</InfPedidoCancelamento>'
                . '</Pedido>'
                . '</CancelarNfseEnvio>';

            return $this->sign($cancelamentoXml, 'InfPedidoCancelamento', 'Id', 'Pedido');
        }

        return $this->buildAbrasfDataForMethod($data, $service);
    }

    private function mapSpeedGovMethod(string $service): string
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'consultar_lote' => 'ConsultarLoteRps',
            'consultar_situacao' => 'ConsultarSituacaoLoteRps',
            'consultar_nfse_rps', 'consultar_nf_se_rps' => 'ConsultarNfsePorRps',
            'consultar_nfse', 'consultar_nf_se' => 'ConsultarNfse',
            'cancelar_nfse', 'cancelar_nf_se' => 'CancelarNfse',
            default => 'RecepcionarLoteRps',
        };
    }
}
