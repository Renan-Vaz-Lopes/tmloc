<?php
include('../conexao.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../config.php';
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

$erro = [];
$mensagem_erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'], $_POST['nome'], $_POST['senha'], $_POST['confirmacao_senha'])) {
    $email = $mysqli->real_escape_string($_POST['email']);
    $nome = $mysqli->real_escape_string($_POST['nome']);
    $senha = $mysqli->real_escape_string($_POST['senha']);
    $confirmacao_senha = $mysqli->real_escape_string($_POST['confirmacao_senha']);

    $sql_busca_responsavel = "SELECT * FROM responsaveis WHERE email = '$email'";
    $resultado_responsavel = $mysqli->query($sql_busca_responsavel);

    $sql_busca_jogador = "SELECT * FROM jogadores WHERE email = '$email'";
    $resultado_jogador = $mysqli->query($sql_busca_jogador);

    $sql_busca_organizador = "SELECT * FROM organizadores WHERE email = '$email'";
    $resultado_organizador = $mysqli->query($sql_busca_organizador);

    $sql_busca_organizador_temp = "SELECT * FROM organizadores_temp WHERE email = '$email'";
    $resultado_organizador_temp = $mysqli->query($sql_busca_organizador_temp);

    if ($resultado_responsavel && $resultado_responsavel->num_rows > 0) {
        $erro[] = "Email já vinculado a um responsável por um CT";
    }

    if ($resultado_jogador && $resultado_jogador->num_rows > 0) {
        $erro[] = "Email já vinculado a um jogador.";
    }

    if ($resultado_organizador && $resultado_organizador->num_rows > 0) {
        $erro[] = "Email já vinculado a um organizador de campeonato.";
    }

    if ($resultado_organizador_temp && $resultado_organizador_temp->num_rows > 0) {
        $erro[] = "Já foi enviado um email de confirmação e ele tem validade de 5 minutos";
    }

    if (preg_match('/[0-9]/', $nome)) {
        $erro[] = "O nome não pode conter números.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro[] = "Formato de email inválido.";
    }

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

    if (empty($erro)) {
        $senha_hash = password_hash($senha, PASSWORD_ARGON2ID);
        $token = bin2hex(random_bytes(16));
        sleep(3);

        $sql_code = "INSERT INTO organizadores_temp (email, nome, senha, token) VALUES ('$email', '$nome', '$senha_hash', '$token')";
        $confirma = $mysqli->query($sql_code) or die($mysqli->error);

        if ($confirma) {
            $link = "http://localhost/tmloc/organizadores/confirmar_organizador.php?token=$token";

            $mail = new PHPMailer(true);
            try {
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );

                // Configurações do servidor
                $mail->isSMTP();
                $mail->Host = $CONFIG['email']['host'];
                $mail->SMTPAuth = true;
                $mail->Port = $CONFIG['email']['port'];
                $mail->SMTPSecure = $CONFIG['email']['SMTPSecure'];
                'ssl';
                $mail->Username = $CONFIG['email']['username'];
                $mail->Password = $CONFIG['email']['password'];

                $mail->setFrom($CONFIG['email']['username'], 'tmloc');
                $mail->addAddress($email, $nome);


                $mail->isHTML(true);
                $mail->Subject = 'Confirme seu cadastro';
                $mail->Body = "
                    <h2>Bem-vindo, $nome!</h2>
                    <p>Clique no link abaixo para confirmar seu cadastro:</p>
                    <a href='$link'>Confirmar cadastro</a>";

                $mail->send();
                $msg = 'E-mail de confirmação enviado! Verifique sua caixa de entrada(spam também), tem 5 minutos antes de expirar.';
            } catch (Exception $e) {
                $msg = "Erro ao enviar e-mail: {$mail->ErrorInfo}";
            }
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

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Organizador</title>
    <link rel="stylesheet" type="text/css" href="../css/cadastrar_organizador.css">
</head>

<body>
    <a href="../dashboard_cadastrar.php">Voltar</a>
    <div class="form-container">

        <div>
            <?php if (!empty($msg)) { ?>
                <div class="msg-envio-email">
                    <?php echo $msg; ?>
                </div>
            <?php } ?>
        </div>

        <div>
            <?php if (!empty($mensagem_erro)) { ?>
                <div class="erro">
                    <?php echo $mensagem_erro; ?>
                </div>
            <?php } ?>
        </div>

        <div>
            <form action="" id="formCadastrar" method="POST">
                <p>
                    <label>Nome</label><br>
                    <input class="input-text" type="text" name="nome" value="<?= isset($nome) ? $nome : '' ?>">
                </p>

                <p>
                    <label>E-mail</label><br>
                    <input class="input-text" type="text" name="email" value="<?= isset($email) ? $email : '' ?>">
                </p>

                <p>
                    <label>Senha</label><br>
                    <input class="input-text" type="password" name="senha" value="<?= isset($senha) ? $senha : '' ?>">
                </p>

                <p>
                    <label>Confirmação de senha</label><br>
                    <input class="input-text" type="password" name="confirmacao_senha" value="<?= isset($confirmacao_senha) ? $confirmacao_senha : '' ?>">
                </p>

                <p class="meio">
                    <button type="submit" class="semBorda" id="btnCadastrar" onclick="return validaSelecaoDeCt()">Cadastrar</button>
                </p>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('formCadastrar').addEventListener('submit', function() {
            document.getElementById('btnCadastrar').disabled = true;
        });
    </script>

    </div>
</body>

</html>