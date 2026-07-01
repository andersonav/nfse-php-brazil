# Guia Técnico do Projeto `nfse-php-brazil`

## 1. Objetivo

O `nfse-php-brazil` é o núcleo de integração NFS-e do projeto. Ele foi estruturado para:

- resolver município, provedor e URLs de serviço;
- centralizar regras de operação por provedor;
- expor uma API PHP unificada para emissão, cancelamento, substituição e consultas;
- gerar DANFSe em PDF dentro do próprio componente;
- servir de base para uma API externa ou integração com ERP.

## 2. Modelo atual de catálogo

O projeto não depende mais de ACBr em runtime.

O catálogo já está compilado, versionado e pronto para uso em qualquer clone do repositório. Os artefatos mantidos são:

- `storage/municipios-catalog.php`
- `storage/municipios-catalog.js`

Uso esperado:

- `municipios-catalog.php`
  - fonte principal do runtime PHP;
- `municipios-catalog.js`
  - serialização auxiliar do catálogo, útil para exportação, inspeção ou consumo externo;
- `storage/schemes/`
  - schemas e arquivos técnicos de apoio.

## 3. Estrutura do projeto

### 3.1 Núcleo de execução

- `src/Tools.php`
  - fachada principal da biblioteca;
  - expõe as operações de alto nível.

- `src/RestCurl.php`
  - resolve o contexto municipal;
  - carrega o catálogo compilado;
  - monta a matriz de capacidades e URLs;
  - executa chamadas HTTP.

- `src/Common/MunicipioCatalog.php`
  - carrega o catálogo compilado;
  - aceita tanto o arquivo PHP quanto a representação serializada do catálogo.

- `src/Common/CatalogConfig.php`
  - define os caminhos padrão do catálogo.

### 3.2 Adapters e builders

- `src/Provider/`
  - registry, factory, profiles e contracts dos provedores.

- `src/Provider/Adapter/`
  - adapters por provedor.

- `src/ProviderBuilders/`
  - builders de XML, envelopes SOAP, payloads REST e particularidades por provedor.

### 3.3 DANFSe

- `src/Danfse/DanfsePdfGenerator.php`
  - renderização do DANFSe em PDF;
  - leitura do XML da NFS-e;
  - formatação de campos, valores e QR Code.

### 3.4 Operação local

- `web/public/index.php`
  - painel de uso manual para homologação e suporte técnico.

- `bin/municipio-info.php`
  - utilitário de linha de comando para consulta do catálogo.

## 4. Operações disponíveis

### 4.1 Catálogo e diagnóstico

- `detalhesMunicipio($prefeitura = null)`
- `diagnosticoMunicipio()`
- `listarMunicipios($uf = null, $provedor = null, $limit = null)`
- `municipioSuportado()`

### 4.2 Operações municipais

- `emitirNfseMunicipal($payload, $service = 'recepcionar')`
- `cancelarNfseMunicipal($payload, $service = 'cancelar_nfse')`
- `substituirNfseMunicipal($payload, $service = 'substituir_nfse')`
- `consultarMunicipal($service = 'consultar_nfse')`
- `consultarDanfseMunicipal($service = 'consultar_danfse')`

### 4.3 Padrão nacional

- `emitirNfsePadraoNacional($xmlDps)`
- `cancelarNfsePadraoNacional($evento)`
- `consultarNfseChave($chave, $encoding = true)`
- `consultarDanfse($chave)`
- `consultarDanfseNfse($chave)`

## 5. Fluxo de execução

Fluxo resumido do runtime:

1. o usuário informa município ou alias;
2. `RestCurl` resolve o contexto pelo catálogo compilado;
3. o `ProviderAdapterRegistry` identifica o adapter do provedor;
4. o `Tools` delega a montagem do plano de execução;
5. o builder correspondente gera o XML, envelope ou payload;
6. a chamada é enviada ao endpoint do provedor;
7. o retorno pode ser tratado, salvo e usado para geração de DANFSe.

## 6. DANFSe

O projeto já possui geração interna de DANFSe em PDF.

Métodos principais:

- `gerarDanfsePdf($xmlNfse, $outputPath = null)`
- `gerarDanfsePdfPorChave($chave, $outputPath = null)`

Pontos cobertos hoje:

- leitura do XML emitido;
- identificação de prestador, tomador e serviço;
- montagem do layout técnico do DANFSe;
- QR Code de consulta pública;
- formatação monetária, datas e percentuais.

## 7. Interface web

A interface em `web/public/index.php` foi mantida como ferramenta operacional. Ela permite:

- consulta do município e do provedor;
- emissão e cancelamento;
- consultas por lote, situação, chave e protocolo;
- visualização do XML de retorno;
- geração de DANFSe por chave ou por XML colado manualmente.

## 8. Estrutura de armazenamento

A pasta `storage` passa a manter apenas os artefatos necessários para runtime e apoio técnico:

- `storage/schemes/`
- `storage/municipios-catalog.php`
- `storage/municipios-catalog.js`

Arquivos antigos de auditoria, cobertura ou apoio temporário não fazem mais parte da estrutura final do projeto.

## 9. Estrutura da pasta `bin`

A pasta `bin` fica reduzida ao utilitário:

- `bin/municipio-info.php`

Esse script serve para validar rapidamente a resolução do catálogo sem depender da interface web.

## 10. Testes

Comandos disponíveis:

```bash
composer test
composer test:municipios
composer test:integration
composer test:real
```

## 11. Recomendação de uso em API própria

Para encapsular este pacote em uma API:

1. centralize a carga do certificado;
2. use `detalhesMunicipio()` antes de montar o payload;
3. padronize seus endpoints por operação;
4. salve request, response e XML final;
5. gere o DANFSe a partir do XML autorizado.

## 12. Resumo executivo

Hoje o `nfse-php-brazil` é uma base pronta para operação e evolução:

- catálogo já consolidado no repositório;
- runtime desacoplado de arquivos externos temporários;
- adapters e builders por provedor;
- painel web de apoio;
- geração de DANFSe dentro do próprio pacote.
