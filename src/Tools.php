<?php

namespace Alves\NfseBrasil;

use DOMDocument;
use RuntimeException;
use NFePHP\Common\Certificate;
use Alves\NfseBrasil\Danfse\DanfsePdfGenerator;
use Alves\NfseBrasil\Provider\ProviderProfile;
use Alves\NfseBrasil\Provider\Contract\ProviderRuntimeAdapterInterface;

class Tools extends RestCurl
{
    use ToolsProviderBuildersTrait;

    public function __construct(string $config, ?Certificate $cert = null)
    {
        parent::__construct($config, $cert);
    }

    public function consultarNfseChave($chave, $encoding = true)
    {
        $operacao = str_replace("{chave}", $chave, $this->getOperation('consultar_nfse'));
        $retorno = $this->getData($operacao);

        if (isset($retorno['erro'])) {
            return $retorno;
        }
        if ($retorno) {
            $base_decode = base64_decode($retorno['nfseXmlGZipB64']);
            $gz_decode = gzdecode($base_decode);
            return $encoding ? mb_convert_encoding($gz_decode, 'ISO-8859-1') : $gz_decode;
        }
        return null;
    }

    public function consultarDpsChave($chave)
    {
        $operacao = str_replace("{chave}", $chave, $this->getOperation('consultar_dps'));
        $retorno = $this->getData($operacao);

        return $retorno;
    }

    public function consultarNfseEventos($chave, $tipoEvento = null, $nSequencial = null)
    {
        $operacao = str_replace("{chave}", $chave, $this->getOperation('consultar_eventos'));
        if (!$tipoEvento) {
            $operacao = str_replace("/{tipoEvento}/{nSequencial}", "", $operacao);
        }
        $operacao = str_replace("{tipoEvento}", $tipoEvento, $operacao);

        if (!$nSequencial) {
            $operacao = str_replace("/{nSequencial}", "", $operacao);
        }
        $operacao = str_replace("{nSequencial}", $nSequencial, $operacao);

        $retorno = $this->getData($operacao);
        return $retorno;
    }

    public function consultarDanfse($chave)
    {
        $operacao = str_replace("{chave}", $chave, $this->getOperation('consultar_danfse'));
        $retorno = $this->getData($operacao, null, 2);
        if (isset($retorno['erro'])) {
            return $retorno;
        }
        if ($retorno) {
            return $retorno;
        }
        if(empty($retorno)){
            return $this->consultarDanfseNfse($chave);
        }
        return null;
    }

    /**
     * Gera DANFSE em PDF a partir do XML da NFS-e.
     */
    public function gerarDanfsePdf(string $nfseXml, ?string $outputPath = null, ?string $provider = null): string
    {
        $pdf = (new DanfsePdfGenerator())->generate($nfseXml, $provider);
        if ($outputPath !== null && $outputPath !== '') {
            file_put_contents($outputPath, $pdf);
        }
        return $pdf;
    }

    /**
     * Consulta XML da NFS-e pela chave e gera DANFSE em PDF.
     */
    public function gerarDanfsePdfPorChave(string $chave, ?string $outputPath = null): string
    {
        $xml = $this->consultarNfseChave($chave, false);
        if (!is_string($xml) || trim($xml) === '') {
            throw new RuntimeException('Não foi possível obter XML da NFS-e para gerar DANFSE.');
        }
        $provider = $this->getProviderProfile()?->provedor();
        return $this->gerarDanfsePdf($xml, $outputPath, $provider);
    }

    /**
     * Consulta o DANFSe via NFSe caso o serviço direto falhe
     *
     * @param string $chave
     * @return array|binary|null
     */
    public function consultarDanfseNfse($chave)
    {
        $operacao = $this->getOperation('consultar_danfse_nfse_certificado');
        $retorno = $this->getData($operacao, null, 3);
        if(isset($retorno) and isset($retorno['sucesso']) and $retorno['sucesso']==true){
            $operacao = str_replace("{chave}", $chave, $this->getOperation('consultar_danfse_nfse_download'));
            $retorno = $this->getData($operacao, null, 3);
        }
        if (isset($retorno['erro'])) {
            return $retorno;
        }
        if ($retorno) {
            return $retorno;
        }
        return null;
    }

    public function enviaDps($content)
    {
        //$content = $this->canonize($content);
        $content = $this->sign($content, 'infDPS', '', 'DPS');
        $content = '<?xml version="1.0" encoding="UTF-8"?>' . $content;
        $gz = gzencode($content);
        $data = base64_encode($gz);
        $dados = [
            'dpsXmlGZipB64' => $data
        ];
        $operacao = $this->getOperation('emitir_nfse');
        $retorno = $this->postData($operacao, json_encode($dados));
        return $retorno;
    }

