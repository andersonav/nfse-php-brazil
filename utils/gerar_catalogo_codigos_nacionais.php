<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$origem = $root . '/codigos_nacionais.txt';
$destino = $root . '/src/Utils/data/codigos_tributacao_nacional.php';

if (!is_file($origem)) {
    fwrite(STDERR, "Arquivo nao encontrado: {$origem}\n");
    exit(1);
}

$linhas = file($origem, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if ($linhas === false) {
    fwrite(STDERR, "Nao foi possivel ler: {$origem}\n");
    exit(1);
}

$map = [];
foreach ($linhas as $linha) {
    $linha = trim((string) $linha);
    if ($linha === '') {
        continue;
    }
    if (preg_match('/^([0-9]{3,6})\s*-\s*(.+)$/u', $linha, $m) === 1) {
        $map[$m[1]] = trim($m[2]);
    }
}

ksort($map, SORT_STRING);

$out = "<?php\n";
$out .= "declare(strict_types=1);\n\n";
$out .= "/**\n";
$out .= " * Catálogo de códigos de tributação nacional.\n";
$out .= " * Formato: [codigo => descricao].\n";
$out .= " */\n";
$out .= "return [\n";
foreach ($map as $codigo => $descricao) {
    $codigoEsc = var_export((string) $codigo, true);
    $descEsc = var_export((string) $descricao, true);
    $out .= "    {$codigoEsc} => {$descEsc},\n";
}
$out .= "];\n";

file_put_contents($destino, $out);
fwrite(STDOUT, "Catalogo gerado em: {$destino}\n");

