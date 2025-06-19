<?php
include('../../conexao.php');
include('../../protect.php');

$erro = [];
$mensagem_erro = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nome'], $_POST['endereco'], $_POST['telefone'], $_POST['estado'], $_POST['cidade'], $_POST['apresentacao'])) {
    $id = $_GET['codigo'];
    $nome = $mysqli->real_escape_string($_POST['nome']);
    $endereco = $mysqli->real_escape_string($_POST['endereco']);
    $telefone = $mysqli->real_escape_string($_POST['telefone']);
    $estado = $mysqli->real_escape_string($_POST['estado']);
    $cidade = $mysqli->real_escape_string($_POST['cidade']);
    $apresentacao = $mysqli->real_escape_string($_POST['apresentacao']);

    if (empty($nome) || empty($endereco) || empty($telefone) || empty($estado) || empty($cidade) || empty($apresentacao)) {
        $erro[] = "Por favor, preencha todos os campos do formulário.";
    }

    if (preg_match('/[0-9]/', $nome)) {
        $erro[] = "O nome não pode conter números.";
    }


    if (!preg_match('/^\([0-9]{2}\) [0-9]{4,5}-[0-9]{4}$/', $telefone)) {
        $erro[] = "Utilize o formato (XX) XXXXX-XXXX no telefone";
    }

    if (empty($erro)) {
        $sql_code = "UPDATE cts SET nome = '$nome', endereco = '$endereco', telefone = '$telefone', estado = '$estado', cidade = '$cidade', apresentacao = '$apresentacao' WHERE id = $id";
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
        alert("CT atualizado com sucesso!");
        window.location.href = '../admin_cts.php';
    <?php } elseif (isset($error_message)) { ?>
        alert("Erro ao atualizar os dados:\n<?php echo $error_message; ?>");
    <?php } ?>
</script>

<?php
$id = $_GET['codigo'];
$sql_busca_responsavel = "SELECT * FROM cts WHERE id = $id";
$resultado = $mysqli->query($sql_busca_responsavel);

if ($resultado && $resultado->num_rows > 0) {
    $ct = $resultado->fetch_assoc();
    $id_estado = $ct['estado'];
} else {
    echo "Responsável não encontrado.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualizar CT</title>
    <link rel="stylesheet" type="text/css" href="../../css/atualizar_ct.css">
</head>

<body>
    <a href="../admin_cts.php" class="botao">Voltar</a>
    <?php
    if (!empty($mensagem_erro)) {
        echo "<div class='erro'>$mensagem_erro</div>";
    }
    ?>
    <form action="" method="POST">

        <p>
            <label>Estado</label><br>
            <select class="select-style" name="estado" id="estado" class="estado-dropdown">
                <option class="select-style option" value="">Selecione um estado</option>
                <?php
                $url = 'https://servicodados.ibge.gov.br/api/v1/localidades/estados';
                $response = file_get_contents($url);
                if ($response !== false) {
                    $estados = json_decode($response, true);
                    if (!empty($estados)) {
                        foreach ($estados as $estado) {
                            echo '<option value="' . $estado['id'] . '">' . $estado['nome'] . ' - ' . $estado['sigla'] . '</option>';
                        }
                    } else {
                        echo 'Nenhum estado encontrado.';
                    }
                } else {
                    echo 'Erro ao obter os dados dos estados.';
                }
                ?>
            </select>
        </p>

        <p>
            <label>Cidade</label><br>
            <select class="select-style" name="cidade" id="cidade" class="cidade-dropdown">
                <option class="select-style option" value="">Selecione uma cidade</option>
                <?php
                $url = "https://servicodados.ibge.gov.br/api/v1/localidades/estados/$id_estado/municipios";
                $response = file_get_contents($url);
                if ($response !== false) {
                    $cidades = json_decode($response, true);
                    if (!empty($cidades)) {
                        foreach ($cidades as $cidade) {
                            echo '<option value="' . $cidade['id'] . '">' . $cidade['nome'] . '</option>';
                        }
                    } else {
                        echo 'Nenhuma cidade encontrada.';
                    }
                } else {
                    echo 'Erro ao obter os dados dos cidades.';
                }
                ?>
            </select>
        </p>

        <p>
            <label>Nome</label><br>
            <input class="input-text" type="text" name="nome" value="<?= isset($_POST['nome']) ? $_POST['nome'] : (isset($ct) ? $ct['nome'] : '') ?>">
        </p>

        <p>
            <label>Endereço</label><br>
            <input class="input-text" type="text" name="endereco" value="<?= isset($_POST['endereco']) ? $_POST['endereco'] : (isset($ct) ? $ct['endereco'] : '') ?>">
        </p>

        <p>
            <label>Telefone</label><br>
            <input class="input-text" type="text" name="telefone" oninput="formatarTelefone(this)" value="<?= isset($_POST['telefone']) ? $_POST['telefone'] : (isset($ct) ? $ct['telefone'] : '') ?>">
        </p>

        <p>
            <label>Apresentação do CT</label><br>
            <textarea id="apresentacao" class="input-text" name="apresentacao" rows="5" cols="35"><?= isset($_POST['apresentacao']) ? htmlspecialchars($_POST['apresentacao']) : (isset($ct) ? htmlspecialchars($ct['apresentacao']) : '') ?></textarea>
        </p>

        <p>
            <button type="submit" class="semBorda">Atualizar</button>
        </p>
    </form>

    <script>
        function formatarTelefone(input) {

            let numero = input.value.replace(/\D/g, '');

            numero = numero.substring(0, 11);

            input.value = numero.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
        }
    </script>

    <script>
        document.getElementById('estado').addEventListener('change', function() {
            const estadoId = this.value;
            const cidadeDropdown = document.getElementById('cidade');
            if (estadoId) {
                cidadeDropdown.disabled = true;
                fetch(`https://servicodados.ibge.gov.br/api/v1/localidades/estados/${estadoId}/municipios`)
                    .then(response => response.json())
                    .then(data => {
                        cidadeDropdown.innerHTML = '<option value="">Selecione uma cidade</option>';
                        data.forEach(cidade => {
                            cidadeDropdown.innerHTML += `<option value="${cidade.id}">${cidade.nome}</option>`;
                        });
                        cidadeDropdown.disabled = false;
                    })
                    .catch(error => console.error('Erro ao carregar as cidades:', error));
            } else {
                cidadeDropdown.innerHTML = '<option value="">Selecione um estado primeiro</option>';
                cidadeDropdown.disabled = true;
            }
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var estadoSelect = document.getElementById('estado');
            var cidadeSelect = document.getElementById('cidade');

            var estadoId = "<?= $ct['estado']; ?>";
            var cidadeId = "<?= $ct['cidade']; ?>";

            estadoSelect.value = estadoId;
            cidadeSelect.value = cidadeId;

        });
    </script>
</body>

</html>