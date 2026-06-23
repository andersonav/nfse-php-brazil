<?php

namespace Alves\NfseBrasil\Common;

use RuntimeException;

final class MunicipioCatalog
{
    private array $catalog;

    private function __construct(array $catalog)
    {
        $this->catalog = $catalog;
    }

    public static function fromCompiledFile(string $path): self
    {
        if (!is_file($path)) {
            throw new RuntimeException("Catalogo compilado nao encontrado: {$path}");
        }

        $catalog = require $path;
        if (!is_array($catalog)) {
            throw new RuntimeException("Catalogo compilado invalido: {$path}");
        }

        return new self($catalog);
    }

    public static function fromJsonFile(string $path): self
    {
        if (!is_file($path)) {
            throw new RuntimeException("Catalogo JSON nao encontrado: {$path}");
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new RuntimeException("Falha ao ler catalogo JSON: {$path}");
        }

        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw) ?? $raw;
        $raw = trim($raw);
        if (preg_match('/^\s*(?:module\.exports\s*=\s*|export\s+default\s+|window\.[A-Za-z0-9_]+\s*=\s*)(.*)\s*;?\s*$/s', $raw, $matches)) {
            $raw = trim($matches[1]);
        }

        $catalog = json_decode($raw, true);
        if (!is_array($catalog)) {
            throw new RuntimeException("Catalogo JSON invalido: {$path}");
        }

