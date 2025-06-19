<?php
include('../../conexao.php');

if (isset($_POST['estado'], $_POST['cidade'])) {
    $estado = $mysqli->real_escape_string($_POST['estado']);
    $cidade = $mysqli->real_escape_string($_POST['cidade']);

    $sql_code = "SELECT * FROM cts WHERE estado = '$estado' AND cidade = '$cidade'";
    $result = $mysqli->query($sql_code);

    $cts = array();
    while ($row = $result->fetch_assoc()) {
        $cts[] = $row;
    }
    echo json_encode($cts); 
} else {
    echo json_encode(array('message' => 'Parâmetros inválidos.'));
}
