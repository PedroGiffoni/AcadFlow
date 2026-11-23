<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| ACADFLOW - PROCESSAMENTO DE CADASTRO
|--------------------------------------------------------------------------
| Responsabilidades:
| - Receber dados do formulário
| - Validar método HTTP
| - Validar token CSRF
| - Validar campos obrigatórios
| - Impedir matrículas duplicadas
| - Persistir dados em JSON
| - Redirecionar com mensagem de resultado
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/seguranca.php';

header('Content-Type: text/html; charset=UTF-8');


/*
|--------------------------------------------------------------------------
| FUNÇÃO DE RETORNO
|--------------------------------------------------------------------------
*/

function retornar(string $status, string $mensagem): never
{
    $parametros = http_build_query([
        'status' => $status,
        'mensagem' => $mensagem
    ]);

    header(
        'Location: index.php?' . $parametros . '#cadastro',
        true,
        303
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| 1. VALIDAR MÉTODO HTTP
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    retornar(
        'erro',
        'Método de requisição inválido.'
    );
}


/*
|--------------------------------------------------------------------------
| 2. VALIDAR TOKEN CSRF
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
| 3. RECEBER E VALIDAR DADOS
|--------------------------------------------------------------------------
*/

$nomeRecebido = $_POST['nome'] ?? null;
$matriculaRecebida = $_POST['matricula'] ?? null;
$cursoRecebido = $_POST['curso'] ?? null;

if (
    !is_string($nomeRecebido) ||
    !is_string($matriculaRecebida) ||
    !is_string($cursoRecebido)
) {
    retornar(
        'erro',
        'Os dados enviados são inválidos.'
    );
}

$nome = trim($nomeRecebido);
$matricula = trim($matriculaRecebida);
$curso = trim($cursoRecebido);

if ($nome === '' || $matricula === '' || $curso === '') {
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
| 4. PREPARAR ARMAZENAMENTO
|--------------------------------------------------------------------------
*/

$diretorioDados = __DIR__ . '/dados';
$arquivoDados = $diretorioDados . '/alunos.json';

if (!is_dir($diretorioDados)) {
    if (
        !mkdir($diretorioDados, 0755, true) &&
        !is_dir($diretorioDados)
    ) {
        retornar(
            'erro',
            'Não foi possível preparar o armazenamento.'
        );
    }
}

$arquivo = fopen($arquivoDados, 'c+');

if ($arquivo === false) {
    retornar(
        'erro',
        'Não foi possível acessar os dados dos alunos.'
    );
}


/*
|--------------------------------------------------------------------------
| 5. BLOQUEAR ARQUIVO PARA ESCRITA
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
| 6. CARREGAR REGISTROS EXISTENTES
|--------------------------------------------------------------------------
*/

try {

    rewind($arquivo);

    $conteudo = stream_get_contents($arquivo);

    if ($conteudo === false) {
        throw new RuntimeException(
            'Falha ao ler o arquivo de dados.'
        );
    }

    if (trim($conteudo) === '') {
        $alunos = [];
    } else {

        $alunos = json_decode(
            $conteudo,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        if (!is_array($alunos) || !array_is_list($alunos)) {
            throw new RuntimeException(
                'Estrutura inválida no arquivo de dados.'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | 7. VERIFICAR MATRÍCULA DUPLICADA
    |--------------------------------------------------------------------------
    */

    foreach ($alunos as $aluno) {

        if (
            !is_array($aluno) ||
            !isset($aluno['matricula'])
        ) {
            continue;
        }

        if ((string) $aluno['matricula'] === $matricula) {

            flock($arquivo, LOCK_UN);
            fclose($arquivo);

            retornar(
                'erro',
                'Já existe um aluno cadastrado com essa matrícula.'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | 8. CRIAR NOVO ALUNO
    |--------------------------------------------------------------------------
    */

    $novoAluno = [
        'id' => bin2hex(random_bytes(16)),
        'nome' => $nome,
        'matricula' => $matricula,
        'curso' => $curso,
        'criado_em' => date('Y-m-d H:i:s')
    ];

    $alunos[] = $novoAluno;


    /*
    |--------------------------------------------------------------------------
    | 9. CONVERTER PARA JSON
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
    | 10. SALVAR DADOS
    |--------------------------------------------------------------------------
    */

    rewind($arquivo);

    if (!ftruncate($arquivo, 0)) {
        throw new RuntimeException(
            'Não foi possível preparar a gravação.'
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
            'Não foi possível concluir a gravação.'
        );
    }

    flock($arquivo, LOCK_UN);
    fclose($arquivo);


    /*
    |--------------------------------------------------------------------------
    | 11. CADASTRO CONCLUÍDO
    |--------------------------------------------------------------------------
    */

    retornar(
        'sucesso',
        'Aluno cadastrado com sucesso!'
    );

} catch (Throwable $erro) {

    if (is_resource($arquivo)) {
        flock($arquivo, LOCK_UN);
        fclose($arquivo);
    }

    error_log(
        '[AcadFlow] Erro no cadastro: ' . $erro->getMessage()
    );

    retornar(
        'erro',
        'Não foi possível concluir o cadastro. Tente novamente.'
    );
}