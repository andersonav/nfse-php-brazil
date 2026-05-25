<?php

namespace Alves\NfseBrasil\Common;

final class CatalogConfig
{
    public static function defaultCompiledPath(): string
    {
        return __DIR__ . '/../../storage/municipios-catalog.php';
    }

    public static function defaultJsonPath(): string
    {
        return __DIR__ . '/../../storage/municipios-catalog.js';
    }
}
