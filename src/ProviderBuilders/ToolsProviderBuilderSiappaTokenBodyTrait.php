<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderSiappaTokenBodyTrait
{
    private function buildSiappaTokenBody(string $user, string $senha, string $cnpj, string $execucao): string
    {
        return '<Ws_000_in_prest_insc_seq>' . $this->xmlValue($user) . '</Ws_000_in_prest_insc_seq>'
            . '<Ws_000_in_prest_cnpj>' . $this->xmlValue($cnpj) . '</Ws_000_in_prest_cnpj>'
            . '<Ws_000_in_prest_ws_senha>' . $this->xmlValue($senha) . '</Ws_000_in_prest_ws_senha>'
            . '<Ws_000_in_opc_execucao>' . $this->xmlValue($execucao) . '</Ws_000_in_opc_execucao>';
    }
}
