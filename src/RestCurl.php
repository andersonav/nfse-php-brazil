<?php

namespace Alves\NfseBrasil;

use Exception;
use Alves\NfseBrasil\Common\CatalogConfig;
use Alves\NfseBrasil\Common\MunicipioCatalog;
use Alves\NfseBrasil\Common\RestBase;
use Alves\NfseBrasil\Provider\ProviderProfile;
use Alves\NfseBrasil\Provider\ProviderAdapterFactory;
use Alves\NfseBrasil\Provider\ProviderAdapterRegistry;
use Alves\NfseBrasil\Provider\Contract\ProviderAdapterInterface;
use NFePHP\Common\Certificate;
use NFePHP\Common\Exception\SoapException;
use NFePHP\Common\Signer;
use RuntimeException;

class RestCurl extends RestBase
{
    const DEFAULT_URLS = [
        "sefin_homologacao" => "https://sefin.producaorestrita.nfse.gov.br/SefinNacional",
        "sefin_producao" => "https://sefin.nfse.gov.br/sefinnacional",
        "adn_homologacao" => "https://adn.producaorestrita.nfse.gov.br",
        "adn_producao" => "https://adn.nfse.gov.br",
        "nfse_homologacao" => "https://www.producaorestrita.nfse.gov.br/EmissorNacional",
        "nfse_producao" => "https://www.nfse.gov.br/EmissorNacional"
    ];
    const DEFAULT_OPERATIONS = [
        "consultar_nfse" => "nfse/{chave}",
        "consultar_dps" => "dps/{chave}",
        "consultar_eventos" => "nfse/{chave}/eventos/{tipoEvento}/{nSequencial}",
        "consultar_danfse" => "danfse/{chave}",
        "consultar_danfse_nfse_certificado" => "Certificado",
        "consultar_danfse_nfse_download" => "Notas/Download/DANFSe/{chave}",
        "emitir_nfse" => "nfse",
        "cancelar_nfse" => "nfse/{chave}/eventos"
    ];
    private $urls = [];
    private $operations = [];
    private mixed $config;
    private string $url_api;
    private $connection_timeout = 30;
    private $timeout = 30;
    private $httpver;
    public string $soaperror;
    public int $soaperror_code;
    public array $soapinfo;
    public string $responseHead;
    public string $responseBody;
    public string $requestBody = '';
    private string $requestHead;
    private string $cookies = '';
    private array $municipioContext = [];
    private ProviderAdapterRegistry $providerRegistry;
    private ?MunicipioCatalog $catalog = null;
    private ?array $deepProviderRules = null;

    protected $canonical = [true, false, null, null];

    public function __construct(string $config, ?Certificate $cert = null)
    {
        parent::__construct($cert);
        $this->config = json_decode($config);
        $this->certificate = $cert;
        $this->providerRegistry = ProviderAdapterFactory::createDefaultRegistry();
        $configFile = $this->config->provider_overrides_path ?? null;

        $this->loadConfigOverrides($configFile, $this->config->prefeitura ?? null);
    }

    private function loadConfigOverrides($jsonFile, $context): void
    {
        $json = [];
        if (is_string($jsonFile) && $jsonFile !== '' && is_file($jsonFile)) {
            $json = json_decode(file_get_contents($jsonFile) ?: "", true);
        }
        if (!is_array($json)) {
            throw new RuntimeException("JSON invalido em $jsonFile");
        }

        $contextData = [];
        if ($context !== null && $context !== '') {
            $contextData = $json[$context] ?? [];
        }

        $this->urls = $this->mergeDefaults(self::DEFAULT_URLS, $contextData['urls'] ?? []);
        $this->operations = $this->mergeDefaults(self::DEFAULT_OPERATIONS, $contextData['operations'] ?? []);

        $this->municipioContext = $this->resolveMunicipioContext($context);
    }

