<?php
include('../../conexao.php');

if (isset($_POST['id'])) {
    $codigoFeedback = $_POST['id'];

    $consultaExcluirFeedback = "DELETE FROM feedbacks WHERE id = $codigoFeedback";
    $resultadoExcluirFeedback = $mysqli->query($consultaExcluirFeedback);

    if($resultadoExcluirFeedback) {
        echo 'Feedback excluído com sucesso!';
    } else {
        echo 'error';
    }
    
} else {
    echo 'ID do ct não fornecido.';
}
