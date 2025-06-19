<?php
include('../../conexao.php');

if (isset($_POST['id'])) {
    $codigoJogador = $_POST['id'];

    $consultaExcluirJogador = "DELETE FROM jogadores WHERE id = $codigoJogador";
    $resultadoExcluirJogador = $mysqli->query($consultaExcluirJogador);

    $consultaExcluirPublicacoes = "DELETE FROM publicacoes WHERE id_jogador = $codigoJogador";
    $resultadoExcluirPublicacoes = $mysqli->query($consultaExcluirPublicacoes);

    if ($resultadoExcluirJogador && $resultadoExcluirPublicacoes) {
        echo 'Jogador e publicações a ele excluídas com sucesso!';
    } else {
        echo 'Erro ao excluir jogador.';
    }
} else {
    echo 'ID do jogador não fornecido.';
}
?>
