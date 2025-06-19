<?php
$usuario = 'root';
$senha = '';
$database = 'tmloc';
$host = 'localhost';

$mysqli = new mysqli($host, $usuario, $senha, $database);

if($mysqli->error) {
    die("Falha ao conectar ao banco de dados: " . $mysqli->error);
}

// Data atual
date_default_timezone_set('America/Sao_Paulo');
$data_atual = date('Y-m-d');

// Consulta para excluir as publicações com data de jogo maior que a data atual

$query = "DELETE FROM publicacoes WHERE DATE_FORMAT(data_jogo, '%Y-%m-%d') < DATE('$data_atual') AND data_jogo != '0000-00-00'";

// Executar a consulta
if(mysqli_query($mysqli, $query)){
    echo "Publicações excluídas com sucesso!";
} else {
    echo "Erro ao excluir publicações: " . mysqli_error($mysqli);
}
?>
