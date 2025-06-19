<?php
include('../../conexao.php');

if (isset($_POST['id'])) {
    $codigoPerfil = $_POST['id'];

    $consulta = "SELECT * FROM pessoas WHERE perfil = $codigoPerfil";
    $pessoas = $mysqli->query($consulta) or die($mysqli->error);

    if ($pessoas->num_rows == 0) {
        $consulta = "SELECT * FROM titulos WHERE perfil = $codigoPerfil";
        $titulos = $mysqli->query($consulta) or die($mysqli->error);

        if ($titulos->num_rows == 0) {
            $consultaExcluir = "DELETE FROM perfis WHERE id = $codigoPerfil";
            $resultadoExcluir = $mysqli->query($consultaExcluir);

            echo 'Perfil excluído com sucesso.';
        } else {
            echo 'Primeiro exclua os títulos com este perfil para poder excluí-lo.';
        }
    } else {
        echo 'Primeiro exclua as pessoas com este perfil para poder excluí-lo.';
    }
} else {
    echo 'ID do perfil não fornecido.';
}
