<?php
include('../../conexao.php');
include('../../protect.php');

$erro = [];
$mensagem_erro = '';

$id = $_GET['codigo'];
$sql_busca_responsavel = "SELECT * FROM responsaveis WHERE id = $id";
$resultado = $mysqli->query($sql_busca_responsavel);

if ($resultado && $resultado->num_rows > 0) {
    $responsavel = $resultado->fetch_assoc();
} else {
    echo "Responsável não encontrado.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $mysqli->real_escape_string($_POST['email']);
    $senha = $mysqli->real_escape_string($_POST['senha']);
    $confirmacao_senha = $mysqli->real_escape_string($_POST['confirmacao_senha']);
    $estado = $mysqli->real_escape_string($_POST['estado']);
    $cidade = $mysqli->real_escape_string($_POST['cidade']);
    $ct = $mysqli->real_escape_string($_POST['ct']);

    if($ct != $responsavel['ct']){
        $sql_busca_ct = "SELECT * FROM responsaveis WHERE ct = '$ct'";
        $resultado_ct = $mysqli->query($sql_busca_ct);
    
        if ($resultado_ct && $resultado_ct->num_rows > 0) {
            $erro[] = "CT já vinculado a um responsável.";
        }
    }
    

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro[] = "Formato de email inválido.";
    }

    if (!empty($senha)) {
        if (strlen($senha) < 8) {
            $erro[] = "A senha deve ter no mínimo 8 caracteres.";
        }

        if (!preg_match('/[A-Z]/', $senha)) {
            $erro[] = "A senha deve ter no mínimo 1 letra maiúscula";
        }

        if (!preg_match('/[a-z]/', $senha)) {
            $erro[] = "A senha deve ter no mínimo 1 letra minúscula";
        }

        if (!preg_match('/[\W_]/', $senha)) {
            $erro[] = "A senha deve ter no mínimo 1 caractere especial";
        }

        if ($senha != $confirmacao_senha) {
            $erro[] = "As senhas não coincidem!";
        }
    }

    if (empty($erro)) {
        if ($senha > 0 && $confirmacao_senha > 0) {
            $senha_hash = password_hash($senha, PASSWORD_ARGON2ID);

            $sql_code = "UPDATE responsaveis SET email = '$email', senha = '$senha_hash', estado = '$estado', cidade = '$cidade', ct = '$ct' WHERE id = $id";
        } else {
            $sql_code = "UPDATE responsaveis SET email = '$email', estado = '$estado', cidade = '$cidade', ct = '$ct' WHERE id = $id";
        }


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
        alert("Responsável atualizado com sucesso!");
        window.location.href = '../admin_responsaveis.php';
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
    <title>Atualizar Responsável</title>
    <link rel="stylesheet" type="text/css" href="../../css/atualizar_responsavel.css">
</head>

<body>
    <a href="../admin_responsaveis.php">Voltar</a>
    <form action="" method="POST">
        <?php
        if (!empty($mensagem_erro)) {
            echo "<div class='erro'>$mensagem_erro</div>";
        }
        ?>
        <p>
            <label>E-mail</label><br>
            <input type="text" class="input-text" name="email" id="email" value="<?= isset($_POST['email']) && !empty($_POST['email']) ? $_POST['email'] : $responsavel['email'] ?>">

        </p>

        <p>
            <label>Senha</label><br>
            <input class="input-text" type="password" name="senha">
        </p>

        <p>
            <label>Confirmação de senha</label><br>
            <input class="input-text" type="password" name="confirmacao_senha">
        </p>

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
                $url = "https://servicodados.ibge.gov.br/api/v1/localidades/estados/{$responsavel['estado']}/municipios";
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
            <label>CT</label>
            <br>
            <select class="select-style" name="ct" id="ct" class="ct-dropdown">
                <option class="select-style option" value="">Selecione o estado e cidade primeiro</option>
            </select>
        </p>
        <p>
            <button type="submit" class="semBorda" onclick="return validaSelecaoDeCt()">Atualizar</button>
        </p>
    </form>

    <script>
        function validaSelecaoDeCt() {
            const ctDropdown = document.getElementById('ct');
            const email = document.getElementById('email');
            if (ctDropdown.value === "" || email.value === "") {
                alert("Todos os campos devem ser preenchidos.");
                return false;
            }
            return true;
        }
    </script>

    <script>
        document.getElementById('estado').addEventListener('change', function() {
            const estadoId = this.value;
            const cidadeDropdown = document.getElementById('cidade');
            const ctDropdown = document.getElementById('ct');
            if (estadoId) {
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
                ctDropdown.innerHTML = '<option value="">Selecione o CT</option>';
            }
        });
    </script>

    <script>
        document.getElementById('cidade').addEventListener('change', function() {
            const estadoSelecionado = document.getElementById('estado').value;
            const cidadeSelecionada = this.value;
            const ctDropdown = document.getElementById('ct');

            if (estadoSelecionado && cidadeSelecionada) {

                const formData = new FormData();
                formData.append('estado', estadoSelecionado);
                formData.append('cidade', cidadeSelecionada);

                fetch('../cts/carregar_cts.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        ctDropdown.innerHTML = '<option value="">Selecione um CT</option>';
                        data.forEach(ct => {
                            ctDropdown.innerHTML += `<option value="${ct.id}">${ct.nome}</option>`;
                        });
                        ctDropdown.disabled = false;
                    })
                    .catch(error => console.error('Erro ao carregar os CTs:', error));
            } else {
                ctDropdown.innerHTML = '<option value="">Selecione o estado e cidade primeiro</option>';
                ctDropdown.disabled = true;
            }
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var estadoSelect = document.getElementById('estado');
            var cidadeSelect = document.getElementById('cidade');
            var ctSelect = document.getElementById('ct');

            var estadoId = "<?= $responsavel['estado']; ?>";
            var cidadeId = "<?= $responsavel['cidade']; ?>";
            var ctId = "<?= $responsavel['ct']; ?>";

            estadoSelect.value = estadoId;
            cidadeSelect.value = cidadeId;

            var event = new Event('change');
            cidadeSelect.dispatchEvent(event);

            setTimeout(function() {
                ctSelect.value = ctId;
            }, 60);

        });
    </script>

</body>

</html>