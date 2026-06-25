<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
date_default_timezone_set('America/Sao_Paulo');

use Alves\NfseBrasil\Tools;
use NFePHP\Common\Certificate;

include __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/ApiMunicipalPayloadSchema.php';

/**
 * Base para API própria usando nfse-php-brazil.
 * Cada método abaixo representa um endpoint da sua API.
 */
final class ApiMunicipalBase
{
    public static function makeTools(array $ctx): Tools
    {
        $config = new stdClass();
        $config->tpamb = (int) ($ctx['tpamb'] ?? 2);
        $config->prefeitura = (string) ($ctx['prefeitura'] ?? '');
        $config->catalog_compiled_path = $ctx['catalog_compiled_path'] ?? (__DIR__ . '/../storage/municipios-acbr.php');
        $configJson = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $pfxPath = (string) ($ctx['pfx_path'] ?? '');
        $pfxPass = (string) ($ctx['pfx_pass'] ?? '');
        if ($pfxPath !== '' && is_file($pfxPath)) {
            $cert = Certificate::readPfx(file_get_contents($pfxPath), $pfxPass);
            return new Tools($configJson, $cert);
        }

        return new Tools($configJson);
    }

    public static function detalhesMunicipio(Tools $tools, ?string $prefeitura = null): array
    {
        return $tools->detalhesMunicipio($prefeitura);
    }

    public static function emitir(Tools $tools, array $payloadApi, string $service = 'recepcionar'): mixed
    {
        $payload = self::buildEmissionPayload($payloadApi);
        return $tools->emitirNfseMunicipal($payload, $service);
    }

    public static function consultar(Tools $tools, array $payloadApi, string $service = 'consultar_lote'): mixed
    {
        return $tools->emitirNfseMunicipal([
            'protocolo' => (string) ($payloadApi['protocolo'] ?? ''),
            'prestador_cnpj' => preg_replace('/\D+/', '', (string) ($payloadApi['prestador_cnpj'] ?? '')),
            'prestador_im' => (string) ($payloadApi['prestador_im'] ?? ''),
            'rps_numero' => (string) ($payloadApi['rps_numero'] ?? ''),
            'rps_serie' => (string) ($payloadApi['rps_serie'] ?? '1'),
            'rps_tipo' => (string) ($payloadApi['rps_tipo'] ?? '1'),
        ], $service);
    }

    public static function cancelar(Tools $tools, array $payloadApi, string $service = 'cancelar_nf_se'): mixed
    {
        $payload = [
            'numero_nfse' => (string) ($payloadApi['numero_nfse'] ?? ''),
            'codigo_municipio' => (string) ($payloadApi['codigo_municipio'] ?? ''),
            'codigo_cancelamento' => (string) ($payloadApi['codigo_cancelamento'] ?? '1'),
            'motivo' => (string) ($payloadApi['motivo'] ?? ''),
            'prestador_cnpj' => preg_replace('/\D+/', '', (string) ($payloadApi['prestador_cnpj'] ?? '')),
            'prestador_im' => (string) ($payloadApi['prestador_im'] ?? ''),
        ];
        return $tools->cancelarNfseMunicipal($payload, $service);
    }

    public static function substituir(Tools $tools, array $payloadApi, string $service = 'substituir_nf_se'): mixed
    {
        return $tools->substituirNfseMunicipal($payloadApi, $service);
    }

    public static function consultarNfseChave(Tools $tools, string $chave): mixed
    {
        return $tools->consultarNfseChave($chave, false);
    }

    public static function gerarDanfse(Tools $tools, array $payloadApi): string
    {
        if (!empty($payloadApi['chave'])) {
            return $tools->gerarDanfsePdfPorChave((string) $payloadApi['chave']);
        }
        return $tools->gerarDanfsePdf((string) ($payloadApi['xml_nfse'] ?? ''));
    }

