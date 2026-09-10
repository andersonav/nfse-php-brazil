<?php

declare(strict_types=1);

namespace Alves\NfseBrasil\Tests\Unit;

use Alves\NfseBrasil\ProviderBuilders\ToolsProviderBuilderCommonTrait;
use Alves\NfseBrasil\ProviderBuilders\ToolsProviderBuilderSpeedGovTrait;
use PHPUnit\Framework\TestCase;

final class SpeedGovBuilderTest extends TestCase
{
    private function builder(): object
    {
        return new class {
            use ToolsProviderBuilderCommonTrait;
            use ToolsProviderBuilderSpeedGovTrait;

            public function build(array $payload): string
            {
                return $this->buildSpeedGovRecepcionarEnvelope($payload);
            }

            public function buildService(array $payload, string $service): string
            {
                return $this->buildSpeedGovDataForService($payload, $service);
            }

            public function sign(string $content, string $tagname, ?string $mark, $rootname): string
            {
                return $content;
            }
        };
    }

    private function payload(): array
    {
        return [
            'lote' => ['numero_lote' => '3'],
            'rps' => [[
                'identificacao' => ['numero' => '8113', 'serie' => '1', 'tipo' => '1'],
                'data_emissao' => '2026-08-12T17:16:03',
                'prestador' => ['cnpj' => '13743550000657', 'inscricao_municipal' => '102019'],
                'tomador' => ['documento' => '59176608000114', 'nome_razao_social' => 'Tomador'],
                'servico' => [
                    'valor_servicos' => '1808.32', 'aliquota' => '3.00', 'iss_retido' => '2',
                    'item_lista_servico' => '1.06', 'codigo_cnae' => '6204000',
                    'discriminacao' => 'Servicos gerenciados', 'codigo_municipio' => '2307650',
                    'codigo_nbs' => '1.1501.20.00', 'cst_pis_cofins' => '01',
                    'base_calculo_pis_cofins' => '1808.32', 'tipo_retencao_pis_cofins' => '3',
                    'aliquota_pis' => '1.65', 'aliquota_cofins' => '7.60',
                ],
                'dados_dps' => [
                    'tp_emit' => '1', 'tp_amb' => '2', 'dh_emi' => '2026-08-12T17:16:03',
                    'ver_aplic' => 'SpeedGovTest', 'c_loc_emi' => '2307650',
                    'c_loc_prestacao' => '2307650', 'c_trib_nac' => '010601',
                    'serie' => '1', 'numero' => '8113', 'data_competencia' => '2026-08-12',
                ],
                'controle_ibscbs' => [
                    'fin_nfse' => '0', 'ind_final' => '0', 'ind_dest' => '0',
                    'c_ind_op' => '100301', 'cst' => '000', 'c_class_trib' => '000001',
                ],
                'ibscbs' => [
                    'base_calculo' => '1586.80', 'ibs_uf_aliquota' => '0.10',
                    'cbs_aliquota' => '0.90', 'ibs_uf_valor_diferido' => '0',
                    'valor_total_com_tributos' => '1808.32', 'perc_redutor_compra_gov' => '0',
                ],
                'data_competencia' => '2026-08-12',
            ]],
        ];
    }

    public function testGeraCamposDaNovaHomologacaoComCapitalizacaoExata(): void
    {
        $xml = html_entity_decode($this->builder()->build($this->payload()), ENT_QUOTES | ENT_XML1, 'UTF-8');

        self::assertStringContainsString('<cNBS>1.1501.20.00</cNBS>', $xml);
        self::assertStringContainsString('<serie>1</serie><nDPS>8113</nDPS><dCompet>2026-08-12</dCompet>', $xml);
        self::assertStringContainsString('<finNFSe>0</finNFSe>', $xml);
        self::assertStringContainsString('<cIndOp>100301</cIndOp><CST>000</CST><cClassTrib>000001</cClassTrib>', $xml);
        self::assertStringContainsString('<IBSUFValorDiferido>0.00</IBSUFValorDiferido>', $xml);
        self::assertStringNotContainsString('<FinNFSe>', $xml);
    }

    public function testRespeitaOrdemDosBlocosDefinidaNoSchema(): void
    {
        $xml = html_entity_decode($this->builder()->build($this->payload()), ENT_QUOTES | ENT_XML1, 'UTF-8');

        self::assertLessThan(strpos($xml, '<ControleIBSCBS>'), strpos($xml, '<DadosDPS>'));
        self::assertLessThan(strpos($xml, '<IBSCBS>'), strpos($xml, '<ControleIBSCBS>'));
        self::assertLessThan(strpos($xml, '<DataCompetencia>'), strpos($xml, '<IBSCBS>'));
    }

    public function testCancelamentoPreservaCodigoAlfanumericoDoSpeedGov(): void
    {
        $xml = $this->builder()->buildService([
            'numero_nfse' => '202600000000001',
            'prestador_cnpj' => '13743550000657',
            'prestador_im' => '102019',
            'codigo_municipio' => '2307650',
            'codigo_cancelamento' => 'E12',
        ], 'cancelar_nfse');

        self::assertStringContainsString('<CodigoCancelamento>E12</CodigoCancelamento>', $xml);
    }

    public function testOmiteRegimeEspecialZeroPorqueNaoPertenceAoDominioDoXsd(): void
    {
        $payload = $this->payload();
        $payload['rps'][0]['regime_especial_tributacao'] = '0';

        $xml = html_entity_decode($this->builder()->build($payload), ENT_QUOTES | ENT_XML1, 'UTF-8');

        self::assertStringNotContainsString('<RegimeEspecialTributacao>', $xml);
    }
}
