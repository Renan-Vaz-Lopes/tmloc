<?php
include('../../conexao.php');
include('../../protect.php');

$id = $_GET['codigo'];
$sql_busca_titulo = "SELECT * FROM titulos WHERE id = $id";
$resultado = $mysqli->query($sql_busca_titulo);

if ($resultado && $resultado->num_rows > 0) {
    $titulo = $resultado->fetch_assoc();
} else {
    echo "Título não encontrado.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['descricao'])) {
    $perfil = $mysqli->real_escape_string($_POST['perfil']);
    $descricao = $mysqli->real_escape_string($_POST['descricao']);

    if (empty($descricao)) {
        $erro = "Preencha a descrição.";
    }

    if (preg_match('/[0-9]/', $descricao)) {
        $erro = "A descrição não pode conter números.";
    }

    if (empty($erro)) {
        $sql_code = "UPDATE titulos SET perfil = '$perfil', descricao = '$descricao' WHERE id = $id";

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
        alert("Título atualizado com sucesso!");
        window.location.href = '../admin_titulos.php';
    <?php } elseif (isset($error_message)) { ?>
        alert("Erro ao atualizar os dados:\n<?php echo $error_message; ?>");
    <?php } ?>
</script>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualizar Título</title>
    <link rel="stylesheet" type="text/css" href="../../css/atualizar_titulo.css">
</head>

<body>
    <a href="../admin_titulos.php">Voltar</a>
    <form action="" method="POST">

        <?php if (isset($erro) && !empty($erro)) {
            echo "<span style='color:red;'>$erro</span>";
        } ?>

        <p>
            <label>Perfil</label><br>
            <select class="select-style" name="perfil" id="perfil" class="perfil-dropdown">
            </select>
        </p>

        <p>
            <label>Descrição</label><br>
            <input class="input-text" type="text" name="descricao" value="<?= isset($_POST["descricao"]) ? $_POST["descricao"] : (isset($titulo["descricao"]) ? $titulo["descricao"] : '')?>">
        </p>

        <p>
            <button class="semBorda" type="submit">Atualizar</button>
        </p>
    </form>

</body>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const perfilDropdown = document.getElementById('perfil');

        var perfilId = "<?= $titulo['perfil']; ?>";

        fetch('../perfis/carregar_perfis.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                perfilDropdown.innerHTML = '<option value="">Selecione um Perfil</option>';
                data.forEach(perfil => {
                    perfilDropdown.innerHTML += `<option value="${perfil.id}">${perfil.descricao}</option>`;
                });
            })
            .catch(error => console.error('Erro ao carregar os CTs:', error));

        setTimeout(function() {
            perfilDropdown.value = perfilId;
        }, 60);
    });
</script>

</html>