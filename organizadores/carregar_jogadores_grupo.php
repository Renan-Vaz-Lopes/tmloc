<?php
include('../protect.php');
include('../conexao.php');

if (!isset($_POST['grupo'])) {
    echo '<option value="">Grupo inválido</option>';
    exit;
}

$idGrupo = $_POST['grupo'];

$queryJogadores = "
    SELECT DISTINCT j.id, j.nome
    FROM jogadores j
    JOIN jogos g ON j.id = g.id_jogador1 OR j.id = g.id_jogador2
    WHERE g.id_grupo = '$idGrupo'
";

$result = $mysqli->query($queryJogadores);

if ($result->num_rows > 0) {
    echo '<option value="">Selecione</option>';
    while ($row = $result->fetch_assoc()) {
        echo "<option value='{$row['id']}'>{$row['nome']}</option>";
    }
} else {
    echo '<option value="">Nenhum jogador encontrado</option>';
}
?>
