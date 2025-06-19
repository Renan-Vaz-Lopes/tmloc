<?php
include('../../conexao.php');
include('../../protect.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['descricao'])) {
    $perfil = $mysqli->real_escape_string($_POST['perfil']);
    $descricao = $mysqli->real_escape_string($_POST['descricao']);

    $sql_busca_descricao = "SELECT * FROM titulos WHERE descricao = '$descricao'";
    $resultado = $mysqli->query($sql_busca_descricao);

    if (empty($perfil)) {
        $erro = "Selecione um perfil.";
    }

    if (empty($descricao)) {
        $erro = "Preencha a descrição.";
    }

    if (preg_match('/[0-9]/', $descricao)) {
        $erro = "A descrição não pode conter números.";
    }
    
    if (empty($erro)) {
        sleep(3);

        $sql_code = "INSERT INTO titulos (perfil,descricao) VALUES ('$perfil','$descricao')";
        $confirma = $mysqli->query($sql_code) or die($mysqli->error);

        if ($confirma) {
            $confirma = "Título cadastrado com sucesso!";
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
    <title>Cadastrar Título</title>
    <link rel="stylesheet" type="text/css" href="../../css/cadastrar_titulo.css">
</head>

<body>
    <a href="../admin_titulos.php">Voltar</a>
    <form action="" id="formCadastrar" method="POST">
        
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
            window.location.href = "../admin_titulos.php";
        <?php } ?>
    </script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const perfilDropdown = document.getElementById('perfil');

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

    });
</script>
</body>



</html>