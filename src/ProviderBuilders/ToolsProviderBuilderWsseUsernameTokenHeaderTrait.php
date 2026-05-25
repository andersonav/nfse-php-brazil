<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderWsseUsernameTokenHeaderTrait
{
    /**
     * @param array<string,mixed> $payload
     */
    private function buildWsseUsernameTokenHeader(array $payload): string
    {
        $auth = $this->payloadAuth($payload);
        $user = (string) ($auth['username'] ?? '');
        $pass = (string) ($auth['password'] ?? '');
        if ($user === '' || $pass === '') {
            throw new RuntimeException('Saatri requer auth.username e auth.password.');
        }

        return '<wsse:Security soapenv:mustUnderstand="1" '
            . 'xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" '
            . 'xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">'
            . '<wsse:UsernameToken wsu:Id="UsernameToken-18">'
            . '<wsse:Username>' . $this->xmlValue($user) . '</wsse:Username>'
            . '<wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">'
            . $this->xmlValue($pass)
            . '</wsse:Password>'
            . '</wsse:UsernameToken>'
            . '</wsse:Security>';
    }
}
