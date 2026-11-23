<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| ACADFLOW - MÓDULO DE SEGURANÇA
|--------------------------------------------------------------------------
| Responsabilidades:
|
| 1. Inicializar sessões PHP.
| 2. Gerar tokens CSRF.
| 3. Recuperar tokens existentes.
| 4. Validar tokens enviados.
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| 1. INICIALIZAR SESSÃO
|--------------------------------------------------------------------------
*/

function iniciarSessaoSegura(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    if (headers_sent()) {
        throw new RuntimeException(
            'Não foi possível iniciar a sessão: cabeçalhos já enviados.'
        );
    }

    // Não disponibilizar o identificador da sessão ao JavaScript.
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}


/*
|--------------------------------------------------------------------------
| 2. GERAR TOKEN CSRF
|--------------------------------------------------------------------------
*/

function gerarTokenCSRF(): string
{
    iniciarSessaoSegura();

    if (
        !isset($_SESSION['csrf_token']) ||
        !is_string($_SESSION['csrf_token']) ||
        !preg_match(
            '/^[a-f0-9]{64}$/',
            $_SESSION['csrf_token']
        )
    ) {

        $_SESSION['csrf_token'] = bin2hex(
            random_bytes(32)
        );

    }

    return $_SESSION['csrf_token'];
}


/*
|--------------------------------------------------------------------------
| 3. RECUPERAR TOKEN
|--------------------------------------------------------------------------
*/

function obterTokenCSRF(): string
{
    return gerarTokenCSRF();
}


/*
|--------------------------------------------------------------------------
| 4. VALIDAR TOKEN CSRF
|--------------------------------------------------------------------------
*/

function validarTokenCSRF(?string $tokenRecebido): bool
{
    iniciarSessaoSegura();

    if (
        $tokenRecebido === null ||
        !preg_match('/^[a-f0-9]{64}$/', $tokenRecebido)
    ) {
        return false;
    }

    $tokenSessao = $_SESSION['csrf_token'] ?? null;

    if (!is_string($tokenSessao)) {
        return false;
    }

    return hash_equals(
        $tokenSessao,
        $tokenRecebido
    );
}


/*
|--------------------------------------------------------------------------
| 5. VALIDAR TOKEN ENVIADO POR POST
|--------------------------------------------------------------------------
*/

function validarCSRFPost(): bool
{
    $token = $_POST['csrf_token'] ?? null;

    if (!is_string($token)) {
        return false;
    }

    return validarTokenCSRF($token);
}