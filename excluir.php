<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| ACADFLOW — EXCLUSÃO DE ALUNOS
|--------------------------------------------------------------------------
| Responsabilidades:
| - Aceitar exclusivamente requisições POST
| - Validar o token CSRF
| - Validar o identificador do aluno
| - Localizar o registro no arquivo JSON
| - Excluir o aluno solicitado
| - Preservar os demais registros
| - Retornar uma resposta JSON para o JavaScript
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/seguranca.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');


/*
|--------------------------------------------------------------------------
| 1. FUNÇÃO DE RESPOSTA JSON
|--------------------------------------------------------------------------
*/

function responder(
    bool $sucesso,
    string $mensagem,
    int $codigoHTTP = 200
): never {

    http_response_code($codigoHTTP);

    echo json_encode(
        [
            'sucesso' => $sucesso,
            'mensagem' => $mensagem
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| 2. VALIDAR MÉTODO HTTP
|--------------------------------------------------------------------------
*/

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {

    responder(
        false,
        'Método de requisição inválido.',
        405
    );
}


/*
|--------------------------------------------------------------------------
| 3. VALIDAR TOKEN CSRF
|--------------------------------------------------------------------------
*/

if (!validarCSRFPost()) {

    responder(
        false,
        'Sessão inválida ou expirada. Atualize a página e tente novamente.',
        403
    );
}


/*
|--------------------------------------------------------------------------
| 4. RECEBER E VALIDAR IDENTIFICADOR
|--------------------------------------------------------------------------
*/

$idRecebido = $_POST['id'] ?? null;

if (!is_string($idRecebido)) {

    responder(
        false,
        'Identificador do aluno inválido.',
        400
    );
}

$id = trim($idRecebido);

if (!preg_match('/^[a-f0-9]{32}$/', $id)) {

    responder(
        false,
        'Identificador do aluno inválido.',
        400
    );
}


/*
|--------------------------------------------------------------------------
| 5. LOCALIZAR ARQUIVO DE DADOS
|--------------------------------------------------------------------------
*/

$arquivoDados = __DIR__ . '/dados/alunos.json';

if (!is_file($arquivoDados)) {

    responder(
        false,
        'Arquivo de alunos não encontrado.',
        404
    );
}


/*
|--------------------------------------------------------------------------
| 6. ABRIR ARQUIVO
|--------------------------------------------------------------------------
*/

$arquivo = fopen($arquivoDados, 'r+');

if ($arquivo === false) {

    responder(
        false,
        'Não foi possível acessar os dados dos alunos.',
        500
    );
}


/*
|--------------------------------------------------------------------------
| 7. BLOQUEAR ARQUIVO PARA ESCRITA
|--------------------------------------------------------------------------
*/

if (!flock($arquivo, LOCK_EX)) {

    fclose($arquivo);

    responder(
        false,
        'Não foi possível bloquear o arquivo de dados.',
        500
    );
}


/*
|--------------------------------------------------------------------------
| 8. PROCESSAR EXCLUSÃO
|--------------------------------------------------------------------------
*/

try {

    /*
    |--------------------------------------------------------------------------
    | 8.1. LER REGISTROS EXISTENTES
    |--------------------------------------------------------------------------
    */

    rewind($arquivo);

    $conteudo = stream_get_contents($arquivo);

    if (
        $conteudo === false ||
        trim($conteudo) === ''
    ) {

        throw new RuntimeException(
            'Arquivo de alunos vazio ou indisponível.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 8.2. DECODIFICAR JSON
    |--------------------------------------------------------------------------
    */

    $alunos = json_decode(
        $conteudo,
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    if (
        !is_array($alunos) ||
        !array_is_list($alunos)
    ) {

        throw new RuntimeException(
            'Estrutura inválida no arquivo de alunos.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 8.3. LOCALIZAR O ALUNO
    |--------------------------------------------------------------------------
    */

    $indiceAluno = null;

    foreach ($alunos as $indice => $aluno) {

        if (!is_array($aluno)) {

            throw new RuntimeException(
                'Registro inválido no arquivo de alunos.'
            );
        }

        if (($aluno['id'] ?? null) === $id) {

            $indiceAluno = $indice;

            break;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | 8.4. VERIFICAR SE O ALUNO EXISTE
    |--------------------------------------------------------------------------
    */

    if ($indiceAluno === null) {

        flock($arquivo, LOCK_UN);

        fclose($arquivo);

        responder(
            false,
            'Aluno não encontrado.',
            404
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 8.5. REMOVER O REGISTRO
    |--------------------------------------------------------------------------
    */

    unset($alunos[$indiceAluno]);

    /*
     * Reorganiza os índices numéricos do array.
     * Isso garante que o JSON continue sendo uma lista.
     */

    $alunos = array_values($alunos);


    /*
    |--------------------------------------------------------------------------
    | 8.6. CONVERTER DADOS PARA JSON
    |--------------------------------------------------------------------------
    */

    $json = json_encode(
        $alunos,
        JSON_PRETTY_PRINT |
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES |
        JSON_THROW_ON_ERROR
    );


    /*
    |--------------------------------------------------------------------------
    | 8.7. GRAVAR ALTERAÇÕES
    |--------------------------------------------------------------------------
    */

    rewind($arquivo);

    if (!ftruncate($arquivo, 0)) {

        throw new RuntimeException(
            'Não foi possível preparar a exclusão.'
        );
    }

    $tamanho = strlen($json);

    $gravados = 0;

    while ($gravados < $tamanho) {

        $resultado = fwrite(
            $arquivo,
            substr($json, $gravados)
        );

        if (
            $resultado === false ||
            $resultado === 0
        ) {

            throw new RuntimeException(
                'Falha durante a gravação dos dados.'
            );
        }

        $gravados += $resultado;
    }

    if (!fflush($arquivo)) {

        throw new RuntimeException(
            'Não foi possível concluir a exclusão.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 8.8. LIBERAR ARQUIVO
    |--------------------------------------------------------------------------
    */

    flock($arquivo, LOCK_UN);

    fclose($arquivo);


    /*
    |--------------------------------------------------------------------------
    | 8.9. RETORNAR SUCESSO
    |--------------------------------------------------------------------------
    */

    responder(
        true,
        'Aluno excluído com sucesso!'
    );

} catch (Throwable $erro) {

    /*
    |--------------------------------------------------------------------------
    | 9. TRATAMENTO DE ERROS
    |--------------------------------------------------------------------------
    */

    if (is_resource($arquivo)) {

        flock($arquivo, LOCK_UN);

        fclose($arquivo);
    }

    error_log(
        '[AcadFlow] Erro na exclusão: ' .
        $erro->getMessage()
    );

    responder(
        false,
        'Não foi possível excluir o aluno. Tente novamente.',
        500
    );
}