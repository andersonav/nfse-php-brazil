<?php

declare(strict_types=1);

use Alves\NfseBrasil\Common\CatalogConfig;
use Alves\NfseBrasil\Tools;
use NFePHP\Common\Certificate;

require __DIR__ . '/../../vendor/autoload.php';

function h(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function postValue(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function postFloat(string $key, float $default = 0.0): float
{
    $value = str_replace(',', '.', postValue($key, (string) $default));
    return is_numeric($value) ? (float) $value : $default;
}

function postInt(string $key, int $default = 0): int
{
    $value = postValue($key, (string) $default);
    return is_numeric($value) ? (int) $value : $default;
}

/**
 * @return array<int,string>
 */
function postArrayValues(string $key): array
{
    $raw = $_POST[$key] ?? [];
    if (!is_array($raw)) {
        return [];
    }
    $values = [];
    foreach ($raw as $item) {
        $values[] = is_string($item) ? trim($item) : '';
    }
    return $values;
}

/**
 * @param array<string,mixed> $context
 * @return array<string,mixed>
 */
function buildEmissionPayloadFromPost(array $context): array
{
    $numeroLote = postValue('emit_lote_numero', '1');
    $rpsNumero = postValue('emit_rps_numero', '10000');
    $rpsSerie = postValue('emit_rps_serie', '1');
    $rpsTipo = postInt('emit_rps_tipo', 1);
    $dataEmissao = postValue('emit_data_emissao', date('Y-m-d\TH:i:s'));

    $prestadorCnpj = preg_replace('/\D+/', '', postValue('emit_prestador_cnpj', '13268582000133'));
    $prestadorIm = postValue('emit_prestador_im', '1820893');

    $tomadorDoc = preg_replace('/\D+/', '', postValue('emit_tomador_doc', '54830608000172'));
    $tomadorNome = postValue('emit_tomador_nome', 'MP REDMALL COMERCIO DE ALIMENTOS LTDA');
    $tomadorEmail = postValue('emit_tomador_email', 'email@exemplo.com.br');
    $tomadorIm = postValue('emit_tomador_im');
    $tomadorEndereco = postValue('emit_tomador_endereco', 'AV DOUTOR SILAS MUNGUBA');
    $tomadorNumero = postValue('emit_tomador_numero', '643');
    $tomadorComplemento = postValue('emit_tomador_complemento', 'Complemento nao informado');
    $tomadorBairro = postValue('emit_tomador_bairro', 'PARANGABA');
    $tomadorCodigoMunicipio = postValue('emit_tomador_codigo_municipio', '2304400');
    $tomadorUf = strtoupper(postValue('emit_tomador_uf', 'CE'));
    $tomadorCep = preg_replace('/\D+/', '', postValue('emit_tomador_cep', '60740005'));
    $tomadorTelefone = preg_replace('/\D+/', '', postValue('emit_tomador_telefone', '99999999999'));

    $servicoCodigo = postValue('emit_servico_codigo', '6202300');
    $servicoItem = postValue('emit_item_lista_servico', '0104');
    $servicoCodigoTributacaoMunicipio = postValue('emit_codigo_tributacao_municipio', '620230001');
    $servicoCodigoNbs = postValue('emit_codigo_nbs', '115022000');
    $servicoDescricao = postValue('emit_servico_descricao', 'Teste de emissao');
    $servicoValor = postFloat('emit_servico_valor', 100.0);
    $servicoAliquota = postFloat('emit_servico_aliquota', 3.0);
    $issRetido = postInt('emit_iss_retido', 2);
    $codigoMunicipio = postValue('emit_codigo_municipio', '2307650');
    $municipioIncidencia = postValue('emit_municipio_incidencia', $codigoMunicipio);
    $exigibilidadeIss = postInt('emit_exigibilidade_iss', 1);

    $naturezaOperacao = postInt('emit_natureza_operacao', 1);
    $regimeEspecialTributacao = postInt('emit_regime_especial_tributacao', 0);
    $optanteSimples = postInt('emit_optante_simples', 2);
    $incentivadorCultural = postInt('emit_incentivador_cultural', 2);
    $controleIbscbs = [
        'fin_nfse' => postValue('emit_controle_finnfse'),
        'ind_final' => postValue('emit_controle_indfinal'),
        'tp_oper' => postValue('emit_controle_tpoper'),
        'tp_ente_gov' => postValue('emit_controle_tpentegov'),
        'ind_dest' => postValue('emit_controle_inddest'),
        'c_ind_op' => postValue('emit_controle_cindop'),
    ];
    $ibscbs = [
        'base_calculo' => postValue('emit_ibscbs_base_calculo'),
        'ibs_uf_aliquota' => postValue('emit_ibscbs_ibsuf_aliquota'),
        'ibs_mun_aliquota' => postValue('emit_ibscbs_ibsmun_aliquota'),
        'cbs_aliquota' => postValue('emit_ibscbs_cbs_aliquota'),
        'ibs_uf_valor' => postValue('emit_ibscbs_ibsuf_valor'),
        'ibs_mun_valor' => postValue('emit_ibscbs_ibsmun_valor'),
        'cbs_valor' => postValue('emit_ibscbs_cbs_valor'),
        'ibs_valor_total' => postValue('emit_ibscbs_ibs_valor_total'),
        'valor_total_com_tributos' => postValue('emit_ibscbs_valor_total_com_tributos'),
        'localidade_incidencia_cod' => postValue('emit_ibscbs_localidade_incidencia_cod'),
        'localidade_incidencia_nome' => postValue('emit_ibscbs_localidade_incidencia_nome'),
    ];
    $dadosDps = [
        'tp_emit' => postValue('emit_dps_tp_emit'),
        'tp_amb' => postValue('emit_dps_tp_amb'),
        'dh_emi' => postValue('emit_dps_dh_emi'),
        'ver_aplic' => postValue('emit_dps_ver_aplic'),
        'c_loc_emi' => postValue('emit_dps_c_loc_emi'),
        'c_loc_prestacao' => postValue('emit_dps_c_loc_prestacao'),
        'c_trib_nac' => postValue('emit_dps_c_trib_nac'),
        'trib_issqn' => postValue('emit_dps_trib_issqn'),
        'tp_ret_issqn' => postValue('emit_dps_tp_ret_issqn'),
        'op_simp_nac' => postValue('emit_dps_op_simp_nac', '1'),
        'reg_esp_trib' => postValue('emit_dps_reg_esp_trib'),
        'reg_ap_trib_sn' => postValue('emit_dps_reg_ap_trib_sn'),
    ];
    $dadosObra = [
        'codigo_obra' => postValue('emit_obra_codigo_obra'),
        'insc_imob_fisc' => postValue('emit_obra_insc_imob_fisc'),
        'endereco_obra' => [
            'cep' => postValue('emit_obra_cep'),
            'logradouro' => postValue('emit_obra_logradouro'),
            'numero' => postValue('emit_obra_numero'),
            'complemento' => postValue('emit_obra_complemento'),
            'bairro' => postValue('emit_obra_bairro'),
        ],
    ];
    $comercioExterior = [
        'md_prestacao' => postValue('emit_comex_md_prestacao'),
        'vinc_prest' => postValue('emit_comex_vinc_prest'),
        'tp_moeda' => postValue('emit_comex_tp_moeda'),
        'v_serv_moeda' => postValue('emit_comex_v_serv_moeda'),
        'mec_af_comex_p' => postValue('emit_comex_mec_af_comex_p'),
        'mec_af_comex_t' => postValue('emit_comex_mec_af_comex_t'),
        'mov_temp_bens' => postValue('emit_comex_mov_temp_bens'),
        'ndi' => postValue('emit_comex_ndi'),
        'nre' => postValue('emit_comex_nre'),
        'mdic' => postValue('emit_comex_mdic'),
        'c_pais_result' => postValue('emit_comex_c_pais_result'),
    ];
    $exigibilidadeSuspensa = [
        'tp_susp' => postValue('emit_susp_tp_susp'),
        'n_processo' => postValue('emit_susp_n_processo'),
    ];
    $beneficioMunicipal = [
        'tp_bm' => postValue('emit_bm_tp_bm'),
        'n_bm' => postValue('emit_bm_n_bm'),
        'v_red_bcbm' => postValue('emit_bm_v_red_bcbm'),
        'p_red_bcbm' => postValue('emit_bm_p_red_bcbm'),
    ];
    $reembolsoRepasse = [
        'tp_reemb_rep_res' => postValue('emit_rr_tp_reemb_rep_res'),
        'x_tp_reemb_rep_res' => postValue('emit_rr_x_tp_reemb_rep_res'),
        'v_reemb_rep_res' => postValue('emit_rr_v_reemb_rep_res'),
    ];
    $destinatario = [
        'cnpj_cpf' => postValue('emit_dest_cnpj_cpf'),
        'nome' => postValue('emit_dest_nome'),
        'logradouro' => postValue('emit_dest_logradouro'),
        'numero' => postValue('emit_dest_numero'),
        'complemento' => postValue('emit_dest_complemento'),
        'bairro' => postValue('emit_dest_bairro'),
        'cidade' => postValue('emit_dest_cidade'),
        'uf' => postValue('emit_dest_uf'),
        'cep' => postValue('emit_dest_cep'),
        'cod_municipio' => postValue('emit_dest_cod_municipio'),
        'cod_pais' => postValue('emit_dest_cod_pais'),
        'cod_postal_ext' => postValue('emit_dest_cod_postal_ext'),
        'nif' => postValue('emit_dest_nif'),
        'email' => postValue('emit_dest_email'),
        'telefone' => postValue('emit_dest_telefone'),
    ];
    $dataCompetencia = postValue('emit_data_competencia');

    $payload = [
        'lote' => [
            'numero_lote' => $numeroLote,
            'quantidade_rps' => 1,
        ],
        'rps' => [
            [
                'identificacao' => [
                    'numero' => $rpsNumero,
                    'serie' => $rpsSerie,
                    'tipo' => $rpsTipo,
                ],
                'data_emissao' => $dataEmissao,
                'natureza_operacao' => $naturezaOperacao,
                'optante_simples_nacional' => $optanteSimples,
                'regime_especial_tributacao' => $regimeEspecialTributacao,
                'incentivador_cultural' => $incentivadorCultural,
                'status' => 1,
                'controle_ibscbs' => $controleIbscbs,
                'ibscbs' => $ibscbs,
                'dados_dps' => $dadosDps,
                'dados_obra' => $dadosObra,
                'comercio_exterior' => $comercioExterior,
                'exigibilidade_suspensa' => $exigibilidadeSuspensa,
                'beneficio_municipal' => $beneficioMunicipal,
                'reembolso_repasse' => $reembolsoRepasse,
                'destinatario' => $destinatario,
                'data_competencia' => $dataCompetencia,
                'prestador' => [
                    'cnpj' => $prestadorCnpj,
                    'inscricao_municipal' => $prestadorIm,
                ],
                'tomador' => [
                    'documento' => $tomadorDoc,
                    'nome_razao_social' => $tomadorNome,
                    'email' => $tomadorEmail,
                    'inscricao_municipal' => $tomadorIm,
                    'endereco' => $tomadorEndereco,
                    'numero' => $tomadorNumero,
                    'complemento' => $tomadorComplemento,
                    'bairro' => $tomadorBairro,
                    'codigo_municipio' => $tomadorCodigoMunicipio,
                    'uf' => $tomadorUf,
                    'cep' => $tomadorCep,
                    'telefone' => $tomadorTelefone,
                ],
                'servico' => [
                    'codigo_cnae' => $servicoCodigo,
                    'item_lista_servico' => $servicoItem,
                    'codigo_tributacao_municipio' => $servicoCodigoTributacaoMunicipio,
                    'codigo_nbs' => $servicoCodigoNbs,
                    'discriminacao' => $servicoDescricao,
                    'valor_servicos' => $servicoValor,
                    'aliquota' => $servicoAliquota,
                    'iss_retido' => $issRetido,
                    'codigo_municipio' => $codigoMunicipio,
                    'municipio_incidencia' => $municipioIncidencia,
                    'exigibilidade_iss' => $exigibilidadeIss,
                ],
            ]
        ],
    ];

    $extraKeys = postArrayValues('emit_extra_key');
    $extraValues = postArrayValues('emit_extra_value');
    $providerExtras = [];
    $max = max(count($extraKeys), count($extraValues));
    for ($i = 0; $i < $max; $i++) {
        $k = trim((string) ($extraKeys[$i] ?? ''));
        $v = trim((string) ($extraValues[$i] ?? ''));
        if ($k !== '') {
            $providerExtras[$k] = $v;
        }
    }
    if ($providerExtras !== []) {
        $payload['provider_extras'] = $providerExtras;
    }

    return $payload;
}

/**
 * @return array<string,mixed>
 */
function buildCancelPayloadFromPost(array $context): array
{
    return [
        'numero_nfse' => postValue('cancel_numero_nfse'),
        'codigo_municipio' => postValue('cancel_codigo_municipio', (string) ($context['ibge'] ?? '')),
        'codigo_cancelamento' => postValue('cancel_codigo_cancelamento', '1'),
        'motivo' => postValue('cancel_motivo'),
        'prestador_cnpj' => preg_replace('/\D+/', '', postValue('cancel_prestador_cnpj')),
        'prestador_im' => postValue('cancel_prestador_im'),
    ];
}

function buildSubstituicaoDadosXmlFromPost(array $context): string
{
    $numero = postValue('subst_numero_nfse');
    $codigoMunicipio = postValue('subst_codigo_municipio', (string) ($context['ibge'] ?? ''));
    $prestadorCnpj = preg_replace('/\D+/', '', postValue('subst_prestador_cnpj'));
    $prestadorIm = postValue('subst_prestador_im');
    $codigoCancelamento = postValue('subst_codigo_cancelamento', '1');
    $motivo = postValue('subst_motivo');
    $rpsNumero = postValue('subst_rps_numero');
    $rpsSerie = postValue('subst_rps_serie', 'UNICA');
    $rpsTipo = postValue('subst_rps_tipo', '1');

    if ($numero === '' || $prestadorCnpj === '' || $prestadorIm === '' || $rpsNumero === '') {
        throw new RuntimeException('Substituicao: informe numero NFS-e, prestador (CNPJ/IM) e RPS numero.');
    }

    $xml = '<SubstituirNfseEnvio xmlns="http://www.betha.com.br/e-nota-contribuinte-ws">'
        . '<Pedido>'
        . '<InfPedidoCancelamento Id="sub' . h($numero) . '">'
        . '<IdentificacaoNfse>'
        . '<Numero>' . h($numero) . '</Numero>'
        . '<CpfCnpj><Cnpj>' . h($prestadorCnpj) . '</Cnpj></CpfCnpj>'
        . '<InscricaoMunicipal>' . h($prestadorIm) . '</InscricaoMunicipal>'
        . ($codigoMunicipio !== '' ? '<CodigoMunicipio>' . h($codigoMunicipio) . '</CodigoMunicipio>' : '')
        . '</IdentificacaoNfse>'
        . '<CodigoCancelamento>' . h($codigoCancelamento) . '</CodigoCancelamento>'
        . ($motivo !== '' ? '<MotivoCancelamento>' . h($motivo) . '</MotivoCancelamento>' : '')
        . '</InfPedidoCancelamento>'
        . '</Pedido>'
        . '<SubstituicaoNfse>'
        . '<IdentificacaoRps>'
        . '<Numero>' . h($rpsNumero) . '</Numero>'
        . '<Serie>' . h($rpsSerie) . '</Serie>'
        . '<Tipo>' . h($rpsTipo) . '</Tipo>'
        . '</IdentificacaoRps>'
        . '</SubstituicaoNfse>'
        . '</SubstituirNfseEnvio>';

    return $xml;
}

/**
 * @return array<string,mixed>
 */
function buildConsultaPayloadFromPost(): array
{
    return [
        'protocolo' => postValue('consulta_protocolo'),
        'prestador_cnpj' => preg_replace('/\D+/', '', postValue('consulta_prestador_cnpj', '13268582000133')),
        'prestador_im' => postValue('consulta_prestador_im', '1820893'),
        'rps_numero' => postValue('consulta_rps_numero', '10000'),
        'rps_serie' => postValue('consulta_rps_serie', '1'),
        'rps_tipo' => postValue('consulta_rps_tipo', '1'),
    ];
}

/**
 * @return array<string,mixed>
 */
function buildSubstituicaoPayloadFromPost(array $context): array
{
    $xmlRaw = postValue('subst_dados_xml');
    if ($xmlRaw === '') {
        $xmlRaw = buildSubstituicaoDadosXmlFromPost($context);
    }
    return ['dados_xml' => $xmlRaw];
}

/**
 * @param mixed $value
 */
function firstUrlFromMixed($value): ?string
{
    if (is_string($value)) {
        $candidate = trim($value);
        if ($candidate !== '' && preg_match('#^https?://#i', $candidate) === 1) {
            return $candidate;
        }
        return null;
    }

    if (!is_array($value)) {
        return null;
    }

    foreach ($value as $item) {
        $found = firstUrlFromMixed($item);
        if ($found !== null) {
            return $found;
        }
    }

    return null;
}

/**
 * @param mixed $value
 */
function firstXmlFromMixed($value): ?string
{
    if (is_string($value)) {
        return normalizeXmlCandidate($value);
    }

    if (!is_array($value)) {
        return null;
    }

    foreach ($value as $item) {
        $found = firstXmlFromMixed($item);
        if ($found !== null) {
            return $found;
        }
    }

    return null;
}

/**
 * @param string $value
 */
function normalizeXmlCandidate(string $value): ?string
{
    $candidate = trim($value);
    if ($candidate === '') {
        return null;
    }

    if (str_starts_with($candidate, '&lt;')) {
        $decoded = html_entity_decode($candidate, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (str_starts_with(trim($decoded), '<')) {
            return $decoded;
        }
    }

    if (str_starts_with($candidate, '<')) {
        return $value;
    }

    $xmlPos = stripos($candidate, '<?xml');
    if ($xmlPos !== false) {
        return substr($candidate, $xmlPos);
    }

    return null;
}

/**
 * @return array<string,mixed>
 */
function loadCatalog(): array
{
    $path = CatalogConfig::defaultCompiledPath();
    if (!is_file($path)) {
        return [];
    }
    $catalog = require $path;
    return is_array($catalog) ? $catalog : [];
}

/**
 * @param array<string,mixed> $catalog
 * @return array<int,array<string,mixed>>
 */
function catalogMunicipios(array $catalog): array
{
    $rows = [];
    foreach (($catalog['municipios'] ?? []) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $ibge = trim((string) ($item['ibge'] ?? ''));
        $nome = trim((string) ($item['nome'] ?? ''));
        $uf = trim((string) ($item['uf'] ?? ''));
        $provedor = trim((string) ($item['provedor'] ?? ''));
        if ($ibge === '' || $nome === '' || $provedor === '') {
            continue;
        }

        $services = is_array($item['services'] ?? null) ? $item['services'] : [];
        $services = array_values(array_unique(array_map('strval', $services)));

        $rows[] = [
            'ibge' => $ibge,
            'nome' => $nome,
            'uf' => $uf,
            'alias' => (string) ($item['alias'] ?? ''),
            'provedor' => $provedor,
            'services' => $services,
        ];
    }

    usort($rows, static function (array $a, array $b): int {
        $cmp = strcmp((string) $a['nome'], (string) $b['nome']);
        if ($cmp !== 0) {
            return $cmp;
        }
        return strcmp((string) $a['uf'], (string) $b['uf']);
    });

    return $rows;
}

$catalog = loadCatalog();
$municipios = catalogMunicipios($catalog);

$selectedPrefeitura = postValue('prefeitura', $municipios[0]['ibge'] ?? '3550308');
$tpAmb = (int) postValue('tpamb', '2');
$action = postValue('action', 'detalhes_municipio');
$defaultServiceByAction = [
    'detalhes_municipio' => 'consultar_nf_se',
    'diagnostico' => 'consultar_nf_se',
    'listar_municipios' => 'consultar_nf_se',
    'consultar_municipal' => 'consultar_nf_se',
    'emitir_municipal' => 'recepcionar',
    'cancelar_municipal' => 'cancelar_nf_se',
    'substituir_municipal' => 'substituir_nf_se',
    'consultar_danfse_municipal' => 'consultar_danfse',
    'consultar_nfse_chave' => 'consultar_nf_se',
    'gerar_danfse_pdf_chave' => 'consultar_nf_se',
    'gerar_danfse_pdf_xml' => 'consultar_nf_se',
];
$service = postValue('service', $defaultServiceByAction[$action] ?? 'consultar_nf_se');
$chave = postValue('chave');
$lookupPrefeitura = postValue('lookup_prefeitura');

$defaultPfxPath = '/app/certificado.pfx';
$pfxPath = postValue('pfx_path', is_file($defaultPfxPath) ? $defaultPfxPath : '');
$pfxPass = postValue('pfx_pass');

$requestDebug = null;
$responseData = null;
$error = null;
$detailsData = null;
$danfsePdfRaw = null;

try {
    $baseConfig = [
        'tpamb' => $tpAmb,
        'prefeitura' => $selectedPrefeitura,
        'catalog_compiled_path' => CatalogConfig::defaultCompiledPath(),
    ];

    $toolsNoCert = new Tools((string) json_encode($baseConfig));
    $detailsData = $toolsNoCert->detalhesMunicipio();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== '') {
        $cert = null;
        $uploadedPfx = $_FILES['pfx_file'] ?? null;

        if (is_array($uploadedPfx) && ($uploadedPfx['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $tmpPath = (string) ($uploadedPfx['tmp_name'] ?? '');
            if ($tmpPath === '' || !is_file($tmpPath)) {
                throw new RuntimeException('Falha ao ler PFX enviado.');
            }
            if ($pfxPass === '') {
                throw new RuntimeException('Informe a senha do PFX enviado.');
            }
            $content = (string) file_get_contents($tmpPath);
            $cert = Certificate::readPfx($content, $pfxPass);
            $pfxPath = '';
        } elseif ($pfxPath !== '' && is_file($pfxPath) && $pfxPass !== '') {
            $content = (string) file_get_contents($pfxPath);
            $cert = Certificate::readPfx($content, $pfxPass);
        }

        $tools = new Tools((string) json_encode($baseConfig), $cert);

        $requestDebug = [
            'action' => $action,
            'prefeitura' => $selectedPrefeitura,
            'tpamb' => $tpAmb,
            'service' => $service,
            'resolved_url' => $service !== '' ? $tools->resolveProviderServiceUrl($service) : null,
            'payload' => null,
            'pfx_source' => $pfxPath !== '' ? $pfxPath : null,
        ];

        switch ($action) {
            case 'detalhes_municipio':
                $responseData = $tools->detalhesMunicipio($lookupPrefeitura !== '' ? $lookupPrefeitura : null);
                break;

            case 'diagnostico':
                $responseData = $tools->diagnosticoMunicipio();
                break;

            case 'listar_municipios':
                $uf = postValue('uf');
                $provedor = postValue('provedor');
                $limit = (int) postValue('limit', '50');
                $responseData = $tools->listarMunicipios($uf !== '' ? $uf : null, $provedor !== '' ? $provedor : null, $limit > 0 ? $limit : 50);
                break;

            case 'consultar_municipal':
                $payload = buildConsultaPayloadFromPost();
                $requestDebug['payload'] = $payload;
                $serviceNormalized = strtolower(trim((string) $service));
                if ($serviceNormalized === '' || !str_starts_with($serviceNormalized, 'consultar_')) {
                    $serviceNormalized = 'consultar_lote';
                    $service = $serviceNormalized;
                    $requestDebug['service_forced'] = $serviceNormalized;
                }
                $responseData = $tools->emitirNfseMunicipal($payload, $service);
                break;

            case 'emitir_municipal':
                $payload = buildEmissionPayloadFromPost(is_array($detailsData) ? $detailsData : []);
                $requestDebug['payload'] = $payload;
                $responseData = $tools->emitirNfseMunicipal($payload, $service);
                break;

            case 'cancelar_municipal':
                $payload = buildCancelPayloadFromPost(is_array($detailsData) ? $detailsData : []);
                $requestDebug['payload'] = $payload;
                $responseData = $tools->cancelarNfseMunicipal($payload, $service);
                break;

            case 'substituir_municipal':
                $payload = buildSubstituicaoPayloadFromPost(is_array($detailsData) ? $detailsData : []);
                $requestDebug['payload'] = $payload;
                $responseData = $tools->substituirNfseMunicipal($payload, $service);
                break;

            case 'consultar_danfse_municipal':
                $responseData = $tools->consultarDanfseMunicipal($service);
                break;

            case 'consultar_nfse_chave':
                if ($chave === '') {
                    throw new RuntimeException('Informe a chave da NFS-e.');
                }
                $requestDebug['payload'] = ['chave' => $chave];
                $responseData = $tools->consultarNfseChave($chave, false);
                break;

            case 'gerar_danfse_pdf_chave':
                if ($chave === '') {
                    throw new RuntimeException('Informe a chave da NFS-e para gerar o DANFSE.');
                }
                $requestDebug['payload'] = ['chave' => $chave];
                $danfsePdfRaw = $tools->gerarDanfsePdfPorChave($chave);
                $responseData = [
                    'ok' => true,
                    'mensagem' => 'DANFSE gerado com sucesso.',
                    'bytes' => strlen($danfsePdfRaw),
                ];
                break;

            case 'gerar_danfse_pdf_xml':
                $xmlManual = postValue('danfse_xml_manual');
                if ($xmlManual === '') {
                    throw new RuntimeException('Informe o XML da NFS-e para gerar o DANFSE.');
                }
                $requestDebug['payload'] = ['xml_manual_bytes' => strlen($xmlManual)];
                $provider = $tools->getProviderProfile()?->provedor();
                $danfsePdfRaw = $tools->gerarDanfsePdf($xmlManual, null, $provider);
                $responseData = [
                    'ok' => true,
                    'mensagem' => 'DANFSE gerado com sucesso a partir do XML manual.',
                    'bytes' => strlen($danfsePdfRaw),
                ];
                break;

            default:
                throw new RuntimeException('Acao invalida.');
        }

        $requestDebug['http'] = [
            'request_body' => $tools->requestBody ?? null,
            'response_head' => $tools->responseHead ?? null,
            'response_body' => $tools->responseBody ?? null,
            'soap_error' => $tools->soaperror ?? null,
            'soap_error_code' => $tools->soaperror_code ?? null,
            'soap_info' => $tools->soapinfo ?? null,
        ];
    }
} catch (Throwable $t) {
    $error = $t->getMessage();
}

$municipiosByIbge = [];
foreach ($municipios as $m) {
    $municipiosByIbge[(string) $m['ibge']] = $m;
}

$providersSet = [];
$ufsSet = [];
$servicesSet = [];
$municipiosComDanfse = 0;
foreach ($municipios as $row) {
    $providersSet[strtolower((string) $row['provedor'])] = true;
    $ufsSet[strtoupper((string) $row['uf'])] = true;

    $services = is_array($row['services']) ? $row['services'] : [];
    foreach ($services as $srv) {
        $servicesSet[strtolower((string) $srv)] = true;
    }

    if (in_array('consultar_danfse', $services, true) || in_array('link_url', $services, true)) {
        $municipiosComDanfse++;
    }
}

$totalMunicipios = count($municipios);
$totalProviders = count($providersSet);
$totalUfs = count($ufsSet);
$totalServicosCatalogo = count($servicesSet);
$selected = $municipiosByIbge[$selectedPrefeitura] ?? null;

$caps = is_array($detailsData['capabilities'] ?? null) ? $detailsData['capabilities'] : [];
$emissaoCaps = is_array($caps['emissao'] ?? null) ? $caps['emissao'] : [];
$consultaCaps = is_array($emissaoCaps['consulta'] ?? null) ? $emissaoCaps['consulta'] : [];
$servicesEffective = is_array($detailsData['services_effective'] ?? null) ? $detailsData['services_effective'] : [];
$servicesMissing = is_array($detailsData['services_missing_in_adapter'] ?? null) ? $detailsData['services_missing_in_adapter'] : [];
$providerRules = is_array($detailsData['provider_rules'] ?? null) ? $detailsData['provider_rules'] : [];
$requiredServices = is_array($providerRules['requiredServices'] ?? null) ? $providerRules['requiredServices'] : [];
$urlMatrix = is_array($detailsData['url_matrix'] ?? null) ? $detailsData['url_matrix'] : [];
$ambienteKey = $tpAmb === 1 ? 'producao' : 'homologacao';
$danfseCatalogUrl = '';
foreach (['consultar_danfse', 'link_url'] as $svc) {
    $candidate = (string) ($urlMatrix[$svc][$ambienteKey] ?? '');
    if ($candidate !== '') {
        $danfseCatalogUrl = $candidate;
        break;
    }
}
$danfsePlaceholders = [];
if ($danfseCatalogUrl !== '') {
    preg_match_all('/%[A-Za-z0-9_:-]+%/', $danfseCatalogUrl, $matches);
    if (isset($matches[0]) && is_array($matches[0])) {
        $danfsePlaceholders = array_values(array_unique(array_map('strval', $matches[0])));
    }
}

$xmlDownloadB64 = null;
$xmlDownloadFilename = null;
$xmlDownloadRaw = null;
if (in_array($action, ['consultar_nfse_chave', 'emitir_municipal'], true)) {
    $xmlDownloadRaw = firstXmlFromMixed($responseData);
}
if ($xmlDownloadRaw === null && $action === 'consultar_municipal' && isset($requestDebug['http']['response_body']) && is_string($requestDebug['http']['response_body'])) {
    $xmlDownloadRaw = normalizeXmlCandidate($requestDebug['http']['response_body']);
}
if ($xmlDownloadRaw === null && $action === 'consultar_municipal') {
    $xmlDownloadRaw = firstXmlFromMixed($responseData);
}
if (is_string($xmlDownloadRaw) && trim($xmlDownloadRaw) !== '') {
    $xmlDownloadB64 = base64_encode($xmlDownloadRaw);
    $xmlBaseName = $chave !== '' ? $chave : ($action === 'emitir_municipal' ? 'emissao' : (($action === 'consultar_municipal' ? $service : 'consulta') . '-' . date('Ymd-His')));
    $xmlDownloadFilename = 'nfse-' . preg_replace('/[^0-9A-Za-z_.-]/', '', $xmlBaseName) . '.xml';
}

$xmlRequestDownloadB64 = null;
$xmlRequestDownloadFilename = null;
if ($action === 'emitir_municipal' && isset($requestDebug['http']['request_body']) && is_string($requestDebug['http']['request_body'])) {
    $requestXml = trim($requestDebug['http']['request_body']);
    if ($requestXml !== '' && str_starts_with($requestXml, '<')) {
        $xmlRequestDownloadB64 = base64_encode($requestDebug['http']['request_body']);
        $xmlRequestDownloadFilename = 'nfse-envio-' . date('Ymd-His') . '.xml';
    }
}

$danfseResponseUrl = null;
if ($action === 'consultar_danfse_municipal') {
    $danfseResponseUrl = firstUrlFromMixed($responseData);
}

$danfsePdfB64 = null;
$danfsePdfFilename = null;
if (in_array($action, ['gerar_danfse_pdf_chave', 'gerar_danfse_pdf_xml'], true) && is_string($danfsePdfRaw) && $danfsePdfRaw !== '') {
    $danfsePdfB64 = base64_encode($danfsePdfRaw);
    $baseName = $chave !== '' ? $chave : ($action === 'gerar_danfse_pdf_xml' ? 'xml-manual-' . date('Ymd-His') : 'nfse');
    $danfsePdfFilename = 'danfse-' . preg_replace('/[^0-9A-Za-z_.-]/', '', $baseName) . '.pdf';
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>nfse-php-brazil | Control Center</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root {
      --ink: #0f172a;
      --muted: #5b6b7d;
      --panel: #ffffff;
      --line: #d9e3ef;
      --bg-1: #edf2f7;
      --bg-2: #f8fafc;
    }
    body {
      font-family: "Space Grotesk", sans-serif;
      color: var(--ink);
      background:
        radial-gradient(1200px 300px at 80% -10%, #cffafe 0%, transparent 55%),
        radial-gradient(1000px 300px at 0% -10%, #dbeafe 0%, transparent 55%),
        linear-gradient(180deg, var(--bg-1), var(--bg-2));
    }
    .hero {
      border: 1px solid var(--line);
      background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%);
      border-radius: 16px;
      padding: 20px;
      box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
    }
    .hero h1 { font-weight: 700; letter-spacing: -0.02em; }
    .kpi {
      border: 1px solid var(--line);
      border-radius: 14px;
      padding: 14px;
      background: var(--panel);
      height: 100%;
      text-align: left;
      width: 100%;
      transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }
    .kpi.kpi-btn {
      cursor: pointer;
    }
    .kpi.kpi-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 20px rgba(15, 23, 42, 0.09);
      border-color: #93c5fd;
    }
    .kpi .label {
      color: var(--muted);
      font-size: .82rem;
      text-transform: uppercase;
      letter-spacing: .04em;
    }
    .kpi .value {
      font-weight: 700;
      font-size: 1.6rem;
      line-height: 1.1;
      margin-top: 4px;
    }
    .kpi .hint { color: var(--muted); font-size: .82rem; margin-top: 4px; }
    .section-card {
      border: 1px solid var(--line);
      border-radius: 14px;
      background: var(--panel);
      box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05);
    }
    .section-title {
      font-size: .9rem;
      font-weight: 700;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: .04em;
    }
    .chip {
      display: inline-block;
      border: 1px solid #cbd5e1;
      border-radius: 999px;
      padding: 4px 10px;
      margin: 0 6px 6px 0;
      font-size: .82rem;
      background: #f8fafc;
    }
    .chip.ok { border-color: #86efac; background: #f0fdf4; color: #166534; }
    .chip.warn { border-color: #fca5a5; background: #fef2f2; color: #991b1b; }
    .bool-badge {
      font-size: .78rem;
      padding: 4px 8px;
      border-radius: 999px;
      font-weight: 600;
    }
    .bool-yes { background: #dcfce7; color: #166534; }
    .bool-no { background: #fee2e2; color: #991b1b; }
    .code-box {
      background: #0b1220;
      color: #dbeafe;
      border-radius: .6rem;
      padding: 1rem;
      white-space: pre-wrap;
      max-height: 360px;
      overflow: auto;
      font-size: .85rem;
    }
    .form-hint { color: var(--muted); font-size: .85rem; }
    .metric-field {
      border: 1px solid var(--line);
      border-radius: 10px;
      padding: 10px;
      background: #f8fafc;
    }
    .emit-block-title {
      margin-top: 10px;
      margin-bottom: 2px;
      font-size: .9rem;
      font-weight: 700;
      color: #0f172a;
    }
    .emit-block-hint {
      margin-bottom: 4px;
      font-size: .8rem;
      color: var(--muted);
    }
    .emit-divider {
      border-top: 1px dashed var(--line);
      margin-top: 8px;
      margin-bottom: 6px;
    }
    .kpi-modal-table td, .kpi-modal-table th {
      white-space: nowrap;
      vertical-align: middle;
    }
    .kpi-modal-table tbody tr:hover {
      background: #f8fafc;
    }
  </style>
</head>
<body>
  <div class="container py-4">
    <section class="hero mb-4">
      <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
        <div>
          <h1 class="h2 mb-1">NFSe Brasil Control Center</h1>
          <div class="text-secondary">Painel para explorar capacidades por município e executar operações com rastreabilidade.</div>
        </div>
        <div class="text-secondary small">Serviços no catálogo: <?= h((string) $totalServicosCatalogo) ?></div>
      </div>
    </section>

    <section class="row g-3 mb-4">
      <div class="col-6 col-lg-3">
        <button type="button" class="kpi kpi-btn" data-kpi="municipios" title="Listar municípios">
          <div class="label">Municípios</div>
          <div class="value"><?= h((string) $totalMunicipios) ?></div>
          <div class="hint">com provedor definido</div>
        </button>
      </div>
      <div class="col-6 col-lg-3">
        <button type="button" class="kpi kpi-btn" data-kpi="provedores" title="Listar provedores">
          <div class="label">Provedores</div>
          <div class="value"><?= h((string) $totalProviders) ?></div>
          <div class="hint">mapeados</div>
        </button>
      </div>
      <div class="col-6 col-lg-3">
        <button type="button" class="kpi kpi-btn" data-kpi="ufs" title="Listar UFs">
          <div class="label">UFs</div>
          <div class="value"><?= h((string) $totalUfs) ?></div>
          <div class="hint">cobertura federativa</div>
        </button>
      </div>
      <div class="col-6 col-lg-3">
        <button type="button" class="kpi kpi-btn" data-kpi="danfse" title="Listar municípios com DANFSe">
          <div class="label">DANFSe</div>
          <div class="value"><?= h((string) $municipiosComDanfse) ?></div>
          <div class="hint">municípios com rota/link</div>
        </button>
      </div>
    </section>

    <?php if ($error): ?>
      <div class="alert alert-danger mb-4"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="row g-3">
      <div class="col-lg-4">
        <div class="section-card p-3 h-100">
          <div class="section-title mb-3">Passo 1 - Contexto</div>
          <div class="mb-3">
            <label class="form-label">Municipio</label>
            <select class="form-select" name="prefeitura" id="prefeitura">
              <?php foreach ($municipios as $m): ?>
                <option value="<?= h((string) $m['ibge']) ?>" <?= (string) $m['ibge'] === $selectedPrefeitura ? 'selected' : '' ?>>
                  <?= h((string) $m['nome']) ?> - <?= h((string) $m['uf']) ?> (<?= h((string) $m['ibge']) ?> | <?= h((string) $m['provedor']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">Ambiente</label>
              <select class="form-select" name="tpamb">
                <option value="2" <?= $tpAmb === 2 ? 'selected' : '' ?>>2 - Homologação</option>
                <option value="1" <?= $tpAmb === 1 ? 'selected' : '' ?>>1 - Producao</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Lookup (opcional)</label>
              <input class="form-control" name="lookup_prefeitura" value="<?= h($lookupPrefeitura) ?>" placeholder="IBGE/alias">
            </div>
          </div>

          <hr>

          <div class="mb-2"><strong>Certificado (quando exigido)</strong></div>
          <div class="form-hint mb-2">Para emissão/cancelamento, informe PFX por caminho ou upload.</div>
          <div class="row g-2">
            <div class="col-12">
              <input class="form-control" autocomplete="off" name="pfx_path" value="<?= h($pfxPath) ?>" placeholder="/app/certificado.pfx">
            </div>
            <div class="col-12">
              <input class="form-control" type="file" name="pfx_file" accept=".pfx">
            </div>
            <div class="col-12">
              <input class="form-control" type="password" name="pfx_pass" value="<?= h($pfxPass) ?>" placeholder="Senha do PFX">
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="section-card p-3 h-100">
          <div class="section-title mb-3">Passo 2 - Operação</div>

          <div class="mb-3">
            <label class="form-label">Operação</label>
            <select class="form-select" name="action" id="action">
              <option value="detalhes_municipio" <?= $action === 'detalhes_municipio' ? 'selected' : '' ?>>detalhesMunicipio (sem payload)</option>
              <option value="diagnostico" <?= $action === 'diagnostico' ? 'selected' : '' ?>>diagnosticoMunicipio (sem payload)</option>
              <option value="listar_municipios" <?= $action === 'listar_municipios' ? 'selected' : '' ?>>listarMunicipios (sem payload)</option>
              <option value="consultar_municipal" <?= $action === 'consultar_municipal' ? 'selected' : '' ?>>consultarMunicipal</option>
              <option value="emitir_municipal" <?= $action === 'emitir_municipal' ? 'selected' : '' ?>>emitirNfseMunicipal</option>
              <option value="cancelar_municipal" <?= $action === 'cancelar_municipal' ? 'selected' : '' ?>>cancelarNfseMunicipal</option>
              <option value="substituir_municipal" <?= $action === 'substituir_municipal' ? 'selected' : '' ?>>substituirNfseMunicipal</option>
              <option value="consultar_danfse_municipal" <?= $action === 'consultar_danfse_municipal' ? 'selected' : '' ?>>consultarDanfseMunicipal</option>
              <option value="consultar_nfse_chave" <?= $action === 'consultar_nfse_chave' ? 'selected' : '' ?>>consultarNfseChave (requer chave)</option>
              <option value="gerar_danfse_pdf_chave" <?= $action === 'gerar_danfse_pdf_chave' ? 'selected' : '' ?>>gerarDanfsePdfPorChave (visualizar PDF)</option>
              <option value="gerar_danfse_pdf_xml" <?= $action === 'gerar_danfse_pdf_xml' ? 'selected' : '' ?>>gerarDanfsePdf (colar XML)</option>
            </select>
            <div id="operation-help" class="form-hint mt-2"></div>
          </div>

          <div class="mb-3">
            <label class="form-label">Serviço</label>
            <select class="form-select" name="service" id="service"></select>
            <div class="form-hint mt-2">Servicos filtrados pelo município selecionado.</div>
          </div>

          <div class="mt-2 d-none" id="group-consulta-form">
            <div class="border rounded p-3 bg-light-subtle">
              <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <strong>Parâmetros de Consulta SpeedGov</strong>
                <span class="form-hint">Necessário para consultar_lote, consultar_situacao e consultar_nfse_rps</span>
              </div>
              <div class="row g-2">
                <div class="col-md-4">
                  <label class="form-label">Protocolo</label>
                  <input class="form-control" name="consulta_protocolo" value="<?= h(postValue('consulta_protocolo')) ?>" placeholder="UUID do protocolo">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Prestador CNPJ</label>
                  <input class="form-control" name="consulta_prestador_cnpj" value="<?= h(postValue('consulta_prestador_cnpj', '13268582000133')) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Prestador IM</label>
                  <input class="form-control" name="consulta_prestador_im" value="<?= h(postValue('consulta_prestador_im', '1820893')) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">RPS numero</label>
                  <input class="form-control" name="consulta_rps_numero" value="<?= h(postValue('consulta_rps_numero', '10000')) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">RPS serie</label>
                  <input class="form-control" name="consulta_rps_serie" value="<?= h(postValue('consulta_rps_serie', '1')) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">RPS tipo</label>
                  <input class="form-control" name="consulta_rps_tipo" value="<?= h(postValue('consulta_rps_tipo', '1')) ?>">
                </div>
              </div>
            </div>
          </div>

          <div class="mt-2 d-none" id="group-emissao-form">
            <div class="border rounded p-3 bg-light-subtle">
              <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <strong>Formulário de Emissão NFS-e</strong>
                <span class="form-hint">Estrutura unificada ABRASF para provedores municipais</span>
              </div>
              <div class="form-hint mb-2">Preencha os campos na ordem: RPS, Prestador, Tomador e Servico. Os nomes seguem a estrutura do XML de envio.</div>
              <?php if ($requiredServices): ?>
                <div class="mb-2">
                  <span class="form-hint">Serviços catalogados para este provedor:</span><br>
                  <?php foreach ($requiredServices as $srv): ?>
                    <span class="chip ok"><?= h((string) $srv) ?></span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <div class="row g-2">
                <div class="col-12 emit-block-title">RPS e Identificação do Lote</div>
                <div class="col-12 emit-block-hint">Obrigatórios: Número do lote, RPS número, série, tipo e data de emissão.</div>
                <div class="col-md-3">
                  <label class="form-label">Lote número</label>
                  <input class="form-control" name="emit_lote_numero" value="<?= h(postValue('emit_lote_numero', '1')) ?>">
                </div>
                <div class="col-md-3">
                  <label class="form-label">RPS número</label>
                  <input class="form-control" name="emit_rps_numero" value="<?= h(postValue('emit_rps_numero', '10000')) ?>">
                </div>
                <div class="col-md-3">
                  <label class="form-label">RPS série</label>
                  <input class="form-control" name="emit_rps_serie" value="<?= h(postValue('emit_rps_serie', '1')) ?>">
                </div>
                <div class="col-md-3">
                  <label class="form-label">RPS tipo</label>
                  <select class="form-select" name="emit_rps_tipo">
                    <option value="1" <?= postValue('emit_rps_tipo', '1') === '1' ? 'selected' : '' ?>>1 - RPS</option>
                    <option value="2" <?= postValue('emit_rps_tipo') === '2' ? 'selected' : '' ?>>2 - Nota Conjugada</option>
                    <option value="3" <?= postValue('emit_rps_tipo') === '3' ? 'selected' : '' ?>>3 - Cupom</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Data emissão</label>
                  <input class="form-control" name="emit_data_emissao" value="<?= h(postValue('emit_data_emissao', date('Y-m-d\TH:i:s'))) ?>" placeholder="YYYY-MM-DDTHH:MM:SS">
                </div>
                <div class="col-12 emit-divider"></div>
                <div class="col-12 emit-block-title">Prestador</div>
                <div class="col-12 emit-block-hint">Obrigatórios: CNPJ e Inscrição Municipal.</div>
                <div class="col-md-4">
                  <label class="form-label">Prestador CNPJ (Prestador/Cnpj)</label>
                  <input class="form-control" name="emit_prestador_cnpj" value="<?= h(postValue('emit_prestador_cnpj', '13268582000133')) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Prestador IM (Prestador/InscricaoMunicipal)</label>
                  <input class="form-control" name="emit_prestador_im" value="<?= h(postValue('emit_prestador_im', '1820893')) ?>">
                </div>
                <div class="col-12 emit-divider"></div>
                <div class="col-12 emit-block-title">Tomador</div>
                <div class="col-12 emit-block-hint">Obrigatórios: Documento e Nome. IM/contato/endereço são opcionais conforme operação.</div>
                <div class="col-md-4">
                  <label class="form-label">Tomador documento (Tomador/CpfCnpj)</label>
                  <input class="form-control" name="emit_tomador_doc" value="<?= h(postValue('emit_tomador_doc', '54830608000172')) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Tomador nome</label>
                  <input class="form-control" name="emit_tomador_nome" value="<?= h(postValue('emit_tomador_nome', 'MP REDMALL COMERCIO DE ALIMENTOS LTDA')) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Tomador email</label>
                  <input class="form-control" name="emit_tomador_email" value="<?= h(postValue('emit_tomador_email', 'email@exemplo.com.br')) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Tomador IM</label>
                  <input class="form-control" name="emit_tomador_im" value="<?= h(postValue('emit_tomador_im')) ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Tomador endereco</label>
                  <input class="form-control" name="emit_tomador_endereco" value="<?= h(postValue('emit_tomador_endereco', 'AV DOUTOR SILAS MUNGUBA')) ?>">
                </div>
                <div class="col-md-2">
                  <label class="form-label">Tomador numero</label>
                  <input class="form-control" name="emit_tomador_numero" value="<?= h(postValue('emit_tomador_numero', '643')) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Tomador complemento</label>
                  <input class="form-control" name="emit_tomador_complemento" value="<?= h(postValue('emit_tomador_complemento', 'Complemento nao informado')) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Tomador bairro</label>
                  <input class="form-control" name="emit_tomador_bairro" value="<?= h(postValue('emit_tomador_bairro', 'PARANGABA')) ?>">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Tomador municipio (IBGE)</label>
                  <input class="form-control" name="emit_tomador_codigo_municipio" value="<?= h(postValue('emit_tomador_codigo_municipio', '2304400')) ?>">
                </div>
                <div class="col-md-2">
                  <label class="form-label">Tomador UF</label>
                  <input class="form-control" name="emit_tomador_uf" value="<?= h(postValue('emit_tomador_uf', 'CE')) ?>" maxlength="2" placeholder="CE">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Tomador CEP</label>
                  <input class="form-control" name="emit_tomador_cep" value="<?= h(postValue('emit_tomador_cep', '60740005')) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Tomador telefone</label>
                  <input class="form-control" name="emit_tomador_telefone" value="<?= h(postValue('emit_tomador_telefone', '99999999999')) ?>">
                </div>
                <div class="col-12 emit-divider"></div>
                <div class="col-12 emit-block-title">Serviço e Valores</div>
                <div class="col-12 emit-block-hint">Obrigatórios: Item da lista, código de tributação municipal, descrição e valor.</div>
                <div class="col-md-4">
                  <label class="form-label">Serviço CNAE</label>
                  <input class="form-control" name="emit_servico_codigo" value="<?= h(postValue('emit_servico_codigo', '6202300')) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Item lista serviço</label>
                  <input class="form-control" name="emit_item_lista_servico" value="<?= h(postValue('emit_item_lista_servico', '0104')) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Município prestação (IBGE)</label>
                  <input class="form-control" name="emit_codigo_municipio" value="<?= h(postValue('emit_codigo_municipio', '2307650')) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Codigo tributacao municipio (9 digitos)</label>
                  <input class="form-control" name="emit_codigo_tributacao_municipio" value="<?= h(postValue('emit_codigo_tributacao_municipio', '620230001')) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Codigo NBS</label>
                  <input class="form-control" name="emit_codigo_nbs" value="<?= h(postValue('emit_codigo_nbs', '115022000')) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Municipio incidencia (IBGE)</label>
                  <input class="form-control" name="emit_municipio_incidencia" value="<?= h(postValue('emit_municipio_incidencia', '2307650')) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Exigibilidade ISS</label>
                  <input class="form-control" name="emit_exigibilidade_iss" value="<?= h(postValue('emit_exigibilidade_iss', '1')) ?>" maxlength="1">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Descrição serviço</label>
                  <input class="form-control" name="emit_servico_descricao" value="<?= h(postValue('emit_servico_descricao', 'Teste de emissao')) ?>">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Valor serviço</label>
                  <input class="form-control" name="emit_servico_valor" value="<?= h(postValue('emit_servico_valor', '100.00')) ?>">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Alíquota</label>
                  <input class="form-control" name="emit_servico_aliquota" value="<?= h(postValue('emit_servico_aliquota', '3.00')) ?>" placeholder="0.02">
                </div>
                <div class="col-md-3">
                  <label class="form-label">ISS retido</label>
                  <select class="form-select" name="emit_iss_retido">
                    <option value="2" <?= postValue('emit_iss_retido', '2') === '2' ? 'selected' : '' ?>>2 - Não</option>
                    <option value="1" <?= postValue('emit_iss_retido') === '1' ? 'selected' : '' ?>>1 - Sim</option>
                  </select>
                </div>
                <div class="col-12 emit-divider"></div>
                <div class="col-12 emit-block-title">Tributação ABRASF</div>
                <div class="col-12 emit-block-hint">Natureza, Regime, Simples e Incentivo Cultural. Se Regime Especial for 0 e informado, será gerado no XML.</div>
                <div class="col-md-3">
                  <label class="form-label">Natureza operação</label>
                  <input class="form-control" name="emit_natureza_operacao" value="<?= h(postValue('emit_natureza_operacao', '1')) ?>">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Regime especial tributação</label>
                  <input class="form-control" name="emit_regime_especial_tributacao" value="<?= h(postValue('emit_regime_especial_tributacao', '0')) ?>">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Optante SN</label>
                  <select class="form-select" name="emit_optante_simples">
                    <option value="1" <?= postValue('emit_optante_simples') === '1' ? 'selected' : '' ?>>1 - Sim</option>
                    <option value="2" <?= postValue('emit_optante_simples', '2') === '2' ? 'selected' : '' ?>>2 - Não</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Incentivador cultural</label>
                  <select class="form-select" name="emit_incentivador_cultural">
                    <option value="2" <?= postValue('emit_incentivador_cultural', '2') === '2' ? 'selected' : '' ?>>2 - Não</option>
                    <option value="1" <?= postValue('emit_incentivador_cultural') === '1' ? 'selected' : '' ?>>1 - Sim</option>
                  </select>
                </div>
                <div class="col-12 emit-divider"></div>
                <div class="col-12 emit-block-title">Controle IBSCBS</div>
                <div class="col-12 emit-block-hint">Bloco opcional da reforma tributária. Só gere quando for utilizado na sua operação.</div>
                <div class="col-md-2"><label class="form-label">FinNFSe</label><input class="form-control" name="emit_controle_finnfse" value="<?= h(postValue('emit_controle_finnfse')) ?>"></div>
                <div class="col-md-2"><label class="form-label">IndFinal</label><input class="form-control" name="emit_controle_indfinal" value="<?= h(postValue('emit_controle_indfinal')) ?>"></div>
                <div class="col-md-2"><label class="form-label">TpOper</label><input class="form-control" name="emit_controle_tpoper" value="<?= h(postValue('emit_controle_tpoper')) ?>"></div>
                <div class="col-md-2"><label class="form-label">TpEnteGov</label><input class="form-control" name="emit_controle_tpentegov" value="<?= h(postValue('emit_controle_tpentegov')) ?>"></div>
                <div class="col-md-2"><label class="form-label">IndDest</label><input class="form-control" name="emit_controle_inddest" value="<?= h(postValue('emit_controle_inddest')) ?>"></div>
                <div class="col-md-2"><label class="form-label">CIndOp</label><input class="form-control" name="emit_controle_cindop" value="<?= h(postValue('emit_controle_cindop')) ?>"></div>
                <div class="col-12 emit-block-title">IBSCBS</div>
                <div class="col-md-3"><label class="form-label">Base Calculo</label><input class="form-control" name="emit_ibscbs_base_calculo" value="<?= h(postValue('emit_ibscbs_base_calculo')) ?>"></div>
                <div class="col-md-3"><label class="form-label">IBS UF Aliquota</label><input class="form-control" name="emit_ibscbs_ibsuf_aliquota" value="<?= h(postValue('emit_ibscbs_ibsuf_aliquota')) ?>"></div>
                <div class="col-md-3"><label class="form-label">IBS Mun Aliquota</label><input class="form-control" name="emit_ibscbs_ibsmun_aliquota" value="<?= h(postValue('emit_ibscbs_ibsmun_aliquota')) ?>"></div>
                <div class="col-md-3"><label class="form-label">CBS Aliquota</label><input class="form-control" name="emit_ibscbs_cbs_aliquota" value="<?= h(postValue('emit_ibscbs_cbs_aliquota')) ?>"></div>
                <div class="col-md-3"><label class="form-label">IBS UF Valor</label><input class="form-control" name="emit_ibscbs_ibsuf_valor" value="<?= h(postValue('emit_ibscbs_ibsuf_valor')) ?>"></div>
                <div class="col-md-3"><label class="form-label">IBS Mun Valor</label><input class="form-control" name="emit_ibscbs_ibsmun_valor" value="<?= h(postValue('emit_ibscbs_ibsmun_valor')) ?>"></div>
                <div class="col-md-3"><label class="form-label">CBS Valor</label><input class="form-control" name="emit_ibscbs_cbs_valor" value="<?= h(postValue('emit_ibscbs_cbs_valor')) ?>"></div>
                <div class="col-md-3"><label class="form-label">IBS Valor Total</label><input class="form-control" name="emit_ibscbs_ibs_valor_total" value="<?= h(postValue('emit_ibscbs_ibs_valor_total')) ?>"></div>
                <div class="col-md-4"><label class="form-label">Valor Total com Tributos</label><input class="form-control" name="emit_ibscbs_valor_total_com_tributos" value="<?= h(postValue('emit_ibscbs_valor_total_com_tributos')) ?>"></div>
                <div class="col-md-4"><label class="form-label">Localidade Incidencia Cod</label><input class="form-control" name="emit_ibscbs_localidade_incidencia_cod" value="<?= h(postValue('emit_ibscbs_localidade_incidencia_cod')) ?>"></div>
                <div class="col-md-4"><label class="form-label">Localidade Incidencia Nome</label><input class="form-control" name="emit_ibscbs_localidade_incidencia_nome" value="<?= h(postValue('emit_ibscbs_localidade_incidencia_nome')) ?>"></div>
                <div class="col-12 emit-divider"></div>
                <div class="col-12 emit-block-title">DadosDPS</div>
                <div class="col-12 emit-block-hint">Bloco opcional. Se todos os campos ficarem vazios, o bloco não será gerado no XML. Padrão OpSimpNac = 1.</div>
                <div class="col-md-2"><input class="form-control" name="emit_dps_tp_emit" value="<?= h(postValue('emit_dps_tp_emit')) ?>" placeholder="TpEmit"></div>
                <div class="col-md-2"><input class="form-control" name="emit_dps_tp_amb" value="<?= h(postValue('emit_dps_tp_amb')) ?>" placeholder="TpAmb"></div>
                <div class="col-md-4"><input class="form-control" name="emit_dps_dh_emi" value="<?= h(postValue('emit_dps_dh_emi')) ?>" placeholder="DhEmi"></div>
                <div class="col-md-4"><input class="form-control" name="emit_dps_ver_aplic" value="<?= h(postValue('emit_dps_ver_aplic')) ?>" placeholder="VerAplic"></div>
                <div class="col-md-3"><input class="form-control" name="emit_dps_c_loc_emi" value="<?= h(postValue('emit_dps_c_loc_emi')) ?>" placeholder="CLocEmi"></div>
                <div class="col-md-3"><input class="form-control" name="emit_dps_c_loc_prestacao" value="<?= h(postValue('emit_dps_c_loc_prestacao')) ?>" placeholder="CLocPrestacao"></div>
                <div class="col-md-2"><input class="form-control" name="emit_dps_c_trib_nac" value="<?= h(postValue('emit_dps_c_trib_nac')) ?>" placeholder="CTribNac"></div>
                <div class="col-md-2"><input class="form-control" name="emit_dps_trib_issqn" value="<?= h(postValue('emit_dps_trib_issqn')) ?>" placeholder="TribIssqn"></div>
                <div class="col-md-2"><input class="form-control" name="emit_dps_tp_ret_issqn" value="<?= h(postValue('emit_dps_tp_ret_issqn')) ?>" placeholder="TpRetIssqn"></div>
                <div class="col-md-2"><input class="form-control" name="emit_dps_op_simp_nac" value="<?= h(postValue('emit_dps_op_simp_nac', '1')) ?>" placeholder="OpSimpNac"></div>
                <div class="col-md-2"><input class="form-control" name="emit_dps_reg_esp_trib" value="<?= h(postValue('emit_dps_reg_esp_trib')) ?>" placeholder="RegEspTrib"></div>
                <div class="col-md-4"><input class="form-control" name="emit_dps_reg_ap_trib_sn" value="<?= h(postValue('emit_dps_reg_ap_trib_sn')) ?>" placeholder="RegApTribSN"></div>
                <div class="col-12 emit-divider"></div>
                <div class="col-12 emit-block-title">DadosObra</div>
                <div class="col-12 emit-block-hint">Opcional. Use para serviços de construção civil.</div>
                <div class="col-md-3"><input class="form-control" name="emit_obra_codigo_obra" value="<?= h(postValue('emit_obra_codigo_obra')) ?>" placeholder="CodigoObra"></div>
                <div class="col-md-3"><input class="form-control" name="emit_obra_insc_imob_fisc" value="<?= h(postValue('emit_obra_insc_imob_fisc')) ?>" placeholder="InscImobFisc"></div>
                <div class="col-md-2"><input class="form-control" name="emit_obra_cep" value="<?= h(postValue('emit_obra_cep')) ?>" placeholder="Obra CEP"></div>
                <div class="col-md-4"><input class="form-control" name="emit_obra_logradouro" value="<?= h(postValue('emit_obra_logradouro')) ?>" placeholder="Obra Logradouro"></div>
                <div class="col-md-2"><input class="form-control" name="emit_obra_numero" value="<?= h(postValue('emit_obra_numero')) ?>" placeholder="Obra Numero"></div>
                <div class="col-md-4"><input class="form-control" name="emit_obra_complemento" value="<?= h(postValue('emit_obra_complemento')) ?>" placeholder="Obra Complemento"></div>
                <div class="col-md-3"><input class="form-control" name="emit_obra_bairro" value="<?= h(postValue('emit_obra_bairro')) ?>" placeholder="Obra Bairro"></div>
                <div class="col-12 emit-divider"></div>
                <div class="col-12 emit-block-title">ExigibilidadeSuspensa</div>
                <div class="col-md-3"><input class="form-control" name="emit_susp_tp_susp" value="<?= h(postValue('emit_susp_tp_susp')) ?>" placeholder="TpSusp"></div>
                <div class="col-md-5"><input class="form-control" name="emit_susp_n_processo" value="<?= h(postValue('emit_susp_n_processo')) ?>" placeholder="NProcesso"></div>
                <div class="col-12 emit-divider"></div>
                <div class="col-12 emit-block-title">BenefícioMunicipal</div>
                <div class="col-md-2"><input class="form-control" name="emit_bm_tp_bm" value="<?= h(postValue('emit_bm_tp_bm')) ?>" placeholder="TpBM"></div>
                <div class="col-md-4"><input class="form-control" name="emit_bm_n_bm" value="<?= h(postValue('emit_bm_n_bm')) ?>" placeholder="NBM"></div>
                <div class="col-md-3"><input class="form-control" name="emit_bm_v_red_bcbm" value="<?= h(postValue('emit_bm_v_red_bcbm')) ?>" placeholder="VRedBCBM"></div>
                <div class="col-md-3"><input class="form-control" name="emit_bm_p_red_bcbm" value="<?= h(postValue('emit_bm_p_red_bcbm')) ?>" placeholder="PRedBCBM"></div>
                <div class="col-12 emit-divider"></div>
                <div class="col-12 emit-block-title">ReembolsoRepasse</div>
                <div class="col-md-2"><input class="form-control" name="emit_rr_tp_reemb_rep_res" value="<?= h(postValue('emit_rr_tp_reemb_rep_res')) ?>" placeholder="TpReembRepRes"></div>
                <div class="col-md-6"><input class="form-control" name="emit_rr_x_tp_reemb_rep_res" value="<?= h(postValue('emit_rr_x_tp_reemb_rep_res')) ?>" placeholder="XTpReembRepRes"></div>
                <div class="col-md-4"><input class="form-control" name="emit_rr_v_reemb_rep_res" value="<?= h(postValue('emit_rr_v_reemb_rep_res')) ?>" placeholder="VReembRepRes"></div>
                <div class="col-12 emit-divider"></div>
                <div class="col-12 emit-block-title">Destinatário</div>
                <div class="col-md-3"><input class="form-control" name="emit_dest_cnpj_cpf" value="<?= h(postValue('emit_dest_cnpj_cpf')) ?>" placeholder="Dest CnpjCpf"></div>
                <div class="col-md-5"><input class="form-control" name="emit_dest_nome" value="<?= h(postValue('emit_dest_nome')) ?>" placeholder="Dest Nome"></div>
                <div class="col-md-4"><input class="form-control" name="emit_dest_email" value="<?= h(postValue('emit_dest_email')) ?>" placeholder="Dest Email"></div>
                <div class="col-md-4"><input class="form-control" name="emit_dest_logradouro" value="<?= h(postValue('emit_dest_logradouro')) ?>" placeholder="Dest Logradouro"></div>
                <div class="col-md-2"><input class="form-control" name="emit_dest_numero" value="<?= h(postValue('emit_dest_numero')) ?>" placeholder="Dest Numero"></div>
                <div class="col-md-3"><input class="form-control" name="emit_dest_complemento" value="<?= h(postValue('emit_dest_complemento')) ?>" placeholder="Dest Complemento"></div>
                <div class="col-md-3"><input class="form-control" name="emit_dest_bairro" value="<?= h(postValue('emit_dest_bairro')) ?>" placeholder="Dest Bairro"></div>
                <div class="col-md-3"><input class="form-control" name="emit_dest_cidade" value="<?= h(postValue('emit_dest_cidade')) ?>" placeholder="Dest Cidade"></div>
                <div class="col-md-1"><input class="form-control" name="emit_dest_uf" value="<?= h(postValue('emit_dest_uf')) ?>" placeholder="UF"></div>
                <div class="col-md-2"><input class="form-control" name="emit_dest_cep" value="<?= h(postValue('emit_dest_cep')) ?>" placeholder="Dest CEP"></div>
                <div class="col-md-3"><input class="form-control" name="emit_dest_cod_municipio" value="<?= h(postValue('emit_dest_cod_municipio')) ?>" placeholder="Dest CodMunicipio"></div>
                <div class="col-md-2"><input class="form-control" name="emit_dest_cod_pais" value="<?= h(postValue('emit_dest_cod_pais')) ?>" placeholder="Dest CodPais"></div>
                <div class="col-md-2"><input class="form-control" name="emit_dest_cod_postal_ext" value="<?= h(postValue('emit_dest_cod_postal_ext')) ?>" placeholder="Dest CodPostalExt"></div>
                <div class="col-md-3"><input class="form-control" name="emit_dest_nif" value="<?= h(postValue('emit_dest_nif')) ?>" placeholder="Dest NIF"></div>
                <div class="col-md-2"><input class="form-control" name="emit_dest_telefone" value="<?= h(postValue('emit_dest_telefone')) ?>" placeholder="Dest Telefone"></div>
                <div class="col-md-3"><input class="form-control" name="emit_data_competencia" value="<?= h(postValue('emit_data_competencia')) ?>" placeholder="DataCompetencia"></div>
                <div class="col-12 emit-divider"></div>
                <div class="col-12 emit-block-title">ComércioExterior</div>
                <div class="col-md-2"><input class="form-control" name="emit_comex_md_prestacao" value="<?= h(postValue('emit_comex_md_prestacao')) ?>" placeholder="MdPrestacao"></div>
                <div class="col-md-2"><input class="form-control" name="emit_comex_vinc_prest" value="<?= h(postValue('emit_comex_vinc_prest')) ?>" placeholder="VincPrest"></div>
                <div class="col-md-2"><input class="form-control" name="emit_comex_tp_moeda" value="<?= h(postValue('emit_comex_tp_moeda')) ?>" placeholder="TpMoeda"></div>
                <div class="col-md-3"><input class="form-control" name="emit_comex_v_serv_moeda" value="<?= h(postValue('emit_comex_v_serv_moeda')) ?>" placeholder="VServMoeda"></div>
                <div class="col-md-3"><input class="form-control" name="emit_comex_mov_temp_bens" value="<?= h(postValue('emit_comex_mov_temp_bens')) ?>" placeholder="MovTempBens"></div>
                <div class="col-md-3"><input class="form-control" name="emit_comex_mec_af_comex_p" value="<?= h(postValue('emit_comex_mec_af_comex_p')) ?>" placeholder="MecAFComexP"></div>
                <div class="col-md-3"><input class="form-control" name="emit_comex_mec_af_comex_t" value="<?= h(postValue('emit_comex_mec_af_comex_t')) ?>" placeholder="MecAFComexT"></div>
                <div class="col-md-2"><input class="form-control" name="emit_comex_ndi" value="<?= h(postValue('emit_comex_ndi')) ?>" placeholder="NDI"></div>
                <div class="col-md-2"><input class="form-control" name="emit_comex_nre" value="<?= h(postValue('emit_comex_nre')) ?>" placeholder="NRE"></div>
                <div class="col-md-2"><input class="form-control" name="emit_comex_mdic" value="<?= h(postValue('emit_comex_mdic')) ?>" placeholder="MDIC"></div>
                <div class="col-md-2"><input class="form-control" name="emit_comex_c_pais_result" value="<?= h(postValue('emit_comex_c_pais_result')) ?>" placeholder="CPaisResult"></div>
              </div>

              <div class="mt-3">
                <label class="form-label">Extras do provedor (campos opcionais)</label>
                <div class="form-hint mb-2">Preencha apenas se o provedor exigir campos adicionais.</div>
                <?php
                  $extraKeysPosted = postArrayValues('emit_extra_key');
                  $extraValuesPosted = postArrayValues('emit_extra_value');
                  $extraRows = max(5, count($extraKeysPosted), count($extraValuesPosted));
                ?>
                <div class="row g-2">
                  <?php for ($i = 0; $i < $extraRows; $i++): ?>
                    <div class="col-md-5">
                      <input class="form-control" name="emit_extra_key[]" value="<?= h((string) ($extraKeysPosted[$i] ?? '')) ?>" placeholder="Nome do campo (ex: token, inscrição, série)">
                    </div>
                    <div class="col-md-7">
                      <input class="form-control" name="emit_extra_value[]" value="<?= h((string) ($extraValuesPosted[$i] ?? '')) ?>" placeholder="Valor do campo">
                    </div>
                  <?php endfor; ?>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-2 d-none" id="group-cancel-form">
            <div class="border rounded p-3 bg-light-subtle">
              <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <strong>Formulario de Cancelamento NFS-e</strong>
                <span class="form-hint">Campos principais para fluxo municipal/Betha</span>
              </div>
              <div class="row g-2">
                <div class="col-md-4">
                  <label class="form-label">Numero NFS-e</label>
                  <input class="form-control" name="cancel_numero_nfse" value="<?= h(postValue('cancel_numero_nfse')) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Codigo municipio (IBGE)</label>
                  <input class="form-control" name="cancel_codigo_municipio" value="<?= h(postValue('cancel_codigo_municipio', (string) ($selected['ibge'] ?? ''))) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Codigo cancelamento</label>
                  <input class="form-control" name="cancel_codigo_cancelamento" value="<?= h(postValue('cancel_codigo_cancelamento', '1')) ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Prestador CNPJ</label>
                  <input class="form-control" name="cancel_prestador_cnpj" value="<?= h(postValue('cancel_prestador_cnpj')) ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Prestador IM</label>
                  <input class="form-control" name="cancel_prestador_im" value="<?= h(postValue('cancel_prestador_im')) ?>">
                </div>
                <div class="col-12">
                  <label class="form-label">Motivo</label>
                  <input class="form-control" name="cancel_motivo" value="<?= h(postValue('cancel_motivo')) ?>">
                </div>
              </div>
            </div>
          </div>

          <div class="mt-2 d-none" id="group-subst-form">
            <div class="border rounded p-3 bg-light-subtle">
              <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <strong>Formulario de Substituicao NFS-e</strong>
                <span class="form-hint">Fluxo Betha com XML gerado pelo formulario</span>
              </div>
              <div class="row g-2">
                <div class="col-md-4">
                  <label class="form-label">Numero NFS-e antiga</label>
                  <input class="form-control" name="subst_numero_nfse" value="<?= h(postValue('subst_numero_nfse')) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Codigo municipio (IBGE)</label>
                  <input class="form-control" name="subst_codigo_municipio" value="<?= h(postValue('subst_codigo_municipio', (string) ($selected['ibge'] ?? ''))) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Codigo cancelamento</label>
                  <input class="form-control" name="subst_codigo_cancelamento" value="<?= h(postValue('subst_codigo_cancelamento', '1')) ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Prestador CNPJ</label>
                  <input class="form-control" name="subst_prestador_cnpj" value="<?= h(postValue('subst_prestador_cnpj')) ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Prestador IM</label>
                  <input class="form-control" name="subst_prestador_im" value="<?= h(postValue('subst_prestador_im')) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">RPS numero (novo)</label>
                  <input class="form-control" name="subst_rps_numero" value="<?= h(postValue('subst_rps_numero')) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">RPS serie</label>
                  <input class="form-control" name="subst_rps_serie" value="<?= h(postValue('subst_rps_serie', 'UNICA')) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">RPS tipo</label>
                  <select class="form-select" name="subst_rps_tipo">
                    <option value="1" <?= postValue('subst_rps_tipo', '1') === '1' ? 'selected' : '' ?>>1 - RPS</option>
                    <option value="2" <?= postValue('subst_rps_tipo') === '2' ? 'selected' : '' ?>>2 - Nota Conjugada</option>
                    <option value="3" <?= postValue('subst_rps_tipo') === '3' ? 'selected' : '' ?>>3 - Cupom</option>
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label">Motivo</label>
                  <input class="form-control" name="subst_motivo" value="<?= h(postValue('subst_motivo')) ?>">
                </div>
              </div>
              <input type="hidden" name="subst_dados_xml" value="">
            </div>
          </div>

          <div class="row g-2">
            <div class="col-md-4 d-none" id="group-uf">
              <label class="form-label">UF</label>
              <input class="form-control" name="uf" value="<?= h(postValue('uf', 'SP')) ?>">
            </div>
            <div class="col-md-4 d-none" id="group-provedor">
              <label class="form-label">Provedor</label>
              <input class="form-control" name="provedor" value="<?= h(postValue('provedor')) ?>">
            </div>
            <div class="col-md-4 d-none" id="group-limit">
              <label class="form-label">Limite</label>
              <input class="form-control" name="limit" value="<?= h(postValue('limit', '50')) ?>">
            </div>
          </div>

          <div class="mt-2 d-none" id="group-chave">
            <label class="form-label">Chave NFS-e</label>
            <input class="form-control" name="chave" value="<?= h($chave) ?>">
          </div>

          <div class="mt-2 d-none" id="group-danfse-xml-manual">
            <label class="form-label">XML da NFS-e (manual)</label>
            <textarea class="form-control" name="danfse_xml_manual" rows="8" placeholder="Cole aqui o XML completo da NFS-e..."><?= h(postValue('danfse_xml_manual')) ?></textarea>
          </div>

          <div class="mt-3">
            <button class="btn btn-success px-4" type="submit">Executar</button>
          </div>
        </div>
      </div>
    </form>

    <section class="row g-3 mt-1">
      <div class="col-lg-7">
        <div class="section-card p-3 h-100">
          <div class="section-title mb-3">Capacidades do Município</div>
          <?php if ($selected): ?>
            <div class="row g-2 mb-3">
              <div class="col-md-6"><strong>Município:</strong> <?= h((string) $selected['nome']) ?> - <?= h((string) $selected['uf']) ?></div>
              <div class="col-md-6"><strong>Provedor:</strong> <?= h((string) $selected['provedor']) ?></div>
              <div class="col-md-6"><strong>IBGE:</strong> <?= h((string) $selected['ibge']) ?></div>
              <div class="col-md-6"><strong>Alias:</strong> <?= h((string) $selected['alias']) ?></div>
            </div>
          <?php endif; ?>

          <div class="row g-2 mb-3">
            <div class="col-md-4"><span class="form-hint">Integração</span><div><?= h((string) ($caps['integration_mode'] ?? '-')) ?></div></div>
            <div class="col-md-4"><span class="form-hint">Transporte</span><div><?= h((string) ($caps['transport'] ?? '-')) ?></div></div>
            <div class="col-md-4"><span class="form-hint">Serviços efetivos</span><div><?= h((string) count($servicesEffective)) ?></div></div>
          </div>

          <div class="metric-field mb-3">
            <div class="form-hint mb-1">Acesso DANFSe no catálogo (<?= h($ambienteKey) ?>)</div>
            <?php if ($danfseCatalogUrl !== ''): ?>
              <div class="input-group">
                <input type="text" class="form-control" id="danfse-catalog-url" value="<?= h($danfseCatalogUrl) ?>" readonly>
                <button type="button" class="btn btn-outline-primary" id="btn-open-danfse-catalog">Abrir</button>
                <button type="button" class="btn btn-outline-secondary" id="btn-copy-danfse-catalog">Copiar</button>
              </div>
              <?php if ($danfsePlaceholders): ?>
                <div class="mt-3">
                  <div class="form-hint mb-2">Preencha os placeholders para montar o link final do DANFSe</div>
                  <div class="row g-2" id="danfse-placeholder-fields"></div>
                  <div class="input-group mt-2">
                    <input type="text" class="form-control" id="danfse-final-url" placeholder="URL final gerada" readonly>
                    <button type="button" class="btn btn-outline-primary" id="btn-open-danfse-final">Abrir final</button>
                    <button type="button" class="btn btn-outline-secondary" id="btn-copy-danfse-final">Copiar final</button>
                  </div>
                </div>
              <?php endif; ?>
            <?php else: ?>
              <span class="chip warn">Não disponível no catálogo atual</span>
            <?php endif; ?>
          </div>

          <div class="row g-2 mb-2">
            <?php
            $boolMap = [
              'Emissão unitário' => (bool) ($emissaoCaps['unitario'] ?? false),
              'Lote assíncrono' => (bool) ($emissaoCaps['lote_assincrono'] ?? false),
              'Lote síncrono' => (bool) ($emissaoCaps['lote_sincrono'] ?? false),
              'Substituição' => (bool) ($emissaoCaps['substituicao'] ?? false),
              'Cancelamento' => (bool) ($emissaoCaps['cancelamento'] ?? false),
              'DANFSe' => (bool) ($emissaoCaps['danfse'] ?? false),
            ];
            foreach ($boolMap as $label => $flag):
            ?>
              <div class="col-md-4">
                <div class="border rounded p-2 d-flex justify-content-between align-items-center">
                  <span><?= h($label) ?></span>
                  <span class="bool-badge <?= $flag ? 'bool-yes' : 'bool-no' ?>"><?= $flag ? 'SIM' : 'NAO' ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="mt-3">
            <div class="form-hint mb-1">Consultas suportadas</div>
            <?php
            $consultaMap = [
              'NFS-e' => (bool) ($consultaCaps['nfse'] ?? false),
              'RPS' => (bool) ($consultaCaps['rps'] ?? false),
              'Lote' => (bool) ($consultaCaps['lote'] ?? false),
              'Situacao' => (bool) ($consultaCaps['situacao'] ?? false),
              'Eventos' => (bool) ($consultaCaps['eventos'] ?? false),
              'DFe' => (bool) ($consultaCaps['dfe'] ?? false),
            ];
            foreach ($consultaMap as $label => $flag):
            ?>
              <span class="chip <?= $flag ? 'ok' : 'warn' ?>"><?= h($label) ?>: <?= $flag ? 'SIM' : 'NAO' ?></span>
            <?php endforeach; ?>
          </div>

          <div class="mt-3">
            <div class="form-hint mb-1">Serviços efetivos</div>
            <?php if ($servicesEffective): foreach ($servicesEffective as $srv): ?>
              <span class="chip ok"><?= h((string) $srv) ?></span>
            <?php endforeach; else: ?>
              <span class="text-secondary">Nenhum serviço efetivo.</span>
            <?php endif; ?>
          </div>

          <?php if ($servicesMissing): ?>
            <div class="mt-3">
              <div class="form-hint mb-1">Serviços no catálogo sem suporte de adapter</div>
              <?php foreach ($servicesMissing as $srv): ?>
                <span class="chip warn"><?= h((string) $srv) ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="section-card p-3 h-100">
          <div class="section-title mb-3">Resumo Técnico</div>
          <?php if ($xmlDownloadB64 !== null && $xmlDownloadFilename !== null): ?>
            <div class="alert alert-success py-2 px-3 mb-3">
              XML da NFS-e pronto para download<?= $action === 'emitir_municipal' ? ' (retorno da emissao)' : ($action === 'consultar_municipal' ? ' (retorno da consulta)' : '') ?>.
              <button type="button" class="btn btn-sm btn-success ms-2" id="btn-download-xml" data-b64="<?= h($xmlDownloadB64) ?>" data-filename="<?= h($xmlDownloadFilename) ?>">Baixar XML</button>
            </div>
          <?php endif; ?>
          <?php if ($xmlRequestDownloadB64 !== null && $xmlRequestDownloadFilename !== null): ?>
            <div class="alert alert-warning py-2 px-3 mb-3">
              XML de envio da emissão pronto para download.
              <button type="button" class="btn btn-sm btn-warning ms-2" id="btn-download-xml-request" data-b64="<?= h($xmlRequestDownloadB64) ?>" data-filename="<?= h($xmlRequestDownloadFilename) ?>">Baixar XML enviado</button>
            </div>
          <?php endif; ?>
          <?php if ($danfseResponseUrl !== null): ?>
            <div class="alert alert-info py-2 px-3 mb-3">
              Link DANFSe retornado pela consulta.
              <a class="btn btn-sm btn-primary ms-2" href="<?= h($danfseResponseUrl) ?>" target="_blank" rel="noopener noreferrer">Abrir DANFSe</a>
            </div>
          <?php endif; ?>
          <?php if ($danfsePdfB64 !== null && $danfsePdfFilename !== null): ?>
            <div class="alert alert-success py-2 px-3 mb-3">
              DANFSE em PDF gerado com sucesso.
              <button type="button" class="btn btn-sm btn-success ms-2" id="btn-download-danfse-pdf" data-b64="<?= h($danfsePdfB64) ?>" data-filename="<?= h($danfsePdfFilename) ?>">Baixar PDF</button>
              <button type="button" class="btn btn-sm btn-outline-primary ms-2" id="btn-open-danfse-pdf" data-b64="<?= h($danfsePdfB64) ?>">Abrir em nova aba</button>
            </div>
            <div class="mb-3">
              <iframe
                title="Pré-visualização do DANFSE"
                src="data:application/pdf;base64,<?= h($danfsePdfB64) ?>"
                style="width:100%;height:520px;border:1px solid #d9e3ef;border-radius:.6rem;background:#fff;"></iframe>
            </div>
          <?php endif; ?>
          <div class="form-hint mb-1">Requisição enviada</div>
          <div class="code-box mb-3"><?= h(json_encode($requestDebug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'Sem execucao ainda.') ?></div>
          <div class="form-hint mb-1">Resposta</div>
          <div class="code-box"><?= h(json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'Sem execucao ainda.') ?></div>
        </div>
      </div>
    </section>

    <div class="modal fade" id="kpiModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="kpiModalTitle">Detalhes</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
          </div>
          <div class="modal-body">
            <div class="table-responsive">
              <table class="table table-sm align-middle kpi-modal-table">
                <thead>
                  <tr>
                    <th>IBGE</th>
                    <th>Município</th>
                    <th>UF</th>
                    <th>Provedor</th>
                    <th>Serviços</th>
                  </tr>
                </thead>
                <tbody id="kpiModalBody"></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const municipios = <?= json_encode($municipios, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const serviceSelect = document.getElementById('service');
    const prefeituraSelect = document.getElementById('prefeitura');
    const actionSelect = document.getElementById('action');
    const opHelp = document.getElementById('operation-help');
    const postedService = <?= json_encode($service, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const danfseTemplate = <?= json_encode($danfseCatalogUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const danfsePlaceholders = <?= json_encode($danfsePlaceholders, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const kpiButtons = Array.from(document.querySelectorAll('[data-kpi]'));
    const kpiModalTitle = document.getElementById('kpiModalTitle');
    const kpiModalBody = document.getElementById('kpiModalBody');
    const kpiModalEl = document.getElementById('kpiModal');
    const kpiModal = kpiModalEl ? new bootstrap.Modal(kpiModalEl) : null;

    function normalizeService(service) {
      if (!service) return '';
      return String(service).trim();
    }

    function hasDanfseService(municipio) {
      const services = Array.isArray(municipio?.services) ? municipio.services : [];
      return services.includes('consultar_danfse') || services.includes('link_url');
    }

    function escapeHtml(value) {
      return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
    }

    function fillServices() {
      const selectedIbge = prefeituraSelect.value;
      const municipio = municipios.find((m) => String(m.ibge) === String(selectedIbge));
      const options = municipio && Array.isArray(municipio.services) ? [...municipio.services] : [];
      const action = actionSelect ? String(actionSelect.value || '') : '';
      const provedor = String(municipio?.provedor || '').toLowerCase();
      if (action === 'consultar_municipal' && provedor === 'speedgov') {
        ['consultar_lote', 'consultar_situacao', 'consultar_nfse_rps', 'consultar_nfse'].forEach((srv) => {
          if (!options.includes(srv)) {
            options.push(srv);
          }
        });
      }
      if (action === 'cancelar_municipal' && provedor === 'speedgov') {
        ['cancelar_nf_se', 'cancelar_nfse'].forEach((srv) => {
          if (!options.includes(srv)) {
            options.push(srv);
          }
        });
      }
      if (action === 'substituir_municipal' && provedor === 'speedgov') {
        ['substituir_nf_se', 'substituir_nfse'].forEach((srv) => {
          if (!options.includes(srv)) {
            options.push(srv);
          }
        });
      }
      serviceSelect.innerHTML = '';
      if (!options.length) {
        const op = document.createElement('option');
        op.value = '';
        op.textContent = 'Sem servicos disponiveis';
        serviceSelect.appendChild(op);
        return;
      }
      let selectedApplied = false;
      options.forEach((srv) => {
        const op = document.createElement('option');
        op.value = normalizeService(srv);
        op.textContent = normalizeService(srv);
        if (normalizeService(srv) === normalizeService(postedService)) {
          op.selected = true;
          selectedApplied = true;
        }
        serviceSelect.appendChild(op);
      });
      if (!selectedApplied && serviceSelect.options.length > 0) {
        serviceSelect.selectedIndex = 0;
      }
    }

    function setVisibility(elId, visible) {
      const el = document.getElementById(elId);
      if (!el) return;
      el.classList.toggle('d-none', !visible);
    }

    function applyActionUX() {
      const action = actionSelect.value;
      const isEmitir = action === 'emitir_municipal';
      const isConsultar = action === 'consultar_municipal';
      const isCancelar = action === 'cancelar_municipal';
      const isSubstituir = action === 'substituir_municipal';
      setVisibility('group-uf', action === 'listar_municipios');
      setVisibility('group-provedor', action === 'listar_municipios');
      setVisibility('group-limit', action === 'listar_municipios');
      setVisibility('group-chave', action === 'consultar_nfse_chave' || action === 'gerar_danfse_pdf_chave');
      setVisibility('group-danfse-xml-manual', action === 'gerar_danfse_pdf_xml');
      setVisibility('group-emissao-form', isEmitir);
      setVisibility('group-consulta-form', isConsultar);
      setVisibility('group-cancel-form', isCancelar);
      setVisibility('group-subst-form', isSubstituir);

      if (isEmitir) {
        opHelp.textContent = 'Preencha os campos de emissão (RPS/Tomador/Serviço) e informe PFX.';
      } else if (isCancelar) {
        opHelp.textContent = 'Preencha os campos de cancelamento e informe PFX.';
      } else if (isSubstituir) {
        opHelp.textContent = 'Preencha os campos de substituicao e informe PFX.';
      } else if (action === 'consultar_nfse_chave') {
        opHelp.textContent = 'Informe a chave da NFS-e.';
      } else if (action === 'gerar_danfse_pdf_chave') {
        opHelp.textContent = 'Informe a chave da NFS-e para buscar o XML e renderizar o DANFSE em PDF.';
      } else if (action === 'gerar_danfse_pdf_xml') {
        opHelp.textContent = 'Cole o XML da NFS-e para gerar e visualizar o DANFSE em PDF.';
      } else if (action === 'listar_municipios') {
        opHelp.textContent = 'Filtro de municipios por UF/provedor.';
      } else if (action === 'detalhes_municipio') {
        opHelp.textContent = 'Retorna parametros completos do municipio.';
      } else if (action === 'diagnostico') {
        opHelp.textContent = 'Mostra suporte do municipio/provedor no pacote.';
      } else {
        opHelp.textContent = 'Operacao de consulta por servico municipal.';
      }
    }

    function validateEmissionForm() {
      const get = (name) => {
        const el = document.querySelector(`[name="${name}"]`);
        return el ? String(el.value || '').trim() : '';
      };
      const required = [
        ['emit_rps_numero', 'RPS número'],
        ['emit_data_emissao', 'Data emissão'],
        ['emit_prestador_cnpj', 'Prestador CNPJ'],
        ['emit_prestador_im', 'Prestador IM'],
        ['emit_tomador_doc', 'Tomador documento'],
        ['emit_tomador_nome', 'Tomador nome'],
        ['emit_item_lista_servico', 'Item lista serviço'],
        ['emit_codigo_tributacao_municipio', 'Codigo tributação município'],
        ['emit_codigo_nbs', 'Codigo NBS'],
        ['emit_servico_descricao', 'Descrição serviço'],
        ['emit_servico_valor', 'Valor serviço']
      ];
      const missing = required.filter(([name]) => get(name) === '');
      return {
        ok: missing.length === 0,
        missing: missing.map(([, label]) => label)
      };
    }

    function validateCancelForm() {
      const get = (name) => {
        const el = document.querySelector(`[name="${name}"]`);
        return el ? String(el.value || '').trim() : '';
      };
      const required = [
        ['cancel_numero_nfse', 'Numero NFS-e'],
        ['cancel_prestador_cnpj', 'Prestador CNPJ'],
        ['cancel_prestador_im', 'Prestador IM']
      ];
      const missing = required.filter(([name]) => get(name) === '');
      return {
        ok: missing.length === 0,
        missing: missing.map(([, label]) => label)
      };
    }

    function validateSubstituicaoForm() {
      const get = (name) => {
        const el = document.querySelector(`[name="${name}"]`);
        return el ? String(el.value || '').trim() : '';
      };
      const required = [
        ['subst_numero_nfse', 'Numero NFS-e antiga'],
        ['subst_prestador_cnpj', 'Prestador CNPJ'],
        ['subst_prestador_im', 'Prestador IM'],
        ['subst_rps_numero', 'RPS numero novo']
      ];
      const missing = required.filter(([name]) => get(name) === '');
      return {
        ok: missing.length === 0,
        missing: missing.map(([, label]) => label)
      };
    }

    function beforeSubmitBuildPayload(event) {
      const action = actionSelect.value;
      if (!['emitir_municipal', 'cancelar_municipal', 'substituir_municipal'].includes(action)) {
        return;
      }
      let validation = { ok: true, missing: [] };
      let label = '';

      if (action === 'emitir_municipal') {
        validation = validateEmissionForm();
        label = 'emissao';
      } else if (action === 'cancelar_municipal') {
        validation = validateCancelForm();
        label = 'cancelamento';
      } else if (action === 'substituir_municipal') {
        validation = validateSubstituicaoForm();
        label = 'substituicao';
      }

      if (!validation.ok) {
        event.preventDefault();
        alert(`Preencha os campos obrigatorios de ${label}: ${validation.missing.join(', ')}`);
        return;
      }
    }

    function renderKpiRows(type) {
      if (!kpiModalBody || !kpiModalTitle) return;

      let rows = [];
      if (type === 'municipios') {
        kpiModalTitle.textContent = 'Municípios com provedor';
        rows = municipios.slice();
      } else if (type === 'provedores') {
        kpiModalTitle.textContent = 'Provedores mapeados';
        const providersMap = {};
        municipios.forEach((m) => {
          const key = String(m.provedor || '').trim();
          if (!key) return;
          if (!providersMap[key]) {
            providersMap[key] = [];
          }
          providersMap[key].push(m);
        });
        rows = Object.keys(providersMap)
          .sort((a, b) => a.localeCompare(b))
          .map((provider) => ({
            ibge: '-',
            nome: provider,
            uf: '-',
            provedor: `${providersMap[provider].length} municípios`,
            services: []
          }));
      } else if (type === 'ufs') {
        kpiModalTitle.textContent = 'Cobertura por UF';
        const ufMap = {};
        municipios.forEach((m) => {
          const uf = String(m.uf || '').trim().toUpperCase();
          if (!uf) return;
          if (!ufMap[uf]) {
            ufMap[uf] = 0;
          }
          ufMap[uf] += 1;
        });
        rows = Object.keys(ufMap)
          .sort((a, b) => a.localeCompare(b))
          .map((uf) => ({
            ibge: '-',
            nome: `UF ${uf}`,
            uf,
            provedor: `${ufMap[uf]} municípios`,
            services: []
          }));
      } else if (type === 'danfse') {
        kpiModalTitle.textContent = 'Municípios com DANFSe/link no catálogo';
        rows = municipios.filter((m) => hasDanfseService(m));
      }

      kpiModalBody.innerHTML = rows.map((row) => {
        const services = Array.isArray(row.services) && row.services.length > 0
          ? row.services.join(', ')
          : '-';
        return `<tr>
          <td>${escapeHtml(row.ibge ?? '-')}</td>
          <td>${escapeHtml(row.nome ?? '-')}</td>
          <td>${escapeHtml(row.uf ?? '-')}</td>
          <td>${escapeHtml(row.provedor ?? '-')}</td>
          <td>${escapeHtml(services)}</td>
        </tr>`;
      }).join('');
    }

    function copyText(value) {
      if (!value) return;
      navigator.clipboard.writeText(String(value)).catch(() => {});
    }

    function tokenToId(token) {
      return String(token).replace(/[^A-Za-z0-9_-]/g, '_');
    }

    function tokenToLabel(token) {
      return String(token).replaceAll('%', '');
    }

    function buildDanfseFinalUrl() {
      if (!danfseTemplate || !Array.isArray(danfsePlaceholders) || !danfsePlaceholders.length) return '';
      let url = danfseTemplate;
      danfsePlaceholders.forEach((token) => {
        const input = document.getElementById(`danfse-token-${tokenToId(token)}`);
        const value = input ? String(input.value || '').trim() : '';
        url = url.split(token).join(encodeURIComponent(value));
      });
      return url;
    }

    function renderDanfsePlaceholderFields() {
      const container = document.getElementById('danfse-placeholder-fields');
      const finalInput = document.getElementById('danfse-final-url');
      if (!container || !finalInput || !Array.isArray(danfsePlaceholders) || !danfsePlaceholders.length) {
        return;
      }

      container.innerHTML = danfsePlaceholders.map((token) => {
        const id = `danfse-token-${tokenToId(token)}`;
        const label = tokenToLabel(token);
        return `<div class="col-md-6">
          <label class="form-label">${escapeHtml(label)}</label>
          <input type="text" class="form-control danfse-token-input" id="${escapeHtml(id)}" data-token="${escapeHtml(token)}" placeholder="${escapeHtml(token)}">
        </div>`;
      }).join('');

      const maybeChaveInput = document.querySelector('input[name="chave"]');
      const chaveValue = maybeChaveInput ? String(maybeChaveInput.value || '').trim() : '';
      if (chaveValue !== '') {
        const codVerifInput = document.getElementById(`danfse-token-${tokenToId('%CodVerif%')}`);
        if (codVerifInput && codVerifInput.value.trim() === '') {
          codVerifInput.value = chaveValue;
        }
      }

      const refreshFinal = () => {
        finalInput.value = buildDanfseFinalUrl();
      };

      container.querySelectorAll('.danfse-token-input').forEach((input) => {
        input.addEventListener('input', refreshFinal);
      });

      refreshFinal();
    }

    prefeituraSelect.addEventListener('change', fillServices);
    actionSelect.addEventListener('change', () => {
      fillServices();
      applyActionUX();
    });
    const formEl = document.querySelector('form[method="post"]');
    if (formEl) {
      formEl.addEventListener('submit', beforeSubmitBuildPayload);
    }
    kpiButtons.forEach((btn) => {
      btn.addEventListener('click', () => {
        const type = btn.getAttribute('data-kpi') || 'municipios';
        renderKpiRows(type);
        if (kpiModal) {
          kpiModal.show();
        }
      });
    });

    const btnCopyCatalog = document.getElementById('btn-copy-danfse-catalog');
    const btnOpenCatalog = document.getElementById('btn-open-danfse-catalog');
    const inputCatalog = document.getElementById('danfse-catalog-url');
    if (btnCopyCatalog && inputCatalog) {
      btnCopyCatalog.addEventListener('click', () => copyText(inputCatalog.value));
    }
    if (btnOpenCatalog && inputCatalog) {
      btnOpenCatalog.addEventListener('click', () => {
        if (!inputCatalog.value) return;
        window.open(inputCatalog.value, '_blank', 'noopener,noreferrer');
      });
    }
    const btnCopyFinal = document.getElementById('btn-copy-danfse-final');
    const btnOpenFinal = document.getElementById('btn-open-danfse-final');
    const inputFinal = document.getElementById('danfse-final-url');
    if (btnCopyFinal && inputFinal) {
      btnCopyFinal.addEventListener('click', () => copyText(inputFinal.value));
    }
    if (btnOpenFinal && inputFinal) {
      btnOpenFinal.addEventListener('click', () => {
        if (!inputFinal.value) return;
        window.open(inputFinal.value, '_blank', 'noopener,noreferrer');
      });
    }

    const xmlButton = document.getElementById('btn-download-xml');
    if (xmlButton) {
      xmlButton.addEventListener('click', () => {
        const b64 = xmlButton.getAttribute('data-b64') || '';
        const filename = xmlButton.getAttribute('data-filename') || 'nfse.xml';
        if (!b64) return;
        const bytes = atob(b64);
        const buf = new Uint8Array(bytes.length);
        for (let i = 0; i < bytes.length; i++) {
          buf[i] = bytes.charCodeAt(i);
        }
        const blob = new Blob([buf], { type: 'application/xml;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
      });
    }

    const xmlRequestButton = document.getElementById('btn-download-xml-request');
    if (xmlRequestButton) {
      xmlRequestButton.addEventListener('click', () => {
        const b64 = xmlRequestButton.getAttribute('data-b64') || '';
        const filename = xmlRequestButton.getAttribute('data-filename') || 'nfse-envio.xml';
        if (!b64) return;
        const bytes = atob(b64);
        const buf = new Uint8Array(bytes.length);
        for (let i = 0; i < bytes.length; i++) {
          buf[i] = bytes.charCodeAt(i);
        }
        const blob = new Blob([buf], { type: 'application/xml;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
      });
    }

    const danfsePdfDownloadButton = document.getElementById('btn-download-danfse-pdf');
    if (danfsePdfDownloadButton) {
      danfsePdfDownloadButton.addEventListener('click', () => {
        const b64 = danfsePdfDownloadButton.getAttribute('data-b64') || '';
        const filename = danfsePdfDownloadButton.getAttribute('data-filename') || 'danfse.pdf';
        if (!b64) return;
        const bytes = atob(b64);
        const buf = new Uint8Array(bytes.length);
        for (let i = 0; i < bytes.length; i++) {
          buf[i] = bytes.charCodeAt(i);
        }
        const blob = new Blob([buf], { type: 'application/pdf' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
      });
    }

    const danfsePdfOpenButton = document.getElementById('btn-open-danfse-pdf');
    if (danfsePdfOpenButton) {
      danfsePdfOpenButton.addEventListener('click', () => {
        const b64 = danfsePdfOpenButton.getAttribute('data-b64') || '';
        if (!b64) return;
        window.open(`data:application/pdf;base64,${b64}`, '_blank', 'noopener,noreferrer');
      });
    }

    fillServices();
    applyActionUX();
    renderDanfsePlaceholderFields();
  </script>
</body>
</html>