    /**
     * Monta payload unificado para emissão.
     * Flags de blocos opcionais:
     * - blocos.dados_dps
     * - blocos.dados_obra
     * - blocos.comercio_exterior
     * - blocos.exigibilidade_suspensa
     * - blocos.beneficio_municipal
     * - blocos.reembolso_repasse
     * - blocos.destinatario
     * - blocos.controle_ibscbs
     * - blocos.ibscbs
     */
    private static function buildEmissionPayload(array $in): array
    {
        $blocos = is_array($in['blocos'] ?? null) ? $in['blocos'] : [];
        $on = static fn (string $k): bool => (bool) ($blocos[$k] ?? false);

        $payload = [
            'lote' => [
                'numero_lote' => (string) ($in['lote_numero'] ?? '1'),
                'quantidade_rps' => 1,
            ],
            'rps' => [[
                'identificacao' => [
                    'numero' => (string) ($in['rps_numero'] ?? ''),
                    'serie' => (string) ($in['rps_serie'] ?? '1'),
                    'tipo' => (int) ($in['rps_tipo'] ?? 1),
                ],
                'data_emissao' => (string) ($in['data_emissao'] ?? date('Y-m-d\TH:i:s')),
                'natureza_operacao' => (int) ($in['natureza_operacao'] ?? 1),
                'regime_especial_tributacao' => array_key_exists('regime_especial_tributacao', $in) ? (int) $in['regime_especial_tributacao'] : null,
                'optante_simples_nacional' => (int) ($in['optante_simples_nacional'] ?? 2),
                'incentivador_cultural' => (int) ($in['incentivador_cultural'] ?? 2),
                'status' => (int) ($in['status'] ?? 1),
                'prestador' => [
                    'cnpj' => preg_replace('/\D+/', '', (string) ($in['prestador_cnpj'] ?? '')),
                    'inscricao_municipal' => (string) ($in['prestador_im'] ?? ''),
                ],
                'tomador' => [
                    'documento' => preg_replace('/\D+/', '', (string) ($in['tomador_documento'] ?? '')),
                    'nome_razao_social' => (string) ($in['tomador_nome'] ?? ''),
                    'email' => (string) ($in['tomador_email'] ?? ''),
                    'inscricao_municipal' => (string) ($in['tomador_im'] ?? ''),
                    'endereco' => (string) ($in['tomador_endereco'] ?? ''),
                    'numero' => (string) ($in['tomador_numero'] ?? ''),
                    'complemento' => (string) ($in['tomador_complemento'] ?? ''),
                    'bairro' => (string) ($in['tomador_bairro'] ?? ''),
                    'codigo_municipio' => (string) ($in['tomador_codigo_municipio'] ?? ''),
                    'uf' => (string) ($in['tomador_uf'] ?? ''),
                    'cep' => preg_replace('/\D+/', '', (string) ($in['tomador_cep'] ?? '')),
                    'telefone' => preg_replace('/\D+/', '', (string) ($in['tomador_telefone'] ?? '')),
                ],
                'servico' => [
                    'codigo_cnae' => (string) ($in['servico_codigo_cnae'] ?? ''),
                    'item_lista_servico' => (string) ($in['item_lista_servico'] ?? ''),
                    'codigo_tributacao_municipio' => (string) ($in['codigo_tributacao_municipio'] ?? ''),
                    'discriminacao' => (string) ($in['servico_descricao'] ?? ''),
                    'valor_servicos' => (float) ($in['servico_valor'] ?? 0),
                    'aliquota' => (float) ($in['servico_aliquota'] ?? 0),
                    'iss_retido' => (int) ($in['iss_retido'] ?? 2),
                    'codigo_municipio' => (string) ($in['codigo_municipio_prestacao'] ?? ''),
                    'municipio_incidencia' => (string) ($in['municipio_incidencia'] ?? ''),
                    'exigibilidade_iss' => (int) ($in['exigibilidade_iss'] ?? 1),
                ],
            ]],
        ];

        if ($on('dados_dps')) {
            $payload['rps'][0]['dados_dps'] = $in['dados_dps'] ?? [];
        }
        if ($on('dados_obra')) {
            $payload['rps'][0]['dados_obra'] = $in['dados_obra'] ?? [];
        }
        if ($on('comercio_exterior')) {
            $payload['rps'][0]['comercio_exterior'] = $in['comercio_exterior'] ?? [];
        }
        if ($on('exigibilidade_suspensa')) {
            $payload['rps'][0]['exigibilidade_suspensa'] = $in['exigibilidade_suspensa'] ?? [];
        }
        if ($on('beneficio_municipal')) {
            $payload['rps'][0]['beneficio_municipal'] = $in['beneficio_municipal'] ?? [];
        }
        if ($on('reembolso_repasse')) {
            $payload['rps'][0]['reembolso_repasse'] = $in['reembolso_repasse'] ?? [];
        }
        if ($on('destinatario')) {
            $payload['rps'][0]['destinatario'] = $in['destinatario'] ?? [];
        }
        if ($on('controle_ibscbs')) {
            $payload['rps'][0]['controle_ibscbs'] = $in['controle_ibscbs'] ?? [];
        }
        if ($on('ibscbs')) {
            $payload['rps'][0]['ibscbs'] = $in['ibscbs'] ?? [];
        }
        if (!empty($in['data_competencia'])) {
            $payload['rps'][0]['data_competencia'] = (string) $in['data_competencia'];
        }
        if (!empty($in['provider_extras']) && is_array($in['provider_extras'])) {
            $payload['provider_extras'] = $in['provider_extras'];
        }

        return $payload;
    }
}

/**
 * Exemplo de uso:
 */
try {
    $ctx = [
        'tpamb' => 2,
        'prefeitura' => '2307650',
        'catalog_compiled_path' => __DIR__ . '/../storage/municipios-acbr.php',
        'pfx_path' => __DIR__ . '/certificado.pfx',
        'pfx_pass' => 'senha_certificado',
    ];

    $tools = ApiMunicipalBase::makeTools($ctx);
    $detalhes = ApiMunicipalBase::detalhesMunicipio($tools);
    $provider = (string) ($detalhes['provedor'] ?? '');
    $ops = (array) ($detalhes['services_effective'] ?? []);

    dd([
        'provedor' => $provider,
        'operacoes_disponiveis' => $ops,
        'schema_payloads' => apiMunicipalPayloadSchema(),
    ]);
} catch (Throwable $e) {
    dd($e->getMessage(), $e);
}

