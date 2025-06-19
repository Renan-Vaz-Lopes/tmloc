<?php
include('conexao.php');

if (isset($_GET['token'])) {
    $token = $mysqli->real_escape_string($_GET['token']);
    $erro = [];
    // Verifica se o token é válido
    $sql = "SELECT * FROM recuperacao_senha WHERE token = '$token' AND expira_em > NOW()";
    $result = $mysqli->query($sql);

    if ($result->num_rows > 0) {
        if (isset($_POST['senha'])) {
            $senha = $_POST['senha'];
            if (strlen($senha) < 8) {
                $erro[] = "A senha deve ter no mínimo 8 caracteres.";
            }

            if (!preg_match('/[A-Z]/', $senha)) {
                $erro[] = "A senha deve ter no mínimo 1 letra maiúscula";
            }

            if (!preg_match('/[a-z]/', $senha)) {
                $erro[] = "A senha deve ter no mínimo 1 letra minúscula";
            }

            if (!preg_match('/[\W_]/', $senha)) {
                $erro[] = "A senha deve ter no mínimo 1 caractere especial";
            }

            if (empty($erro)) {
                $nova_senha = password_hash($senha, PASSWORD_DEFAULT);
                $dados = $result->fetch_assoc();

                // Atualiza a senha no banco de dados
                $email = $dados['email'];

                $sql_update_responsaveis = "UPDATE responsaveis SET senha = '$nova_senha' WHERE email = '$email'";
                $mysqli->query($sql_update_responsaveis);

                $sql_update_jogadores = "UPDATE jogadores SET senha = '$nova_senha' WHERE email = '$email'";
                $mysqli->query($sql_update_jogadores);

                // Remove o token usado
                $sql_delete = "DELETE FROM recuperacao_senha WHERE email = '$email'";
                $mysqli->query($sql_delete);

                $mensagem_sucesso = '
    <div>
        <p>Senha redefinida com sucesso! Você já pode fazer login.</p>
        <a href="login.php" class="botao-redefinir-senha">Fazer Login</a>
    </div>';
            } else {
                $mensagem_erro = "<ul>";
                foreach ($erro as $mensagem) {
                    $mensagem_erro .= "<li>$mensagem</li>";
                }
                $mensagem_erro .= "</ul>";
            }
        }
    } else {
        $mensagem_erro = "Token inválido ou expirado.";
    }
} else {
    header("Location: esqueceu_senha.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha</title>
    <link rel="stylesheet" href="css/login.css">
    
</head>

<body>
    <form action="" method="POST">
        <h2>Redefinir Senha</h2>
        <?php if (isset($mensagem_erro)) { ?>
            <p style="color: red;"><?= $mensagem_erro; ?></p>
        <?php } ?>

        <?php if (isset($mensagem_sucesso)) { ?>
            <p style="color: green;"><?= $mensagem_sucesso; ?></p>
        <?php } else { ?>
            <p>Digite sua nova senha:</p>
            <input type="password" name="senha" required placeholder="Nova senha" value="<?php echo isset($_POST['senha']) ? $_POST['senha'] : ''; ?>">
            <button type="submit">Redefinir Senha</button>
        <?php } ?>
    </form>
</body>

</html>