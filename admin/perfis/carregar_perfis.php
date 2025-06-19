<?php
include('../../conexao.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sql_code = "SELECT id, descricao FROM perfis";
    $result = $mysqli->query($sql_code);

    if ($result && $result->num_rows > 0) {
        $perfis = array();
        while ($row = $result->fetch_assoc()) {
            $perfis[] = $row;
        }
        echo json_encode($perfis);
    } else {
        echo json_encode(array('message' => 'Nenhum CT encontrado.'));
    }
}