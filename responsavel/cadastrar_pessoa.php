<?php
include('../conexao.php');
include('../protect.php');

$erro = [];
$mensagem_erro = '';

function validarCPF($cpf)
{

    $cpf = preg_replace('/[^0-9]/', '', $cpf);


    if (strlen($cpf) != 11) {
        return false;
    }


    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }

    return true;
}

function validarAno($anos)
{
    $anos_array = explode(',', $anos);
    foreach ($anos_array as $ano) {

        if (!preg_match('/^(19|20)\d{2}$/', $ano) || !is_numeric($ano) || $ano > date('Y')) {
            return false;
        }
    }
    return true;
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['perfil'], $_POST['titulo'], $_POST['nome'], $_POST['cpf'])) {
    $perfil = $mysqli->real_escape_string($_POST['perfil']);
    $titulos = isset($_POST['titulo']) ? $mysqli->real_escape_string(implode(',', $_POST['titulo'])) : '';
    $nome = $mysqli->real_escape_string($_POST['nome']);
    $cpf = $mysqli->real_escape_string($_POST['cpf']);
    $estado = $mysqli->real_escape_string($_SESSION['estado']);
    $cidade = $mysqli->real_escape_string($_SESSION['cidade']);
    $ct = $mysqli->real_escape_string($_SESSION['ct']);
    $valores_selecionados = [
        'estado' => isset($estado) ? $estado : '',
        'cidade' => isset($cidade) ? $cidade : '',
    ];

    $sql_busca_pessoa = "SELECT * FROM pessoas WHERE cpf = '$cpf'";
    $resultado = $mysqli->query($sql_busca_pessoa);

    $perfil_selecionado = (!empty($perfil)) ? $perfil : '';
    $titulo_selecionado = (!empty($titulo)) ? $titulo : '';

    if ($resultado && $resultado->num_rows > 0) {
        $erro[] = "Pessoa já existe.";
    }

    if (empty($perfil) || empty($nome) || empty($cpf)) {
        $erro[] = "Por favor, preencha os campos.";
    }

    if (!validarCPF($cpf)) {
        $erro[] = "CPF inválido";
    }

    if (preg_match('/[0-9]/', $nome)) {
        $erro[] = "O nome não pode conter números.";
    }

    $validouAno = 0;
    if (!empty($_POST['titulo'])) {
        foreach ($_POST['titulo'] as $titulo_id) {
            $um_dos_anos = 'ano_' . $titulo_id;
            if (!empty($_POST[$um_dos_anos])) {
                $ano = $mysqli->real_escape_string($_POST[$um_dos_anos]);
                if (validarAno($ano)) {
                    $validouAno = 1;
                } else {
                    $erro[] = "Ano inválido";
                }
            }
        }
    }

    if (empty($erro)) {

        $sql_code = "INSERT INTO pessoas (perfil, titulo, nome, cpf, estado, cidade, ct, id_responsavel) VALUES ('$perfil', '$titulos', '$nome', '$cpf', '$estado', '$cidade', '$ct', '$_SESSION[id]')";
        $confirma = $mysqli->query($sql_code) or die($mysqli->error);

        $sql_busca_pessoa = "SELECT id FROM pessoas WHERE cpf = '$cpf'";
        $resultado_busca = $mysqli->query($sql_busca_pessoa);

        if ($resultado_busca) {
            $pessoa = $resultado_busca->fetch_assoc();
            $pessoa_id = $pessoa['id'];
        }

        if ($validouAno == 1) {
            foreach ($_POST['titulo'] as $titulo_id) {
                $um_dos_anos = 'ano_' . $titulo_id;
                $ano = $mysqli->real_escape_string($_POST[$um_dos_anos]);
                $sql = "INSERT INTO anos_titulos (id_pessoa,id_titulo, ano) VALUES ('$pessoa_id','$titulo_id', '$ano')";
                $mysqli->query($sql) or die($mysqli->error);
            }
        }

        if ($confirma) {
            $confirma = "Pessoa cadastrada com sucesso!";
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
    <title>Cadastrar Pessoa</title>
    <link rel="stylesheet" type="text/css" href="../css/cadastrar_pessoa.css">
</head>

<body>
    <a href="dashboard_responsavel.php">Voltar</a>
    <div class="form-container">

        <div>
            <?php
            if (!empty($mensagem_erro)) {
                echo "<div class='erro'>$mensagem_erro</div>";
            }
            ?>
        </div>

        <div>
            <form action="" method="POST" id="form_cadastrar_pessoa">

                <p hidden>
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

                <p hidden>
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
                    <select class="select-style semClique" name="ct" id="ct" class="ct-dropdown" disabled>
                    </select>
                </p>

                <p>
                    <label>Perfil</label><br>
                    <select class="select-style" name="perfil" id="perfil" class="perfil-dropdown">
                        <option class="select-style option" value="">Selecione um Perfil</option>;

                    </select>
                </p>

                <p>
                    <label>Títulos</label><br>
                    <select class="select-style" name="titulo[]" id="titulo" class="titulo-dropdown" multiple>
                    </select>
                </p>

                <p>
                    <label>Nome</label><br>
                    <input class="input-text" type="text" name="nome" value="<?= isset($nome) ? $nome : '' ?>">
                </p>

                <p>
                    <label>CPF</label><br>
                    <input class="input-text" type="text" name="cpf" id="cpf" value="<?= isset($cpf) ? $cpf : '' ?>">
                </p>

                <p class="Anos" hidden>
                </p>

                <p>
                    <button type="submit" onclick="return validaSelecoes()">Cadastrar</button>
                </p>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('titulo').addEventListener('blur', function() {
            const campoAno = document.querySelector('.Anos');
            const tituloDropdown = document.getElementById('titulo');
            const opcoesSelecionadas = [];
            const inputsAno = [];

            campoAno.innerHTML = "";
            for (let i = 0; i < tituloDropdown.options.length; i++) {
                const option = tituloDropdown.options[i];

                if (option.selected) {
                    opcoesSelecionadas.push(option.value);

                    campoAno.innerHTML += '<label>' + option.text + '</label><br>';
                    campoAno.innerHTML += '<input class="input-text" type="text" name="ano_' + option.value + '" set-index="' + option.value + '" placeholder="Insira o ano" id="ano_' + i + '" required> <br><br>';

                    var inputAno = document.getElementById('ano_' + i);
                    inputsAno.push(inputAno);
                }
            }

            var apareceAlertEmostraInputs = 0;
            for (let i = 0; i < inputsAno.length; i++) {
                const inputAno = inputsAno[i];

                if (inputAno.getAttribute('set-index') != '' && inputAno.getAttribute('set-index') != '1' && inputAno.getAttribute('set-index') != '2' && inputAno.getAttribute('set-index') != '3') {
                    apareceAlertEmostraInputs = 1;
                }
            }


            if (apareceAlertEmostraInputs == 1) {
                alert("Escreva os anos dos títulos nos campos criados abaixo(caso tenha mais de um ano, escreva os anos separando por ',')                            exemplo: '2003,2005' ");
                campoAno.removeAttribute('hidden');
                inputAno.setAttribute('required', true);
            } else {
                campoAno.setAttribute('hidden', true);
                inputAno.removeAttribute('required');
            }



        });
    </script>

    <script>
        function validaSelecoes() {
            const perfilDropdown = document.getElementById('perfil');
            const tituloDropdown = document.getElementById('titulo');
            const nomeDropdown = document.getElementById('nome');
            const cpfDropdown = document.getElementById('cpf');

            if (perfilDropdown.value == "" || tituloDropdown.value == "" || nomeDropdown.value == "" || cpfDropdown.value == "") {
                alert("Todos os campos devem ser preenchidos.");
                return false;
            }

            return true;
        }
    </script>

    <script>
        <?php if (isset($confirma)) { ?>
            alert("<?php echo $confirma; ?>");
            window.location.href = "dashboard_responsavel.php";
        <?php } ?>
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const perfilDropdown = document.getElementById('perfil');
            const tituloDropdown = document.getElementById('titulo');
            const campoCpf = document.getElementById('cpf');
            const estadoDropdown = document.getElementById('estado');
            const cidadeDropdown = document.getElementById('cidade');
            const ctDropdown = document.getElementById('ct');

            estadoDropdown.value = <?= $_SESSION['estado']; ?>;

            var event = new Event('change');
            estadoDropdown.dispatchEvent(event);

            setTimeout(function() {
                cidadeDropdown.value = <?= $_SESSION['cidade']; ?>;
                var event = new Event('change');
                cidadeDropdown.dispatchEvent(event);
            }, 155);


            setTimeout(function() {
                ctDropdown.value = <?= $_SESSION['ct']; ?>;
            }, 2000);


            <?php
            if (!empty($perfil_selecionado)) { ?>
                perfilDropdown.value = <?= $perfil_selecionado; ?>;
            <?php } ?>

            <?php
            if (!empty($titulo_selecionado)) { ?>
                tituloDropdown.value = <?= $titulo_selecionado; ?>;
            <?php } ?>

            fetch('../admin/perfis/carregar_perfis.php', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    perfilDropdown.innerHTML = '<option value="">Selecione um Perfil</option>';
                    data.forEach(perfil => {
                        perfilDropdown.innerHTML += `<option value="${perfil.id}">${perfil.descricao}</option>`;
                    });

                    <?php
                    if (!empty($perfil_selecionado)) { ?>
                        perfilDropdown.value = <?= $perfil_selecionado; ?>;
                    <?php } ?>

                    var event = new Event('change');
                    perfilDropdown.dispatchEvent(event);
                })
                .catch(error => console.error('Erro ao carregar os CTs:', error));



            campoCpf.addEventListener('input', function() {

                let cpfDigitado = campoCpf.value.replace(/\D/g, '');


                cpfDigitado = cpfDigitado.slice(0, 11);


                if (cpfDigitado.length > 3) {
                    cpfDigitado = cpfDigitado.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                }


                campoCpf.value = cpfDigitado;
            });
        });
    </script>

    <script>
        document.getElementById('perfil').addEventListener('change', function() {
            const perfilDropdown = document.getElementById('perfil');
            const tituloDropdown = document.getElementById('titulo');
            const perfilSelecionado = document.getElementById('perfil').value;

            if (perfilSelecionado) {
                tituloDropdown.innerHTML = '<option value="">Selecione um Título</option>';
                const formData = new FormData();
                formData.append('perfil', perfilSelecionado);

                fetch('../admin/titulos/carregar_titulos.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(titulo => {
                            tituloDropdown.innerHTML += `<option value="${titulo.id}">${titulo.descricao}</option>`;
                        });
                        <?php
                        if (!empty($titulo_selecionado)) { ?>
                            tituloDropdown.value = <?= $titulo_selecionado; ?>;
                        <?php } ?>
                        var event = new Event('change');
                        tituloDropdown.dispatchEvent(event);
                    })
                    .catch(error => console.error('Erro ao carregar os CTs:', error));
            }
        });
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

                fetch('../admin/cts/carregar_cts.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        ctDropdown.innerHTML = '<option class="select-style option" value=""></option>';
                        data.forEach(ct => {
                            ctDropdown.innerHTML += `<option value="${ct.id}">${ct.nome}</option>`;
                        });
                        <?php
                        if (!empty($ct_selecionado)) { ?>
                            ctDropdown.value = <?= $ct_selecionado; ?>;
                        <?php } ?>
                    })
                    .catch(error => {
                        ctDropdown.innerHTML = '<option class="select-style option" value="">Não há CT\'s disponíveis</option>';
                    });
            }
        });
    </script>

    <script>
        document.getElementById('titulo').addEventListener('click', function() {
            const perfilSelecionado = document.getElementById('perfil').value;
            if (!perfilSelecionado) {
                alert('Por favor, selecione um perfil antes de escolher um título.');
                this.blur();
            }
        });
    </script>

    <script>
        document.getElementById('form_cadastrar_pessoa').addEventListener('submit', function(event) {
            const tituloDropdown = document.getElementById('titulo');
            const selectedOptions = Array.from(tituloDropdown.selectedOptions).map(option => option.text);

            if (selectedOptions.includes('') || selectedOptions.includes('Nenhum') && selectedOptions.length > 1) {
                event.preventDefault();
                alert('Selecione apenas "Nenhum" ou outros títulos, não ambos.');
            }
        });
    </script>
</body>

</html>