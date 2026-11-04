<?php

declare(strict_types=1);

namespace Alves\NfseBrasil\Tests\Unit;

use Alves\NfseBrasil\Tools;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SpeedGovXmlNacionalTest extends TestCase
{
    private const CHAVE = '23044001234567800019000000000000000000000000000000';
    private const URL = 'https://tributario.speedgov.com.br/integracao/api/v1';

    private function xml(): string
    {
        return file_get_contents(__DIR__.'/../Fixtures/nfse-nacional-danfse.xml');
    }

    private function tools(array $nota, int $http = 200): Tools
    {
        return new class($nota, $http) extends Tools {
            public array $requests = [];

            public function __construct(private array $nota, private int $http) {}

            public function getFromUrlWithHeaders(string $url, array $headers = [], bool $verifyTls = false): mixed
            {
                $this->requests[] = [$url, $headers, $verifyTls];
                $this->soapinfo = ['http_code' => $this->http];
                return ['status' => 'OK', 'nota' => $this->nota];
            }
        };
    }

    public function test_retorna_xml_original_da_chave_solicitada(): void
    {
        $tools = $this->tools(['chave_acesso' => self::CHAVE, 'codigo_situacao_nfse' => 100, 'xml_nota' => $this->xml()]);
        self::assertSame($this->xml(), $tools->consultarXmlNacionalSpeedGov(self::CHAVE, self::URL, 2));
        self::assertSame([[self::URL.'/nfse/'.self::CHAVE, ['Accept: application/json'], true]], $tools->requests);
    }

    public function test_retorna_null_enquanto_so_existe_dps(): void
    {
        $tools = $this->tools(['chave_acesso' => self::CHAVE, 'codigo_situacao_nfse' => null, 'xml_nota' => '<DPS/>']);
        self::assertNull($tools->consultarXmlNacionalSpeedGov(self::CHAVE, self::URL, 2));
    }

    public function test_erro_http_nao_e_aceito_como_autorizacao(): void
    {
        $this->expectExceptionMessage('HTTP 503');
        $this->tools([], 503)->consultarXmlNacionalSpeedGov(self::CHAVE, self::URL, 2);
    }

    public function test_recusa_chave_diferente_no_json(): void
    {
        $this->expectExceptionMessage('chave diferente');
        $this->tools(['chave_acesso' => str_repeat('1', 50)])->consultarXmlNacionalSpeedGov(self::CHAVE, self::URL, 2);
    }

    public function test_nao_consulta_sandbox_com_nota_de_producao(): void
    {
        $this->expectExceptionMessage('sandbox');
        $this->tools([])->consultarXmlNacionalSpeedGov(self::CHAVE, self::URL, 1);
    }

    public function test_nao_aceita_endereco_soap_ou_http(): void
    {
        foreach (['http://speedgov.com.br/wsmod/Nfes', 'http://tributario.speedgov.com.br/integracao/api/v1'] as $url) {
            try {
                $this->tools([])->consultarXmlNacionalSpeedGov(self::CHAVE, $url, 2);
                self::fail('A URL deveria ser recusada.');
            } catch (RuntimeException $e) {
                self::assertStringContainsString('URL REST', $e->getMessage());
            }
        }
    }

    public function test_confere_chave_ambiente_namespace_autorizacao_e_dps(): void
    {
        $invalidos = [
            str_replace(self::CHAVE, str_repeat('9', 50), $this->xml()),
            str_replace('<tpAmb>2</tpAmb>', '<tpAmb>1</tpAmb>', $this->xml()),
            str_replace('http://www.sped.fazenda.gov.br/nfse', 'urn:municipal', $this->xml()),
            str_replace('<cStat>100</cStat>', '<cStat>0</cStat>', $this->xml()),
            '<DPS xmlns="http://www.sped.fazenda.gov.br/nfse"/>',
            '<!DOCTYPE NFSe [<!ENTITY x SYSTEM "file:///etc/passwd">]><NFSe>&x;</NFSe>',
        ];
        foreach ($invalidos as $xml) {
            try {
                Tools::validarXmlNacionalSpeedGov($xml, self::CHAVE, 2);
                self::fail('O XML deveria ser recusado.');
            } catch (RuntimeException $e) {
                self::assertNotEmpty($e->getMessage());
            }
        }
    }
}
