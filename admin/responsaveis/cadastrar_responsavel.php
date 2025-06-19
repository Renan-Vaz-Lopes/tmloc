<?php
include('../../conexao.php');
include('../../protect.php');

$erro = [];
$mensagem_erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'], $_POST['senha'], $_POST['confirmacao_senha'], $_POST['estado'], $_POST['cidade'], $_POST['ct'])) {
    $email = $mysqli->real_escape_string($_POST['email']);
    $senha = $mysqli->real_escape_string($_POST['senha']);
    $confirmacao_senha = $mysqli->real_escape_string($_POST['confirmacao_senha']);
    $estado = $mysqli->real_escape_string($_POST['estado']);
    $cidade = $mysqli->real_escape_string($_POST['cidade']);
    $ct = $mysqli->real_escape_string($_POST['ct']);

    $sql_busca_responsavel = "SELECT * FROM responsaveis WHERE email = '$email'";
    $resultado_responsavel = $mysqli->query($sql_busca_responsavel);

    $sql_busca_ct = "SELECT * FROM responsaveis WHERE ct = '$ct'";
    $resultado_ct = $mysqli->query($sql_busca_ct);

    $valores_selecionados = [
        'estado' => isset($estado) ? $estado : '',
        'cidade' => isset($cidade) ? $cidade : '',
    ];

    $ct_selecionado = (!empty($ct)) ? $ct : '';

    if ($resultado_responsavel && $resultado_responsavel->num_rows > 0) {
        $erro[] = "Usuário já existe.";
    }

    if ($resultado_ct && $resultado_ct->num_rows > 0) {
        $erro[] = "CT já vinculado a um responsável.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro[] = "Formato de email inválido.";
    }

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

    if (empty($erro)) {
        $senha_hash = password_hash($senha, PASSWORD_ARGON2ID);
        sleep(3);

        $sql_code = "INSERT INTO responsaveis (email, senha, estado, cidade, ct) VALUES ('$email', '$senha_hash', '$estado', '$cidade', '$ct')";
        $confirma = $mysqli->query($sql_code) or die($mysqli->error);

        if ($confirma) {
            $confirma = "Responsável por CT cadastrado com sucesso!";
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

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Responsável</title>
    <link rel="stylesheet" type="text/css" href="../../css/cadastrar_responsavel.css">
</head>

<body>
<a href="../admin_responsaveis.php">Voltar</a>
    <div class="form-container">

        <div>
            <?php if (!empty($mensagem_erro)) { ?>
                <div class="erro">
                    <?php echo $mensagem_erro; ?>
                </div>
            <?php } ?>
        </div>

        <div>
            <form action="" id="formCadastrar" method="POST">
                <p>
                    <label>E-mail</label><br>
                    <input class="input-text" type="text" name="email" value="<?= isset($email) ? $email : '' ?>">
                </p>

                <p>
                    <label>Senha</label><br>
                    <input class="input-text" type="password" name="senha" value="<?= isset($senha) ? $senha : '' ?>">
                </p>

                <p>
                    <label>Confirmação de senha</label><br>
                    <input class="input-text" type="password" name="confirmacao_senha" value="<?= isset($confirmacao_senha) ? $confirmacao_senha : '' ?>">
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
                                    $selecionado = ($estado['id'] == $valores_selecionados['estado']) ? 'selected' : '';
                                    echo '<option value="' . $estado['id'] . '" ' . $selecionado . '>' . $estado['nome'] . ' - ' . $estado['sigla'] . '</option>';
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
                        <option class="select-style option" value="">Selecione um estado primeiro</option>
                        <?php
                        if (!empty($valores_selecionados['estado'])) {
                            $url = "https://servicodados.ibge.gov.br/api/v1/localidades/estados/{$valores_selecionados['estado']}/municipios";
                            $response = file_get_contents($url);
                            if ($response !== false) {
                                $cidades = json_decode($response, true);
                                if (!empty($cidades)) {
                                    foreach ($cidades as $cidade) {
                                        $selecionado = ($cidade['id'] == $valores_selecionados['cidade']) ? 'selected' : '';
                                        echo '<option value="' . $cidade['id'] . '" ' . $selecionado . '>' . $cidade['nome'] . '</option>';
                                    }
                                } else {
                                    echo 'Nenhuma cidade encontrada.';
                                }
                            } else {
                                echo 'Erro ao obter os dados dos cidades.';
                            }
                        }
                        ?>

                    </select>
                </p>

                <p>
                    <label>CT</label><br>
                    <select class="select-style" name="ct" id="ct" class="ct-dropdown">
                        <option class="select-style option" value="">Selecione o estado e cidade primero</option>
                    </select>
                </p>

                <p class="meio">
                    <button type="submit" class="semBorda" id="btnCadastrar" onclick="return validaSelecaoDeCt()">Cadastrar</button>
                </p>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('formCadastrar').addEventListener('submit', function() {
            document.getElementById('btnCadastrar').disabled = true;
        });
    </script>

    <script>
        function validaSelecaoDeCt() {
            const ctDropdown = document.getElementById('ct');
            if (ctDropdown.value === "") {
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
                    })
                    .catch(error => console.error('Erro ao carregar as cidades:', error));
            } else {
                cidadeDropdown.innerHTML = '<option value="">Selecione um estado primeiro</option>';
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
                        <?php
                        if (!empty($ct_selecionado)) { ?>
                            ctDropdown.value = <?= $ct_selecionado; ?>;
                        <?php } ?>
                    })
                    .catch(error => {
                        ctDropdown.innerHTML = '<option value="">Não há CT\'s disponíveis</option>';
                    });
            } else {
                ctDropdown.innerHTML = '<option value="">Selecione o estado e cidade primeiro</option>';
            }
        });
    </script>

    <script>
        <?php if (isset($confirma)) { ?>
            alert("<?php echo $confirma; ?>");
            window.location.href = "../admin_responsaveis.php";
        <?php } ?>
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var cidadeSelect = document.getElementById('cidade');
            var ctSelect = document.getElementById('ct');
            var event = new Event('change');
            cidadeSelect.dispatchEvent(event);
            ctSelect.value = <?= $ct_selecionado; ?>;
        });
    </script>
    </div>
</body>

</html>