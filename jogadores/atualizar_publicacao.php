<?php
include('../conexao.php');
include('../protect.php');
date_default_timezone_set('America/Sao_Paulo');

$erro = [];
$mensagem_erro = '';
$id = $_GET['codigo'];
$consulta = "SELECT * FROM publicacoes WHERE id='$id'";
$resultado = $mysqli->query($consulta) or die($mysqli->error);
$dados = $resultado->fetch_assoc();
$categoria = $dados['categoria'];
$data_atual = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['publi'])) {

    $publi = $mysqli->real_escape_string($_POST['publi']);
    $data_jogo = isset($_POST['data_jogo']) ? $_POST['data_jogo'] : '0000-00-00';
    $partes_data_jogo = explode('-', $data_jogo); // Divide "YYYY-MM-DD" em ["YYYY", "MM", "DD"]
    $ano_jogo = $partes_data_jogo[0]; // O primeiro elemento é o ano
    $ano_atual = date('Y', strtotime($data_atual));

    if (empty($publi)) {
        $erro[] = "Por favor, preencha todos os campos do formulário.";
    }

    if ($data_jogo < $data_atual && $data_jogo != '0000-00-00') {
        $erro[] = "Data do jogo é menor que a data atual";
    }

    if ($ano_jogo > $ano_atual && $data_jogo != '0000-00-00') {
        $erro[] = "Ano de jogo é maior que ano atual";
    }

    if (empty($erro)) {
        $sql_code = "UPDATE publicacoes SET publi = '$publi', data_jogo = '$data_jogo' WHERE id = $id";
        if ($mysqli->query($sql_code) === TRUE) {
            $success = true;
        } else {
            $success = false;
            $error_message = "Erro ao atualizar os dados: " . $mysqli->error;
        }
    } else {
        $mensagem_erro = "<ul>";
        foreach ($erro as $mensagem) {
            $mensagem_erro .= "<li class='letra-maior'>$mensagem</li>";
        }
        $mensagem_erro .= "</ul>";
    }
}
?>

<script>
    <?php if (isset($success) && $success) { ?>
        alert("Publicação atualizada com sucesso!");
        const categoria = <?php echo json_encode($categoria); ?>;
        window.location.href = `dashboard_jogador.php?categoria=${categoria}`;
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
    <link rel="stylesheet" type="text/css" href="../css/atualizar_publicacao.css">
</head>

<body>
    <a href="dashboard_jogador.php" class="botao">Voltar</a>
    <?php
    if (!empty($mensagem_erro)) {
        echo "<div class='erro'>$mensagem_erro</div>";
    }
    ?>
    <form action="" method="POST">

        <div class="data-jogo" id="data_jogo" style="display: none;"><br>
            <label for="">Data do Evento</label><br>
            <input type="date" class="input-text-data-jogo" name="data_jogo" value="<?= isset($_POST['data_jogo']) ? htmlspecialchars($_POST['data_jogo']) : (isset($dados['data_jogo']) ? htmlspecialchars($publicacao['data_jogo']) : '') ?>">
        </div>

        <div>
            <label>Texto da publicação</label><br>
            <textarea id="publi" class="input-text" name="publi" rows="5" cols="35"><?= isset($_POST['publi']) ? htmlspecialchars($_POST['publi']) : (isset($publicacao) ? htmlspecialchars($publicacao['publi']) : '') ?></textarea>
        </div>

        <br>
        <p>
            <button type="submit" class="semBorda">Atualizar</button>
        </p>
    </form>

    <script>
        function ocultarOuMostrarDataJogo() {
            const campoData = document.getElementById('data_jogo');
            const categoria = <?php echo json_encode($categoria); ?>;

            if (categoria == '1' || categoria == '4') {
                campoData.style.display = 'block';
            } else {
                campoData.style.display = 'none';
            }

        }

        ocultarOuMostrarDataJogo();
    </script>
</body>

</html>