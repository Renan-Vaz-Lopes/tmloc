<?php
include('../../conexao.php');
include('../../protect.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['descricao'])) {
    $descricao = $mysqli->real_escape_string($_POST['descricao']);

    $sql_busca_descricao = "SELECT * FROM perfis WHERE descricao = '$descricao'";
    $resultado = $mysqli->query($sql_busca_descricao);

    if ($resultado && $resultado->num_rows > 0) {
        $erro = "Perfil já existe.";
    }

    if (empty($descricao)) {
        $erro = "Preencha a descrição.";
    }

    if (preg_match('/[0-9]/', $descricao)) {
        $erro = "A descrição não pode conter números.";
    }

    if (empty($erro)) {
        sleep(3);

        $sql_code = "INSERT INTO perfis (descricao) VALUES ('$descricao')";
        $confirma = $mysqli->query($sql_code) or die($mysqli->error);

        if ($confirma) {
            $confirma = "Perfil cadastrado com sucesso!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Perfil</title>
    <link rel="stylesheet" type="text/css" href="../../css/cadastrar_perfil.css">
</head>

<body>
    <a href="../admin_perfis.php">Voltar</a>
    <form action="" id="formCadastrar" method="POST">

        <?php if (isset($erro) && !empty($erro)) {
            echo "<span style='color:red;'>$erro</span>";
        } ?>

        <p>
            <label>Descrição</label><br>
            <input class="input-text" type="text" name="descricao" value="<?= isset($descricao) ? $descricao : '' ?>">
        </p>

        <p>
            <button type="submit" class="semBorda" id="btnCadastrar">Cadastrar</button>
        </p>
    </form>

    <script>
        document.getElementById('formCadastrar').addEventListener('submit', function() {
            document.getElementById('btnCadastrar').disabled = true;
        });
    </script>

    <script>
        <?php if (isset($confirma)) { ?>
            alert("<?php echo $confirma; ?>");
            window.location.href = "../admin_perfis.php";
        <?php } ?>
    </script>

</body>

</html>