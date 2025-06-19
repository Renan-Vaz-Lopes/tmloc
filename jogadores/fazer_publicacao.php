<?php
include('../conexao.php');
include('../protect.php');
date_default_timezone_set('America/Sao_Paulo');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_jogador = $_SESSION['id'];
    $estado = $_SESSION['estado'];
    $cidade = $_SESSION['cidade'];
    $email = $_SESSION['email'];
    $nome_jogador_logado = $_SESSION['nome'];
    $descricao_contato_jogador_logado = $_SESSION['descricao_contato'];
    $id_nivel_jogador_logado = $_SESSION['nivel'];
    $data_publi = date("d/m/Y");
    $hora_publi = date("H:i");
    $categoria = $_POST['categoria'];
    $data_jogo = isset($_POST['data_jogo']) ? $_POST['data_jogo'] : '0000-00-00';

    $publi = $mysqli->real_escape_string($_POST['publi']);

    $erro = [];


    if (empty($publi)) {
        $erro[] = "Por favor, preencha todos os campos do formulário.";
    }

    if (empty($erro)) {
        $sql_code = "INSERT INTO publicacoes (id_jogador, nome, nivel, descricao_contato, estado, cidade, publi, data_publi, hora_publi, data_jogo, categoria) VALUES ('$id_jogador', '$nome_jogador_logado', '$id_nivel_jogador_logado', '$descricao_contato_jogador_logado', '$estado', '$cidade', '$publi', '$data_publi', '$hora_publi', '$data_jogo', '$categoria')";
        $confirma = $mysqli->query($sql_code) or die($mysqli->error);
    }
} else {
    $mensagem_erro = "<ul>";
    foreach ($erro as $mensagem) {
        $mensagem_erro .= "<li>$mensagem</li>";
    }
    $mensagem_erro .= "</ul>";
}
