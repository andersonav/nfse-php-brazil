<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderIiBrasilLikeTrait
{
    /**
     * Helper para provedores municipalizados que usam padrão ABRASF request/cabecalho.
     *
     * @param array|string $payload
     */
    private function postIiBrasilLike(string $url, array|string $payload, string $service, ?string $versao): mixed
    {
        $xml = $this->buildIiBrasilEnvelope($payload, $service, $versao);
        $soapAction = $this->buildIiBrasilSoapAction($service);
        return $this->postXmlToUrl($url, $xml, [
            'SOAPAction: "' . $soapAction . '"',
        ]);
    }

    /**
     * @return string[]
     */
    private function iiBrasilLikeEmitProviders(): array
    {
        return [
            'egoverneiss',
            'issbarueri',
            'isscambe',
            'isscampinas',
            'isscuritiba',
            'isse',
            'issfortaleza',
            'issgoiania',
            'issjoinville',
            'isslencois',
            'issmap',
            'issnatal',
            'issrio',
            'issrecife',
            'isssalvador',
            'isssjp',
            'issvitoria',
            'isscamacari',
            'issportovelho',
            'lexsom',
            'libre',
            'nfeletronica',
            'notainteligente',
            'prodaub',
            'safeweb',
            'simple',
            'smart4',
            'ssinformatica',
        ];
    }

    /**
     * @return string[]
     */
    private function iiBrasilLikeCancelProviders(): array
    {
        return [
            'egoverneiss',
            'issbarueri',
            'isscambe',
            'isscampinas',
            'isscuritiba',
            'isse',
            'issfortaleza',
            'issgoiania',
            'issjoinville',
            'isslencois',
            'issmap',
            'issnatal',
            'issrio',
            'issrecife',
            'isssalvador',
            'isssjp',
            'issvitoria',
            'isscamacari',
            'issportovelho',
            'lexsom',
            'libre',
            'nfeletronica',
            'notainteligente',
            'prodaub',
            'safeweb',
            'simple',
            'smart4',
            'ssinformatica',
        ];
    }

    /**
     * @return string[]
     */
    private function iiBrasilLikeSubstituirProviders(): array
    {
        return [
            'egoverneiss',
            'issbarueri',
            'isscambe',
            'isscampinas',
            'isscuritiba',
            'isse',
            'issfortaleza',
            'issgoiania',
            'issjoinville',
            'issmap',
            'issnatal',
            'issrecife',
            'isssalvador',
            'issvitoria',
            'isscamacari',
            'issportovelho',
            'libre',
            'notainteligente',
            'prodaub',
            'safeweb',
            'ssinformatica',
        ];
    }
}
