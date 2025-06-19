<?php
include('../conexao.php');

if (isset($_POST['id'])) {
    $codigoPessoa = $_POST['id'];

    $consultaExcluirAnosTitulos = "DELETE FROM anos_titulos WHERE id_pessoa = $codigoPessoa";
    $resultadoExcluirAnosTitulos = $mysqli->query($consultaExcluirAnosTitulos);

    $consultaExcluir = "DELETE FROM pessoas WHERE id = $codigoPessoa";
    $resultadoExcluir = $mysqli->query($consultaExcluir);

    if ($resultadoExcluirAnosTitulos && $resultadoExcluir) {
        echo 'Responsável excluído com sucesso!';
    } else {
        echo 'Erro ao excluir Responsável.';
    }
} else {
    echo 'ID do perfil não fornecido.';
}
?>