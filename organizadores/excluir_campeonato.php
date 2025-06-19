<?php
include('../conexao.php');

if (isset($_POST['id'])) {
    $codigoCampeonato = $_POST['id'];
    $consultaExcluir = "DELETE FROM campeonatos WHERE id = $codigoCampeonato";
    $resultadoExcluir = $mysqli->query($consultaExcluir);

    $consultaExcluirGrupos = "DELETE FROM grupos WHERE id_campeonato = $codigoCampeonato";
    $consultaExcluirJogos = "DELETE FROM jogos WHERE id_campeonato = $codigoCampeonato";
    $consultaExcluirJogadores = "DELETE FROM jogadores_campeonatos WHERE id_campeonato = $codigoCampeonato";
    $consultaExcluirMesas = "DELETE FROM mesas WHERE id_campeonato = $codigoCampeonato";

    $resultadoExcluirGrupos = $mysqli->query($consultaExcluirGrupos);
    $resultadoExcluirJogos = $mysqli->query($consultaExcluirJogos);
    $resultadoExcluirJogadores = $mysqli->query($consultaExcluirJogadores);
    $resultadoExcluirMesas = $mysqli->query($consultaExcluirMesas);

    // Exclui todos os arquivos dentro da pasta uploads/chaves/
    $pastaChaves = '../uploads/chaves/';
    if (is_dir($pastaChaves)) {
        $arquivos = glob($pastaChaves . '*');
        foreach ($arquivos as $arquivo) {
            if (is_file($arquivo)) {
                unlink($arquivo);
            }
        }
    }


    if ($resultadoExcluir) {
        echo 'Campeonato excluído com sucesso!';
    } else {
        echo 'Erro ao excluir campeonato.';
    }
} else {
    echo 'ID do campeonato não fornecido.';
}
