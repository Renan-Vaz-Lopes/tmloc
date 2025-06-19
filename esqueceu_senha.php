<?php
include('conexao.php');
date_default_timezone_set('America/Sao_Paulo');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'config.php';
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if (isset($_POST['email'])) {
    $email = $mysqli->real_escape_string($_POST['email']);

    // Verifica se o e-mail existe no banco de dados
    $sql_responsaveis = "SELECT * FROM responsaveis WHERE email = '$email'";
    $result_responsaveis = $mysqli->query($sql_responsaveis);

    $sql_jogadores = "SELECT * FROM jogadores WHERE email = '$email'";
    $result_jogadores = $mysqli->query($sql_jogadores);

    if ($result_responsaveis->num_rows > 0 || $result_jogadores->num_rows > 0) {
        // Verifica se já existe um token não expirado
        $sql_verifica_token = "SELECT * FROM recuperacao_senha WHERE email = '$email' AND expira_em > NOW()";
        $result_token = $mysqli->query($sql_verifica_token);

        if ($result_token->num_rows > 0) {
            $mensagem_erro = "Você já solicitou a recuperação de senha. Verifique seu e-mail ou aguarde o token expirar.";
        } else {
            // Cria um novo token
            $token = bin2hex(random_bytes(16));
            $expira_em = date("Y-m-d H:i:s", strtotime("+5 minute"));

            // Insere o token no banco de dados
            $sql_token = "INSERT INTO recuperacao_senha (email, token, expira_em) VALUES ('$email', '$token', '$expira_em')";
            $confirma = $mysqli->query($sql_token);

            if ($confirma) {
                // Monta o link de redefinição de senha
                $link = "http://localhost/tmloc/redefinir_senha.php?token=$token";

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
                    $mail->Username = $CONFIG['email']['username'];
                    $mail->Password = $CONFIG['email']['password'];

                    // Configurações de charset
                    $mail->CharSet = 'UTF-8';

                    $mail->setFrom($CONFIG['email']['username'], 'tmloc');
                    $mail->addAddress($email, '');

                    $mail->isHTML(true);
                    $mail->Subject = 'Redefinição de Senha';
                    $mail->Body = "
                    <p>Clique no link abaixo para redefinir sua senha:</p>
                    <a href='$link'>Redefinir senha</a>";

                    $mail->send();
                    $mensagem_sucesso = 'Um e-mail foi enviado com instruções para redefinir sua senha. Você tem 5 minutos antes de expirar.';
                } catch (Exception $e) {
                    $mensagem_erro = "Erro ao enviar e-mail: {$mail->ErrorInfo}";
                }
            }
        }
    } else {
        $mensagem_erro = "E-mail não encontrado.";
    }
}
?>


<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueceu a Senha</title>
    <link rel="stylesheet" href="css/esqueceu_senha.css">
</head>

<body>
    <a class="botao" href="login.php">Voltar</a>

    <div class="div_form_esqueceu_senha">
        <form class="form_esqueceu_senha" action="esqueceu_senha.php" method="POST">

            <?php if (isset($mensagem_sucesso)) { ?>
                <p style="color: green; text-align: center;"><?= $mensagem_sucesso; ?></p>
            <?php } ?>

            <?php if (isset($mensagem_erro)) { ?>
                <p style="color: red; text-align: center;"><?= $mensagem_erro; ?></p>
            <?php } ?>

            <h2>Esqueceu sua senha?</h2>
            <p>Digite seu e-mail para receber um link de redefinição de senha:</p>
            <input type="email" name="email" class="input-text" required placeholder="Digite seu e-mail">
            <br><br>
            <div class="meio">
                <button type="submit" class="botao">Enviar</button>
            </div>
        </form>
    </div>

</body>

</html>