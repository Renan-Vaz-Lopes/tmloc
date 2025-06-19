<?php
include('../conexao.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ctId'])) {
    $ct = $mysqli->real_escape_string($_POST['ctId']);

    $consulta = "SELECT * FROM cts WHERE id = $ct";
    $con = $mysqli->query($consulta) or die($mysqli->error);
    $ct = $con->fetch_all(MYSQLI_ASSOC);
    $apresentacao = $ct[0]['apresentacao'];
    echo json_encode($apresentacao);
}
