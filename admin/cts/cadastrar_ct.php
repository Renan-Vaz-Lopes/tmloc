<?php
include('../../conexao.php');
include('../../protect.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$erro = [];
$mensagem_erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nome'], $_POST['endereco'], $_POST['telefone'], $_POST['estado'], $_POST['cidade'], $_POST['apresentacao'])) {
    $nome = $mysqli->real_escape_string($_POST['nome']);
    $endereco = $mysqli->real_escape_string($_POST['endereco']);
    $telefone = $mysqli->real_escape_string($_POST['telefone']);
    $estado = $mysqli->real_escape_string($_POST['estado']);
    $cidade = $mysqli->real_escape_string($_POST['cidade']);
    $apresentacao = $mysqli->real_escape_string($_POST['apresentacao']);

    $valores_selecionados = [
        'estado' => isset($estado) ? $estado : '',
        'cidade' => isset($cidade) ? $cidade : '',
    ];

    $sql_busca_ct = "SELECT * FROM cts WHERE nome = '$nome'";
    $resultado = $mysqli->query($sql_busca_ct);

    if ($resultado && $resultado->num_rows > 0) {
        $erro[] = "Ct já existe.";
    }

    if (empty($nome) || empty($endereco) || empty($telefone) || empty($estado) || empty($cidade) || empty($apresentacao)) {
        $erro[] = "Por favor, preencha todos os campos do formulário.";
    }

    if (preg_match('/[0-9]/', $nome)) {
        $erro[] = "O nome não pode conter números.";
    }


    if (!preg_match('/^\([0-9]{2}\) [0-9]{4,5}-[0-9]{4}$/', $telefone)) {
        $erro[] = "Utilize o formato (XX)X XXXX-XXXX no telefone";
    }

    if (empty($erro)) {
        sleep(3);
        $sql_code = "INSERT INTO cts (nome, endereco, telefone, estado, cidade, apresentacao) VALUES ('$nome', '$endereco', '$telefone' , '$estado', '$cidade', '$apresentacao')";
        $confirma = $mysqli->query($sql_code) or die($mysqli->error);

        if ($confirma) {
            $confirma = "CT cadastrado com sucesso!";
        } else {
            $erro[] = "Erro ao cadastrar CT: " . $mysqli->error;
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
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar CT</title>
    <link rel="stylesheet" type="text/css" href="../../css/cadastrar_ct.css">
</head>

<body>
    <a href="../admin_cts.php">Voltar</a>
    <?php
    if (!empty($mensagem_erro)) {
        echo "<div class='erro'>$mensagem_erro</div>";
    }
    ?>

    <form action="" id="formCadastrar" method="POST">

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
            <label>Nome</label><br>
            <input type="text" class="input-text" name="nome" pattern="[A-Za-zÀ-ú\s]+" title="Digite apenas letras." value="<?= isset($nome) ? htmlspecialchars($nome) : '' ?>">
        </p>

        <p>
            <label>Endereço</label><br>
            <input type="text" class="input-text" name="endereco" value="<?= isset($endereco) ? htmlspecialchars($endereco) : '' ?>">
        </p>

        <p>
            <label>Telefone</label><br>
            <input type="text" class="input-text" name="telefone" id="telefone" maxlength="15" oninput="formatarTelefone(this)" value="<?= isset($telefone) ? htmlspecialchars($telefone) : '' ?>">
        </p>

        <p>
            <label>Apresentação do CT</label><br>
            <textarea id="apresentacao" class="input-text" name="apresentacao" rows="2" cols="35"><?= isset($_POST['apresentacao']) ? htmlspecialchars($_POST['apresentacao']) : '' ?></textarea>

        </p>

        <p>
            <button type="submit" class="semBorda" id="btnCadastrar" onclick="return validaSelecoes()">Cadastrar</button>
        </p>


    </form>

    <script>
        document.getElementById('formCadastrar').addEventListener('submit', function() {
            document.getElementById('btnCadastrar').disabled = true;
        });
    </script>

    <script>
        function validaSelecoes() {
            const estadoDropdown = document.getElementById('estado');
            const cidadeDropdown = document.getElementById('cidade');
            const nomeDropdown = document.getElementById('nome');
            const enderecoDropdown = document.getElementById('endereco');
            const telefoneDropdown = document.getElementById('telefone');
            const apresentacaoTextArea = document.getElementById('apresentacao');

            if (estadoDropdown.value == "" || cidadeDropdown.value == "" || nomeDropdown.value == "" || enderecoDropdown.value == "" || telefoneDropdown.value == "" || apresentacaoTextArea.value == "") {
                alert("Todos os campos devem ser preenchidos.");
                return false;
            }

            return true;
        }
    </script>

    <script>
        function formatarTelefone(input) {

            let numero = input.value.replace(/\D/g, '');

            numero = numero.substring(0, 11);

            input.value = numero.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
        }

        function validaForm() {
            const telefone = document.getElementById('telefone').value;

            if (!/^(\(\d{2}\) \d{5}-\d{4})$/.test(telefone)) {
                alert('O número de telefone deve seguir o formato (XX) XXXXX-XXXX.');
                return false;
            }
            return true;
        }
    </script>

    <script>
        document.getElementById('estado').addEventListener('change', function() {
            const estadoId = this.value;
            const cidadeDropdown = document.getElementById('cidade');
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
                        console.log(data);
                        ctDropdown.innerHTML = '<option value="">Selecione um CT</option>';
                        data.forEach(ct => {
                            ctDropdown.innerHTML += `<option value="${ct.id}">${ct.nome}</option>`;
                        });
                    })
                    .catch(error => console.error('Erro ao carregar os CTs:', error));
            } else {
                ctDropdown.innerHTML = '<option value="">Selecione o estado e cidade primeiro</option>';
            }
        });
    </script>


    <script>
        <?php if (isset($confirma)) { ?>
            alert("<?php echo $confirma; ?>");
            window.location.href = "../admin_cts.php";
        <?php } ?>
    </script>
</body>

</html>