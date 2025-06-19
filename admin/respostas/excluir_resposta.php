<?php
include('../../conexao.php');

if (isset($_POST['id'])) {
    $codigoResposta = $_POST['id'];

    $consultaExcluirResposta = "DELETE FROM respostas WHERE id = '$codigoResposta'";
    $resultadoExcluirResposta = $mysqli->query($consultaExcluirResposta);

    if($resultadoExcluirResposta) {
        echo 'Resposta excluída com sucesso.';
    }
    
} else {
    echo 'ID do ct não fornecido.';
}
