<?php
include('../protect.php');
include('../conexao.php');

if (isset($_POST['categoria'])) {
    $categoria = $mysqli->real_escape_string($_POST['categoria']);

    $query = "SELECT id, grupo FROM grupos WHERE categoria = '$categoria' ORDER BY grupo";
    $result = $mysqli->query($query);

    if ($result->num_rows > 0) {
        echo '<option value="">Selecione</option>';
        while ($row = $result->fetch_assoc()) {
            $idGrupo = $row['id'];
            $nomeGrupo = $row['grupo'];
            echo "<option value='$idGrupo'>$nomeGrupo</option>";
        }
    } else {
        echo '<option value="">Nenhum grupo encontrado</option>';
    }
} else {
    echo '<option value="">Categoria não informada</option>';
}
?>