        return new self($catalog);
    }

    public static function fromIniFile(string $iniPath): self
    {
        if (!is_file($iniPath)) {
            throw new RuntimeException("Arquivo de servicos nao encontrado: {$iniPath}");
        }

        $builder = new self([
            'meta' => [],
            'providers' => [],
            'municipios' => [],
            'aliases' => [],
        ]);
        $builder->catalog = $builder->buildFromIni($iniPath);
        return $builder;
    }

    public function export(): array
    {
        return $this->catalog;
    }

    /**
     * @return array<string,array>
     */
    public function municipios(): array
    {
        $municipios = $this->catalog['municipios'] ?? [];
        return is_array($municipios) ? $municipios : [];
    }

    /**
     * @return array<string,array>
     */
    public function providers(): array
    {
        $providers = $this->catalog['providers'] ?? [];
        return is_array($providers) ? $providers : [];
    }

    public function resolve(string|int $prefeitura): ?array
    {
        $key = strtolower(trim((string) $prefeitura));
        if ($key === '') {
            return null;
        }

        $ibge = null;
        if (preg_match('/^\d{7}$/', $key)) {
            $ibge = $key;
        } elseif (isset($this->catalog['aliases'][$key])) {
            $ibge = $this->catalog['aliases'][$key];
        }

        if (!$ibge || !isset($this->catalog['municipios'][$ibge])) {
            return null;
        }

        return $this->catalog['municipios'][$ibge];
    }

    private function buildFromIni(string $iniPath): array
    {
        $sections = $this->parseIniFile($iniPath);
        $providers = [];
        $municipios = [];
        $aliases = [];

        foreach ($sections as $section => $values) {
            if (!preg_match('/^\d{7}$/', $section)) {
                $providers[$section] = $values;
                continue;
            }

            $providerName = trim((string) ($values['Provedor'] ?? ''));
            $providerDefaults = $providerName !== '' ? ($providers[$providerName] ?? []) : [];
            $merged = array_merge($providerDefaults, $values);

            $nome = trim((string) ($merged['Nome'] ?? ''));
            $uf = strtoupper(trim((string) ($merged['UF'] ?? '')));
            $alias = $nome !== '' && $uf !== '' ? $this->buildAlias($nome, $uf) : null;

            if ($alias) {
                $aliases[$alias] = $section;
            }

            $municipios[$section] = [
                'ibge' => $section,
                'nome' => $nome,
                'uf' => $uf,
                'alias' => $alias,
                'provedor' => $providerName,
                'versao' => isset($merged['Versao']) ? (string) $merged['Versao'] : null,
                'params' => isset($merged['Params']) ? (string) $merged['Params'] : null,
                'params_map' => $this->parseParams((string) ($merged['Params'] ?? '')),
                'urls' => $this->extractUrls($merged),
                'services' => $this->extractServices($merged),
                'raw' => $merged,
            ];
        }

        return [
            'meta' => [
                'source' => basename($iniPath),
                'municipios_count' => count($municipios),
                'providers_count' => count($providers),
            ],
            'providers' => $providers,
            'municipios' => $municipios,
            'aliases' => $aliases,
        ];
    }

    private function parseIniFile(string $path): array
    {
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException("Falha ao ler: {$path}");
        }
        $contents = $this->normalizeUtf8($contents);
        $lines = preg_split('/\R/u', $contents) ?: [];

        $sections = [];
        $currentSection = null;

        foreach ($lines as $line) {
            $line = $this->normalizeUtf8((string) $line);
            $line = trim($line);
            if ($line === '' || str_starts_with($line, ';')) {
                continue;
            }

            if (preg_match('/^\[(.+)]$/', $line, $matches)) {
                $currentSection = trim($this->normalizeUtf8($matches[1]));
                if (!isset($sections[$currentSection])) {
                    $sections[$currentSection] = [];
                }
                continue;
            }

            if ($currentSection === null) {
                continue;
            }

            $equalsPos = strpos($line, '=');
            if ($equalsPos === false) {
                continue;
            }

            $key = trim($this->normalizeUtf8(substr($line, 0, $equalsPos)));
            $value = trim($this->normalizeUtf8(substr($line, $equalsPos + 1)));
            if ($key === '') {
                continue;
            }

            $sections[$currentSection][$key] = $value;
        }

        return $sections;
    }

    private function buildAlias(string $nome, string $uf): string
    {
        $base = strtolower(trim($this->normalizeUtf8($nome)));
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $base);
        if ($ascii !== false) {
            $base = $ascii;
        }
        $base = preg_replace('/[^a-z0-9]+/', '-', $base);
        $base = trim((string) $base, '-');

        return $base . '-' . strtolower($uf);
    }

    private function extractUrls(array $raw): array
    {
        $urls = [
            'producao' => [],
            'homologacao' => [],
        ];

        foreach ($raw as $key => $value) {
            if (!preg_match('/^(Pro|Hom)([A-Z][A-Za-z0-9_]*)$/', $key, $matches)) {
                continue;
            }

            $env = $matches[1] === 'Pro' ? 'producao' : 'homologacao';
            $service = $this->toSnakeCase($matches[2]);
            $urls[$env][$service] = $value;
        }

        return $urls;
    }

    private function extractServices(array $raw): array
    {
        $services = [];

        foreach ($raw as $key => $_value) {
            if (!preg_match('/^(Pro|Hom)([A-Z][A-Za-z0-9_]*)$/', $key, $matches)) {
                continue;
            }
            $services[$this->toSnakeCase($matches[2])] = true;
        }

        return array_keys($services);
    }

    private function parseParams(string $params): array
    {
        $result = [];
        foreach (explode('|', $params) as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '') {
                continue;
            }
            $parts = explode(':', $chunk, 2);
            $key = trim($parts[0] ?? '');
            $value = trim($parts[1] ?? '');
            if ($key !== '') {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    private function toSnakeCase(string $value): string
    {
        $value = preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $value) ?? $value;
        $value = strtolower($value);
        $value = str_replace('__', '_', $value);

        return trim($value, '_');
    }

    private function normalizeUtf8(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (!mb_check_encoding($value, 'UTF-8')) {
            $converted = @mb_convert_encoding($value, 'UTF-8', 'Windows-1252,ISO-8859-1,UTF-8');
            if (is_string($converted) && $converted !== '') {
                $value = $converted;
            }
        }

        return preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
    }
}
