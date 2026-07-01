<?php
declare(strict_types=1);

namespace Alves\NfseBrasil\Utils;

final class NfseCodigoPadrao
{
    /** @return array<string,string> */
    public static function ambiente(): array
    {
        return [
            '1' => 'Produção',
            '2' => 'Homologação',
        ];
    }

    /** @return array<string,string> */
    public static function situacaoNfse(): array
    {
        return [
            '100' => 'Autorizada',
            '101' => 'Cancelada',
        ];
    }

    /** @return array<string,string> */
    public static function finalidadeNfse(): array
    {
        return [
            '1' => 'Normal',
            '2' => 'Substituição',
            '3' => 'Ajuste',
        ];
    }

    /** @return array<string,string> */
    public static function simplesNacionalCompetencia(): array
    {
        return [
            '1' => 'Sim',
            '2' => 'Não',
        ];
    }

    /** @return array<string,string> */
    public static function regimeApuracaoSn(): array
    {
        return [
            '1' => 'Caixa',
            '2' => 'Competência',
        ];
    }

    /** @return array<string,string> */
    public static function regimeEspecialIssqn(): array
    {
        return [
            '0' => 'Nenhum',
            '1' => 'Microempresa',
            '2' => 'Estimativa',
            '3' => 'Soc. Profissionais',
            '4' => 'Cooperativa',
            '5' => 'MEI',
            '6' => 'ME/EPP SN',
        ];
    }

    /** @return array<string,string> */
    public static function tipoTributacaoIssqn(): array
    {
        return [
            '1' => 'Tributação no município',
            '2' => 'Fora do município',
            '3' => 'Isenção',
            '4' => 'Imune',
            '5' => 'Exig. suspensa judicial',
            '6' => 'Exig. suspensa administrativa',
        ];
    }

    /** @return array<string,string> */
    public static function retencaoIssqn(): array
    {
        return [
            '1' => 'Sim',
            '2' => 'Não',
        ];
    }

    /**
     * Lê o catálogo PHP de códigos de tributação nacional.
     *
     * @return array<string,string>
     */
    public static function codigosTributacaoNacional(): array
    {
        static $cache = null;
        if (is_array($cache)) {
            return $cache;
        }

        $path = __DIR__ . '/data/codigos_tributacao_nacional.php';
        if (!is_file($path)) {
            $cache = [];
            return $cache;
        }

        $catalogo = require $path;
        if (!is_array($catalogo)) {
            $cache = [];
            return $cache;
        }

        $map = [];
        foreach ($catalogo as $codigo => $descricao) {
            $codigo = trim((string) $codigo);
            $descricao = self::normalizarTexto((string) $descricao);
            if ($codigo === '' || $descricao === '') {
                continue;
            }
            $map[$codigo] = $descricao;
        }

        $cache = $map;
        return $cache;
    }

    public static function descricaoTributacaoNacional(string $codigo): string
    {
        $codigo = trim($codigo);
        if ($codigo === '') {
            return '';
        }
        $map = self::codigosTributacaoNacional();
        return $map[$codigo] ?? '';
    }

    private static function normalizarTexto(string $texto): string
    {
        $texto = trim($texto);
        if ($texto === '') {
            return '';
        }

        if (preg_match('/(?:Ã.|Â.|â.|Ê.|Õ.|Ç.|�)/u', $texto) === 1) {
            $corrigido = @iconv('Windows-1252', 'UTF-8//IGNORE', $texto);
            if (is_string($corrigido) && $corrigido !== '') {
                $texto = $corrigido;
            }
        }

        return strtr($texto, [
            "\u{2013}" => '-',
            "\u{2014}" => '-',
            "\u{2018}" => "'",
            "\u{2019}" => "'",
            "\u{201C}" => '"',
            "\u{201D}" => '"',
            "\u{2026}" => '...',
            "\u{00A0}" => ' ',
        ]);
    }
}
