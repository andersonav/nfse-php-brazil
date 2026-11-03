<?php

declare(strict_types=1);

namespace Alves\NfseBrasil\Provider;

use DOMDocument;
use DOMElement;
use DOMXPath;
use NFePHP\Common\Certificate;
use RuntimeException;

/** XMLDSig do contrato SpeedGov: preserva a assinatura do RPS ao assinar o lote. */
final class SpeedGovXmlSigner
{
    private const DS = 'http://www.w3.org/2000/09/xmldsig#';
    private const C14N = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';

    public static function sign(Certificate $certificate, string $xml, string $tag, string $mark, string $parent): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        if (!@$dom->loadXML($xml, LIBXML_NONET) || $dom->doctype !== null) {
            throw new RuntimeException('XML SpeedGov inválido para assinatura.');
        }
        $targets = $dom->getElementsByTagName($tag);
        $parents = $dom->getElementsByTagName($parent);
        if ($targets->length !== 1 || $parents->length !== 1) {
            throw new RuntimeException('Elemento de assinatura SpeedGov ausente ou ambíguo: ' . $tag);
        }
        $target = $targets->item(0);
        $container = $parents->item(0);
        $id = $target->getAttribute($mark);
        if ($id === '' || $target->parentNode !== $container) {
            throw new RuntimeException('Identificador ou posição inválida para assinatura SpeedGov: ' . $tag);
        }
        $xp = new DOMXPath($dom);
        $xp->registerNamespace('ds', self::DS);
        if ($xp->query('./ds:Signature', $container)->length !== 0) {
            throw new RuntimeException('O elemento SpeedGov já possui assinatura: ' . $tag);
        }
        // A canonicalização deve coincidir com o algoritmo declarado, inclusive namespaces ancestrais.
        $digest = base64_encode(hash('sha1', $target->C14N(false, false), true));
        $signature = $dom->createElementNS(self::DS, 'Signature');
        $container->appendChild($signature);
        $add = static function (DOMElement $node, string $name, ?string $value = null) use ($dom): DOMElement {
            $child = $dom->createElementNS(self::DS, $name);
            if ($value !== null) {
                $child->appendChild($dom->createTextNode($value));
            }
            $node->appendChild($child);
            return $child;
        };
        $info = $add($signature, 'SignedInfo');
        $add($info, 'CanonicalizationMethod')->setAttribute('Algorithm', self::C14N);
        $add($info, 'SignatureMethod')->setAttribute('Algorithm', self::DS . 'rsa-sha1');
        $reference = $add($info, 'Reference');
        $reference->setAttribute('URI', '#' . $id);
        $transforms = $add($reference, 'Transforms');
        $add($transforms, 'Transform')->setAttribute('Algorithm', self::DS . 'enveloped-signature');
        $add($transforms, 'Transform')->setAttribute('Algorithm', self::C14N);
        $add($reference, 'DigestMethod')->setAttribute('Algorithm', self::DS . 'sha1');
        $add($reference, 'DigestValue', $digest);
        $add($signature, 'SignatureValue', base64_encode($certificate->sign($info->C14N(false, false), OPENSSL_ALGO_SHA1)));
        $key = $add($signature, 'KeyInfo');
        $x509 = $add($key, 'X509Data');
        $add($x509, 'X509Certificate', $certificate->publicKey->unFormated());

        return $dom->saveXML($dom->documentElement);
    }
}