    private function resolveMunicipioContext($context): array
    {
        if ($context === null || $context === '') {
            return [];
        }

        $catalog = $this->loadMunicipioCatalog();
        if (!$catalog) {
            return [];
        }

        $resolved = $catalog->resolve((string) $context);
        return is_array($resolved) ? $resolved : [];
    }

    private function loadMunicipioCatalog(): ?MunicipioCatalog
    {
        if ($this->catalog instanceof MunicipioCatalog) {
            return $this->catalog;
        }

        $compiledPath = $this->config->catalog_compiled_path ?? CatalogConfig::defaultCompiledPath();
        if (is_string($compiledPath) && is_file($compiledPath)) {
            $this->catalog = MunicipioCatalog::fromCompiledFile($compiledPath);
            return $this->catalog;
        }

        $jsonPath = $this->config->catalog_json_path ?? CatalogConfig::defaultJsonPath();
        if (is_string($jsonPath) && is_file($jsonPath)) {
            $this->catalog = MunicipioCatalog::fromJsonFile($jsonPath);
            return $this->catalog;
        }

        $iniPath = $this->config->catalog_ini_path ?? null;
        if (is_string($iniPath) && is_file($iniPath)) {
            $this->catalog = MunicipioCatalog::fromIniFile($iniPath);
            return $this->catalog;
        }

        return null;
    }

