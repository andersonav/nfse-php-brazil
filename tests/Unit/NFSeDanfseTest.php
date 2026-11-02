<?php
namespace Alves\NfseBrasil\Tests\Unit;

use Alves\NfseBrasil\Danfse\DanfsePdfGenerator;
use Alves\NfseBrasil\Danfse\NationalDanfseParser;
use Alves\NfseBrasil\Danfse\MunicipalDanfseParser;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class NFSeDanfseTest extends TestCase
{
    private function xml(string $tipo = 'nacional'): string { return file_get_contents(__DIR__ . '/../Fixtures/nfse-' . $tipo . '-danfse.xml'); }

    public function test_parser_nacional_le_capitalizacao_correta_e_total_ibs_mais_cbs(): void
    {
        $d = (new NationalDanfseParser())->parse($this->xml());
        $this->assertSame('30/10/2026', $d['competencia']);
        $this->assertSame('456', $d['numero_rps']);
        $this->assertSame('1', $d['serie_rps']);
        $this->assertSame('115012000', $d['nbs']);
        $this->assertSame('Homologação', $d['ambiente_desc']);
        $this->assertSame('R$ 3,80', $d['v_ibscbs']);
        $this->assertSame('R$ 3,42', $d['cbs_valor']);
        $this->assertMatchesRegularExpression('/^\d{50}$/', $d['chave']);
        $this->assertStringEndsWith($d['chave'], $d['qrcode_payload']);
    }

    public function test_parser_speedgov_le_partes_enderecos_cbs_e_retencoes_sem_confundir_dominios(): void
    {
        $d = (new MunicipalDanfseParser())->parse($this->xml('speedgov'));
        $this->assertSame('NFSE123', $d['chave']);
        $this->assertSame('Não Optante', $d['sn_comp_desc']);
        $this->assertSame('Retido', $d['ret_issqn_desc']);
        $this->assertSame('Prestador de teste', $d['prestador']['nome']);
        $this->assertSame('Tomador de teste', $d['tomador']['nome']);
        $this->assertSame('Destinatário de teste', $d['destinatario']['nome']);
        $this->assertSame('Intermediário de teste', $d['intermediario']['nome']);
        $this->assertStringContainsString('Rua do Prestador', $d['prestador']['endereco']);
        $this->assertStringContainsString('Rua Intermediário', $d['intermediario']['endereco']);
        $this->assertSame('200 / 200001', $d['cst_class']);
        $this->assertSame('R$ 848,00', $d['ibs_base_apos']);
        $this->assertSame('R$ 3,05', $d['cbs_valor']);
        $this->assertSame('R$ 3,39', $d['v_ibscbs']);
        $this->assertSame('R$ 91,00', $d['ret_total']);
        $this->assertSame('', $d['qrcode_payload']);
    }

    public function test_documento_nao_autorizado_nao_gera_danfse(): void
    {
        $this->expectException(RuntimeException::class);
        (new DanfsePdfGenerator())->generate('<EnviarLoteRpsEnvio><InfRps><Numero>1</Numero></InfRps></EnviarLoteRpsEnvio>');
    }

    public function test_nao_mistura_duas_notas_no_mesmo_pdf(): void
    {
        $this->expectException(RuntimeException::class);
        $xml = preg_replace('/<\?xml[^>]+>/', '', $this->xml());
        (new NationalDanfseParser())->parse('<lote>' . $xml . $xml . '</lote>');
    }

    public function test_contexto_de_cancelamento_precisa_corresponder_a_chave(): void
    {
        $this->expectException(RuntimeException::class);
        (new DanfsePdfGenerator())->generate($this->xml(), null, ['chave' => str_repeat('9', 50), 'situacao' => 'cancelada']);
    }

    public function test_nacional_e_municipal_geram_uma_pagina_com_fontes_incorporadas(): void
    {
        foreach (['nacional','speedgov'] as $tipo) {
            $pdf = (new DanfsePdfGenerator())->generate($this->xml($tipo));
            $this->assertStringStartsWith('%PDF-', $pdf);
            $this->assertSame(1, preg_match_all('/\/Type\s*\/Page\b/', $pdf));
            $this->assertSame(3, preg_match_all('/\/FontFile2\b/', $pdf));
        }
    }

    public function test_descricao_longa_obedece_limite_especifico_com_reticencias(): void
    {
        $xml = str_replace('Serviço de teste', str_repeat('Descrição longa do serviço. ', 160), $this->xml());
        $pdf = (new DanfsePdfGenerator())->generate($xml);
        $this->assertSame(1, preg_match_all('/\/Type\s*\/Page\b/', $pdf));
    }
    public function test_dominios_nacionais_nao_confundem_status_de_geracao_com_cancelamento(): void
    {
        $xml = str_replace('<cStat>100</cStat>', '<cStat>102</cStat>', $this->xml());
        $d = (new NationalDanfseParser())->parse($xml);
        $this->assertSame('NFS-e de Decisão Judicial', $d['situacao_nfse_desc']);
        $this->assertSame('', $d['marca_agua']);
    }

    public function test_nome_e_endereco_longos_quebram_linha_sem_reduzir_fonte(): void
    {
        $xml = str_replace(['Prestador de teste','Rua do Prestador'], [str_repeat('W', 80), str_repeat('W', 80)], $this->xml('speedgov'));
        $pdf = (new DanfsePdfGenerator())->generate($xml);
        $this->assertSame(1, preg_match_all('/\/Type\s*\/Page\b/', $pdf));
        $this->assertSame(3, preg_match_all('/\/FontFile2\b/', $pdf));
    }

}
