<?php
include('../../conexao.php');
include('../../protect.php');

$erro = [];
$mensagem_erro = '';
$id = $_GET['codigo'];
$consulta = "SELECT DISTINCT id_publicacao FROM respostas WHERE id=$id";
$con = $mysqli->query($consulta) or die($mysqli->error);
$id_publicacao = $con->fetch_column();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['texto_resposta'])) {
    $texto_resposta = $mysqli->real_escape_string($_POST['texto_resposta']);

    if (empty($texto_resposta)) {
        $erro[] = "Por favor, preencha todos os campos do formulário.";
    }

    if (empty($erro)) {
        $sql_code = "UPDATE respostas SET texto_resposta = '$texto_resposta' WHERE id = $id";
        if ($mysqli->query($sql_code) === TRUE) {
            $success = true;
        } else {
            $success = false;
            $error_message = "Erro ao atualizar os dados: " . $mysqli->error;
        }
    } else {
        $mensagem_erro = "<ul>";
        foreach ($erro as $mensagem) {
            $mensagem_erro .= "<li>$mensagem</li>";
        }
        $mensagem_erro .= "</ul>";
    }
}
?>

<script>
    <?php if (isset($success) && $success) { ?>
        alert("Resposta atualizada com sucesso!");
        idPublicacao = <?php echo json_encode($id_publicacao); ?>;
        window.location.href = `../admin_respostas.php?codigo=${idPublicacao}`;
    <?php } else if (isset($error_message)) { ?>
        alert("Erro ao atualizar os dados:\n<?php echo $error_message; ?>");
    <?php } ?>
</script>

<?php
$sql_busca_resposta = "SELECT * FROM respostas WHERE id = $id";
$resultado = $mysqli->query($sql_busca_resposta);

if ($resultado && $resultado->num_rows > 0) {
    $resposta = $resultado->fetch_assoc();
} else {
    echo "Publicação não encontrada.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualizar resposta</title>
    <link rel="stylesheet" type="text/css" href="../../css/atualizar_resposta.css">
</head>

<body>
    <a href="../admin_respostas.php?codigo=<?= $id_publicacao ?>" class="botao">Voltar</a>
    <?php
    if (!empty($mensagem_erro)) {
        echo "<div class='erro'>$mensagem_erro</div>";
    }
    ?>
    <form action="" method="POST">

        <p>
            <label>Texto da resposta</label><br>
            <textarea id="texto_resposta" class="input-text" name="texto_resposta" rows="5" cols="35"><?= isset($_POST['texto_resposta']) ? htmlspecialchars($_POST['texto_resposta']) : (isset($resposta) ? htmlspecialchars($resposta['texto_resposta']) : '') ?></textarea>
        </p>

        <p>
            <button type="submit" class="semBorda">Atualizar</button>
        </p>
    </form>
</body>

</html>