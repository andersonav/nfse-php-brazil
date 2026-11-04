<?php

namespace Alves\NfseBrasil\Tests\Unit;

use Alves\NfseBrasil\Tools;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AdnConsultasTest extends TestCase
{
    private const CHAVE = '23044001234567800019000000000000000000000000000000';

    private function evento(string $chave = self::CHAVE): string
    {
        return '<evento xmlns="http://www.sped.fazenda.gov.br/nfse"><infEvento Id="EVT_TESTE"><pedRegEvento><infPedReg>'
            .'<tpAmb>2</tpAmb><chNFSe>'.$chave.'</chNFSe><e101101><xMotivo>Teste</xMotivo></e101101>'
            .'</infPedReg></pedRegEvento></infEvento></evento>';
    }

    private function tools(array $resposta, int $http = 200): Tools
    {
        return new class($resposta, $http) extends Tools
        {
            public array $chamadas = [];

            public function __construct(private array $resposta, private int $http) {}

            public function getFromUrlWithHeaders(string $url, array $headers = [], bool $verifyTls = false): mixed
            {
                $this->chamadas[] = [$url, $headers, $verifyTls];
                $this->soapinfo = ['http_code' => $this->http];

                return $this->resposta;
            }
        };
    }

    private function resposta(string $tipo = 'EVENTO', ?string $xml = null): array
    {
        return ['StatusProcessamento' => 'DOCUMENTOS_LOCALIZADOS', 'TipoAmbiente' => 'HOMOLOGACAO', 'Erros' => [], 'LoteDFe' => [[
            'ChaveAcesso' => self::CHAVE, 'TipoDocumento' => $tipo, 'TipoEvento' => 'CANCELAMENTO',
            'ArquivoXml' => base64_encode(gzencode($xml ?? $this->evento())), 'DataHoraGeracao' => '2026-11-03T10:00:00-03:00',
        ]]];
    }

    public function test_consulta_chave_no_adn_sem_nsu_e_descompacta_evento(): void
    {
        $tools = $this->tools($this->resposta());
        $ret = $tools->consultarEventosAdn(self::CHAVE, 2);
        $this->assertSame('https://adn.producaorestrita.nfse.gov.br/contribuintes/NFSe/'.self::CHAVE.'/Eventos', $tools->chamadas[0][0]);
        $this->assertTrue($tools->chamadas[0][2]);
        $this->assertSame('EVENTO', $ret['DOCUMENTOS'][0]['TIPO_DOCUMENTO']);
        $this->assertSame('e101101', $ret['DOCUMENTOS'][0]['CODIGO_EVENTO']);
        $this->assertSame($this->evento(), $ret['DOCUMENTOS'][0]['XML']);
    }

    public function test_producao_utiliza_host_de_producao(): void
    {
        $r = ['StatusProcessamento' => 'NENHUM_DOCUMENTO_LOCALIZADO', 'TipoAmbiente' => 'PRODUCAO',
            'Erros' => [['Codigo' => 'E2240', 'Descricao' => 'Nenhum evento']]];
        $tools = $this->tools($r, 404);
        $ret = $tools->consultarEventosAdn(self::CHAVE, 1);
        $this->assertSame('https://adn.nfse.gov.br/contribuintes/NFSe/'.self::CHAVE.'/Eventos', $tools->chamadas[0][0]);
        $this->assertSame([], $ret['DOCUMENTOS']);
        $this->assertSame('E2240', $ret['ERROS'][0]['Codigo']);
    }

    public function test_tipo_nfse_so_e_aceito_quando_xml_for_realmente_nacional(): void
    {
        $xml = file_get_contents(__DIR__.'/../Fixtures/nfse-nacional-danfse.xml');
        $ret = $this->tools($this->resposta('NFSE', $xml))->consultarEventosAdn(self::CHAVE, 2);
        $this->assertSame($xml, $ret['DOCUMENTOS'][0]['XML']);
        $this->expectException(RuntimeException::class);
        $this->tools($this->resposta('NFSE'))->consultarEventosAdn(self::CHAVE, 2);
    }

    public function test_recusa_documentos_divergentes_e_compactacao_invalida(): void
    {
        $casos = [];
        $casos[] = $this->resposta('EVENTO', $this->evento(str_repeat('9', 50)));
        $casos[] = $this->resposta('EVENTO', str_replace('<tpAmb>2</tpAmb>', '<tpAmb>1</tpAmb>', $this->evento()));
        $casos[] = $this->resposta('DPS');
        $r = $this->resposta();
        $r['LoteDFe'][0]['ArquivoXml'] = 'invalido';
        $casos[] = $r;
        $r = $this->resposta();
        $r['TipoAmbiente'] = 'PRODUCAO';
        $casos[] = $r;
        foreach ($casos as $caso) {
            try {
                $this->tools($caso)->consultarEventosAdn(self::CHAVE, 2);
                $this->fail('O retorno deveria ser recusado.');
            } catch (RuntimeException $e) {
                $this->assertNotEmpty($e->getMessage());
            }
        }
    }

    public function test_consulta_por_numero_segue_xsd_speedgov(): void
    {
        $builder = new class {
            use \Alves\NfseBrasil\ProviderBuilders\ToolsProviderBuilderCommonTrait;
            use \Alves\NfseBrasil\ProviderBuilders\ToolsProviderBuilderSpeedGovTrait;

            public function consulta(array $dados): string
            {
                return $this->buildSpeedGovDataForService($dados, 'consultar_nfse');
            }
        };
        $xml = $builder->consulta(['prestador_cnpj' => '12345678000190', 'prestador_im' => '12345', 'numero_nfse' => '4692']);
        $dom = new \DOMDocument;
        $dom->loadXML($xml);
        self::assertTrue($dom->schemaValidate(__DIR__.'/../../storage/schemes/speedgov/consultar_nfse_envio_v1.xsd'));
        self::assertSame('ConsultarNfseEnvio', $dom->documentElement->localName);
        self::assertSame('4692', $dom->getElementsByTagName('NumeroNfse')->item(0)->textContent);
        $this->expectException(\RuntimeException::class);
        $builder->consulta(['prestador_cnpj' => '12345678000190', 'prestador_im' => '12345']);
    }
}
