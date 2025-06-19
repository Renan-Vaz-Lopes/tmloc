<?php
include('../../conexao.php');
include('../../protect.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['descricao'])) {
    $id = $_GET['codigo'];
    $descricao = $mysqli->real_escape_string($_POST['descricao']);

    if (empty($descricao)) {
        $erro = "Preencha a descrição.";
    }

    if (preg_match('/[0-9]/', $descricao)) {
        $erro = "A descrição não pode conter números.";
    }

    if (empty($erro)) {
        $sql_code = "UPDATE perfis SET descricao = '$descricao' WHERE id = $id";
        if ($mysqli->query($sql_code) === TRUE) {
            $success = true;
        } else {
            $success = false;
            $error_message = "Erro ao atualizar os dados: " . $mysqli->error;
        }
    }
}
?>

<script>
    <?php if (isset($success) && $success) { ?>
        alert("Perfil atualizado com sucesso!");
        window.location.href = '../admin_perfis.php';
    <?php } elseif (isset($error_message)) { ?>
        alert("Erro ao atualizar os dados:\n<?php echo $error_message; ?>");
    <?php } ?>
</script>

<?php
$id = $_GET['codigo'];
$sql_busca_perfil = "SELECT * FROM perfis WHERE id = $id";
$resultado = $mysqli->query($sql_busca_perfil);

if ($resultado && $resultado->num_rows > 0) {
    $perfil = $resultado->fetch_assoc();
} else {
    echo "Perfil não encontrado.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualizar Perfil</title>
    <link rel="stylesheet" type="text/css" href="../../css/atualizar_perfil.css">
</head>

<body>
    <a href="../admin_perfis.php">Voltar</a>
    <form action="" method="POST">

        <?php if (isset($erro) && !empty($erro)) {
            echo "<span style='color:red;'>$erro</span>";
        } ?>

        <p>
            <label>Descrição</label><br>
            <input class="input-text" type="text" name="descricao" value="<?= isset($_POST['descricao']) ? $_POST['descricao'] : (isset($perfil) ? $perfil['descricao'] : '') ?>"> 

        </p>

        <p>
            <button type="submit" class="semBorda">Atualizar</button>
        </p>
    </form>

</body>

</html>