<?php

declare(strict_types=1);

namespace Alves\NfseBrasil\Tests\Integration;

use Alves\NfseBrasil\Tools;
use NFePHP\Common\Certificate;
use PHPUnit\Framework\TestCase;

final class RealEmissionTest extends TestCase
{
    private static function enabled(): bool
    {
        return getenv('NFSE_REAL_TESTS') === '1';
    }

    private static function env(string $key): ?string
    {
        $value = getenv($key);
        if ($value === false) {
            return null;
        }
        $value = trim($value);
        return $value === '' ? null : $value;
    }

    private static function requireEnv(string $key): string
    {
        $value = self::env($key);
        self::assertNotNull($value, "Variavel de ambiente obrigatoria ausente: {$key}");
        return (string) $value;
    }

    protected function setUp(): void
    {
        if (!self::enabled()) {
            self::markTestSkipped('NFSE_REAL_TESTS != 1. Testes reais desabilitados.');
        }
    }

    private function makeTools(): Tools
    {
        $catalogPath = self::env('NFSE_CATALOG_PATH') ?: __DIR__ . '/../../storage/municipios-catalog.php';
        $prefeitura = self::requireEnv('NFSE_PREFEITURA');
        $tpAmb = (int) (self::env('NFSE_TPAMB') ?: '2');

        $pfxPath = self::requireEnv('NFSE_CERT_PFX_PATH');
        self::assertFileExists($pfxPath, "PFX nao encontrado em {$pfxPath}");
        $pfxPass = self::requireEnv('NFSE_CERT_PFX_PASS');
        $pfx = (string) file_get_contents($pfxPath);
        $cert = Certificate::readPfx($pfx, $pfxPass);

        $config = [
            'tpamb' => $tpAmb,
            'prefeitura' => $prefeitura,
            'catalog_compiled_path' => $catalogPath,
            'catalog_ini_path' => self::env('NFSE_CATALOG_INI_PATH'),
        ];

        return new Tools((string) json_encode($config), $cert);
    }

    public function testDiagnosticoMunicipioReal(): void
    {
        $tools = $this->makeTools();
        $info = $tools->detalhesMunicipio();

        self::assertTrue($info['resolved']);
        self::assertNotEmpty($info['ibge']);
        self::assertNotEmpty($info['provedor']);
        self::assertIsArray($info['services_effective']);
    }

    public function testConsultaNacionalOpcionalPorChave(): void
    {
        $chave = self::env('NFSE_TEST_CHAVE');
        if (!$chave) {
            self::markTestSkipped('NFSE_TEST_CHAVE nao informada.');
        }

        $tools = $this->makeTools();
        $result = $tools->consultarNfseChave($chave, false);

        self::assertNotNull($result);
    }

    public function testEmitirMunicipalOpcionalComPayload(): void
    {
        $payloadPath = self::env('NFSE_TEST_EMITIR_PAYLOAD_JSON');
        if (!$payloadPath) {
            self::markTestSkipped('NFSE_TEST_EMITIR_PAYLOAD_JSON nao informado.');
        }
        self::assertFileExists($payloadPath);
        $payload = (string) file_get_contents($payloadPath);

        $service = self::env('NFSE_TEST_EMITIR_SERVICE') ?: 'recepcionar';

        $tools = $this->makeTools();
        $result = $tools->emitirNfseMunicipal($payload, $service);

        self::assertNotNull($result);
    }

    public function testCancelarMunicipalOpcionalComPayload(): void
    {
        $payloadPath = self::env('NFSE_TEST_CANCELAR_PAYLOAD_JSON');
        if (!$payloadPath) {
            self::markTestSkipped('NFSE_TEST_CANCELAR_PAYLOAD_JSON nao informado.');
        }
        self::assertFileExists($payloadPath);
        $payload = (string) file_get_contents($payloadPath);

        $service = self::env('NFSE_TEST_CANCELAR_SERVICE') ?: 'cancelar_nf_se';

        $tools = $this->makeTools();
        $result = $tools->cancelarNfseMunicipal($payload, $service);

        self::assertNotNull($result);
    }
}
