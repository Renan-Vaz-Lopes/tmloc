<?php
include('../../conexao.php');

if (isset($_POST['id'])) {
    $codigoPublicacao = $_POST['id'];

    $consultaNumRespostas = "SELECT COUNT(id) FROM respostas WHERE id_publicacao = $codigoPublicacao";
    $numRespostas = $mysqli->query($consultaNumRespostas);
    if ($numRespostas->num_rows > 0) {
        $consultaExcluirRespostas = "DELETE FROM respostas WHERE id_publicacao = $codigoPublicacao";
        $resultadoExcluirRespostas = $mysqli->query($consultaExcluirRespostas);
    } else $resultadoExcluirRespostas = true;


    $consultaExcluirPubli = "DELETE FROM publicacoes WHERE id = $codigoPublicacao";
    $resultadoExcluirPubli = $mysqli->query($consultaExcluirPubli);

    if ($resultadoExcluirRespostas && $resultadoExcluirPubli) {
        echo 'success';
    } else {
        echo 'error';
    }
} else {
    echo 'ID do ct não fornecido.';
}
