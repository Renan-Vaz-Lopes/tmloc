<?php
include('../../conexao.php');
include('../../protect.php');

$erro = [];
$mensagem_erro = '';

$id = $_GET['codigo'];
$sql_busca_organizador = "SELECT * FROM organizadores WHERE id = $id";
$resultado = $mysqli->query($sql_busca_organizador);

if ($resultado && $resultado->num_rows > 0) {
    $organizador = $resultado->fetch_assoc();
} else {
    echo "Organizador não encontrado.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $mysqli->real_escape_string($_POST['nome']);
    $email = $mysqli->real_escape_string($_POST['email']);
    $senha = $mysqli->real_escape_string($_POST['senha']);
    $confirmacao_senha = $mysqli->real_escape_string($_POST['confirmacao_senha']);

    if (preg_match('/[0-9]/', $nome)) {
        $erro[] = "O nome não pode conter números.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro[] = "Formato de email inválido.";
    }

    if (!empty($senha)) {
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

        if ($senha != $confirmacao_senha) {
            $erro[] = "As senhas não coincidem!";
        }
    }

    if (empty($erro)) {
        if ($senha > 0 && $confirmacao_senha > 0) {
            $senha_hash = password_hash($senha, PASSWORD_ARGON2ID);

            $sql_code = "UPDATE organizadores SET nome = '$nome', email = '$email', senha = '$senha_hash' WHERE id = $id";
        } else {
            $sql_code = "UPDATE organizadores SET nome = '$nome', email = '$email' WHERE id = $id";
        }


        if ($mysqli->query($sql_code) === TRUE) {
            $success = true;
        } else {
            $success = false;
            $error_message = "Erro ao atualizar os dados: " . $mysqli->error;
        }
    } else {

        $mensagem_erro = "<ul>";
        foreach ($erro as $mensagem) {
            $mensagem_erro .= "<li>$mensagem</li>";
        }
        $mensagem_erro .= "</ul>";
    }
}

?>

<script>
    <?php if (isset($success) && $success) { ?>
        alert("Organizador atualizado com sucesso!");
        window.location.href = '../admin_organizadores.php';
    <?php } elseif (isset($error_message)) { ?>
        alert("Erro ao atualizar os dados:\n<?php echo $error_message; ?>");
    <?php } ?>
</script>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualizar Organizador</title>
    <link rel="stylesheet" type="text/css" href="../../css/atualizar_organizador.css">
</head>

<body>
    <a href="../admin_organizadores.php">Voltar</a>
    <form action="" method="POST">
        <?php
        if (!empty($mensagem_erro)) {
            echo "<div class='erro'>$mensagem_erro</div>";
        }
        ?>

        <p>
            <label>Nome</label><br>
            <input class="input-text" type="text" name="nome" value="<?= isset($_POST['nome']) ? $_POST['nome'] : (isset($organizador) ? $organizador['nome'] : '') ?>">
        </p>

        <p>
            <label>E-mail</label><br>
            <input type="text" class="input-text" name="email" id="email" value="<?= isset($_POST['email']) && !empty($_POST['email']) ? $_POST['email'] : $organizador['email'] ?>">

        </p>

        <!-- <p>
            <label>Senha</label><br>
            <input class="input-text" type="password" name="senha">
        </p>

        <p>
            <label>Confirmação de senha</label><br>
            <input class="input-text" type="password" name="confirmacao_senha">
        </p> -->

        <p>
            <button type="submit" class="semBorda" onclick="return validaSelecaoDeCt()">Atualizar</button>
        </p>
    </form>

</body>

</html>