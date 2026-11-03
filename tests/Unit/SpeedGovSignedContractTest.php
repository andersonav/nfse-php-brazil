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
    public function testRecepcaoPreservaAssinaturaDoContratoProtocoladoEPassaNoXsd(): void
    {
        $soap = $this->documento($this->tools()->build($this->payload()));
        $xp = new DOMXPath($soap);
        $xml = $xp->evaluate('string(//*[local-name()="parameters"])');
        SpeedGovSchemaValidator::validarEnvio($xml);
        $dom = $this->documento($xml);
        $xp = new DOMXPath($dom);
        $xp->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
        self::assertSame(1, $xp->query('//ds:Signature')->length);
        self::assertSame(0, $xp->query('/*/ds:Signature')->length);
        self::assertSame(0, $xp->query('//*[local-name()="InfRps"]/@Id')->length);
        self::assertSame(1, $xp->query('//*[local-name()="Rps"]/ds:Signature')->length);
        foreach ($xp->query('//ds:Signature') as $signature) {
            $info = $xp->query('./ds:SignedInfo', $signature)->item(0);
            $uri = $xp->evaluate('string(ds:Reference/@URI)', $info);
            self::assertSame('', $uri);
            $target = clone $xp->query('//*[local-name()="Rps"]')->item(0);
            foreach (iterator_to_array($target->getElementsByTagNameNS('http://www.w3.org/2000/09/xmldsig#', 'Signature')) as $child) { $child->parentNode->removeChild($child); }
            $digestDom = new DOMDocument();
            $digestDom->appendChild($digestDom->importNode($target, true));
            $target = $digestDom->documentElement;
            self::assertNotNull($target);
            self::assertSame(base64_encode(hash('sha1', $target->C14N(true, false), true)), $xp->evaluate('string(ds:Reference/ds:DigestValue)', $info));
            $cert = "-----BEGIN CERTIFICATE-----\n".$xp->evaluate('string(ds:KeyInfo/ds:X509Data/ds:X509Certificate)', $signature)."\n-----END CERTIFICATE-----";
            $value = base64_decode($xp->evaluate('string(ds:SignatureValue)', $signature));
            self::assertSame(1, openssl_verify($info->C14N(true, false), $value, $cert, OPENSSL_ALGO_SHA1));
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

    public function testEstruturaEConteudoReproduzemReferenciaProtocoladaDeSetembro(): void
    {
        // Estrutura fornecida pelo integrador, com dados pessoais e certificado substituídos.
        $referencia = $this->documento(file_get_contents(__DIR__.'/../Fixtures/speedgov-recepcao-protocolada-estrutura.xml'));
        $xp = new DOMXPath($referencia);
        $get = static function (string $path) use ($xp): string {
            $parts = array_map(static fn ($part) => '*[local-name()="'.$part.'"]', explode('/', $path));
            return $xp->evaluate('string(//*[local-name()="InfRps"]/'.implode('/', $parts).')');
        };
        $map = static function (string $prefix, array $campos) use ($get): array {
            $ret = [];
            foreach ($campos as $campo => $tag) { $ret[$campo] = $get($prefix.$tag); }
            return $ret;
        };
        $rps = $map('', ['data_emissao' => 'DataEmissao', 'natureza_operacao' => 'NaturezaOperacao',
            'optante_simples_nacional' => 'OptanteSimplesNacional', 'incentivador_cultural' => 'IncentivadorCultural', 'status' => 'Status']);
        $rps['identificacao'] = $map('IdentificacaoRps/', ['numero' => 'Numero', 'serie' => 'Serie', 'tipo' => 'Tipo']);
        $rps['prestador'] = $map('Prestador/', ['cnpj' => 'Cnpj', 'inscricao_municipal' => 'InscricaoMunicipal']);
        $rps['servico'] = $map('Servico/', ['item_lista_servico' => 'ItemListaServico', 'codigo_cnae' => 'CodigoCnae',
            'codigo_tributacao_municipio' => 'CodigoTributacaoMunicipio', 'discriminacao' => 'Discriminacao',
            'codigo_municipio' => 'CodigoMunicipio', 'codigo_nbs' => 'cNBS']);
        $rps['servico'] += $map('Servico/Valores/', ['valor_servicos' => 'ValorServicos', 'valor_deducoes' => 'ValorDeducoes',
            'valor_pis' => 'ValorPis', 'valor_cofins' => 'ValorCofins', 'valor_inss' => 'ValorInss', 'valor_ir' => 'ValorIr',
            'valor_csll' => 'ValorCsll', 'iss_retido' => 'IssRetido', 'valor_iss' => 'ValorIss', 'valor_iss_retido' => 'ValorIssRetido',
            'outras_retencoes' => 'OutrasRetencoes', 'base_calculo' => 'BaseCalculo', 'aliquota' => 'Aliquota',
            'valor_liquido_nfse' => 'ValorLiquidoNfse', 'desconto_condicionado' => 'DescontoCondicionado',
            'desconto_incondicionado' => 'DescontoIncondicionado', 'cst_pis_cofins' => 'CSTPisCofins',
            'base_calculo_pis_cofins' => 'BaseCalculoPisCofins', 'tipo_retencao_pis_cofins' => 'TipoRetencaoPisCofins',
            'aliquota_pis' => 'AliqPis', 'aliquota_cofins' => 'AliqCofins']);
        $rps['tomador'] = $map('Tomador/', ['documento' => 'IdentificacaoTomador/CpfCnpj/Cnpj', 'nome_razao_social' => 'RazaoSocial',
            'endereco' => 'Endereco/Endereco', 'numero' => 'Endereco/Numero', 'bairro' => 'Endereco/Bairro',
            'codigo_municipio' => 'Endereco/CodigoMunicipio', 'uf' => 'Endereco/Uf', 'cep' => 'Endereco/Cep', 'telefone' => 'Contato/Telefone']);
        $rps['dados_dps'] = $map('DadosDPS/', ['tp_emit' => 'TpEmit', 'tp_amb' => 'TpAmb', 'dh_emi' => 'DhEmi', 'ver_aplic' => 'VerAplic',
            'c_loc_emi' => 'CLocEmi', 'c_loc_prestacao' => 'CLocPrestacao', 'c_trib_nac' => 'CTribNac', 'trib_issqn' => 'TribIssqn',
            'tp_ret_issqn' => 'TpRetIssqn', 'op_simp_nac' => 'OpSimpNac', 'reg_esp_trib' => 'RegEspTrib',
            'reg_ap_trib_sn' => 'RegApTribSN', 'serie' => 'serie', 'numero' => 'nDPS', 'data_competencia' => 'dCompet']);
        $rps['data_competencia'] = $get('DataCompetencia');
        $lote = $xp->evaluate('string(//*[local-name()="NumeroLote"])');
        $soap = $this->documento($this->tools()->build(['validar_schema' => true, 'lote' => ['numero_lote' => $lote], 'rps' => [$rps]]));
        $xml = (new DOMXPath($soap))->evaluate('string(//*[local-name()="parameters"])');
        $gerado = $this->documento($xml);
        SpeedGovSchemaValidator::validarEnvio($xml);
        foreach ([$referencia, $gerado] as $dom) {
            foreach (['DigestValue', 'SignatureValue', 'X509Certificate'] as $tag) {
                $dom->getElementsByTagName($tag)->item(0)->nodeValue = 'AA==';
            }
        }
        // C14N ignora somente detalhes de serialização; compara namespaces, ordem, atributos e valores.
        self::assertSame($referencia->C14N(), $gerado->C14N());
    }
}
