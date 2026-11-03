<?php

declare(strict_types=1);
namespace Alves\NfseBrasil\Tests\Unit;

use Alves\NfseBrasil\Tools;
use Alves\NfseBrasil\Provider\SpeedGovSchemaValidator;
use DOMDocument;
use DOMXPath;
use NFePHP\Common\Certificate;
use PHPUnit\Framework\TestCase;

final class SpeedGovSignedContractTest extends TestCase
{
    private function tools(): Tools
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        $csr = openssl_csr_new(['commonName' => 'Teste local SpeedGov'], $key);
        $x509 = openssl_csr_sign($csr, null, $key, 1);
        openssl_pkcs12_export($x509, $pfx, $key, 'teste');
        return new class(json_encode(['tpamb' => '2', 'prefeitura' => '2307650']), Certificate::readPfx($pfx, 'teste')) extends Tools {
            public int $postsXml = 0;
            public int $postsJson = 0;
            public function build(array $data): string { return (new \ReflectionMethod(Tools::class, 'buildSpeedGovRecepcionarEnvelope'))->invoke($this, $data); }
            public function postXmlToUrl(string $url, string $xml, array $headers = []): mixed {
                $this->postsXml++;
                throw new \RuntimeException('Timeout simulado após POST SOAP');
            }
            public function postJsonToUrl(string $url, array|string $data): mixed {
                $this->postsJson++;
                return 'reenvio indevido';
            }
        };
    }
    private function payload(): array { return json_decode(file_get_contents(__DIR__.'/../Fixtures/nfse-speedgov-envio.json'), true); }
    private function documento(string $xml): DOMDocument {
        $dom = new DOMDocument();
        $dom->loadXML($xml, LIBXML_NONET);
        return $dom;
    }
    public function testContratoFinalPossuiDuasAssinaturasValidasEPassaNoXsdFornecido(): void
    {
        $soap = $this->documento($this->tools()->build($this->payload()));
        $xp = new DOMXPath($soap);
        $xml = $xp->evaluate('string(//*[local-name()="parameters"])');
        SpeedGovSchemaValidator::validarEnvio($xml);
        $dom = $this->documento($xml);
        $xp = new DOMXPath($dom);
        $xp->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
        self::assertSame(2, $xp->query('//ds:Signature')->length);
        self::assertSame(1, $xp->query('/*/ds:Signature')->length);
        self::assertSame(1, $xp->query('//*[local-name()="Rps"]/ds:Signature')->length);
        foreach ($xp->query('//ds:Signature') as $signature) {
            $info = $xp->query('./ds:SignedInfo', $signature)->item(0);
            $uri = $xp->evaluate('string(ds:Reference/@URI)', $info);
            self::assertNotSame('', $uri);
            $target = $xp->query('//*[@Id="'.substr($uri, 1).'"]')->item(0);
            self::assertNotNull($target);
            self::assertSame(base64_encode(hash('sha1', $target->C14N(false, false), true)), $xp->evaluate('string(ds:Reference/ds:DigestValue)', $info));
            $cert = "-----BEGIN CERTIFICATE-----\n".$xp->evaluate('string(ds:KeyInfo/ds:X509Data/ds:X509Certificate)', $signature)."\n-----END CERTIFICATE-----";
            $value = base64_decode($xp->evaluate('string(ds:SignatureValue)', $signature));
            self::assertSame(1, openssl_verify($info->C14N(false, false), $value, $cert, OPENSSL_ALGO_SHA1));
        }
        self::assertSame(1, $xp->query('//*[local-name()="finNFSe"]')->length);
        self::assertSame(0, $xp->query('//*[local-name()="FinNFSe"]')->length);
    }
    public function testTimeoutNaoReenviaPayloadComoJson(): void
    {
        $tools = $this->tools();
        try {
            $tools->emitirNfseMunicipal($this->payload());
            self::fail('Timeout ocultado');
        } catch (\RuntimeException $e) {
            self::assertSame('Timeout simulado após POST SOAP', $e->getMessage());
        }
        self::assertSame(1, $tools->postsXml);
        self::assertSame(0, $tools->postsJson);
    }
    public function testLoteComMaisDeUmRpsNaoDescartaItensSilenciosamente(): void
    {
        $p = $this->payload(); $p['rps'][] = $p['rps'][0];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('exatamente um RPS');
        $this->tools()->build($p);
    }

    public function testItemDaListaPreservaQuatroDigitosComoNoXmlAutorizado(): void
    {
        $tools = $this->tools();
        foreach (['104', '0104', '1.04', '01.04'] as $codigo) {
            $payload = $this->payload();
            $payload['rps'][0]['servico']['item_lista_servico'] = $codigo;
            $soap = $this->documento($tools->build($payload));
            $xml = (new DOMXPath($soap))->evaluate('string(//*[local-name()="parameters"])');
            self::assertSame('0104', (new DOMXPath($this->documento($xml)))->evaluate('string(//*[local-name()="ItemListaServico"])'));
        }
    }
}
