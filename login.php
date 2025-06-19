<?php
include('conexao.php');

if (isset($_POST['email']) && isset($_POST['senha'])) {
    $email = $mysqli->real_escape_string($_POST['email']);
    $senha_digitada = $mysqli->real_escape_string($_POST['senha']);

    $sql_code_responsaveis = "SELECT * FROM responsaveis WHERE email = '$email'";
    $sql_query_responsaveis = $mysqli->query($sql_code_responsaveis) or die("Falha na execução do código SQL: " . $mysqli->error);
    $quantidade_responsaveis = $sql_query_responsaveis->num_rows;

    $sql_code_jogadores = "SELECT * FROM jogadores WHERE email = '$email'";
    $sql_query_jogadores = $mysqli->query($sql_code_jogadores) or die("Falha na execução do código SQL: " . $mysqli->error);
    $quantidade_jogadores = $sql_query_jogadores->num_rows;

    $sql_code_organizadores = "SELECT * FROM organizadores WHERE email = '$email'";
    $sql_query_organizadores = $mysqli->query($sql_code_organizadores) or die("Falha na execução do código SQL: " . $mysqli->error);
    $quantidade_organizadores = $sql_query_organizadores->num_rows;

    if ($quantidade_responsaveis == 1) {
        $responsavel = $sql_query_responsaveis->fetch_assoc();

        if (password_verify($senha_digitada, $responsavel['senha'])) {
            if (!isset($_SESSION)) {
                session_start();
            }

            $_SESSION['id'] = $responsavel['id'];
            $_SESSION['email'] = $responsavel['email'];
            $_SESSION['estado'] = $responsavel['estado'];
            $_SESSION['cidade'] = $responsavel['cidade'];
            $_SESSION['ct'] = $responsavel['ct'];
            $_SESSION['painel'] = 'responsavel';

            if ($responsavel['tipo'] == 'admin') {
                header("Location: admin/dashboard_admin.php");
            } else {
                header("Location: responsavel/dashboard_responsavel.php");
            }

            exit;
        } else {
            $erro = "E-mail ou senha inválidos!";
        }
    } else if ($quantidade_jogadores == 1) {
        $jogador = $sql_query_jogadores->fetch_assoc();

        if (password_verify($senha_digitada, $jogador['senha'])) {
            if (!isset($_SESSION)) {
                session_start();
            }

            $_SESSION['id'] = $jogador['id'];
            $_SESSION['email'] = $jogador['email'];
            $_SESSION['nome'] = $jogador['nome'];
            $_SESSION['estado'] = $jogador['estado'];
            $_SESSION['cidade'] = $jogador['cidade'];
            $_SESSION['descricao_contato'] = $jogador['descricao_contato'];
            $_SESSION['nivel'] = $jogador['nivel'];
            $_SESSION['id_jogador'] = $jogador['id'];
            $_SESSION['painel'] = 'jogador';

            header("Location: jogadores/dashboard_jogador.php");

            exit;
        } else {
            $erro = "E-mail ou senha inválidos!";
        }
    } else if ($quantidade_organizadores == 1) {
        $organizador = $sql_query_organizadores->fetch_assoc();

        if (password_verify($senha_digitada, $organizador['senha'])) {
            if (!isset($_SESSION)) {
                session_start();
            }

            $_SESSION['id'] = $organizador['id'];
            $_SESSION['email'] = $organizador['email'];
            $_SESSION['nome'] = $organizador['nome'];
            $_SESSION['painel'] = 'organizador';

            header("Location: organizadores/dashboard_organizador.php");

            exit;
        } else {
            $erro = "E-mail ou senha inválidos!";
        }
    }else {
        $erro = "E-mail ou senha incorretos.";
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" type="text/css" href="css/login.css">
</head>

<body>

    <form id="formulario" action="" method="POST" onsubmit="FocaSenha()">
        <p>
            <label>E-mail</label>
            <br>
            <input type="text" class="input-text" <?php if (isset($email)) { ?> value="<?= $email; ?>" <?php } ?> name="email">
        </p>
        <p>
            <label>Senha</label>
            <br>
            <input class="input-text" type="password" name="senha" id="senha">
            <br>
            <br>
            <small><a href="esqueceu_senha.php" style="font-size: 15px; text-decoration: none; color: gray; display: block;">Esqueceu a senha?</a></small>
        </p>
        <p>
            <a class="botao" href="index.php">Voltar</a>
            &nbsp &nbsp &nbsp
            <button type="submit" class="botao">Logar</button>
        </p>
        <div class="meio"> <a class="botao" id='cadastrar' href="dashboard_cadastrar.php" onclick="FocaSenha()">Cadastrar</a> </div>
    </form>

    <?php if (isset($erro)) { ?>
        <script>
            alert("<?php echo $erro; ?>");
        </script>
    <?php } ?>
</body>

<script>
    function FocaSenha() {
        localStorage.setItem('focarSenha', 'true');
    }

    document.addEventListener('DOMContentLoaded', function() {
        const senha = document.getElementById('senha');

        if (localStorage.getItem('focarSenha') === 'true') {
            senha.focus();
            localStorage.removeItem('focarSenha');
        }
    });
</script>

</html>