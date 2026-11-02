<?php
namespace Alves\NfseBrasil\Tests\Unit;
use Alves\NfseBrasil\ProviderBuilders\ToolsProviderBuilderCommonTrait;
use Alves\NfseBrasil\ProviderBuilders\ToolsProviderBuilderSpeedGovTrait;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class NFSeSpeedGovSchemaTest extends TestCase
{
    private function build(array $payload): string
    {
        $builder = new class {
            use ToolsProviderBuilderCommonTrait, ToolsProviderBuilderSpeedGovTrait;
            public function build(array $p): string { return $this->buildSpeedGovRecepcionarEnvelope($p); }
            // Valida estrutura. Não simula validade de assinatura ou autorização.
            public function sign(string $xml, string $tagname, ?string $mark, $rootname): string { return $xml; }
        };
        return html_entity_decode($builder->build($payload), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
    private function payload(): array { return json_decode(file_get_contents(__DIR__ . '/../Fixtures/nfse-speedgov-envio.json'), true, 512, JSON_THROW_ON_ERROR); }
    public function test_envio_com_rtc_obedece_estrutura_e_capitalizacao_do_xsd_publicado(): void
    {
        $xml = $this->build($this->payload());
        foreach (['<FinNFSe>0</FinNFSe>', '<CIndOp>100301</CIndOp>', '<TpEnteGov>4</TpEnteGov>', '<IBSUFValorDiferido>0.19</IBSUFValorDiferido>', '<IBSCreditoPresumidoValor>9.50</IBSCreditoPresumidoValor>'] as $tag) { $this->assertStringContainsString($tag, $xml); }
    }
    public function test_campo_incompativel_com_xsd_e_recusado_antes_de_assinar_e_enviar(): void
    {
        $p = $this->payload(); $p['rps'][0]['prestador']['cnpj'] = '123';
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('XSD SpeedGov');
        $this->build($p);
    }
    public function test_nao_arredonda_silenciosamente_percentual_externo_com_precisao_incompativel(): void
    {
        $p = $this->payload(); $p['rps'][0]['ibscbs']['ibs_uf_aliquota_efetiva'] = '0.0667';
        $this->expectException(RuntimeException::class);
        $this->build($p);
    }
}