    /**
     * Alias semântico para emissão no padrão nacional.
     */
    public function emitirNfsePadraoNacional($content)
    {
        return $this->enviaDps($content);
    }

    public function cancelaNfse($std)
    {
        $dps = new \Alves\NfseBrasil\Dps($std);
        $content = $dps->renderEvento($std);
        //$content = $this->canonize($content);
        $content = $this->sign($content, 'infPedReg', '', 'pedRegEvento');
        $content = '<?xml version="1.0" encoding="UTF-8"?>' . $content;
        $gz = gzencode($content);
        $data = base64_encode($gz);
        $dados = [
            'pedidoRegistroEventoXmlGZipB64' => $data
        ];
        $operacao = str_replace("{chave}", $std->infPedReg->chNFSe, $this->getOperation('cancelar_nfse'));
        $retorno = $this->postData($operacao, json_encode($dados));
        return $retorno;
    }

    /**
     * Alias semântico para cancelamento no padrão nacional.
     */
    public function cancelarNfsePadraoNacional($std)
    {
        return $this->cancelaNfse($std);
    }

    /**
     * Emissao municipal genérica (fora Sefin Nacional), resolvendo endpoint pelo provedor.
     *
     * @param array|string $payload
     * @param string $service chave canônica do serviço (ex.: gerar_nf_se, recepcionar)
     * @return mixed
     */
    public function emitirNfseMunicipal(array|string $payload, string $service = 'recepcionar', int $debugCurl = 0)
    {
        ['url' => $url, 'profile' => $profile] = $this->resolveMunicipalDispatchContext($service);

        $previousCurlDebug = $this->setCurlDebugLogging((bool) $debugCurl, [
            'provider' => $profile?->provedor(),
            'service' => $service,
            'operation' => 'emitir',
            'debug_curl' => $debugCurl,
        ]);

        try {
            $runtime = $this->dispatchViaProviderAdapterRuntime('emitir', $service, $url, $payload, $profile);
            if ($runtime['handled']) {
                return $runtime['response'];
            }

            return $this->postJsonToUrl($url, $payload);
        } finally {
            $this->setCurlDebugLogging(
                (bool) ($previousCurlDebug['enabled'] ?? false),
                is_array($previousCurlDebug['context'] ?? null) ? $previousCurlDebug['context'] : []
            );
        }
    }

    /**
     * Cancelamento municipal genérico.
     *
     * @param array|string $payload
     * @param string $service
     * @return mixed
     */
    public function cancelarNfseMunicipal(array|string $payload, string $service = 'cancelar_nf_se')
    {
        ['url' => $url, 'profile' => $profile] = $this->resolveMunicipalDispatchContext($service);

        $runtime = $this->dispatchViaProviderAdapterRuntime('cancelar', $service, $url, $payload, $profile);
        if ($runtime['handled']) {
            return $runtime['response'];
        }

        return $this->postJsonToUrl($url, $payload);
    }

    /**
     * Substituicao municipal genérica.
     *
     * @param array|string $payload
     * @param string $service
     * @return mixed
     */
    public function substituirNfseMunicipal(array|string $payload, string $service = 'substituir_nf_se')
    {
        ['url' => $url, 'profile' => $profile] = $this->resolveMunicipalDispatchContext($service);

        $runtime = $this->dispatchViaProviderAdapterRuntime('substituir', $service, $url, $payload, $profile);
        if ($runtime['handled']) {
            return $runtime['response'];
        }

        return $this->postJsonToUrl($url, $payload);
    }

    /**
     * Resolve o contexto base de execução municipal para operações de envio.
     *
     * @return array{url:string,profile:?ProviderProfile}
     */
    private function resolveMunicipalDispatchContext(string $service): array
    {
        if (!$this->isMunicipioSupported()) {
            $status = $this->getMunicipioSupportStatus();
            throw new RuntimeException($status['reason'] ?? 'Municipio/provedor nao suportado.');
        }

        $url = $this->resolveProviderServiceUrl($service);
        if (!$url) {
            throw new RuntimeException("Servico municipal '{$service}' nao encontrado para a prefeitura configurada.");
        }

        $profile = $this->getProviderProfile();
        return [
            'url' => $url,
            'profile' => $profile,
        ];
    }

