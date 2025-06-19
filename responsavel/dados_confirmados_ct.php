<?php
include('../conexao.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../config.php';
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';


if (isset($_GET['token'])) {
    $token = $mysqli->real_escape_string($_GET['token']);

    $sql_busca_responsaveis = "SELECT * FROM responsaveis WHERE token = '$token'";
    $resultado_responsaveis = $mysqli->query($sql_busca_responsaveis);

    $sql_busca_cts = "SELECT * FROM cts_temp WHERE token = '$token'";
    $resultado_cts = $mysqli->query($sql_busca_cts);


    if ($resultado_cts && $resultado_cts->num_rows > 0) {
        $dados = $resultado_cts->fetch_assoc();
        $nome_ct = $dados['nome'];
        $sql_insert_ct = "INSERT INTO cts (nome, endereco, telefone, estado, cidade, apresentacao)
                          VALUES ('{$dados['nome']}', '{$dados['endereco']}', '{$dados['telefone']}' , '{$dados['estado']}', '{$dados['cidade']}', '{$dados['apresentacao']}')";
        if ($mysqli->query($sql_insert_ct)) {
            $mysqli->query("DELETE FROM cts_temp WHERE token = '$token'");
            echo "Cadastro confirmado com sucesso!";
        }
    } else {
        echo "Token inválido ou expirado.";
    }

    if ($resultado_responsaveis && $resultado_responsaveis->num_rows > 0) {
        $dados = $resultado_responsaveis->fetch_assoc();

        $sql_busca_id_ct = "SELECT id FROM cts WHERE nome = '$nome_ct'";
        $resultado_id_ct = $mysqli->query($sql_busca_id_ct);
        $id_ct = $resultado_id_ct->fetch_assoc();
        
        $email = $dados['email'];
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
            $CONFIG = getConfig();
            
            $mail->Host = $CONFIG['email']['host'];
            $mail->SMTPAuth = true;
            $mail->Port = $CONFIG['email']['port'];
            $mail->SMTPSecure = $CONFIG['email']['SMTPSecure'];
            'ssl';
            $mail->Username = $CONFIG['email']['username'];
            $mail->Password = $CONFIG['email']['password'];

            $mail->CharSet = 'UTF-8';
            $mail->setFrom($CONFIG['email']['username'], 'tmloc');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Seu usuário e CT foram adicionados ao nosso sistema';
            $mail->Body = "
                    <h2>Bem-vindo!</h2>";

            $mail->send();
            $msg = 'Dados enviados para análise com sucesso, você receberá a confirmação pelo seu email';
        } catch (Exception $e) {
            $msg = "Erro ao enviar e-mail: {$mail->ErrorInfo}";
        }

        $sql_insert_responsavel = "INSERT INTO responsaveis (email, senha, estado, cidade, ct)
        VALUES ('{$dados['email']}', '{$dados['senha']}', '{$dados['estado']}' , '{$dados['cidade']}', '{$id_ct['id']}')";
        if ($mysqli->query($sql_insert_responsavel)) {
            $mysqli->query("DELETE FROM responsaveis WHERE token = '$token'");
        }
    } else {
        echo "Token inválido ou expirado.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dados do ct confirmados</title>
</head>

<body>

</body>

</html>