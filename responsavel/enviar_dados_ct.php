<?php
include('../conexao.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../config.php';
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

$erro = [];
$mensagem_erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cnpj'], $_POST['nome_ct'], $_POST['nome_resp'], $_POST['endereco'], $_POST['telefone'], $_POST['estado'], $_POST['cidade'], $_POST['apresentacao'], $_POST['email'], $_POST['senha'], $_POST['confirmacao_senha'])) {
    $nome_ct = $mysqli->real_escape_string($_POST['nome_ct']);
    $cnpj = $mysqli->real_escape_string($_POST['cnpj']);
    $nome_resp = $mysqli->real_escape_string($_POST['nome_resp']);
    $endereco = $mysqli->real_escape_string($_POST['endereco']);
    $telefone = $mysqli->real_escape_string($_POST['telefone']);
    $estado = $mysqli->real_escape_string($_POST['estado']);
    $cidade = $mysqli->real_escape_string($_POST['cidade']);
    $apresentacao = $mysqli->real_escape_string($_POST['apresentacao']);
    $email = $mysqli->real_escape_string($_POST['email']);
    $senha = $mysqli->real_escape_string($_POST['senha']);
    $confirmacao_senha = $mysqli->real_escape_string($_POST['confirmacao_senha']);

    $sql_busca_responsavel = "SELECT * FROM responsaveis WHERE email = '$email'";
    $resultado_responsavel = $mysqli->query($sql_busca_responsavel);

    $sql_busca_ct = "SELECT * FROM cts WHERE nome = '$nome_ct'";
    $resultado_ct = $mysqli->query($sql_busca_ct);

    $sql_busca_responsavel_ct = "SELECT * FROM responsaveis WHERE ct = '$nome_ct'";
    $resultado_responsavel_ct = $mysqli->query($sql_busca_ct);

    $sql_busca_responsavel_temp = "SELECT * FROM responsaveis_temp WHERE email = '$email'";
    $resultado_responsavel_temp = $mysqli->query($sql_busca_responsavel_temp);

    $sql_busca_jogador = "SELECT * FROM jogadores WHERE email = '$email'";
    $resultado_jogador = $mysqli->query($sql_busca_jogador);

    $valores_selecionados = [
        'estado' => isset($estado) ? $estado : '',
        'cidade' => isset($cidade) ? $cidade : '',
    ];

    if ($resultado_jogador && $resultado_jogador->num_rows > 0) {
        $erro[] = "Email já está em uso.";
    }

    if ($resultado_responsavel && $resultado_responsavel->num_rows > 0) {
        $erro[] = "Usuário já existe.";
    }

    if ($resultado_ct && $resultado_ct->num_rows > 0) {
        $erro[] = "Ct já existe.";
    }

    if ($resultado_responsavel_ct && $resultado_responsavel_ct->num_rows > 0) {
        $erro[] = "CT já vinculado a um usuário.";
    }

    if ($resultado_responsavel_temp && $resultado_responsavel_temp->num_rows > 0) {
        $erro[] = "Já foi enviado seus dados pro administrador, você receberá a confirmação pelo seu email";
    }

    if (empty($nome_ct) || empty($cnpj) || empty($nome_resp) || empty($endereco) || empty($telefone) || empty($estado) || empty($cidade) || empty($apresentacao) || empty($email) || empty($senha) || empty($confirmacao_senha)) {
        $erro[] = "Por favor, preencha todos os campos do formulário.";
    }

    if (preg_match('/[0-9]/', $nome_ct)) {
        $erro[] = "O nome não pode conter números.";
    }

    if (!preg_match('/^\([0-9]{2}\) [0-9]{4,5}-[0-9]{4}$/', $telefone)) {
        $erro[] = "Utilize o formato (XX)X XXXX-XXXX no telefone";
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
        $token = bin2hex(random_bytes(16));
        $link = "http://localhost/tmloc/responsavel/dados_confirmados_ct.php?token=$token";
        sleep(3);

        $sql_code = "INSERT INTO cts_temp (nome, endereco, telefone, estado, cidade, apresentacao, token) VALUES ('$nome_ct', '$endereco', '$telefone' , '$estado', '$cidade', '$apresentacao', '$token')";
        $confirma = $mysqli->query($sql_code) or die($mysqli->error);

        $sql_code = "INSERT INTO responsaveis_temp (email, senha, estado, cidade, ct, token) VALUES ('$email', '$senha_hash', '$estado', '$cidade', '$nome_ct', '$token')";
        $confirma = $mysqli->query($sql_code) or die($mysqli->error);

        $mail = new PHPMailer(true);
        try {
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // Configurações do servidor
            $mail->isSMTP();
            $mail->Host = $CONFIG['email']['host'];
            $mail->SMTPAuth = true;
            $mail->Port = $CONFIG['email']['port'];
            $mail->SMTPSecure = $CONFIG['email']['SMTPSecure'];
            'ssl';
            $mail->Username = $CONFIG['email']['username'];
            $mail->Password = $CONFIG['email']['password'];

            $mail->setFrom($CONFIG['email']['username'], 'tmloc');
            $mail->addAddress($CONFIG['email']['username'], $nome_resp);

            $mail->isHTML(true);
            $mail->Subject = 'Validar CT';
            $mail->Body = "
                    <p>Seguem os dados enviados para análise:</p>
                    <ul>
                        <li><strong>Estado:</strong> $estado</li>
                        <li><strong>Cidade:</strong> $cidade</li>
                        <li><strong>Nome do CT:</strong> $nome_ct</li>
                        <li><strong>Nome do Responsável:</strong> $nome_resp</li>
                        <li><strong>CNPJ:</strong> $cnpj</li>
                        <li><strong>Endereço:</strong> $endereco</li>
                        <li><strong>Telefone:</strong> $telefone</li>
                        <li><strong>Email:</strong> $email</li>
                        <li><strong>Apresentação do CT:</strong> $apresentacao</li>

                    </ul>
                    <p>Clique no link abaixo para validar o CT:</p>
                    <a href='$link'>Validar CT</a>";

            $mail->send();
            $msg = 'Dados enviados para análise com sucesso, você receberá a confirmação pelo seu email';
        } catch (Exception $e) {
            $msg = "Erro ao enviar e-mail: {$mail->ErrorInfo}";
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
    <link rel="stylesheet" type="text/css" href="../css/enviar_dados_ct.css">
</head>

<body>
    <a class="botao" href="../dashboard_cadastrar.php">Voltar</a>
    <?php
    if (!empty($mensagem_erro)) {
        echo "<div class='erro'>$mensagem_erro</div>";
    }
    ?>

    <div>
        <?php if (!empty($msg)) { ?>
            <div>
                <p class="msg-envio-email"><?php echo $msg; ?></p>
            </div>
        <?php } ?>
    </div>


    <div class="form_enviar_dados_ct">
        <div class="caixa_form">
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
                    <label>Nome do CT</label><br>
                    <input type="text" class="input-text" name="nome_ct" pattern="[A-Za-zÀ-ú\s]+" title="Digite apenas letras." value="<?= isset($nome_ct) ? htmlspecialchars($nome_ct) : '' ?>">
                </p>

                <p>
                    <label>Nome do responsável</label><br>
                    <input type="text" class="input-text" name="nome_resp" pattern="[A-Za-zÀ-ú\s]+" title="Digite apenas letras." value="<?= isset($nome_resp) ? htmlspecialchars($nome_resp) : '' ?>">
                </p>

                <p>
                    <label for="">CNPJ(<a href="#" class="aviso" onclick="abrirModal()"> porque pedimos isso? </a>)</label>
                    <input type="text" class="input-text" id="cnpj" name="cnpj" oninput="formatarCnpj(this)" value="<?= isset($cnpj) ? htmlspecialchars($cnpj) : '' ?>">
                </p>

                <div id="modal" class="modal">
                    <div class="modal-content">
                        <span class="close" onclick="fecharModal()">&times;</span>
                        <h2>Por que pedimos o CNPJ?</h2>
                        <p>Pedimos o CNPJ para garantir que você seja um dos sócios do CT, a partir do CNPJ é possível ter os nomes dos sócios no site da receita federal.</p>
                    </div>
                </div>

                <p>
                    <label>Endereço</label><br>
                    <input type="text" class="input-text" name="endereco" value="<?= isset($endereco) ? htmlspecialchars($endereco) : '' ?>">
                </p>

                <p>
                    <label>Telefone</label><br>
                    <input type="text" class="input-text" name="telefone" id="telefone" maxlength="15" oninput="formatarTelefone(this)" value="<?= isset($telefone) ? htmlspecialchars($telefone) : '' ?>">
                </p>

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
                    <label>Apresentação do CT</label><br>
                    <textarea id="apresentacao" class="input-text" name="apresentacao" rows="2" cols="35"><?= isset($_POST['apresentacao']) ? htmlspecialchars($_POST['apresentacao']) : '' ?></textarea>
                </p>

                <p>
                    <button type="submit" class="semBorda" id="btnCadastrar" onclick="return validaSelecoes()">Enviar para avaliação</button>
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
        function validaSelecoes() {
            const estadoDropdown = document.getElementById('estado');
            const cidadeDropdown = document.getElementById('cidade');
            const nomeCtDropdown = document.getElementById('nome_ct');
            const nomeRespDropdown = document.getElementById('nome_resp');
            const enderecoDropdown = document.getElementById('endereco');
            const telefoneDropdown = document.getElementById('telefone');
            const apresentacaoTextArea = document.getElementById('apresentacao');
            const cnpjDropdown = document.getElementById('cnpj');
            const emailDropdown = document.getElementById('email');
            const senhaDropdown = document.getElementById('senha');
            const confirmacaoSenhaDropdown = document.getElementById('confirmacao_senha');

            if (estadoDropdown.value == "" || cidadeDropdown.value == "" || nomeCtDropdown.value == "" || nomeRespDropdown.value == "" || enderecoDropdown.value == "" || telefoneDropdown.value == "" || apresentacaoTextArea.value == "" || cnpjDropdown == "" || emailDropdown.value == "" || senhaDropdown == "" || confirmacaoSenhaDropdown == "") {
                alert("Todos os campos devem ser preenchidos.");
                return false;
            }

            return true;
        }
    </script>

    <script>
        function formatarCnpj(input) {

            let numero = input.value.replace(/\D/g, '');

            numero = numero.substring(0, 14);

            input.value = numero.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
        }

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

    <!-- JavaScript para abrir e fechar o modal -->
    <script>
        function abrirModal() {
            document.getElementById("modal").style.display = "block";
        }

        function fecharModal() {
            document.getElementById("modal").style.display = "none";
        }

        // Fecha o modal ao clicar fora dele
        window.onclick = function(event) {
            const modal = document.getElementById("modal");
            if (event.target === modal) {
                modal.style.display = "none";
            }
        }
    </script>

</body>

</html>