    /**
     * Encaminha operacoes de runtime para o adapter do provedor quando implementado.
     *
     * @param array|string $payload
     * @return array{handled:bool,response:mixed}
     */
    private function dispatchViaProviderAdapterRuntime(
        string $operation,
        string $service,
        string $url,
        array|string $payload,
        ?ProviderProfile $profile
    ): array {
        if (!$profile) {
            return ['handled' => false, 'response' => null];
        }

        $adapter = $this->resolveProviderAdapter();
        if (!$adapter instanceof ProviderRuntimeAdapterInterface) {
            return ['handled' => false, 'response' => null];
        }

        $plan = $adapter->buildRuntimePlan($operation, $service, $profile, (int) ($this->tpAmb ?? 2), $url, $payload);
        if (!is_array($plan) || $plan === []) {
            return ['handled' => false, 'response' => null];
        }

        try {
            return [
                'handled' => true,
                'response' => $this->executeRuntimePlan($plan, $payload, $service, $profile, $url, $operation),
            ];
        } catch (\Throwable) {
            return ['handled' => false, 'response' => null];
        }
    }

    /**
     * Executa o plano de runtime retornado pelo adapter.
     *
     * @param array<string,mixed> $plan
     * @param array|string $payload
     */
    private function executeRuntimePlan(
        array $plan,
        array|string $payload,
        string $service,
        ?ProviderProfile $profile,
        string $url,
        string $operation
    ): mixed {

        $this->requestXmlBody = '';

        $directMethod = $this->resolveRuntimePlanMethod($plan['direct'] ?? null, $plan['direct_candidates'] ?? null);
        if ($directMethod) {
            return $this->invokeRuntimeMethod($directMethod, null, $payload, $service, $profile, $url, $operation);
        }

        $transport = strtolower((string) ($plan['transport'] ?? ''));

        if ($transport === 'json') {
            $this->requestXmlBody = '';
            return $this->postJsonToUrl($url, $payload);
        }

        if ($transport !== 'xml') {
            throw new RuntimeException('Plano runtime invalido: transport nao suportado.');
        }

        $envelopeSpec = is_array($plan['envelope'] ?? null) ? $plan['envelope'] : [];
        $envelopeMethod = $this->resolveRuntimePlanMethod(
            $envelopeSpec['method'] ?? null,
            $envelopeSpec['candidates'] ?? null
        );
        if (!$envelopeMethod) {
            throw new RuntimeException('Plano runtime invalido: envelope nao definido.');
        }

        $xml = $this->invokeRuntimeMethod(
            $envelopeMethod,
            is_array($envelopeSpec['args'] ?? null) ? $envelopeSpec['args'] : null,
            $payload,
            $service,
            $profile,
            $url,
            $operation
        );

        $headers = [];
        if (is_array($plan['soap_action'] ?? null)) {
            $soapSpec = $plan['soap_action'];
            $soapMethod = $this->resolveRuntimePlanMethod(
                $soapSpec['method'] ?? null,
                $soapSpec['candidates'] ?? null
            );
            if ($soapMethod) {
                $soapAction = $this->invokeRuntimeMethod(
                    $soapMethod,
                    is_array($soapSpec['args'] ?? null) ? $soapSpec['args'] : null,
                    $payload,
                    $service,
                    $profile,
                    $url,
                    $operation
                );
                $headers[] = 'SOAPAction: "' . $soapAction . '"';
            }
        }

        $headersPlan = $plan['headers'] ?? null;
        if (is_array($headersPlan) && array_is_list($headersPlan)) {
            $headers = array_merge($headers, $headersPlan);
        } elseif (is_array($headersPlan)) {
            $headerSpec = $headersPlan;
            $headerMethod = $this->resolveRuntimePlanMethod(
                $headerSpec['method'] ?? null,
                $headerSpec['candidates'] ?? null
            );
            if ($headerMethod) {
                $dynamicHeaders = $this->invokeRuntimeMethod(
                    $headerMethod,
                    is_array($headerSpec['args'] ?? null) ? $headerSpec['args'] : null,
                    $payload,
                    $service,
                    $profile,
                    $url,
                    $operation
                );
                if (is_array($dynamicHeaders)) {
                    $headers = array_merge($headers, $dynamicHeaders);
                }
            }
        }

        if ($headers === []) {
            $headers[] = 'SOAPAction: ""';
        }

        $this->requestXmlBody = (string) $xml;

        return $this->postXmlToUrl($url, $xml, $headers);
    }

    /**
     * @param array<int,string> $tokens
     * @param array|string $payload
     * @return array<int,mixed>
     */
    private function resolveRuntimePlanArgs(
        ?array $tokens,
        array|string $payload,
        string $service,
        ?ProviderProfile $profile,
        string $url,
        string $operation
    ): array {
        if (!is_array($tokens) || $tokens === []) {
            return [];
        }

        $args = [];
        foreach ($tokens as $token) {
            $args[] = match ($token) {
                'payload' => $payload,
                'service' => $service,
                'url' => $url,
                'tpAmb' => (int) ($this->tpAmb ?? 2),
                'operation' => $operation,
                'profile' => $profile,
                'profile.versao' => $profile?->versao(),
                'profile.params' => $profile?->params() ?? [],
                default => null,
            };
        }

        return $args;
    }

