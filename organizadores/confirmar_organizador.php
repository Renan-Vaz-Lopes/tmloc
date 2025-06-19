<?php
include('../conexao.php');

if (isset($_GET['token'])) {
    $token = $mysqli->real_escape_string($_GET['token']);

    // Verifica se o token é válido e obtém a diferença em segundos
    $sql_busca = "SELECT *, TIMESTAMPDIFF(SECOND, criado_em, NOW()) as segundos_passados 
                  FROM organizadores_temp 
                  WHERE token = '$token'";
    $resultado = $mysqli->query($sql_busca);

    if ($resultado && $resultado->num_rows > 0) {
        $dados = $resultado->fetch_assoc();

        // Log para debug
        file_put_contents('debug_log.txt', "Token: $token, Segundos Passados: {$dados['segundos_passados']}\n", FILE_APPEND);

        // Verifica se o tempo decorrido é menor ou igual a 300 segundos (5 minutos)
        if ($dados['segundos_passados'] <= 300) {
            // Move os dados para a tabela principal
            $sql_insert = "INSERT INTO organizadores (nome, email, senha)
                           VALUES ('{$dados['nome']}', '{$dados['email']}', '{$dados['senha']}')";
            if ($mysqli->query($sql_insert)) {
                // Remove o registro da tabela temporária
                $mysqli->query("DELETE FROM organizadores_temp WHERE token = '$token'");
                $mensagem_sucesso = '
    <div>
        <p>Cadastro confirmado com sucesso!</p>
        <a href="../login.php" class="botao-confirmar-email">Fazer Login</a>
    </div>';
            } else {
                echo "Erro ao mover os dados para a tabela principal.";
            }
        } else {
            $mysqli->query("DELETE FROM organizadores_temp WHERE token = '$token'");
            echo "Token expirado. Por favor, solicite um novo cadastro.";
        }
    } else {
        echo "Token inválido ou expirado.";
    }
}
?>


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar email</title>
    <link rel="stylesheet" href="../css/login.css">
    
</head>

<body>
        <?php if (isset($mensagem_sucesso)) { ?>
            <p style="color: green;"><?= $mensagem_sucesso; ?></p>
        <?php } ?>
</body>

</html>