    private function mergeDefaults(array $defaults, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            if (array_key_exists($key, $defaults)) {
                $defaults[$key] = $value;
            }
        }
        return $defaults;
    }

    public function getOperation($operation)
    {
        return $this->operations[$operation];
    }

    public function getMunicipioContext(): array
    {
        return $this->municipioContext;
    }

    public function getMunicipioServiceUrl(string $service, ?int $tpAmb = null): ?string
    {
        if (empty($this->municipioContext['urls']) || !is_array($this->municipioContext['urls'])) {
            return null;
        }

        $ambiente = ($tpAmb ?? ($this->config->tpamb ?? 2)) === 1 ? 'producao' : 'homologacao';
        return $this->municipioContext['urls'][$ambiente][$service] ?? null;
    }

    public function getProviderProfile(): ?ProviderProfile
    {
        if (empty($this->municipioContext)) {
            return null;
        }
        return new ProviderProfile($this->municipioContext);
    }

    public function resolveProviderServiceUrl(
        string $service,
        ?ProviderAdapterRegistry $registry = null,
        ?int $tpAmb = null
    ): ?string {
        $profile = $this->getProviderProfile();
        if (!$profile) {
            return null;
        }

        $ambient = $tpAmb ?? ($this->config->tpamb ?? 2);
        $provider = $profile->provedor();

        $activeRegistry = $registry ?? $this->providerRegistry;
        $adapter = $activeRegistry->resolve($provider);
        if (!$adapter) {
            return null;
        }
        return $adapter->buildServiceUrl($profile, $service, $ambient);
    }

    public function resolveProviderAdapter(?ProviderAdapterRegistry $registry = null): ?ProviderAdapterInterface
    {
        $profile = $this->getProviderProfile();
        if (!$profile) {
            return null;
        }

        $activeRegistry = $registry ?? $this->providerRegistry;
        return $activeRegistry->resolve($profile->provedor());
    }

    public function isMunicipioSupported(?ProviderAdapterRegistry $registry = null): bool
    {
        $profile = $this->getProviderProfile();
        if (!$profile) {
            return false;
        }

        $activeRegistry = $registry ?? $this->providerRegistry;
        return $activeRegistry->resolve($profile->provedor()) !== null;
    }

    public function getMunicipioSupportStatus(?ProviderAdapterRegistry $registry = null): array
    {
        $profile = $this->getProviderProfile();
        if (!$profile) {
            return [
                'resolved' => false,
                'supported' => false,
                'reason' => 'Municipio nao encontrado no catalogo.'
            ];
        }

        $activeRegistry = $registry ?? $this->providerRegistry;
        $adapter = $activeRegistry->resolve($profile->provedor());
        if (!$adapter) {
            return [
                'resolved' => true,
                'supported' => false,
                'ibge' => $profile->ibge(),
                'municipio' => $profile->nomeMunicipio(),
                'uf' => $profile->uf(),
                'provedor' => $profile->provedor(),
                'padrao_nacional_like' => $profile->isPadraoNacionalLike(),
                'reason' => 'Provedor ainda nao implementado no pacote.'
            ];
        }

        return [
            'resolved' => true,
            'supported' => true,
            'ibge' => $profile->ibge(),
            'municipio' => $profile->nomeMunicipio(),
            'uf' => $profile->uf(),
            'provedor' => $profile->provedor(),
            'padrao_nacional_like' => $profile->isPadraoNacionalLike(),
            'services_catalog' => $profile->services(),
            'services_supported' => $adapter->supportedServices()
        ];
    }

    /**
     * Retorna matriz completa de capacidades para o municipio configurado ou informado.
     *
     * @param string|int|null $prefeitura
     */
    public function consultarMunicipio(string|int|null $prefeitura = null, ?ProviderAdapterRegistry $registry = null): array
    {
        $context = [];
        if ($prefeitura === null || $prefeitura === '') {
            $context = $this->municipioContext;
        } else {
            $catalog = $this->loadMunicipioCatalog();
            if ($catalog) {
                $resolved = $catalog->resolve((string) $prefeitura);
                if (is_array($resolved)) {
                    $context = $resolved;
                }
            }
        }

        if (empty($context)) {
            return [
                'resolved' => false,
                'supported' => false,
                'reason' => 'Municipio nao encontrado no catalogo.',
            ];
        }

        return $this->buildMunicipioCapabilities($context, $registry);
    }

    /**
     * Lista municipios do catalogo com filtros opcionais.
     *
     * @return array<int,array>
     */
    public function listarMunicipios(?string $uf = null, ?string $provedor = null, ?int $limit = null): array
    {
        $catalog = $this->loadMunicipioCatalog();
        if (!$catalog) {
            return [];
        }

        $rows = [];
        $ufFilter = $uf ? strtoupper(trim($uf)) : null;
        $providerFilter = $provedor ? strtolower(trim($provedor)) : null;

        foreach ($catalog->municipios() as $item) {
            if (!is_array($item)) {
                continue;
            }
            $ibge = trim((string) ($item['ibge'] ?? ''));
            $provider = trim((string) ($item['provedor'] ?? ''));
            if ($ibge === '' || $provider === '') {
                // Mantem apenas municipios com identificacao e provedor definidos.
                continue;
            }
            if ($ufFilter && strtoupper((string) ($item['uf'] ?? '')) !== $ufFilter) {
                continue;
            }
            if ($providerFilter && strtolower($provider) !== $providerFilter) {
                continue;
            }

            $rows[] = [
                'ibge' => $ibge,
                'nome' => $item['nome'] ?? null,
                'uf' => $item['uf'] ?? null,
                'alias' => $item['alias'] ?? null,
                'provedor' => $provider,
                'versao' => $item['versao'] ?? null,
            ];
        }

        usort($rows, static function (array $a, array $b): int {
            return strcmp((string) ($a['nome'] ?? ''), (string) ($b['nome'] ?? ''));
        });

        if ($limit !== null && $limit > 0) {
            return array_slice($rows, 0, $limit);
        }
        return $rows;
    }

    public function getSupportedProviders(?ProviderAdapterRegistry $registry = null): array
    {
        $activeRegistry = $registry ?? $this->providerRegistry;
        return $activeRegistry->allProviderNames();
    }

    private function buildMunicipioCapabilities(array $context, ?ProviderAdapterRegistry $registry = null): array
    {
        $activeRegistry = $registry ?? $this->providerRegistry;
        $profile = new ProviderProfile($context);
        $adapter = $activeRegistry->resolve($profile->provedor());
        $supported = $adapter !== null;

        $catalogServices = $this->normalizeServices($profile->services());
        $adapterServices = $adapter ? $this->normalizeServices($adapter->supportedServices()) : [];

        $effective = array_values(array_filter(
            $catalogServices,
            static fn (string $service): bool => in_array($service, $adapterServices, true)
        ));
        $missingInAdapter = array_values(array_filter(
            $catalogServices,
            static fn (string $service): bool => !in_array($service, $adapterServices, true)
        ));

        $providerRules = $this->providerRules((string) $profile->provedor());
        $urlMatrix = $this->buildServiceUrlMatrix($profile, $catalogServices);
        $capabilities = $this->classifyCapabilities($profile, $effective, $urlMatrix);

        return [
            'resolved' => true,
            'supported' => $supported,
            'ibge' => $profile->ibge(),
            'municipio' => $profile->nomeMunicipio(),
            'uf' => $profile->uf(),
            'alias' => $context['alias'] ?? null,
            'provedor' => $profile->provedor(),
            'versao' => $profile->versao(),
            'params' => $profile->params(),
            'padrao_nacional_like' => $profile->isPadraoNacionalLike(),
            'catalog_source' => [
                'compiled' => (string) ($this->config->catalog_compiled_path ?? CatalogConfig::defaultCompiledPath()),
                'json' => (string) ($this->config->catalog_json_path ?? CatalogConfig::defaultJsonPath()),
                'ini' => $this->config->catalog_ini_path ?? null,
            ],
            'services_catalog' => $catalogServices,
            'services_supported' => $adapterServices,
            'services_effective' => $effective,
            'services_missing_in_adapter' => $missingInAdapter,
            'url_matrix' => $urlMatrix,
            'capabilities' => $capabilities,
            'provider_rules' => $providerRules,
            'reason' => $supported ? null : 'Provedor ainda nao implementado no pacote.',
        ];
    }

    /**
     * @param string[] $services
     * @return string[]
     */
    private function normalizeServices(array $services): array
    {
        $result = [];
        foreach ($services as $service) {
            $normalized = $this->normalizeServiceName((string) $service);
            if ($normalized !== '' && !in_array($normalized, $result, true)) {
                $result[] = $normalized;
            }
        }
        sort($result);
        return $result;
    }

    private function normalizeServiceName(string $service): string
    {
        $service = strtolower(trim($service));
        if ($service === '') {
            return '';
        }
        $service = str_replace('nf_se', 'nfse', $service);
        $service = str_replace('d_fe', 'dfe', $service);

        if ($service === 'gerar_nfse' || $service === 'gerar_nf_se') {
            return 'emitir_nfse';
        }
        if ($service === 'cancelar_nf_se') {
            return 'cancelar_nfse';
        }
        if ($service === 'consultar_nf_se') {
            return 'consultar_nfse';
        }
        if ($service === 'consultar_nf_se_rps') {
            return 'consultar_nfse_rps';
        }
        if ($service === 'consultar_nf_se_faixa') {
            return 'consultar_nfse_faixa';
        }
        if ($service === 'consultar_evento') {
            return 'consultar_eventos';
        }
        if ($service === 'consultar_d_fe') {
            return 'consultar_dfe';
        }
        return $service;
    }

    /**
     * @param string[] $services
     * @return array<string,array>
     */
    private function buildServiceUrlMatrix(ProviderProfile $profile, array $services): array
    {
        $matrix = [];
        foreach ($services as $service) {
            $matrix[$service] = [
                'homologacao' => $profile->serviceUrl($service, 2),
                'producao' => $profile->serviceUrl($service, 1),
            ];
        }
        ksort($matrix);
        return $matrix;
    }

    /**
     * @param string[] $effectiveServices
     * @param array<string,array> $urlMatrix
     */
    private function classifyCapabilities(ProviderProfile $profile, array $effectiveServices, array $urlMatrix): array
    {
        $has = static fn (string $service): bool => in_array($service, $effectiveServices, true);
        $transport = 'unknown';
        foreach ($urlMatrix as $rows) {
            foreach (['homologacao', 'producao'] as $env) {
                $url = $rows[$env] ?? null;
                if (!is_string($url) || $url === '') {
                    continue;
                }
                $l = strtolower($url);
                if (str_contains($l, 'http') && str_contains($l, '/api')) {
                    $transport = 'rest';
                    break 2;
                }
                if (str_contains($l, '?wsdl') || str_contains($l, '.asmx') || str_contains($l, 'soap')) {
                    $transport = 'soap';
                    break 2;
                }
            }
        }

        return [
            'integration_mode' => $profile->isPadraoNacionalLike() ? 'padrao_nacional' : 'provedor_municipal',
            'transport' => $transport,
            'emissao' => [
                'unitario' => $has('emitir_nfse'),
                'lote_assincrono' => $has('recepcionar'),
                'lote_sincrono' => $has('recepcionar_sincrono'),
                'substituicao' => $has('substituir_nfse'),
                'cancelamento' => $has('cancelar_nfse'),
                'consulta' => [
                    'nfse' => $has('consultar_nfse'),
                    'rps' => $has('consultar_nfse_rps'),
                    'lote' => $has('consultar_lote'),
                    'situacao' => $has('consultar_situacao'),
                    'eventos' => $has('consultar_eventos'),
                    'dfe' => $has('consultar_dfe'),
                ],
                'danfse' => $has('consultar_danfse') || $has('link_url'),
            ],
        ];
    }

    private function providerRules(string $provider): ?array
    {
        $rules = $this->loadDeepProviderRules();
        if ($rules === null) {
            return null;
        }
        $providerKey = strtolower(trim($provider));
        foreach ($rules as $row) {
            if (!is_array($row)) {
                continue;
            }
            $name = strtolower((string) ($row['provider'] ?? ''));
            if ($name === $providerKey) {
                return $row;
            }
        }
        return null;
    }

    private function loadDeepProviderRules(): ?array
    {
        if (is_array($this->deepProviderRules)) {
            return $this->deepProviderRules;
        }
        $path = __DIR__ . '/../storage/provider-rules-deep-audit.json';
        if (!is_file($path)) {
            return null;
        }
        $json = json_decode((string) file_get_contents($path), true);
        if (!is_array($json) || !isset($json['providers']) || !is_array($json['providers'])) {
            return null;
        }
        $this->deepProviderRules = $json['providers'];
        return $this->deepProviderRules;
    }

    /**
     * @param $operacao
     * @param $data
     * @param $origem - URL de consulta 1 = Sefin (emissão), 2 = ADN (DANFSe)
     * @return mixed|string
     */
    public function getData($operacao, $data = null, $origem = 1)
    {
        $this->resolveUrl($origem);
        $this->saveTemporarilyKeyFiles();
        try {
            $msgSize = $data ? strlen($data) : 0;
            $parameters = [
                "Content-Type: application/json;charset=utf-8;",
                "Content-length: $msgSize"
            ];
            $oCurl = curl_init();
            $api_url = $this->url_api;
            if (strlen($operacao) > 0) {
                $api_url .= '/' . $operacao;
            }
            curl_setopt($oCurl, CURLOPT_URL, $api_url);
            curl_setopt($oCurl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            curl_setopt($oCurl, CURLOPT_CONNECTTIMEOUT, $this->connection_timeout);
            curl_setopt($oCurl, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($oCurl, CURLOPT_HEADER, 1);
            curl_setopt($oCurl, CURLOPT_HTTP_VERSION, $this->httpver);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, 0);
            if (!empty($this->security_level)) {
                curl_setopt($oCurl, CURLOPT_SSL_CIPHER_LIST, "{$this->security_level}");
            }
            //            if (!$this->disablesec) {
            //                curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, 2);
            //                if (!empty($this->casefaz)) {
            //                    if (is_file($this->casefaz)) {
            //                        curl_setopt($oCurl, CURLOPT_CAINFO, $this->casefaz);
            //                    }
            //                }
            //            }
            curl_setopt($oCurl, CURLOPT_SSLVERSION, CURL_SSLVERSION_DEFAULT);
            curl_setopt($oCurl, CURLOPT_SSLCERT, $this->tempdir . $this->certfile);
            curl_setopt($oCurl, CURLOPT_SSLKEY, $this->tempdir . $this->prifile);
            if (!empty($this->temppass)) {
                curl_setopt($oCurl, CURLOPT_KEYPASSWD, $this->temppass);
            }
            curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
            if (!empty($data)) {
                curl_setopt($oCurl, CURLOPT_POST, 1);
                curl_setopt($oCurl, CURLOPT_POSTFIELDS, $data);
                curl_setopt($oCurl, CURLOPT_HTTPHEADER, $parameters);
            } elseif ($origem === 3 && !empty($this->cookies)) {
                $parameters[] = 'Cookie: ' . $this->cookies;
                curl_setopt($oCurl, CURLOPT_HTTPHEADER, $parameters);
            }
            $response = curl_exec($oCurl);

            $this->soaperror = curl_error($oCurl);
            $this->soaperror_code = curl_errno($oCurl);
            $ainfo = curl_getinfo($oCurl);
            if (is_array($ainfo)) {
                $this->soapinfo = $ainfo;
            }
            $headsize = curl_getinfo($oCurl, CURLINFO_HEADER_SIZE);
            $httpcode = curl_getinfo($oCurl, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($oCurl, CURLINFO_CONTENT_TYPE);
            $this->responseHead = trim(substr($response, 0, $headsize));
            $this->responseBody = trim(substr($response, $headsize));
            //detecta redirect, conseguiu logar com certificado na origem 3 e pega cookies
            if ($origem == 3 and $httpcode == 302) {
                $this->captureCookies($this->responseHead, $origem);
                return ['sucesso' => true];
            }
            if ($contentType == 'application/pdf') {
                return $this->responseBody;
            } else {
                return json_decode($this->responseBody, true);
            }
        } catch (Exception $e) {
            throw SoapException::unableToLoadCurl($e->getMessage());
        }
    }

    /**
     * @param $operacao
     * @param $data
     * @param $origem - URL de consulta 1 = Sefin (emissão), 2 = ADN (DANFSe)
     * @return mixed|string
     */
    public function postData($operacao, $data, $origem = 1)
    {
        $this->resolveUrl($origem);
        $this->saveTemporarilyKeyFiles();
        try {
            $msgSize = $data ? strlen($data) : 0;
            $parameters = [
                //                'Accept: */*; ',
                'Content-Type: application/json',
                //                "Content-Type: application/x-www-form-urlencoded;charset=utf-8;",
                'Content-length: ' . $msgSize,
            ];
            //            $this->requestHead = implode("\n", $parameters);
            $oCurl = curl_init();
            $api_url = $this->url_api;
            if (strlen($operacao) > 0) {
                $api_url .= '/' . $operacao;
            }
            curl_setopt($oCurl, CURLOPT_URL, $api_url);
            curl_setopt($oCurl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            curl_setopt($oCurl, CURLOPT_CONNECTTIMEOUT, $this->connection_timeout);
            curl_setopt($oCurl, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($oCurl, CURLOPT_HEADER, 1);
            curl_setopt($oCurl, CURLOPT_HTTP_VERSION, $this->httpver);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, 0);
            if (!empty($this->security_level)) {
                curl_setopt($oCurl, CURLOPT_SSL_CIPHER_LIST, "{$this->security_level}");
            }

            curl_setopt($oCurl, CURLOPT_SSLVERSION, CURL_SSLVERSION_DEFAULT);
            curl_setopt($oCurl, CURLOPT_SSLCERT, $this->tempdir . $this->certfile);
            curl_setopt($oCurl, CURLOPT_SSLKEY, $this->tempdir . $this->prifile);
            if (!empty($this->temppass)) {
                curl_setopt($oCurl, CURLOPT_KEYPASSWD, $this->temppass);
            }
            curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
            if (!empty($data)) {
                curl_setopt($oCurl, CURLOPT_POST, 1);
                curl_setopt($oCurl, CURLOPT_POSTFIELDS, $data);
                //curl_setopt($oCurl, CURLOPT_POSTFIELDS, http_build_query($data)); // Dados para enviar no POST
                curl_setopt($oCurl, CURLOPT_HTTPHEADER, $parameters);
            }
            $response = curl_exec($oCurl);

            $this->soaperror = curl_error($oCurl);
            $this->soaperror_code = curl_errno($oCurl);
            $ainfo = curl_getinfo($oCurl);
            if (is_array($ainfo)) {
                $this->soapinfo = $ainfo;
            }
            $headsize = curl_getinfo($oCurl, CURLINFO_HEADER_SIZE);
            $httpcode = curl_getinfo($oCurl, CURLINFO_HTTP_CODE);
            curl_close($oCurl);
            $this->responseHead = trim(substr($response, 0, $headsize));
            $this->responseBody = trim(substr($response, $headsize));
            return json_decode($this->responseBody, true);
        } catch (Exception $e) {
            throw SoapException::unableToLoadCurl($e->getMessage());
        }
    }

    public function postJsonToUrl(string $url, array|string $data): mixed
    {
        $payload = is_array($data) ? json_encode($data) : $data;
        if ($payload === false) {
            throw SoapException::unableToLoadCurl('Falha ao serializar payload JSON.');
        }
        return $this->requestAbsoluteUrl($url, $payload, [
            'Content-Type: application/json',
            'Accept: application/json, text/plain, */*',
        ]);
    }

    public function postJsonToUrlWithHeaders(string $url, array|string $data, array $headers = []): mixed
    {
        $payload = is_array($data) ? json_encode($data) : $data;
        if ($payload === false) {
            throw SoapException::unableToLoadCurl('Falha ao serializar payload JSON.');
        }
        return $this->requestAbsoluteUrl($url, $payload, array_merge([
            'Content-Type: application/json',
            'Accept: application/json, text/plain, */*',
        ], $headers));
    }

    public function postXmlToUrl(string $url, string $xml, array $headers = []): mixed
    {
        $merged = array_merge([
            'Content-Type: text/xml; charset=utf-8',
            'Accept: text/xml, application/xml, */*',
        ], $headers);

        return $this->requestAbsoluteUrl($url, $xml, $merged);
    }

    public function getFromUrl(string $url): mixed
    {
        return $this->requestAbsoluteUrl($url);
    }

    public function getFromUrlWithHeaders(string $url, array $headers = []): mixed
    {
        return $this->requestAbsoluteUrl($url, null, $headers);
    }

    public function postFormToUrl(string $url, array|string $data, array $headers = []): mixed
    {
        $payload = is_array($data) ? http_build_query($data) : (string) $data;
        $merged = array_merge([
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json, text/plain, */*',
        ], $headers);
        return $this->requestAbsoluteUrl($url, $payload, $merged);
    }

    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    public function setConnectionTimeout($connection_timeout)
    {
        $this->connection_timeout = $connection_timeout;
    }

    /**
     * Sign XML passing in content
     * @param string $content
     * @param string $tagname
     * @param string $mark
     * @return string XML signed
     */
    public function sign(string $content, string $tagname, ?string $mark, $rootname)
    {
        if (empty($mark)) {
            $mark = 'Id';
        }
        $xml = Signer::sign(
            $this->certificate,
            $content,
            $tagname,
            $mark,
            OPENSSL_ALGO_SHA1,
            $this->canonical,
            $rootname
        );
        return $xml;
    }

    private function resolveUrl(int $origem = 0)
    {
        switch ($origem) {
            case 1: // SEFIN
                $this->url_api = $this->urls['sefin_homologacao'];
                if ($this->config->tpamb === 1) {
                    $this->url_api = $this->urls['sefin_producao'];
                }
                break;
            case 2: // ADN
                $this->url_api = $this->urls['adn_homologacao'];
                if ($this->config->tpamb === 1) {
                    $this->url_api = $this->urls['adn_producao'];
                }
                break;
            case 3: // NFSE
                $this->url_api = $this->urls['nfse_homologacao'];
                if ($this->config->tpamb === 1) {
                    $this->url_api = $this->urls['nfse_producao'];
                }
                break;
        }

    }

    private function captureCookies(string $headers, int $origem): void
    {
        if ($origem !== 3) {
            return;
        }
        if (!preg_match_all('/^Set-Cookie:\s*([^;\r\n]*)/mi', $headers, $matches)) {
            return;
        }
        $cookies = array_map('trim', $matches[1]);
        if (!empty($cookies)) {
            $this->cookies = implode('; ', $cookies);
        }
    }

    private function requestAbsoluteUrl(string $url, ?string $data = null, array $headers = []): mixed
    {
        $this->saveTemporarilyKeyFiles();
        try {
            $this->requestBody = (string) ($data ?? '');
            $msgSize = $data !== null ? strlen($data) : 0;
            $effectiveHeaders = $headers;
            $hasContentLength = false;
            foreach ($effectiveHeaders as $header) {
                if (stripos((string) $header, 'Content-length:') === 0) {
                    $hasContentLength = true;
                    break;
                }
            }
            if (!$hasContentLength) {
                $effectiveHeaders[] = 'Content-length: ' . $msgSize;
            }

            $oCurl = curl_init();
            curl_setopt($oCurl, CURLOPT_URL, $url);
            curl_setopt($oCurl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            curl_setopt($oCurl, CURLOPT_CONNECTTIMEOUT, $this->connection_timeout);
            curl_setopt($oCurl, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($oCurl, CURLOPT_HEADER, 1);
            curl_setopt($oCurl, CURLOPT_HTTP_VERSION, $this->httpver);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, 0);
            if (!empty($this->security_level)) {
                curl_setopt($oCurl, CURLOPT_SSL_CIPHER_LIST, "{$this->security_level}");
            }
            curl_setopt($oCurl, CURLOPT_SSLVERSION, CURL_SSLVERSION_DEFAULT);
            curl_setopt($oCurl, CURLOPT_SSLCERT, $this->tempdir . $this->certfile);
            curl_setopt($oCurl, CURLOPT_SSLKEY, $this->tempdir . $this->prifile);
            if (!empty($this->temppass)) {
                curl_setopt($oCurl, CURLOPT_KEYPASSWD, $this->temppass);
            }
            curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
            if ($data !== null) {
                curl_setopt($oCurl, CURLOPT_POST, 1);
                curl_setopt($oCurl, CURLOPT_POSTFIELDS, $data);
                curl_setopt($oCurl, CURLOPT_HTTPHEADER, $effectiveHeaders);
            }

            $response = curl_exec($oCurl);
            $this->soaperror = curl_error($oCurl);
            $this->soaperror_code = curl_errno($oCurl);
            $ainfo = curl_getinfo($oCurl);
            if (is_array($ainfo)) {
                $this->soapinfo = $ainfo;
            }

            $headsize = curl_getinfo($oCurl, CURLINFO_HEADER_SIZE);
            $contentType = curl_getinfo($oCurl, CURLINFO_CONTENT_TYPE);
            curl_close($oCurl);

            $this->responseHead = trim(substr((string) $response, 0, $headsize));
            $this->responseBody = trim(substr((string) $response, $headsize));

            $contentType = strtolower((string) $contentType);
            if (str_contains($contentType, 'application/pdf')) {
                return $this->responseBody;
            }
            if (str_contains($contentType, 'xml') || str_contains($contentType, 'soap')) {
                return $this->responseBody;
            }
            $decoded = json_decode($this->responseBody, true);
            return $decoded ?? $this->responseBody;
        } catch (Exception $e) {
            throw SoapException::unableToLoadCurl($e->getMessage());
        }
    }
}
