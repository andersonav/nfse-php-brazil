<?php

declare(strict_types=1);

namespace Alves\NfseBrasil\Tests\Unit;

use Alves\NfseBrasil\Tools;
use PHPUnit\Framework\TestCase;

final class MunicipioCatalogTest extends TestCase
{
    private function makeTools(string $prefeitura = '3550308'): Tools
    {
        $config = [
            'tpamb' => 2,
            'prefeitura' => $prefeitura,
            'catalog_compiled_path' => __DIR__ . '/../../storage/municipios-catalog.php',
        ];

        return new Tools((string) json_encode($config));
    }

    public function testDetalhesMunicipioRetornaEstruturaCompleta(): void
    {
        $tools = $this->makeTools();
        $info = $tools->detalhesMunicipio();

        self::assertTrue($info['resolved']);
        self::assertArrayHasKey('ibge', $info);
        self::assertArrayHasKey('provedor', $info);
        self::assertArrayHasKey('services_catalog', $info);
        self::assertArrayHasKey('services_supported', $info);
        self::assertArrayHasKey('url_matrix', $info);
        self::assertArrayHasKey('capabilities', $info);
        self::assertArrayHasKey('integration_mode', $info['capabilities']);
    }

    public function testConsultaMunicipioPorOutroIbgeFunciona(): void
    {
        $tools = $this->makeTools();
        $info = $tools->detalhesMunicipio('2611606'); // Recife

        self::assertTrue($info['resolved']);
        self::assertSame('2611606', (string) $info['ibge']);
        self::assertNotEmpty($info['provedor']);
    }

    public function testListagemMunicipiosComFiltroUf(): void
    {
        $tools = $this->makeTools();
        $rows = $tools->listarMunicipios('SP', null, 20);

        self::assertNotEmpty($rows);
        foreach ($rows as $row) {
            self::assertSame('SP', $row['uf']);
            self::assertNotEmpty($row['ibge']);
            self::assertNotEmpty($row['provedor']);
        }
    }
}
