<?php
include('../../conexao.php');

if (isset($_POST['id'])) {
    $codigoTitulo = $_POST['id'];
    $tevePessoa = 0;

    $consulta = "SELECT * FROM pessoas";
    $pessoas = $mysqli->query($consulta) or die($mysqli->error);

    while ($pessoa = $pessoas->fetch_assoc()) {
        $titulo = $pessoa['titulo'];

        if (strpos($titulo, ',') !== false) {
            $titulosArray = explode(',', $titulo);
            foreach ($titulosArray as $tituloItem) {
                if (trim($tituloItem) == $codigoTitulo) {
                    $tevePessoa = 1;
                    echo 'Primeiro exclua as pessoas com este título para poder excluí-lo.';
                    break;
                }
        }
    } else {
        if($titulo == $codigoTitulo) {
            $tevePessoa = 1;
            echo 'Primeiro exclua as pessoas com este título para poder excluí-lo.';
        }
    }
}

    if (!$tevePessoa) {
        $consultaExcluir = "DELETE FROM titulos WHERE id = $codigoTitulo";
        $resultadoExcluir = $mysqli->query($consultaExcluir);
        echo 'Título excluído com sucesso.';
    }
} else {
    echo 'ID do perfil não fornecido.';
}
