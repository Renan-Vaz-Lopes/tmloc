<?php
include('../protect.php');
include('../conexao.php');

$query = "SELECT DISTINCT categoria FROM grupos";
$result = $mysqli->query($query);

if ($result->num_rows > 0) {
    echo '<option value="">Selecione</option>';
    while ($row = $result->fetch_assoc()) {
        $categoria = $row['categoria'];
        echo "<option value='$categoria'>$categoria</option>";
    }
} else {
    echo '<option value="">Nenhuma categoria encontrada</option>';
}
?>
