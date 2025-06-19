<?php
include('../../conexao.php');
include('../../protect.php');

$erro = [];
$mensagem_erro = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['publi'])) {
    $id = $_GET['codigo'];
    $publi = $mysqli->real_escape_string($_POST['publi']);

    if (empty($publi)) {
        $erro[] = "Por favor, preencha todos os campos do formulário.";
    }

    if (empty($erro)) {
        $sql_code = "UPDATE publicacoes SET publi = '$publi' WHERE id = $id";
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
        alert("Publicação atualizada com sucesso!");
        window.location.href = '../admin_publicacoes.php';
    <?php } else if (isset($error_message)) { ?>
        alert("Erro ao atualizar os dados:\n<?php echo $error_message; ?>");
    <?php } ?>
</script>

<?php
$id = $_GET['codigo'];
$sql_busca_publicacao = "SELECT * FROM publicacoes WHERE id = $id";
$resultado = $mysqli->query($sql_busca_publicacao);

if ($resultado && $resultado->num_rows > 0) {
    $publicacao = $resultado->fetch_assoc();
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
    <title>Atualizar publicacão</title>
    <link rel="stylesheet" type="text/css" href="../../css/atualizar_publicacao.css">
</head>

<body>
    <a href="../admin_publicacoes.php" class="botao">Voltar</a>
    <?php
    if (!empty($mensagem_erro)) {
        echo "<div class='erro'>$mensagem_erro</div>";
    }
    ?>
    <form action="" method="POST">

        <p>
            <label>Texto da publicação</label><br>
            <textarea id="publi" class="input-text" name="publi" rows="5" cols="35"><?= isset($_POST['publi']) ? htmlspecialchars($_POST['publi']) : (isset($publicacao) ? htmlspecialchars($publicacao['publi']) : '') ?></textarea>
        </p>

        <p>
            <button type="submit" class="semBorda">Atualizar</button>
        </p>
    </form>
</body>

</html>