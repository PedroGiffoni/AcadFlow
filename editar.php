<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| ACADFLOW — EDIÇÃO DE ALUNOS
|--------------------------------------------------------------------------
| Responsabilidades:
| - Validar método HTTP
| - Validar token CSRF
| - Validar identificador do aluno
| - Validar dados recebidos
| - Impedir matrículas duplicadas
| - Atualizar o registro no arquivo JSON
| - Preservar o histórico de criação
| - Registrar a data da atualização
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/seguranca.php';

header('Content-Type: text/html; charset=UTF-8');


/*
|--------------------------------------------------------------------------
| 1. FUNÇÃO DE RETORNO
|--------------------------------------------------------------------------
*/

function retornar(string $status, string $mensagem): never
{
    $parametros = http_build_query([
        'status' => $status,
        'mensagem' => $mensagem
    ]);

    header(
        'Location: index.php?' . $parametros . '#alunos',
        true,
        303
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| 2. VALIDAR MÉTODO HTTP
|--------------------------------------------------------------------------
*/

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    retornar(
        'erro',
        'Método de requisição inválido.'
    );
}


/*
|--------------------------------------------------------------------------
| 3. VALIDAR TOKEN CSRF
|--------------------------------------------------------------------------
*/

if (!validarCSRFPost()) {
    retornar(
        'erro',
        'Sessão inválida ou expirada. Atualize a página e tente novamente.'
    );
}


/*
|--------------------------------------------------------------------------
| 4. RECEBER OS DADOS
|--------------------------------------------------------------------------
*/

$idRecebido = $_POST['id'] ?? null;
$nomeRecebido = $_POST['nome'] ?? null;
$matriculaRecebida = $_POST['matricula'] ?? null;
$cursoRecebido = $_POST['curso'] ?? null;

if (
    !is_string($idRecebido) ||
    !is_string($nomeRecebido) ||
    !is_string($matriculaRecebida) ||
    !is_string($cursoRecebido)
) {
    retornar(
        'erro',
        'Os dados enviados são inválidos.'
    );
}

$id = trim($idRecebido);
$nome = trim($nomeRecebido);
$matricula = trim($matriculaRecebida);
$curso = trim($cursoRecebido);


/*
|--------------------------------------------------------------------------
| 5. VALIDAR IDENTIFICADOR
|--------------------------------------------------------------------------
*/

if (!preg_match('/^[a-f0-9]{32}$/', $id)) {
    retornar(
        'erro',
        'Identificador do aluno inválido.'
    );
}


/*
|--------------------------------------------------------------------------
| 6. VALIDAR CAMPOS OBRIGATÓRIOS
|--------------------------------------------------------------------------
*/

if (
    $nome === '' ||
    $matricula === '' ||
    $curso === ''
) {
    retornar(
        'erro',
        'Preencha todos os campos obrigatórios.'
    );
}

if (mb_strlen($nome, 'UTF-8') > 120) {
    retornar(
        'erro',
        'O nome deve ter no máximo 120 caracteres.'
    );
}

if (!preg_match('/^[0-9]{1,20}$/', $matricula)) {
    retornar(
        'erro',
        'A matrícula deve conter apenas números, com no máximo 20 dígitos.'
    );
}

$cursosPermitidos = [
    'ADS',
    'SI',
    'CC',
    'ES'
];

if (!in_array($curso, $cursosPermitidos, true)) {
    retornar(
        'erro',
        'Selecione um curso válido.'
    );
}


/*
|--------------------------------------------------------------------------
| 7. LOCALIZAR ARQUIVO DE DADOS
|--------------------------------------------------------------------------
*/

$arquivoDados = __DIR__ . '/dados/alunos.json';

if (!is_file($arquivoDados)) {
    retornar(
        'erro',
        'Arquivo de alunos não encontrado.'
    );
}

$arquivo = fopen($arquivoDados, 'r+');

if ($arquivo === false) {
    retornar(
        'erro',
        'Não foi possível acessar os dados dos alunos.'
    );
}


/*
|--------------------------------------------------------------------------
| 8. BLOQUEAR ARQUIVO PARA EDIÇÃO
|--------------------------------------------------------------------------
*/

if (!flock($arquivo, LOCK_EX)) {
    fclose($arquivo);

    retornar(
        'erro',
        'Não foi possível bloquear o arquivo de dados.'
    );
}


/*
|--------------------------------------------------------------------------
| 9. PROCESSAR ATUALIZAÇÃO
|--------------------------------------------------------------------------
*/

try {

    rewind($arquivo);

    $conteudo = stream_get_contents($arquivo);

    if ($conteudo === false || trim($conteudo) === '') {
        throw new RuntimeException(
            'Arquivo de alunos vazio ou indisponível.'
        );
    }

    $alunos = json_decode(
        $conteudo,
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    if (!is_array($alunos) || !array_is_list($alunos)) {
        throw new RuntimeException(
            'Estrutura inválida no arquivo de alunos.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 10. LOCALIZAR ALUNO E VALIDAR MATRÍCULA
    |--------------------------------------------------------------------------
    */

    $indiceAluno = null;

    foreach ($alunos as $indice => $aluno) {

        if (!is_array($aluno)) {
            throw new RuntimeException(
                'Registro inválido no arquivo de alunos.'
            );
        }

        $idAtual = $aluno['id'] ?? null;

        if ($idAtual === $id) {
            $indiceAluno = $indice;
        }

        /*
         * A matrícula pode continuar igual para o próprio aluno.
         * Ela não pode pertencer a outro registro.
         */

        if (
            $idAtual !== $id &&
            isset($aluno['matricula']) &&
            (string) $aluno['matricula'] === $matricula
        ) {
            flock($arquivo, LOCK_UN);
            fclose($arquivo);

            retornar(
                'erro',
                'Já existe outro aluno cadastrado com essa matrícula.'
            );
        }
    }

    if ($indiceAluno === null) {
        flock($arquivo, LOCK_UN);
        fclose($arquivo);

        retornar(
            'erro',
            'Aluno não encontrado.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 11. ATUALIZAR REGISTRO
    |--------------------------------------------------------------------------
    */

    $alunos[$indiceAluno]['nome'] = $nome;
    $alunos[$indiceAluno]['matricula'] = $matricula;
    $alunos[$indiceAluno]['curso'] = $curso;

    /*
     * Preservamos o campo criado_em.
     * Atualizamos apenas a data de modificação.
     */

    $alunos[$indiceAluno]['atualizado_em'] = date(
        'Y-m-d H:i:s'
    );


    /*
    |--------------------------------------------------------------------------
    | 12. CONVERTER PARA JSON
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
    | 13. GRAVAR ALTERAÇÕES
    |--------------------------------------------------------------------------
    */

    rewind($arquivo);

    if (!ftruncate($arquivo, 0)) {
        throw new RuntimeException(
            'Não foi possível preparar a atualização.'
        );
    }

    $tamanho = strlen($json);
    $gravados = 0;

    while ($gravados < $tamanho) {

        $resultado = fwrite(
            $arquivo,
            substr($json, $gravados)
        );

        if ($resultado === false || $resultado === 0) {
            throw new RuntimeException(
                'Falha durante a gravação dos dados.'
            );
        }

        $gravados += $resultado;
    }

    if (!fflush($arquivo)) {
        throw new RuntimeException(
            'Não foi possível concluir a atualização.'
        );
    }

    flock($arquivo, LOCK_UN);
    fclose($arquivo);


    /*
    |--------------------------------------------------------------------------
    | 14. ATUALIZAÇÃO CONCLUÍDA
    |--------------------------------------------------------------------------
    */

    retornar(
        'sucesso',
        'Dados do aluno atualizados com sucesso!'
    );

} catch (Throwable $erro) {

    if (is_resource($arquivo)) {
        flock($arquivo, LOCK_UN);
        fclose($arquivo);
    }

    error_log(
        '[AcadFlow] Erro na edição: ' . $erro->getMessage()
    );

    retornar(
        'erro',
        'Não foi possível atualizar o aluno. Tente novamente.'
    );
}