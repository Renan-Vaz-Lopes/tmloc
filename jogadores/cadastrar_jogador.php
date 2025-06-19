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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nome'], $_POST['email'], $_POST['senha'], $_POST['confirmacao_senha'], $_POST['estado'], $_POST['cidade'], $_POST['descricao_contato'], $_POST['nivel'])) {
    $nome = $mysqli->real_escape_string($_POST['nome']);
    $email = $mysqli->real_escape_string($_POST['email']);
    $senha = $mysqli->real_escape_string($_POST['senha']);
    $confirmacao_senha = $mysqli->real_escape_string($_POST['confirmacao_senha']);
    $estado = $mysqli->real_escape_string($_POST['estado']);
    $cidade = $mysqli->real_escape_string($_POST['cidade']);
    $descricao_contato = $mysqli->real_escape_string($_POST['descricao_contato']);
    $nivel = $mysqli->real_escape_string($_POST['nivel']);

    $sql_busca_responsavel = "SELECT * FROM responsaveis WHERE email = '$email'";
    $resultado_responsavel = $mysqli->query($sql_busca_responsavel);

    $sql_busca_jogador = "SELECT * FROM jogadores WHERE email = '$email'";
    $resultado_jogador = $mysqli->query($sql_busca_jogador);

    $sql_busca_organizador = "SELECT * FROM organizadores WHERE email = '$email'";
    $resultado_organizador = $mysqli->query($sql_busca_organizador);

    $sql_busca_jogador_temp = "SELECT * FROM jogadores_temp WHERE email = '$email'";
    $resultado_jogador_temp = $mysqli->query($sql_busca_jogador_temp);

    $valores_selecionados = [
        'estado' => isset($estado) ? $estado : '',
        'cidade' => isset($cidade) ? $cidade : '',
    ];

    if (empty($nome) || empty($email) || empty($senha) || empty($confirmacao_senha) || empty($cidade) || empty($cidade) || empty($descricao_contato) || empty($nivel)) {
        $erro[] = "Por favor, preencha todos os campos do formulário.";
    }

    if ($resultado_responsavel && $resultado_responsavel->num_rows > 0) {
        $erro[] = "Email já vinculado a um responsável por um CT";
    }

    if ($resultado_jogador && $resultado_jogador->num_rows > 0) {
        $erro[] = "Email já vinculado a um jogador.";
    }

    if ($resultado_organizador && $resultado_organizador->num_rows > 0) {
        $erro[] = "Email já vinculado a um organizador de campeonato.";
    }

    if ($resultado_jogador_temp && $resultado_jogador_temp->num_rows > 0) {
        $erro[] = "Já foi enviado um email de confirmação e ele tem validade de 5 minutos";
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
        sleep(3);

        $sql_code = "INSERT INTO jogadores_temp (nome, email, senha, estado, cidade, nivel, descricao_contato, token) VALUES ('$nome', '$email', '$senha_hash', '$estado', '$cidade', '$nivel', '$descricao_contato', '$token')";
        $confirma = $mysqli->query($sql_code) or die($mysqli->error);

        if ($confirma) {
            $link = "http://localhost/tmloc/jogadores/confirmar_jogador.php?token=$token";

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
                $CONFIG = getConfig();

                $mail->Host = $CONFIG['email']['host'];
                $mail->SMTPAuth = true;
                $mail->Port = $CONFIG['email']['port'];
                $mail->SMTPSecure = $CONFIG['email']['SMTPSecure'];
                'ssl';
                $mail->Username = $CONFIG['email']['username'];
                $mail->Password = $CONFIG['email']['password'];

                $mail->setFrom($CONFIG['email']['username'], 'tmloc');
                $mail->addAddress($email, $nome);


                $mail->isHTML(true);
                $mail->Subject = 'Confirme seu cadastro';
                $mail->Body = "
                    <h2>Bem-vindo, $nome!</h2>
                    <p>Clique no link abaixo para confirmar seu cadastro:</p>
                    <a href='$link'>Confirmar cadastro</a>";

                $mail->send();
                $msg = 'E-mail de confirmação enviado! Verifique sua caixa de entrada(spam também), tem 5 minutos antes de expirar.';
            } catch (Exception $e) {
                $msg = "Erro ao enviar e-mail: {$mail->ErrorInfo}";
            }
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
    <title>Cadastrar Jogador</title>
    <link rel="stylesheet" type="text/css" href="../css/cadastrar_jogador.css">
</head>

<body>
    <a href="../dashboard_cadastrar.php">Voltar</a>
    <div class="form-container">

        <div>
            <?php if (!empty($msg)) { ?>
                <div class="msg-envio-email">
                    <?php echo $msg; ?>
                </div>
            <?php } ?>
        </div>

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
                    <label>Nome</label><br>
                    <input class="input-text" type="text" name="nome" value="<?= isset($nome) ? $nome : '' ?>">
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
                    <label>Qual você considera seu nível?</label><br>
                    <select name="nivel" id="nivel" class="select-style">
                        <option value="">Selecione um nível</option>
                        <option value="1" <?= isset($nivel) && $nivel == "1" ? 'selected' : '' ?>>Iniciante</option>
                        <option value="2" <?= isset($nivel) && $nivel == "2" ? 'selected' : '' ?>>Intermediário</option>
                        <option value="3" <?= isset($nivel) && $nivel == "3" ? 'selected' : '' ?>>Avançado</option>
                    </select>
                </p>


                <p>
                    <label>Número</label><br>
                    <input type="text" class="input-text" name="descricao_contato" id="telefone" maxlength="15" oninput="formatarTelefone(this)" value="<?= isset($descricao_contato) ? htmlspecialchars($descricao_contato) : '' ?>">

                </p>

                <p class="meio">
                    <button type="submit" class="semBorda" id="btnCadastrar">Cadastrar</button>
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
    </div>
</body>

</html>