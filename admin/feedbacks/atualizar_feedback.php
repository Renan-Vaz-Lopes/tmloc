<?php
include('../../conexao.php');
include('../../protect.php');

$erro = [];
$mensagem_erro = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['feedback'])) {
    $id = $_GET['codigo'];
    $feedback = $mysqli->real_escape_string($_POST['feedback']);

    if (empty($feedback)) {
        $erro[] = "Por favor, preencha todos os campos do formulário.";
    }

    if (empty($erro)) {
        $sql_code = "UPDATE feedbacks SET feedback = '$feedback' WHERE id = $id";
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
        alert("Feedback atualizado com sucesso!");
        window.location.href = '../admin_feedbacks.php';
    <?php } else if (isset($error_message)) { ?>
        alert("Erro ao atualizar os dados:\n<?php echo $error_message; ?>");
    <?php } ?>
</script>

<?php
$id = $_GET['codigo'];
$sql_busca_feedback = "SELECT * FROM feedbacks WHERE id = $id";
$resultado = $mysqli->query($sql_busca_feedback);

if ($resultado && $resultado->num_rows > 0) {
    $feedback = $resultado->fetch_assoc();
} else {
    echo "feedback não encontrado.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualizar feedback</title>
    <link rel="stylesheet" type="text/css" href="../../css/atualizar_feedback.css">
</head>

<body>
    <a href="../admin_feedback.php" class="botao">Voltar</a>
    <?php
    if (!empty($mensagem_erro)) {
        echo "<div class='erro'>$mensagem_erro</div>";
    }
    ?>
    <form action="" method="POST">

        <p>
            <label>Texto do feedback</label><br>
            <textarea id="feedback" class="input-text" name="feedback" rows="5" cols="35"><?= isset($_POST['feedback']) ? htmlspecialchars($_POST['feedback']) : (isset($feedback) ? htmlspecialchars($feedback['feedback']) : '') ?></textarea>
        </p>

        <p>
            <button type="submit" class="semBorda">Atualizar</button>
        </p>
    </form>
</body>

</html>