    /**
     * @param mixed $method
     * @param mixed $candidates
     */
    private function resolveRuntimePlanMethod(mixed $method, mixed $candidates): ?string
    {
        if (is_string($method) && $method !== '' && method_exists($this, $method)) {
            return $method;
        }

        if (is_array($candidates)) {
            foreach ($candidates as $candidate) {
                if (is_string($candidate) && $candidate !== '' && method_exists($this, $candidate)) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    /**
     * @param array|string $payload
     * @param array<int,string>|null $argTokens
     */
    private function invokeRuntimeMethod(
        string $method,
        ?array $argTokens,
        array|string $payload,
        string $service,
        ?ProviderProfile $profile,
        string $url,
        string $operation
    ): mixed {
        if ($argTokens !== null) {
            $args = $this->resolveRuntimePlanArgs($argTokens, $payload, $service, $profile, $url, $operation);
            return $this->{$method}(...$args);
        }

        $ref = new \ReflectionMethod($this, $method);
        $args = [];
        foreach ($ref->getParameters() as $param) {
            $name = strtolower($param->getName());
            $valueResolved = true;
            $value = match ($name) {
                'payload', 'content', 'data' => $payload,
                'service', 'servico' => $service,
                'url', 'endpoint', 'baseurl', 'base_url' => $url,
                'operation', 'operacao' => $operation,
                'tpamb', 'ambiente', 'amb' => (int) ($this->tpAmb ?? 2),
                'versao', 'version' => $profile?->versao(),
                'params', 'parametros' => $profile?->params() ?? [],
                'profile' => $profile,
                default => null,
            };

            if (!in_array($name, [
                'payload', 'content', 'data',
                'service', 'servico',
                'url', 'endpoint', 'baseurl', 'base_url',
                'operation', 'operacao',
                'tpamb', 'ambiente', 'amb',
                'versao', 'version',
                'params', 'parametros',
                'profile',
            ], true)) {
                $valueResolved = false;
            }

            if (!$valueResolved) {
                if ($param->isDefaultValueAvailable()) {
                    $args[] = $param->getDefaultValue();
                    continue;
                }
                throw new RuntimeException("Nao foi possivel resolver argumento '{$param->getName()}' para {$method}.");
            }

            $args[] = $value;
        }

        return $this->{$method}(...$args);
    }

    /**
     * Consulta municipal genérica.
     *
     * @param string $service
     * @return mixed
     */
    public function consultarMunicipal(string $service = 'consultar_nf_se')
    {
        if (!$this->isMunicipioSupported()) {
            $status = $this->getMunicipioSupportStatus();
            throw new RuntimeException($status['reason'] ?? 'Municipio/provedor nao suportado.');
        }
        $url = $this->resolveProviderServiceUrl($service);
        if (!$url) {
            throw new RuntimeException("Servico municipal '{$service}' nao encontrado para a prefeitura configurada.");
        }
        return $this->getFromUrl($url);
    }

    /**
     * Consulta DANFSe municipal quando o provedor disponibiliza endpoint/URL.
     * Tenta primeiro consultar_danfse e depois link_url.
     *
     * @return mixed
     */
    public function consultarDanfseMunicipal(string $service = 'consultar_danfse')
    {
        if (!$this->isMunicipioSupported()) {
            $status = $this->getMunicipioSupportStatus();
            throw new RuntimeException($status['reason'] ?? 'Municipio/provedor nao suportado.');
        }

        $url = $this->resolveProviderServiceUrl($service);
        if (!$url) {
            $url = $this->resolveProviderServiceUrl('link_url');
        }
        if (!$url) {
            throw new RuntimeException("DANFSe municipal nao disponivel para a prefeitura configurada.");
        }

        return $this->getFromUrl($url);
    }

    public function municipioSuportado(): bool
    {
        return $this->isMunicipioSupported();
    }

    public function diagnosticoMunicipio(): array
    {
        return $this->getMunicipioSupportStatus();
    }

    /**
     * Retorna todas as regras/capacidades do municipio (provedor, servicos, URLs, forma de emissao).
     *
     * @param string|int|null $prefeitura
     */
    public function detalhesMunicipio(string|int|null $prefeitura = null): array
    {
        return $this->consultarMunicipio($prefeitura);
    }

    /**
     * Lista municipios do catalogo com filtros opcionais.
     *
     * @return array<int,array>
     */
    public function listarMunicipios(?string $uf = null, ?string $provedor = null, ?int $limit = null): array
    {
        return parent::listarMunicipios($uf, $provedor, $limit);
    }

    protected function canonize($content)
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = false;
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($content);
        dump($dom->saveXML());
        return $dom->C14N(false, false, null, null);
    }
}
