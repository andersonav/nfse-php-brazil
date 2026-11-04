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

    /**
     * Recupera o XML autorizado pelo ADN disponível na API REST do SpeedGov.
     * $baseUrl deve incluir o município e /api/v1; não é o endereço SOAP /Nfes.
     * Retorna null enquanto o provedor disponibilizar somente a DPS ou nenhum XML.
     */
    public function consultarXmlNacionalSpeedGov(string $chave, string $baseUrl, int $ambiente): ?string
    {
        $baseUrl = rtrim(trim($baseUrl), '/');
        if (!preg_match('/^\d{50}$/D', $chave) || !in_array($ambiente, [1, 2], true)) {
            throw new RuntimeException('Chave de acesso ou ambiente inválido para consultar o XML nacional.');
        }
        if (!preg_match('~^https://tributario\.speedgov\.com\.br/[a-z0-9_-]+/api/v1$~D', $baseUrl)) {
            throw new RuntimeException('Configure a URL REST do SpeedGov com o município e /api/v1.');
        }
        if ($ambiente === 1 && str_contains($baseUrl, '/integracao/')) {
            throw new RuntimeException('O sandbox integracao do SpeedGov não pode consultar notas de produção.');
        }

        $retorno = $this->getFromUrlWithHeaders($baseUrl.'/nfse/'.$chave, ['Accept: application/json'], true);
        $http = (int) ($this->soapinfo['http_code'] ?? 0);
        if ($http < 200 || $http >= 300 || !is_array($retorno) || ($retorno['status'] ?? '') !== 'OK') {
            throw new RuntimeException('Consulta do XML nacional no SpeedGov indisponível (HTTP '.$http.').');
        }
        $nota = $retorno['nota'] ?? [];
        if (!is_array($nota) || (string) ($nota['chave_acesso'] ?? '') !== $chave) {
            throw new RuntimeException('A API SpeedGov retornou uma chave diferente da NFS-e consultada.');
        }
        if ((int) ($nota['codigo_situacao_nfse'] ?? 0) !== 100 || empty($nota['xml_nota'])) {
            return null;
        }

        $xml = (string) $nota['xml_nota'];
        self::validarXmlNacionalSpeedGov($xml, $chave, $ambiente);

        return $xml;
    }

    /** Confere a identidade e a autorização sem reserializar o XML assinado. */
    public static function validarXmlNacionalSpeedGov(string $xml, string $chave, int $ambiente): void
    {
        $dom = new DOMDocument();
        if (stripos($xml, '<!DOCTYPE') !== false || !@$dom->loadXML($xml, LIBXML_NONET) || $dom->doctype !== null) {
            throw new RuntimeException('Não foi disponibilizado um XML nacional válido.');
        }
        $xp = new \DOMXPath($dom);
        $xp->registerNamespace('n', 'http://www.sped.fazenda.gov.br/nfse');
        $inf = $xp->query('/n:NFSe/n:infNFSe');
        if ($inf->length !== 1
            || !preg_match('/^\d{50}$/D', $chave)
            || !in_array($ambiente, [1, 2], true)
            || $inf->item(0)->getAttribute('Id') !== 'NFS'.$chave
            || trim($xp->evaluate('string(/n:NFSe/n:infNFSe/n:nNFSe)')) === ''
            || $xp->evaluate('string(/n:NFSe/n:infNFSe/n:cStat)') !== '100'
            || $xp->query('/n:NFSe/n:infNFSe/n:DPS/n:infDPS')->length !== 1
            || $xp->evaluate('string(/n:NFSe/n:infNFSe/n:DPS/n:infDPS/n:tpAmb)') !== (string) $ambiente) {
            throw new RuntimeException('O XML retornado não é a NFS-e nacional autorizada da mesma chave e ambiente.');
        }
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

    /** Consulta por chave no ADN Contribuinte. /Eventos não é distribuição por NSU. */
    public function consultarEventosAdn(string $chave, int $ambiente): array
    {
        if (!preg_match('/^\d{50}$/D', $chave) || !in_array($ambiente, [1, 2], true)) {
            throw new RuntimeException('Informe a chave nacional de 50 dígitos e um ambiente válido para consultar o ADN.');
        }
        $base = $ambiente === 1 ? 'https://adn.nfse.gov.br/contribuintes' : 'https://adn.producaorestrita.nfse.gov.br/contribuintes';
        $url = $base.'/NFSe/'.$chave.'/Eventos';
        $resposta = $this->getFromUrlWithHeaders($url, ['Accept: application/json'], true);
        $http = (int) ($this->soapinfo['http_code'] ?? 0);
        if (!is_array($resposta) || !isset($resposta['StatusProcessamento'])) {
            throw new RuntimeException('Resposta inválida na consulta ADN (HTTP '.$http.'). Verifique a conexão e o certificado.');
        }
        $ret = [
            'HTTP_STATUS' => $http, 'STATUS_PROCESSAMENTO' => (string) $resposta['StatusProcessamento'],
            'CHAVE_ACESSO' => $chave, 'AMBIENTE' => $ambiente, 'URL' => $url,
            'ERROS' => $resposta['Erros'] ?? [], 'ALERTAS' => $resposta['Alertas'] ?? [], 'DOCUMENTOS' => [],
        ];
        $tipoAmbiente = $ambiente === 1 ? 'PRODUCAO' : 'HOMOLOGACAO';
        if (($resposta['TipoAmbiente'] ?? '') !== $tipoAmbiente) {
            throw new RuntimeException('O ambiente retornado pelo ADN difere do ambiente configurado para a NFS-e.');
        }
        if ($http !== 200 || $ret['ERROS'] !== []) {
            return $ret;
        }
        if ($ret['STATUS_PROCESSAMENTO'] !== 'DOCUMENTOS_LOCALIZADOS' || !is_array($resposta['LoteDFe'] ?? null)) {
            throw new RuntimeException('Retorno de documentos do ADN incompleto.');
        }
        foreach ($resposta['LoteDFe'] as $documento) {
            if (!is_array($documento) || ($documento['ChaveAcesso'] ?? '') !== $chave) {
                throw new RuntimeException('O ADN retornou documento de outra chave de acesso.');
            }
            $tipo = (string) ($documento['TipoDocumento'] ?? '');
            if (!in_array($tipo, ['NFSE', 'EVENTO'], true)) {
                throw new RuntimeException('Tipo de documento inesperado na consulta de eventos do ADN: '.$tipo.'.');
            }
            $compactado = base64_decode((string) ($documento['ArquivoXml'] ?? ''), true);
            $xml = $compactado === false ? false : @gzdecode($compactado, 10485760);
            if ($xml === false || $xml === '') {
                throw new RuntimeException('O ADN retornou um XML compactado inválido.');
            }
            $codigoEvento = '';
            if ($tipo === 'NFSE') {
                // O enum é compartilhado; só aceite NFSE se o conteúdo realmente for uma nota autorizada.
                self::validarXmlNacionalSpeedGov($xml, $chave, $ambiente);
            } else {
                $dom = new DOMDocument();
                if (stripos($xml, '<!DOCTYPE') !== false || !@$dom->loadXML($xml, LIBXML_NONET) || $dom->doctype !== null) {
                    throw new RuntimeException('XML de evento ADN inválido.');
                }
                $xp = new \DOMXPath($dom);
                $xp->registerNamespace('n', 'http://www.sped.fazenda.gov.br/nfse');
                $pedido = '/n:evento/n:infEvento/n:pedRegEvento/n:infPedReg';
                if ($xp->query($pedido)->length !== 1
                    || $xp->evaluate('string('.$pedido.'/n:chNFSe)') !== $chave
                    || $xp->evaluate('string('.$pedido.'/n:tpAmb)') !== (string) $ambiente) {
                    throw new RuntimeException('O evento ADN não corresponde à chave e ao ambiente consultados.');
                }
                foreach ($xp->query($pedido.'/*') as $node) {
                    if (preg_match('/^e\d{6}$/D', $node->localName)) {
                        $codigoEvento = $node->localName;
                        break;
                    }
                }
                if ($codigoEvento === '') {
                    throw new RuntimeException('O XML retornado não identifica o tipo de evento nacional.');
                }
            }
            $ret['DOCUMENTOS'][] = [
                'TIPO_DOCUMENTO' => $tipo, 'TIPO_EVENTO' => (string) ($documento['TipoEvento'] ?? ''),
                'CODIGO_EVENTO' => $codigoEvento, 'DATA' => (string) ($documento['DataHoraGeracao'] ?? ''),
                'XML' => $xml,
            ];
        }

        return $ret;
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
    /** Validação local, inclusive assinatura/XSD, sem chamada ao provedor. */
    public function validarPayloadMunicipal(array $payload): void
    {
        if (strtolower((string) ($this->getProviderProfile()?->provedor() ?? '')) === 'speedgov') {
            $payload['validar_schema'] = true;
            $this->buildSpeedGovRecepcionarEnvelope($payload);
        }
    }

    public function gerarDanfsePdf(string $nfseXml, ?string $outputPath = null, ?string $provider = null, array $context = []): string
    {
        $pdf = (new DanfsePdfGenerator())->generate($nfseXml, $provider, $context);
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
        } catch (\Throwable $e) {
            // Uma falha SOAP não autoriza nova tentativa em JSON, especialmente após timeout.
            if (strtolower($profile->provedor()) === 'speedgov') {
                throw $e;
            }
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
