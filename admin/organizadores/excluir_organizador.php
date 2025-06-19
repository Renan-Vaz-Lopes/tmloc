<?php
include('../../conexao.php');

if (isset($_POST['id'])) {
    $codigoOrganizador = $_POST['id'];
    $consultaExcluir = "DELETE FROM organizadores WHERE id = $codigoOrganizador";
    $resultadoExcluir = $mysqli->query($consultaExcluir);

    if ($resultadoExcluir) {
        echo 'Organizador excluído com sucesso!';
    } else {
        echo 'Erro ao excluir organizador.';
    }
} else {
    echo 'ID do organizador não fornecido.';
}
?>
