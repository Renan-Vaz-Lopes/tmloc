<?php
include('../../conexao.php');
include('../../protect.php');

$erro = [];
$mensagem_erro = '';

$id = $_GET['codigo'];
$sql_busca_jogadores = "SELECT * FROM jogadores WHERE id = $id";
$resultado = $mysqli->query($sql_busca_jogadores);

if ($resultado && $resultado->num_rows > 0) {
    $jogador = $resultado->fetch_assoc();
} else {
    echo "Jogador não encontrado.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $mysqli->real_escape_string($_POST['nome']);
    $email = $mysqli->real_escape_string($_POST['email']);
    $senha = $mysqli->real_escape_string($_POST['senha']);
    $confirmacao_senha = $mysqli->real_escape_string($_POST['confirmacao_senha']);
    $estado = $mysqli->real_escape_string($_POST['estado']);
    $cidade = $mysqli->real_escape_string($_POST['cidade']);
    $descricao_contato = $mysqli->real_escape_string($_POST['descricao_contato']);
    $nivel = $mysqli->real_escape_string($_POST['nivel']);

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
        if (strlen($senha) > 0 && strlen($confirmacao_senha) > 0) {
            $senha_hash = password_hash($senha, PASSWORD_ARGON2ID);

            $atualizaJogador = "UPDATE jogadores SET nome = '$nome', email = '$email', senha = '$senha_hash', estado = '$estado', cidade = '$cidade', descricao_contato = '$descricao_contato', nivel = '$nivel' WHERE id = $id";
            $atualizaPublicacoes = "UPDATE publicacoes SET nome = '$nome', nivel = '$nivel', descricao_contato = '$descricao_contato' WHERE id_jogador = $id";

        } else {
            $atualizaJogador = "UPDATE jogadores SET nome = '$nome', email = '$email', estado = '$estado', cidade = '$cidade', descricao_contato = '$descricao_contato', nivel = '$nivel' WHERE id = $id";
            $atualizaPublicacoes = "UPDATE publicacoes SET nome = '$nome', nivel = '$nivel', descricao_contato = '$descricao_contato' WHERE id_jogador = $id";
        }


        if ($mysqli->query($atualizaJogador) === TRUE && $mysqli->query($atualizaPublicacoes) === TRUE) {
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
        alert("Jogador atualizado com sucesso!");
        window.location.href = '../admin_jogadores.php';
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
    <title>Atualizar Jogador</title>
    <link rel="stylesheet" type="text/css" href="../../css/atualizar_jogador.css">
</head>

<body>
    <a href="../admin_jogadores.php">Voltar</a>
    <form action="" method="POST">
        <?php
        if (!empty($mensagem_erro)) {
            echo "<div class='erro'>$mensagem_erro</div>";
        }
        ?>
        <p>
            <label>Nome</label><br>
            <input class="input-text" type="text" name="nome" id="nome" value="<?= isset($_POST['nome']) && !empty($_POST['nome']) ? $_POST['nome'] : $jogador['nome'] ?>">
        </p>

        <p>
            <label>E-mail</label><br>
            <input class="input-text" type="text" name="email" id="email" value="<?= isset($_POST['email']) && !empty($_POST['email']) ? $_POST['email'] : $jogador['email'] ?>">
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
                $url = "https://servicodados.ibge.gov.br/api/v1/localidades/estados/{$jogador['estado']}/municipios";
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
            <label>Qual você considera seu nível?</label><br>
            <select name="nivel" id="nivel" class="select-style">
                <option value="">Selecione um nível</option>
                <option value="1" <?= isset($jogador['nivel']) && !empty($jogador['nivel']) && $jogador['nivel'] == "1" ? 'selected' : '' ?>>Iniciante</option>
                <option value="2" <?= isset($jogador['nivel']) && !empty($jogador['nivel']) && $jogador['nivel'] == "2" ? 'selected' : '' ?>>Intermediário</option>
                <option value="3" <?= isset($jogador['nivel']) && !empty($jogador['nivel']) && $jogador['nivel'] == "3" ? 'selected' : '' ?>>Avançado</option>
            </select>
        </p>

        <p>
            <label>Número</label><br>
            <input class="input-text" type="text" name="descricao_contato" oninput="formatarTelefone(this)" value="<?= isset($_POST['descricao_contato']) ? $_POST['descricao_contato'] : (isset($jogador) ? $jogador['descricao_contato'] : '') ?>">

        </p>

        <p>
            <button type="submit" class="semBorda" onclick="return validaSelecaoDeCt()">Atualizar</button>
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
        function validaSelecaoDeCt() {
            const nome = document.getElementById('nome');
            const email = document.getElementById('email');
            const estado = document.getElementById('estado');
            const cidade = document.getElementById('cidade');
            const descricaoContato = document.getElementById('descricao_contato');
            const nivel = document.getElementById('nivel');
            if (nome.value === "" || email.value === "" || estado.value === "" || cidade.value === "" || descricaoContato.value === "" || nivel.value === "") {
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
        document.addEventListener("DOMContentLoaded", function() {
            var estadoSelect = document.getElementById('estado');
            var cidadeSelect = document.getElementById('cidade');

            var estadoId = "<?= $jogador['estado']; ?>";
            var cidadeId = "<?= $jogador['cidade']; ?>";

            estadoSelect.value = estadoId;
            cidadeSelect.value = cidadeId;

            var event = new Event('change');
            cidadeSelect.dispatchEvent(event);

        });
    </script>

</body>

</html>