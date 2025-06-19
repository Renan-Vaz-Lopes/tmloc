<?php
include('../../conexao.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['perfil'])) {
    $perfilSelecionado = $_POST['perfil'];
    $sql_code = "SELECT id, descricao FROM titulos WHERE perfil = $perfilSelecionado";
    $result = $mysqli->query($sql_code);

    if ($result && $result->num_rows > 0) {
        $titulos = array();
        while ($row = $result->fetch_assoc()) {
            $titulos[] = $row;
        }
        echo json_encode($titulos);
    } else {
        echo json_encode(array('message' => 'Nenhum CT encontrado.'));
    }
}
?>
