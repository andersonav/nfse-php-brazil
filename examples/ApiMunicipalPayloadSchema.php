<?php

/**
 * Contratos de payload para sua API.
 * Use como base para validação de request em cada endpoint.
 */
function apiMunicipalPayloadSchema(): array
{
    return [
        'detalhesMunicipio' => [
            'required' => ['prefeitura'],
            'optional' => [],
        ],
        'emitirNfseMunicipal' => [
            'required' => [
                'lote_numero',
                'rps_numero',
                'rps_serie',
                'rps_tipo',
                'data_emissao',
                'prestador_cnpj',
                'prestador_im',
                'tomador_documento',
                'tomador_nome',
                'item_lista_servico',
                'codigo_tributacao_municipio',
                'servico_descricao',
                'servico_valor',
                'codigo_municipio_prestacao',
            ],
            'optional' => [
                'servico_codigo_cnae',
                'servico_aliquota',
                'iss_retido',
                'natureza_operacao',
                'regime_especial_tributacao',
                'optante_simples_nacional',
                'incentivador_cultural',
                'status',
                'tomador_email',
                'tomador_im',
                'tomador_endereco',
                'tomador_numero',
                'tomador_complemento',
                'tomador_bairro',
                'tomador_codigo_municipio',
                'tomador_uf',
                'tomador_cep',
                'tomador_telefone',
                'municipio_incidencia',
                'exigibilidade_iss',
                'data_competencia',
                'provider_extras',
            ],
            'blocos_opcionais' => [
                'dados_dps' => [
                    'flag' => 'blocos.dados_dps',
                    'payload_key' => 'dados_dps',
                    'campos' => [
                        'tp_emit', 'tp_amb', 'dh_emi', 'ver_aplic',
                        'c_loc_emi', 'c_loc_prestacao', 'c_trib_nac',
                        'trib_issqn', 'tp_ret_issqn', 'op_simp_nac',
                        'reg_esp_trib', 'reg_ap_trib_sn',
                    ],
                ],
                'dados_obra' => [
                    'flag' => 'blocos.dados_obra',
                    'payload_key' => 'dados_obra',
                    'campos' => ['codigo_obra', 'insc_imob_fisc', 'endereco_obra'],
                ],
                'comercio_exterior' => [
                    'flag' => 'blocos.comercio_exterior',
                    'payload_key' => 'comercio_exterior',
                    'campos' => [
                        'md_prestacao', 'vinc_prest', 'tp_moeda', 'v_serv_moeda',
                        'mec_af_comex_p', 'mec_af_comex_t', 'mov_temp_bens',
                        'ndi', 'nre', 'mdic', 'c_pais_result',
                    ],
                ],
                'exigibilidade_suspensa' => [
                    'flag' => 'blocos.exigibilidade_suspensa',
                    'payload_key' => 'exigibilidade_suspensa',
                    'campos' => ['tp_susp', 'n_processo'],
                ],
                'beneficio_municipal' => [
                    'flag' => 'blocos.beneficio_municipal',
                    'payload_key' => 'beneficio_municipal',
                    'campos' => ['tp_bm', 'n_bm', 'v_red_bcbm', 'p_red_bcbm'],
                ],
                'reembolso_repasse' => [
                    'flag' => 'blocos.reembolso_repasse',
                    'payload_key' => 'reembolso_repasse',
                    'campos' => ['tp_reemb_rep_res', 'x_tp_reemb_rep_res', 'v_reemb_rep_res'],
                ],
                'destinatario' => [
                    'flag' => 'blocos.destinatario',
                    'payload_key' => 'destinatario',
                    'campos' => [
                        'cnpj_cpf', 'nome', 'logradouro', 'numero', 'complemento',
                        'bairro', 'cidade', 'uf', 'cep', 'cod_municipio',
                        'cod_pais', 'cod_postal_ext', 'nif', 'email', 'telefone',
                    ],
                ],
                'controle_ibscbs' => [
                    'flag' => 'blocos.controle_ibscbs',
                    'payload_key' => 'controle_ibscbs',
                    'campos' => ['fin_nfse', 'ind_final', 'tp_oper', 'tp_ente_gov', 'ind_dest', 'c_ind_op'],
                ],
                'ibscbs' => [
                    'flag' => 'blocos.ibscbs',
                    'payload_key' => 'ibscbs',
                    'campos' => [
                        'base_calculo', 'ibs_uf_aliquota', 'ibs_mun_aliquota',
                        'cbs_aliquota', 'ibs_uf_valor', 'ibs_mun_valor',
                        'cbs_valor', 'ibs_valor_total', 'valor_total_com_tributos',
                        'localidade_incidencia_cod', 'localidade_incidencia_nome',
                    ],
                ],
            ],
        ],
        'consultarMunicipal' => [
            'required' => ['service'],
            'optional' => ['protocolo', 'prestador_cnpj', 'prestador_im', 'rps_numero', 'rps_serie', 'rps_tipo'],
            'services_comuns' => ['consultar_lote', 'consultar_situacao', 'consultar_nfse_rps', 'consultar_nfse'],
        ],
        'cancelarNfseMunicipal' => [
            'required' => ['service', 'numero_nfse', 'prestador_cnpj', 'codigo_municipio'],
            'optional' => ['prestador_im', 'codigo_cancelamento', 'motivo'],
            'default_values' => ['codigo_cancelamento' => '1'],
        ],
        'substituirNfseMunicipal' => [
            'required' => ['service'],
            'optional' => ['dados_xml'],
        ],
        'consultarNfseChave' => [
            'required' => ['chave'],
            'optional' => [],
        ],
        'gerarDanfse' => [
            'required_one_of' => ['chave', 'xml_nfse'],
            'optional' => [],
        ],
    ];
}

