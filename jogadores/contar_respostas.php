<?php
include('../conexao.php');
include('../protect.php');

if (isset($_POST['id'])) {
    $id = $_POST['id'];

    $consulta_qnt_respostas = "SELECT COUNT(id) as qnt_respostas FROM respostas WHERE id_publicacao = '$id'";
    $con_quant_respostas = $mysqli->query($consulta_qnt_respostas);

    if ($con_quant_respostas) {
        $dados_quant_respostas = $con_quant_respostas->fetch_assoc(); // Usa fetch_assoc para facilitar o acesso
        $quant_respostas = $dados_quant_respostas['qnt_respostas'];
    }

    echo $quant_respostas;
}
