<?php
include('../../conexao.php');

if (isset($_POST['id'])) {
    $codigoCt = $_POST['id'];

    $consultaResponsavel = "SELECT * FROM responsaveis WHERE ct = $codigoCt";
    $responsavel = $mysqli->query($consultaResponsavel) or die($mysqli->error);

    $consultaPessoasDoCt = "SELECT * FROM pessoas WHERE ct = $codigoCt";
    $pessoasDoCt = $mysqli->query($consultaPessoasDoCt) or die($mysqli->error);
    $msg = 0;

    if ($responsavel->num_rows != 0) {
        echo 'Primeiro exclua o responsável por esse CT para poder excluí-lo.';
    } else if ($pessoasDoCt->num_rows != 0) {
        $consultaExcluirct = "DELETE FROM cts WHERE id = $codigoCt";
        $consultaExcluirPessoas = "DELETE FROM pessoas WHERE ct = $codigoCt";
        $resultadoExcluirCt = $mysqli->query($consultaExcluirct);
        $resultadoExcluirPessoas = $mysqli->query($consultaExcluirPessoas);
        echo 'Ct excluído e pessoas vinculadas excluídas com sucesso.';
    } else {
        $consultaExcluir = "DELETE FROM cts WHERE id = $codigoCt";
        $resultadoExcluir = $mysqli->query($consultaExcluir);
        echo 'Ct excluído com sucesso.';
    }
} else {
    echo 'ID do ct não fornecido.';
}
