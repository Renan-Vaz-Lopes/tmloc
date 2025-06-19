        <?php
        include('../conexao.php');
        include('../protect.php');

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

        $erro = [];
        $mensagem_erro = '';

        $id = $_GET['codigo'];
        $sql_busca_pessoa = "SELECT * FROM pessoas WHERE id = $id";
        $resultado = $mysqli->query($sql_busca_pessoa);

        if ($resultado && $resultado->num_rows > 0) {
            $pessoa = $resultado->fetch_assoc();
        } else {
            echo "Perfil não encontrado.";
        }

        $ids_titulos = $pessoa['titulo'];
        $id_titulos_array = explode(',', $ids_titulos);

        foreach ($id_titulos_array as $id_titulo) {
            ${"sql_busca_anos_titulo_$id_titulo"} = "SELECT * FROM anos_titulos WHERE id_pessoa = '$id' AND id_titulo = '$id_titulo'";


            ${"resultado_anos_titulos_$id_titulo"} = $mysqli->query(${"sql_busca_anos_titulo_$id_titulo"});

            if (${"resultado_anos_titulos_$id_titulo"} && ${"resultado_anos_titulos_$id_titulo"}->num_rows > 0) {
                ${"anos_titulos_$id_titulo"} = [];
                while ($ano_titulo = ${"resultado_anos_titulos_$id_titulo"}->fetch_assoc()) {
                    ${"anos_titulos_$id_titulo"}[$ano_titulo['id_titulo']][] = $ano_titulo['ano'];
                }
            }
        }


        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['perfil'], $_POST['titulo'], $_POST['nome'], $_POST['cpf'])) {
            $perfil = $mysqli->real_escape_string($_POST['perfil']);
            $titulos = isset($_POST['titulo']) ? $mysqli->real_escape_string(implode(',', $_POST['titulo'])) : '';
            $nome = $mysqli->real_escape_string($_POST['nome']);
            $cpf = $mysqli->real_escape_string($_POST['cpf']);
            $estado = $mysqli->real_escape_string($_SESSION['estado']);
            $cidade = $mysqli->real_escape_string($_SESSION['cidade']);
            $ct = $mysqli->real_escape_string($_SESSION['ct']);

            if (empty($perfil) || empty($nome) || empty($cpf)) {

                $erro[] = "Por favor, preencha os campos.";
            }

            if (!validarCPF($cpf)) {
                $erro[] = "CPF inválido";
            }


            if (preg_match('/[0-9]/', $nome)) {
                $erro[] = "O nome não pode conter números.";
            }

            if (!empty($_POST['titulo'])) {
                foreach ($_POST['titulo'] as $id_titulo_enviado) {
                    $um_dos_anos = 'ano_' . $id_titulo_enviado;
                    if (!empty($_POST[$um_dos_anos])) {
                        $ano = $mysqli->real_escape_string($_POST[$um_dos_anos]);
                        if (!validarAno($ano)) {
                            $erro[] = "Ano inválido";
                        }
                    } else {

                        $sql_remove_ano = "DELETE FROM anos_titulos WHERE id_pessoa = $id AND id_titulo = '$id_titulo_enviado'";
                        $mysqli->query($sql_remove_ano) or die($mysqli->error);
                    }
                }
            }
            if (empty($erro)) {

                $sql_code = "UPDATE pessoas SET perfil = '$perfil', titulo = '$titulos', nome = '$nome', cpf = '$cpf', estado = '$estado', cidade = '$cidade', ct = '$ct' WHERE id = $id";
                $mysqli->query($sql_code) or die($mysqli->error);

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

        if (!empty($_POST['titulo'])) {
            if (sizeof($_POST['titulo']) == sizeof($id_titulos_array)) {
                $titulos_combinados = array_combine($_POST['titulo'], $id_titulos_array);
                foreach ($titulos_combinados as $id_titulo_enviado => $id_titulo_ja_salvo) {
                    $um_dos_anos = 'ano_' . $id_titulo_enviado;
                    if (!empty($_POST[$um_dos_anos])) {
                        $ano = $mysqli->real_escape_string($_POST[$um_dos_anos]);
                        if (validarAno($ano)) {
                            if (in_array($id_titulo_enviado, $id_titulos_array)) {
                                $sql = "UPDATE anos_titulos set id_titulo = '$id_titulo_enviado', ano = '$ano' WHERE id_pessoa = '$id' AND id_titulo = '$id_titulo_ja_salvo'";
                            } else {
                                $sql = "INSERT INTO anos_titulos (id_pessoa, id_titulo, ano) VALUES ('$id', '$id_titulo_enviado', '$ano')";
                            }
                            $mysqli->query($sql) or die($mysqli->error);
                        } else {
                            $erro[] = "Ano inválido";
                        }
                    }
                }
            } else {
                foreach ($_POST['titulo'] as $id_titulo_enviado) {
                    $um_dos_anos = 'ano_' . $id_titulo_enviado;
                    if (!empty($_POST[$um_dos_anos])) {
                        $ano = $mysqli->real_escape_string($_POST[$um_dos_anos]);
                        if (validarAno($ano)) {
                            if (in_array($id_titulo_enviado, $id_titulos_array)) {
                                $sql = "UPDATE anos_titulos SET id_titulo = '$id_titulo_enviado', ano = '$ano' WHERE id_pessoa = $id AND id_titulo = '$id_titulo_enviado'";
                            } else {
                                $sql = "INSERT INTO anos_titulos (id_pessoa, id_titulo, ano) VALUES ('$id', '$id_titulo_enviado', '$ano')";
                            }
                            $mysqli->query($sql) or die($mysqli->error);
                        } else {
                            $erro[] = "Ano inválido";
                        }
                    } else {

                        $sql_remove_ano = "DELETE FROM anos_titulos WHERE id_pessoa = $id AND id_titulo = '$id_titulo_enviado'";
                        $mysqli->query($sql_remove_ano) or die($mysqli->error);
                    }
                }
            }
        }
        ?>

        <script>
            <?php if (isset($success) && $success) { ?>
                alert("Pessoa atualizada com sucesso!");
                window.location.href = 'dashboard_responsavel.php';
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
            <title>Atualizar Pessoa</title>
            <link rel="stylesheet" type="text/css" href="../css/atualizar_pessoa.css">
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
                    <form action="" method="POST" id="form_atualizar_pessoa">
                        <p>
                            <label>Perfil</label><br>
                            <select class="select-style" name="perfil" id="perfil" class="perfil-dropdown">
                                <option class="select-style option" value="<?= $pessoa['perfil'] ?>" selected><?= $pessoa['perfil'] ?></option>
                            </select>
                        </p>

                        <p>
                            <label>Títulos</label><br>
                            <select class="select-style" name="titulo[]" id="titulo" class="titulo-dropdown" multiple>
                                <?php
                                $sql_busca_titulos = "SELECT * FROM titulos";
                                $resultado_titulos = $mysqli->query($sql_busca_titulos);
                                if ($resultado_titulos && $resultado_titulos->num_rows > 0) {
                                    while ($titulo = $resultado_titulos->fetch_assoc()) {
                                        $selected = in_array($titulo['id'], $id_titulos_array) ? 'selected' : '';
                                        echo '<option class="select-style option" value="' . $titulo['id'] . '" ' . $selected . '>' . $titulo['descricao'] . '</option>';
                                    }
                                } else {
                                    echo '<option class="select-style option" value="">Nenhum título disponível</option>';
                                }
                                ?>
                            </select>
                        </p>

                        <p>
                            <label>Nome</label><br>
                            <input class="input-text" type="text" name="nome" value="<?= isset($_POST['nome']) && !empty($_POST['nome']) ? $_POST['nome'] : $pessoa['nome'] ?>">
                        </p>

                        <p>
                            <label>CPF</label><br>
                            <input class="input-text" type="text" name="cpf" id="cpf" value="<?= isset($_POST['cpf']) && !empty($_POST['cpf']) ? $_POST['cpf'] : $pessoa['cpf'] ?>">
                        </p>

                        <p class="Anos">
                            <?php
                            if (!empty($id_titulos_array)) {
                                foreach ($id_titulos_array as $id_titulo) {

                                    if (isset(${"anos_titulos_$id_titulo"}) && !empty(${"anos_titulos_$id_titulo"})) {

                                        $sql_titulo = "SELECT descricao FROM titulos WHERE id = $id_titulo";
                                        $resultado_titulo = $mysqli->query($sql_titulo);

                                        if ($resultado_titulo && $resultado_titulo->num_rows > 0) {
                                            $titulo_nome = $resultado_titulo->fetch_assoc()['descricao'];

                                            echo '<label>' . $titulo_nome . '</label><br>';


                                            $anos_string = '';

                                            foreach (${"anos_titulos_$id_titulo"} as $ano_array) {
                                                foreach ($ano_array as $ano) {
                                                    $anos_string .= $ano . ',';
                                                }
                                            }


                                            $anos_string = rtrim($anos_string, ',');

                                            echo '<input class="input-text" type="text" name="ano_' . $id_titulo . '" value="' . $anos_string . '"><br>';

                                            echo '<br>';
                                        }
                                    }
                                }
                            }
                            ?>
                        </p>


                        <p>
                            <button type="submit" class="semBorda" onclick="return validaSelecoes()">Atualizar</button>
                        </p>
                    </form>
                </div>
            </div>

            <script>
                document.getElementById('perfil').addEventListener('change', function() {
                    const perfilDropdown = document.getElementById('perfil');
                    const tituloDropdown = document.getElementById('titulo');
                    const perfilSelecionado = document.getElementById('perfil').value;

                    tituloDropdown.innerHTML = '<option value="">Selecione um Título</option>';

                    if (perfilSelecionado) {
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


                                const selectedTitles = <?= json_encode(explode(',', $pessoa['titulo'])); ?>;
                                selectedTitles.forEach(id => {
                                    const option = tituloDropdown.querySelector(`option[value="${id}"]`);
                                    if (option) {
                                        option.selected = true;
                                    }
                                });
                            })
                            .catch(error => console.error('Erro ao carregar os títulos:', error));
                    } else {
                        tituloDropdown.innerHTML = '<option value="">Selecione um Título</option>';
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
                document.addEventListener("DOMContentLoaded", function() {
                    const perfilDropdown = document.getElementById('perfil');
                    const tituloDropdown = document.getElementById('titulo');
                    const campoCpf = document.getElementById('cpf');
                    const formData = new FormData();
                    const selectedTitles = <?= json_encode(explode(',', $pessoa['titulo'])); ?>;

                    var perfilId = "<?= $pessoa['perfil']; ?>";
                    var tituloId = "<?= $pessoa['titulo']; ?>";

                    fetch('../admin/perfis/carregar_perfis.php', {
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

                    formData.append('perfil', perfilId);

                    fetch('../admin/titulos/carregar_titulos.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            tituloDropdown.innerHTML = '<option value="">Selecione um Título</option>';
                            data.forEach(titulo => {
                                tituloDropdown.innerHTML += `<option value="${titulo.id}">${titulo.descricao}</option>`;
                            });
                        })
                        .catch(error => console.error('Erro ao carregar os CTs:', error));

                    setTimeout(function() {
                        for (let i = 0; i < tituloDropdown.options.length; i++) {
                            const option = tituloDropdown.options[i];


                            if (selectedTitles.includes(option.value)) {
                                option.selected = true;
                            }
                        }

                    }, 60);

                    campoCpf.addEventListener('input', function() {

                        let cpfDigitado = campoCpf.value.replace(/\D/g, '');


                        cpfDigitado = cpfDigitado.slice(0, 11);


                        if (cpfDigitado.length > 3) {
                            cpfDigitado = cpfDigitado.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                        }


                        campoCpf.value = cpfDigitado;
                    });

                    setTimeout(function() {
                        var event = new Event('change');
                        tituloDropdown.dispatchEvent(event);
                    }, 60);
                });
            </script>

            <script>
                document.getElementById('form_atualizar_pessoa').addEventListener('submit', function(event) {
                    const tituloDropdown = document.getElementById('titulo');
                    const selectedOptions = Array.from(tituloDropdown.selectedOptions).map(option => option.text);


                    if (selectedOptions.includes('') || selectedOptions.includes('Nenhum') && selectedOptions.length > 1) {
                        event.preventDefault();
                        alert('Selecione apenas "Nenhum" ou outros títulos, não ambos.');
                    }
                });
            </script>

            <script>
                document.getElementById('titulo').addEventListener('blur', function() {
                    const campoAno = document.querySelector('.Anos');
                    const tituloDropdown = document.getElementById('titulo');
                    const opcoesSelecionadas = [];
                    const inputsAno = [];
                    var apareceAlertEmostraInputs = 0;

                    campoAno.innerHTML = "";
                    for (let i = 0; i < tituloDropdown.options.length; i++) {
                        const option = tituloDropdown.options[i];

                        if (option.selected) {
                            opcoesSelecionadas.push(option.value);

                            campoAno.innerHTML += '<label>' + option.text + '</label><br>';
                            campoAno.innerHTML += '<input class="input-text" type="text" name="ano_' + option.value + '" set-index="' + option.value + '" placeholder="Insira o ano" id="ano_' + option.value + '"><br>';
                            campoAno.innerHTML += '<div style="height: 10px;"></div>';
                        }
                    }


                    const TodosInputs = document.querySelectorAll('.Anos input');
                    TodosInputs.forEach(input => {
                        const setIndexValue = input.getAttribute('set-index');
                        if (setIndexValue && setIndexValue !== '' && setIndexValue !== '1' && setIndexValue !== '2' && setIndexValue !== '3') {
                            apareceAlertEmostraInputs = 1;
                            inputsAno.push(input);
                        }
                    });

                    if (apareceAlertEmostraInputs == 1) {
                        alert("Escreva os anos dos títulos nos campos criados abaixo(caso tenha mais de um ano, escreva os anos separando por ',')                            exemplo: '2003,2005' ");
                        campoAno.removeAttribute('hidden');
                        inputsAno.forEach(input => {
                            input.setAttribute('required', true);
                        });
                    } else {
                        campoAno.setAttribute('hidden', true);
                        inputsAno.forEach(input => {
                            input.removeAttribute('required');
                        });
                    }
                });
            </script>

        </body>

        </html>