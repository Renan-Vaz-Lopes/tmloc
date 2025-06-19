<?php
include('../conexao.php');
include('../protect.php');
date_default_timezone_set('America/Sao_Paulo');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_jogador = $_SESSION['id'];
    $nome_jogador_logado = $_SESSION['nome'];
    $descricao_contato_jogador_logado = $_SESSION['descricao_contato'];

    $feedback = $mysqli->real_escape_string($_POST['feedback']);

    $erro = [];


    if (empty($feedback)) {
        $erro[] = "Por favor, preencha todos os campos do formulário.";
    }

    if (empty($erro)) {
        $sql_code = "INSERT INTO feedbacks (id_jogador, nome_jogador, descricao_contato, feedback) VALUES ('$id_jogador', '$nome_jogador_logado', '$descricao_contato_jogador_logado', '$feedback')";
        $confirma = $mysqli->query($sql_code) or die($mysqli->error);
    }
} else {
    $mensagem_erro = "<ul>";
    foreach ($erro as $mensagem) {
        $mensagem_erro .= "<li>$mensagem</li>";
    }
    $mensagem_erro .= "</ul>";
}
