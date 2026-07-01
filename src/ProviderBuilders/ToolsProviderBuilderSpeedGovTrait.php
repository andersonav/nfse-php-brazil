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
            'iss_valor' => number_format($servicoValor * ($aliquotaNormalizada > 0 ? $aliquotaNormalizada : 0), 2, '.', ''),
            'aliquota' => number_format($servicoAliquota, 4, '.', ''),
            'base_calculo' => number_format($servicoValor, 2, '.', ''),
            'valor_iss_retido' => number_format($issRetido === 1 ? ($servicoValor * ($aliquotaNormalizada > 0 ? $aliquotaNormalizada : 0)) : 0, 2, '.', ''),
            'valor_liquido_nfse' => number_format($servicoValor, 2, '.', ''),
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
                . (trim((string) ($controleIbscbs['fin_nfse'] ?? '')) !== '' ? '<FinNFSe>' . $this->xmlValue((string) $controleIbscbs['fin_nfse']) . '</FinNFSe>' : '')
                . (trim((string) ($controleIbscbs['ind_final'] ?? '')) !== '' ? '<IndFinal>' . $this->xmlValue((string) $controleIbscbs['ind_final']) . '</IndFinal>' : '')
                . (trim((string) ($controleIbscbs['tp_oper'] ?? '')) !== '' ? '<TpOper>' . $this->xmlValue((string) $controleIbscbs['tp_oper']) . '</TpOper>' : '')
                . (trim((string) ($controleIbscbs['tp_ente_gov'] ?? '')) !== '' ? '<TpEnteGov>' . $this->xmlValue((string) $controleIbscbs['tp_ente_gov']) . '</TpEnteGov>' : '')
                . (trim((string) ($controleIbscbs['ind_dest'] ?? '')) !== '' ? '<IndDest>' . $this->xmlValue((string) $controleIbscbs['ind_dest']) . '</IndDest>' : '')
                . (trim((string) ($controleIbscbs['c_ind_op'] ?? '')) !== '' ? '<CIndOp>' . $this->xmlValue((string) $controleIbscbs['c_ind_op']) . '</CIndOp>' : '')
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
                . (($v = $onlyDecimal((string) ($ibscbs['ibs_valor_total'] ?? ''), 2)) !== '' ? '<IBSValorTotal>' . $this->xmlValue($v) . '</IBSValorTotal>' : '')
                . (($v = $onlyDecimal((string) ($ibscbs['valor_total_com_tributos'] ?? ''), 2)) !== '' ? '<ValorTotalComTributos>' . $this->xmlValue($v) . '</ValorTotalComTributos>' : '')
                . (($v = $onlyDigits((string) ($ibscbs['localidade_incidencia_cod'] ?? ''), 7)) !== '' ? '<LocalidadeIncidenciaCod>' . $this->xmlValue($v) . '</LocalidadeIncidenciaCod>' : '')
                . (($v = $limitText((string) ($ibscbs['localidade_incidencia_nome'] ?? ''), 2000)) !== '' ? '<LocalidadeIncidenciaNome>' . $this->xmlValue($v) . '</LocalidadeIncidenciaNome>' : '')
                . '</IBSCBS>';
        }

        $dadosDpsXml = '';
        if ($hasAny($dadosDps)) {
            $dadosDpsXml = '<DadosDPS>'
                . (($v = $onlyDigits((string) ($dadosDps['tp_emit'] ?? ''), 1)) !== '' ? '<TpEmit>' . $this->xmlValue($v) . '</TpEmit>' : '')
                . (($v = $onlyDigits((string) ($dadosDps['tp_amb'] ?? ''), 1)) !== '' ? '<TpAmb>' . $this->xmlValue($v) . '</TpAmb>' : '')
                . (($v = $limitText((string) ($dadosDps['dh_emi'] ?? ''), 25)) !== '' ? '<DhEmi>' . $this->xmlValue($v) . '</DhEmi>' : '')
                . (($v = $limitText((string) ($dadosDps['ver_aplic'] ?? ''), 20)) !== '' ? '<VerAplic>' . $this->xmlValue($v) . '</VerAplic>' : '')
                . (($v = $onlyDigits((string) ($dadosDps['c_loc_emi'] ?? ''), 7)) !== '' ? '<CLocEmi>' . $this->xmlValue($v) . '</CLocEmi>' : '')
                . (($v = $onlyDigits((string) ($dadosDps['c_loc_prestacao'] ?? ''), 7)) !== '' ? '<CLocPrestacao>' . $this->xmlValue($v) . '</CLocPrestacao>' : '')
                . (($v = $limitText((string) ($dadosDps['c_trib_nac'] ?? ''), 6)) !== '' ? '<CTribNac>' . $this->xmlValue($v) . '</CTribNac>' : '')
                . (($v = $onlyDigits((string) ($dadosDps['trib_issqn'] ?? ''), 1)) !== '' ? '<TribIssqn>' . $this->xmlValue($v) . '</TribIssqn>' : '')
                . (($v = $onlyDigits((string) ($dadosDps['tp_ret_issqn'] ?? ''), 1)) !== '' ? '<TpRetIssqn>' . $this->xmlValue($v) . '</TpRetIssqn>' : '')
                . (($v = $onlyDigits((string) ($dadosDps['op_simp_nac'] ?? ''), 1)) !== '' ? '<OpSimpNac>' . $this->xmlValue($v) . '</OpSimpNac>' : '')
                . (($v = $onlyDigits((string) ($dadosDps['reg_esp_trib'] ?? ''), 1)) !== '' ? '<RegEspTrib>' . $this->xmlValue($v) . '</RegEspTrib>' : '')
                . (($v = $onlyDigits((string) ($dadosDps['reg_ap_trib_sn'] ?? ''), 1)) !== '' ? '<RegApTribSN>' . $this->xmlValue($v) . '</RegApTribSN>' : '')
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
                . (($v = $onlyDigits((string) ($beneficioMunicipal['tp_bm'] ?? ''), 1)) !== '' ? '<TpBM>' . $this->xmlValue($v) . '</TpBM>' : '')
                . (($v = $limitText((string) ($beneficioMunicipal['n_bm'] ?? ''), 14)) !== '' ? '<NBM>' . $this->xmlValue($v) . '</NBM>' : '')
                . (($v = $onlyDecimal((string) ($beneficioMunicipal['v_red_bcbm'] ?? ''), 2)) !== '' ? '<VRedBCBM>' . $this->xmlValue($v) . '</VRedBCBM>' : '')
                . (($v = $onlyDecimal((string) ($beneficioMunicipal['p_red_bcbm'] ?? ''), 2)) !== '' ? '<PRedBCBM>' . $this->xmlValue($v) . '</PRedBCBM>' : '')
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
            . '<ValorPis>0.00</ValorPis>'
            . '<ValorCofins>0.00</ValorCofins>'
            . '<ValorInss>0.00</ValorInss>'
            . '<ValorIr>0.00</ValorIr>'
            . '<ValorCsll>0.00</ValorCsll>'
            . '<IssRetido>' . $this->xmlValue((string) $issRetido) . '</IssRetido>'
            . '<ValorIss>' . $values['iss_valor'] . '</ValorIss>'
            . '<ValorIssRetido>' . $values['valor_iss_retido'] . '</ValorIssRetido>'
            . '<BaseCalculo>' . $values['base_calculo'] . '</BaseCalculo>'
            . '<Aliquota>' . $values['aliquota'] . '</Aliquota>'
            . '<ValorLiquidoNfse>' . $values['valor_liquido_nfse'] . '</ValorLiquidoNfse>'
            . '<DescontoIncondicionado>0.00</DescontoIncondicionado>'
            . '</Valores>'
            . '<ItemListaServico>' . $this->xmlValue($itemListaServico) . '</ItemListaServico>'
            . '<CodigoCnae>' . $this->xmlValue($codigoCnae) . '</CodigoCnae>'
            . ($codigoTributacaoMunicipio !== '' ? '<CodigoTributacaoMunicipio>' . $this->xmlValue($codigoTributacaoMunicipio) . '</CodigoTributacaoMunicipio>' : '')
            . '<Discriminacao>' . $this->xmlValue($servicoDesc) . '</Discriminacao>'
            . '<CodigoMunicipio>' . $this->xmlValue($codigoMunicipio) . '</CodigoMunicipio>'
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
            . $controleIbscbsXml
            . $ibscbsXml
            . $dadosDpsXml
            . $dadosObraXml
            . $comercioExteriorXml
            . $exigibilidadeSuspensaXml
            . $beneficioMunicipalXml
            . $reembolsoRepasseXml
            . $destinatarioXml
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
        $codigoCancelamento = preg_replace('/\D+/', '', (string) ($data['codigo_cancelamento'] ?? '1')) ?: '1';
        $motivoCancelamento = trim((string) ($data['motivo'] ?? $data['motivo_cancelamento'] ?? ''));

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
            return '<CancelarNfseEnvio xmlns="http://ws.speedgov.com.br/cancelar_nfse_envio_v1.xsd">'
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
