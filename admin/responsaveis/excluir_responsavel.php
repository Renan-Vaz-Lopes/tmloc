<?php
include('../../conexao.php');

if (isset($_POST['id'])) {
    $codigoResponsavel = $_POST['id'];
    $consultaExcluir = "DELETE FROM responsaveis WHERE id = $codigoResponsavel";
    $resultadoExcluir = $mysqli->query($consultaExcluir);

    if ($resultadoExcluir) {
        echo 'Responsável excluído com sucesso!';
    } else {
        echo 'Erro ao excluir responsável.';
    }
} else {
    echo 'ID do responsável não fornecido.';
}
?>
