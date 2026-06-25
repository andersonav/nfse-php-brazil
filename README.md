# nfse-php-brazil

Biblioteca PHP para integração de NFS-e em municípios brasileiros, com catálogo compilado de provedores, runtime por adapter e geração de DANFSe em PDF.

## Visão geral

O projeto foi organizado para servir como base de uma API própria de NFS-e. Hoje ele entrega:

- resolução de município, provedor e URLs por ambiente;
- execução unificada de emissão, cancelamento, substituição e consultas;
- catálogo versionado dentro do próprio repositório;
- geração de DANFSe em PDF a partir do XML da NFS-e;
- interface web para operação, diagnóstico e testes.

## Estado atual do catálogo

O projeto não depende mais de ACBr em runtime.

O catálogo já está consolidado e versionado em:

- `storage/municipios-catalog.php`
- `storage/municipios-catalog.js`

Em produção, a biblioteca usa esses arquivos compilados diretamente.

## Estrutura principal

- `src/Tools.php`
  - fachada principal da biblioteca.
- `src/RestCurl.php`
  - resolve contexto municipal, provedor e URLs.
- `src/Provider/`
  - contracts, registry e adapters por provedor.
- `src/ProviderBuilders/`
  - builders de envelopes e payloads por provedor.
- `src/Danfse/`
  - geração de DANFSe em PDF.
- `storage/municipios-catalog.php`
  - catálogo principal consumido em runtime.
- `storage/municipios-catalog.js`
  - catálogo serializado para consumo auxiliar.
- `storage/schemes/`
  - schemas e artefatos técnicos mantidos no projeto.
- `bin/municipio-info.php`
  - utilitário simples para consulta do catálogo.
- `web/public/index.php`
  - interface web de operação e homologação.

## Instalação

```bash
composer require alves/nfse-php-brazil
```

## Configuração mínima

Exemplo:

```json
{
  "tpamb": 2,
  "prefeitura": "3550308",
  "catalog_compiled_path": "/app/storage/municipios-catalog.php"
}
```

## Operações principais

Consulta e diagnóstico:

- `detalhesMunicipio($prefeitura = null)`
- `diagnosticoMunicipio()`
- `listarMunicipios($uf = null, $provedor = null, $limit = null)`
- `municipioSuportado()`

Operações municipais:

- `emitirNfseMunicipal($payload, $service = 'recepcionar')`
- `cancelarNfseMunicipal($payload, $service = 'cancelar_nfse')`
- `substituirNfseMunicipal($payload, $service = 'substituir_nfse')`
- `consultarMunicipal($service = 'consultar_nfse')`
- `consultarDanfseMunicipal($service = 'consultar_danfse')`

Padrão nacional:

- `emitirNfsePadraoNacional($xmlDps)`
- `cancelarNfsePadraoNacional($evento)`
- `consultarNfseChave($chave, $encoding = true)`

## DANFSe

O componente já possui geração de DANFSe em PDF:

- `gerarDanfsePdf($xmlNfse, $outputPath = null)`
- `gerarDanfsePdfPorChave($chave, $outputPath = null)`

Exemplo:

- `examples/GerarDanfsePdf.php`

## Interface web

A interface em `web/public/index.php` permite:

- consultar detalhes do município;
- executar diagnóstico;
- listar municípios;
- emitir, consultar, cancelar e substituir NFS-e;
- consultar NFS-e por chave;
- gerar DANFSe por chave ou por XML manual;
- visualizar request e response técnicos.

## Utilitário de catálogo

Consultar um município pelo terminal:

```bash
php bin/municipio-info.php 3550308
```

Saída em JSON:

```bash
php bin/municipio-info.php 3550308 --json
```

## Testes

Executar a suíte principal:

```bash
composer test
```

Somente municípios:

```bash
composer test:municipios
```

Integração:

```bash
composer test:integration
```

## Observações

- O catálogo está versionado no repositório e pronto para uso em novos clones.
- O arquivo `.php` é a fonte principal de runtime.
- O arquivo `.js` é mantido como serialização auxiliar do mesmo catálogo.
- A pasta `storage/schemes` permanece como apoio técnico para validações e integração com provedores.