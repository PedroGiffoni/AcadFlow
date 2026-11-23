<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| ACADFLOW - LISTAGEM DE ALUNOS
|--------------------------------------------------------------------------
| Método: GET
| Formato de resposta: JSON
| Responsabilidade: consultar os alunos cadastrados.
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| 1. CONFIGURAÇÕES
|--------------------------------------------------------------------------
*/

header('Content-Type: application/json; charset=UTF-8');

header('Cache-Control: no-store, no-cache, must-revalidate');

header('X-Content-Type-Options: nosniff');

$arquivoDados = __DIR__ . '/dados/alunos.json';


/*
|--------------------------------------------------------------------------
| 2. FUNÇÃO DE RESPOSTA
|--------------------------------------------------------------------------
*/

function responder(
    int $codigo,
    bool $sucesso,
    string $mensagem,
    array $dados = []
): void {

    http_response_code($codigo);

    echo json_encode(
        [
            'sucesso' => $sucesso,
            'mensagem' => $mensagem,
            'dados' => $dados
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_PRETTY_PRINT
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| 3. VALIDAR MÉTODO HTTP
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {

    header('Allow: GET');

    responder(
        405,
        false,
        'Método HTTP não permitido.'
    );

}


/*
|--------------------------------------------------------------------------
| 4. VERIFICAR EXISTÊNCIA DO ARQUIVO
|--------------------------------------------------------------------------
*/

if (!file_exists($arquivoDados)) {

    responder(
        200,
        true,
        'Nenhum aluno cadastrado.',
        []
    );

}


/*
|--------------------------------------------------------------------------
| 5. ABRIR ARQUIVO
|--------------------------------------------------------------------------
*/

$arquivo = fopen($arquivoDados, 'rb');

if ($arquivo === false) {

    responder(
        500,
        false,
        'Não foi possível abrir o arquivo de dados.'
    );

}


/*
|--------------------------------------------------------------------------
| 6. BLOQUEAR PARA LEITURA
|--------------------------------------------------------------------------
| O bloqueio compartilhado evita a leitura enquanto
| outro processo estiver gravando os dados.
|--------------------------------------------------------------------------
*/

if (!flock($arquivo, LOCK_SH)) {

    fclose($arquivo);

    responder(
        500,
        false,
        'Não foi possível acessar os registros.'
    );

}


/*
|--------------------------------------------------------------------------
| 7. LER CONTEÚDO
|--------------------------------------------------------------------------
*/

$conteudo = stream_get_contents($arquivo);

flock($arquivo, LOCK_UN);

fclose($arquivo);

if ($conteudo === false) {

    responder(
        500,
        false,
        'Erro ao ler os registros.'
    );

}


/*
|--------------------------------------------------------------------------
| 8. VERIFICAR ARQUIVO VAZIO
|--------------------------------------------------------------------------
*/

if (trim($conteudo) === '') {

    responder(
        200,
        true,
        'Nenhum aluno cadastrado.',
        []
    );

}


/*
|--------------------------------------------------------------------------
| 9. DECODIFICAR JSON
|--------------------------------------------------------------------------
*/

try {

    $alunos = json_decode(
        $conteudo,
        true,
        512,
        JSON_THROW_ON_ERROR
    );

} catch (JsonException $erro) {

    responder(
        500,
        false,
        'Erro ao interpretar os dados armazenados.'
    );

}


/*
|--------------------------------------------------------------------------
| 10. VALIDAR ESTRUTURA
|--------------------------------------------------------------------------
*/

if (!is_array($alunos) || !array_is_list($alunos)) {

    responder(
        500,
        false,
        'Estrutura de dados inválida.'
    );

}


/*
|--------------------------------------------------------------------------
| 11. VALIDAR REGISTROS
|--------------------------------------------------------------------------
*/

foreach ($alunos as $aluno) {

    if (
        !is_array($aluno) ||
        !isset(
            $aluno['id'],
            $aluno['nome'],
            $aluno['matricula'],
            $aluno['curso']
        )
    ) {

        responder(
            500,
            false,
            'Foi encontrado um registro inválido.'
        );

    }

}


/*
|--------------------------------------------------------------------------
| 12. ORDENAR ALUNOS POR NOME
|--------------------------------------------------------------------------
*/

usort($alunos, function ($a, $b) {

    return strcasecmp(
        $a['nome'],
        $b['nome']
    );

});


/*
|--------------------------------------------------------------------------
| 13. RETORNAR DADOS
|--------------------------------------------------------------------------
*/

responder(
    200,
    true,
    'Alunos consultados com sucesso.',
    $alunos
);