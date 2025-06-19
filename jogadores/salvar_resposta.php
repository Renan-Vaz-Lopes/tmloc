<?php
include('../protect.php');
include('../conexao.php');
date_default_timezone_set('America/Sao_Paulo');

$id_publicacao = $_POST['id_publicacao'];
$id_jogador = $_SESSION['id'];
$texto_resposta = $mysqli->real_escape_string($_POST['texto_resposta']);
$data_resposta = date("d/m/Y");
$hora_resposta = date("H:i");

if (!empty($id_publicacao) && !empty($texto_resposta)) {
    $sql = "INSERT INTO respostas (id_publicacao, id_jogador, texto_resposta, data_resposta, hora_resposta) VALUES ('$id_publicacao', '$id_jogador', '$texto_resposta', '$data_resposta', '$hora_resposta')";
    if ($mysqli->query($sql)) {
        echo json_encode(['success' => true]);
        exit(); 
    } else {
        echo json_encode(['success' => false]);
        exit();
    }
} else {
    echo json_encode(['success' => false]);
    exit();
}
?>