    <?php
    include('../protect.php');
    include('../conexao.php');
    date_default_timezone_set('America/Sao_Paulo');

    $erro = [];
    $mensagem_erro = '';
    $id_jogador = $_SESSION['id'];

    $queryPodePassar = "SELECT passar_resultado_grupo FROM jogadores_campeonatos WHERE id_jogador = '$id_jogador'";
    $resultPermissao = $mysqli->query($queryPodePassar);
    $podePassarResultado = ($resultPermissao->num_rows > 0) ? $resultPermissao->fetch_assoc()['passar_resultado_grupo'] : 0;

    $queryPodePassarChave = "SELECT passar_resultado_chave FROM jogadores_campeonatos WHERE id_jogador = '$id_jogador'";
    $resultPermissaoChave = $mysqli->query($queryPodePassarChave);
    $podePassarResultadoChave = ($resultPermissaoChave->num_rows > 0) ? $resultPermissaoChave->fetch_assoc()['passar_resultado_chave'] : 0;


    $categoria_atual = isset($_GET['categoria']) ? $_GET['categoria'] : '';
    $estado = $_SESSION['estado'];
    $cidade = $_SESSION['cidade'];
    $email = $_SESSION['email'];
    $nome_jogador_logado = $_SESSION['nome'];
    $descricao_contato_jogador_logado = $_SESSION['descricao_contato'];
    $id_nivel_jogador_logado = $_SESSION['nivel'];
    $categoria = '';

    $adversarios = [];
    if ($podePassarResultado) {
        $queryGrupos = "SELECT DISTINCT g.id AS id_grupo, g.categoria, g.grupo, g.mesa
                FROM grupos g
                JOIN jogos j ON g.id = j.id_grupo
                WHERE j.id_jogador1 = '$id_jogador' OR j.id_jogador2 = '$id_jogador'";

        $resultGrupos = $mysqli->query($queryGrupos);

        if ($resultGrupos->num_rows > 0) {
            $idGrupo = $resultGrupos->fetch_assoc()['id_grupo'];
            $queryAdversarios = "SELECT DISTINCT id_jogador1, id_jogador2 FROM jogos WHERE id_grupo = '$idGrupo' AND (id_jogador1 != '$id_jogador' OR id_jogador2 != '$id_jogador')";
            $resultAdversarios = $mysqli->query($queryAdversarios);
            while ($row = $resultAdversarios->fetch_assoc()) {
                $adversarios[] = ($row['id_jogador1'] != $id_jogador) ? $row['id_jogador1'] : $row['id_jogador2'];
            }
        }
    }

    if ($id_nivel_jogador_logado == '1') {
        $texto_nivel = 'Iniciante';
    } else if ($id_nivel_jogador_logado == '2') {
        $texto_nivel = 'Intermediário';
    } else if ($id_nivel_jogador_logado == '3') {
        $texto_nivel = 'Avançado';
    } else {
        $texto_nivel = 'Não pegou nenhum nível';
    }

    $consulta = "SELECT * FROM jogadores WHERE estado = '$estado' AND cidade = '$cidade' AND id!='$id_jogador'";
    $con = $mysqli->query($consulta) or die($mysqli->error);

    ?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" type="text/css" href="../css/dashboard_jogador.css">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

        <title>Lista de jogadores</title>
    </head>

    <body>
        <a type="button" class="botao" href="../logout.php">Deslogar</a>
        <br><br>

        <p class="meio bem_vindo">Bem vindo <?= $nome_jogador_logado ?></p>

        <div class="meio">
            <a type="button" class="botao" href="#" onclick="abrirModaleReceberFeedback()">O que está achando do tmloc? dê sua opinião aqui</a>
        </div>

        <br>

        <?php if ($podePassarResultado) { ?>
            <div class="meio">
                <button class="botao" onclick="abrirModalEPassarResultado()">Passar Resultado do Grupo</button>
            </div>
        <?php } ?>

        <?php if ($podePassarResultadoChave) { ?>
            <div class="meio">
                <button class="botao" onclick="abrirModalEPassarResultadoChave()">Passar Resultado da Chave</button>
            </div>
        <?php } ?>


        <div id="modalResultado" class="modal">
            <div class="modal-content-passar-resultado">
                <span class="close" onclick="fecharModalEPassarResultado()">&times;</span>
                <h2>Passar Resultado</h2>
                <form id="formResultado" method="POST" action="../organizadores/processar_resultado_grupos.php">
                    <label class="letraMaior" for="adversario">Escolha seu adversário:</label>
                    <select class="letraNormal" name="adversario" required>
                        <option value="">Selecione</option>

                        <?php
                        $adversarios = array_unique($adversarios);

                        foreach ($adversarios as $idAdversario) {
                            $queryNome = "SELECT DISTINCT nome FROM jogadores WHERE id = '$idAdversario'";
                            $resultNome = $mysqli->query($queryNome);
                            $nomeAdversario = ($resultNome->num_rows > 0) ? $resultNome->fetch_assoc()['nome'] : 'Desconhecido';
                            echo "<option value='$idAdversario'>$nomeAdversario</option>";
                        } ?>
                    </select>
                    <br><br>
                    <label class="letraMaior" for="sets_jogador">Seus sets:</label>
                    <input type="number" name="sets_jogador" min="0" max="3" required>
                    <br><br>
                    <label class="letraMaior" for="sets_adversario">Sets do adversário:</label>
                    <input type="number" name="sets_adversario" min="0" max="3" required>
                    <br><br>
                    <div class="meio">
                        <button class="botao" type="submit">Enviar Resultado</button>
                    </div>

                </form>
            </div>
        </div>

        <div id="modalResultadoChave" class="modal">
            <div class="modal-content-passar-resultado">
                <span class="close" onclick="fecharModalEPassarResultadoChave()">&times;</span>
                <h2>Passar Resultado</h2>
                <form id="formResultadoChave" method="POST" action="../organizadores/processar_resultado_chaves.php">
                    <label class="letraMaior" for="adversario_chave">Escolha seu adversário:</label>
                    <select class="letraNormal" name="jogador2" required>
                        <option value="">Selecione</option>
                        <?php
                        $adversariosChave = [];
                        if ($podePassarResultadoChave) {
                            $queryChaves = "SELECT DISTINCT id_jogador1, id_jogador2 
                                    FROM jogos 
                                    WHERE fase = 'chave' 
                                    AND status = 'Pendente' 
                                    AND (id_jogador1 = '$id_jogador' OR id_jogador2 = '$id_jogador')";
                            $resultChaves = $mysqli->query($queryChaves);
                            while ($row = $resultChaves->fetch_assoc()) {
                                $adversariosChave[] = ($row['id_jogador1'] != $id_jogador) ? $row['id_jogador1'] : $row['id_jogador2'];
                            }
                            $adversariosChave = array_unique($adversariosChave);
                            foreach ($adversariosChave as $idAdversario) {
                                $queryNome = "SELECT nome FROM jogadores WHERE id = '$idAdversario'";
                                $resultNome = $mysqli->query($queryNome);
                                $nomeAdversario = ($resultNome->num_rows > 0) ? $resultNome->fetch_assoc()['nome'] : 'Desconhecido';
                                echo "<option value='$idAdversario'>$nomeAdversario</option>";
                            }
                        }
                        ?>
                    </select>
                    <br><br>
                    <label class="letraMaior" for="sets_jogador">Seus sets:</label>
                    <input type="number" name="sets_jogador1" min="0" max="3" required>
                    <br><br>
                    <label class="letraMaior" for="sets_adversario">Sets do adversário:</label>
                    <input type="number" name="sets_jogador2" min="0" max="3" required>
                    <br><br>
                    <div class="meio">
                        <button id="btnResultadoChave" class="botao" type="submit">Enviar Resultado</button>
                    </div>
                </form>
            </div>
        </div>

        <p class="explicacao">Lista dos jogadores da sua cidade cadastrados no sistema</p>

        <div id="modal" class="modal">
            <div>
                <?php if (!empty($mensagem_erro)) { ?>
                    <div class="erro">
                        <?php echo $mensagem_erro; ?>
                    </div>
                <?php } ?>
            </div>
            <div class="modal-content">
                <span class="close" onclick="fecharModal()">&times;</span>

                <form action="" id="formPublicar" method="POST">
                    <h2>Texto da Publicação:</h3>
                        <textarea name="publi" id="publi" class="publi" rows="9"></textarea>
                        <br>
                        <div class="data-jogo" id="data_jogo" style="display: none;"><br>
                            <label for="">Data do evento</label><br>
                            <input type="date" class="input-text-data-jogo" name="data_jogo">
                        </div>

                        <div class="div-flex">
                            <button type="submit" class="botao margin" id="btnPublicar">Publicar</button>
                        </div>
                </form>
            </div>
        </div>

        <div id="modalFeedback" class="modal">
            <div>
                <?php if (!empty($mensagem_erro)) { ?>
                    <div class="erro">
                        <?php echo $mensagem_erro; ?>
                    </div>
                <?php } ?>
            </div>
            <div class="modal-content">
                <span class="close" onclick="fecharModalFeedback()">&times;</span>

                <form action="" id="formFeedback" method="POST">
                    <h2>Sua opinião:</h3>
                        <textarea name="feedback" id="feedback" class="feedback" rows="9"></textarea>
                        <br>
                        <div class="div-flex">
                            <button type="submit" class="botao margin" id="btnFeedback">Enviar</button>
                        </div>
                </form>
            </div>
        </div>

        <?php
        if ($con->num_rows == 0) {
        ?>
            <table class="tabela">
                <tr>
                    <td class="meio">Jogadores</td>
                </tr>

                <tr>
                    <td class="meio">Não há jogadores ainda</td>
                </tr>
            </table>

        <?php
        } else {
        ?>
            <table class="tabela">
                <tr>
                    <td>Nome</td>
                    <td>Número</td>
                </tr>
                <?php
                while ($dados = $con->fetch_array()) {
                    $nome_jogador = $dados['nome'];
                    $descricao_contato = $dados['descricao_contato'];
                ?>
                    <tr>
                        <td><?php echo $nome_jogador; ?></td>
                        <td><?php echo $descricao_contato; ?></td>
                    </tr>
            <?php }
            }
            ?>
            </table>

            <p class="meio">
                <br>
                <label class="letraMaior">Categorias do Feed</label><br>
                <select name="categoria" id="categoria" class="categoria-dropdown select-style">
                    <option class="select-style option" value="">Selecione a categoria</option>
                    <option class="select-style option" value="1">Marcação de Jogos</option>
                    <option class="select-style option" value="2">Materiais</option>
                    <option class="select-style option" value="3">Tudo, menos tênis de mesa</option>
                    <option class="select-style option" value="4">Divulgação de campeonatos</option>
                </select>
            </p>

            <br>

            <div id="div-botao-publicacao" class="div-botao-publicacao">
                <a type="button" class="meio botao" href="#" onclick="abrirModalEIdentificarCategoria()">Fazer uma publicação</a>
            </div>

            <div id="publicacoes" class="publicacoes" style="display: none;">

            </div>

            <script>
                function abrirModalEPassarResultado() {
                    document.getElementById("modalResultado").style.display = "flex";
                }

                function fecharModalEPassarResultado() {
                    document.getElementById("modalResultado").style.display = "none";
                }

                function abrirModalEPassarResultadoChave() {
                    document.getElementById("modalResultadoChave").style.display = "flex";
                }

                function fecharModalEPassarResultadoChave() {
                    document.getElementById("modalResultadoChave").style.display = "none";
                }


                // Fecha o modal se clicar fora dele
                window.onclick = function(event) {
                    let modal = document.getElementById("modalResultado");
                    if (event.target === modal) {
                        fecharModal();
                    }
                };
            </script>

            <script>
                $(document).ready(function() {
                    $("#formResultado").submit(function(event) {
                        event.preventDefault();

                        $.ajax({
                            type: "POST",
                            url: "../organizadores/processar_resultado_grupos.php",
                            data: $(this).serialize(),
                            dataType: "json",
                            success: function(response) {
                                alert(response.mensagem);
                                if (response.status === "sucesso") {
                                }
                            },
                            error: function() {
                                alert("Erro ao processar o resultado.");
                            }
                        });
                    });
                });
            </script>


            <script>
                function abrirModalEIdentificarCategoria() {
                    document.getElementById("modal").style.display = "block";

                    const categoria = document.getElementById('categoria').value;
                    const campoData = document.getElementById('data_jogo');
                    if (categoria == '1' || categoria == '4') {
                        campoData.style.display = 'block';
                    } else {
                        campoData.style.display = 'none';
                    }
                }

                function fecharModal() {
                    document.getElementById("modal").style.display = "none";
                }

                function abrirModaleReceberFeedback() {
                    document.getElementById("modalFeedback").style.display = "block";
                }

                function fecharModalFeedback() {
                    document.getElementById("modalFeedback").style.display = "none";
                }

                // Fecha o modal ao clicar fora dele
                window.onclick = function(event) {
                    const modal = document.getElementById("modal");
                    if (event.target === modal) {
                        modal.style.display = "none";
                    }
                }
            </script>

            <script>
                document.getElementById('formPublicar').addEventListener('submit', function(event) {
                    event.preventDefault(); // Impede o envio padrão do formulário

                    const btnPublicar = document.getElementById('btnPublicar');
                    const publi = document.getElementById('publi').value;
                    const categoria = document.getElementById('categoria').value;
                    const categoriaDropdown = document.getElementById('categoria');
                    var dataJogo = document.querySelector('.input-text-data-jogo').value;
                    var anoJogo = dataJogo.split('-')[0]; // Divide "2024-11-25" em ["2024", "11", "25"] e pega a primeira parte
                    var dataAtualDate = new Date();
                    var ano = dataAtualDate.getFullYear();
                    var mes = String(dataAtualDate.getMonth() + 1).padStart(2, '0'); // Adiciona zero à esquerda para meses de 1 a 9
                    var dia = String(dataAtualDate.getDate()).padStart(2, '0'); // Adiciona zero à esquerda para dias de 1 a 9
                    var dataAtual = `${ano}-${mes}-${dia}`;

                    btnPublicar.disabled = true; // Desativa o botão

                    if (!publi.trim()) {
                        alert("Por favor, preencha todos os campos do formulário.");
                        btnPublicar.disabled = false; // Reativa o botão se houver erro
                        return;
                    }

                    if (dataJogo != '') {
                        if (anoJogo > 9999) {
                            alert('Ano inválido');
                            btnPublicar.disabled = false; // Reativa o botão se houver erro
                            return;
                        } else if (dataJogo < dataAtual) {
                            alert('Data do jogo é menor que a data atual');
                            btnPublicar.disabled = false; // Reativa o botão se houver erro
                            return;
                        } else if (anoJogo > ano) {
                            alert("O ano não pode ser maior que o ano atual.");
                            btnPublicar.disabled = false; // Reativa o botão se houver erro
                            return;
                        } else {
                            $.ajax({
                                type: 'POST',
                                url: 'fazer_publicacao.php', // Insira a URL apropriada
                                data: {
                                    publi: publi,
                                    categoria: categoria,
                                    data_jogo: dataJogo
                                },
                                success: function(response) {
                                    console.log("Publicação enviada com sucesso!");
                                    btnPublicar.disabled = false;
                                    document.getElementById('formPublicar').reset();
                                    fecharModal();
                                    validaCategoriaFeed();
                                },
                                error: function(xhr, status, error) {
                                    console.error("Erro ao enviar publicação:", error);
                                    alert("Erro ao enviar publicação. Tente novamente.");
                                    btnPublicar.disabled = false; // Reativa o botão após erro
                                }
                            });
                        }
                    } else {
                        $.ajax({
                            type: 'POST',
                            url: 'fazer_publicacao.php', // Insira a URL apropriada
                            data: {
                                publi: publi,
                                categoria: categoria
                            },
                            success: function(response) {
                                console.log("Publicação enviada com sucesso!");
                                btnPublicar.disabled = false;
                                document.getElementById('formPublicar').reset();
                                fecharModal();
                                validaCategoriaFeed();
                            },
                            error: function(xhr, status, error) {
                                console.error("Erro ao enviar publicação:", error);
                                alert("Erro ao enviar publicação. Tente novamente.");
                                btnPublicar.disabled = false; // Reativa o botão após erro
                            }
                        });
                    }



                });
            </script>

            <script>
                document.getElementById('formFeedback').addEventListener('submit', function(event) {
                    event.preventDefault(); // Impede o envio padrão do formulário

                    const btnFeedback = document.getElementById('btnFeedback');
                    const feedback = document.getElementById('feedback').value;

                    btnFeedback.disabled = true; // Desativa o botão

                    if (!feedback.trim()) {
                        alert("Por favor, preencha todos os campos do formulário.");
                        btnFeedback.disabled = false; // Reativa o botão se houver erro
                        return;
                    }

                    $.ajax({
                        type: 'POST',
                        url: 'fazer_feedback.php',
                        data: {
                            feedback: feedback
                        },
                        success: function(response) {
                            btnFeedback.disabled = false;
                            document.getElementById('formFeedback').reset();
                            fecharModalFeedback();
                            validaCategoriaFeed();
                        },
                        error: function(xhr, status, error) {
                            console.error("Erro ao enviar feedback:", error);
                            alert("Erro ao enviar publicação. Tente novamente.");
                            btnFeedback.disabled = false; // Reativa o botão após erro
                        }
                    });
                });
            </script>

            <script>
                function enviarResposta(idPublicacao) {
                    const textAreaResposta = document.getElementById(`texto-resposta-${idPublicacao}`);
                    const textoResposta = document.getElementById(`texto-resposta-${idPublicacao}`).value;
                    if (textAreaResposta != '') {
                        textAreaResposta.value = '';
                    }
                    if (!textoResposta.trim()) {
                        alert("Digite uma resposta antes de enviar.");
                        return;
                    }

                    $.ajax({
                        url: "salvar_resposta.php",
                        type: "POST",
                        data: {
                            id_publicacao: idPublicacao,
                            texto_resposta: textoResposta
                        },
                        dataType: "json", // Especifica que a resposta deve ser tratada como JSON
                        success: function(response) {
                            if (response.success) {

                                const nomeJogadorLogado = <?= json_encode($nome_jogador_logado) ?>;
                                const nivelJogadorLogado = <?= json_encode($texto_nivel) ?>;
                                const descricaoContatoJogadorLogado = <?= json_encode($descricao_contato_jogador_logado) ?>;
                                const respostaContainer = document.querySelector(`.resposta-container-${idPublicacao}`);
                                const dataAtual = new Date();
                                const dia = String(dataAtual.getDate()).padStart(2, '0');
                                const mes = String(dataAtual.getMonth() + 1).padStart(2, '0');
                                const ano = dataAtual.getFullYear();
                                const hora = dataAtual.toLocaleTimeString([], {
                                    hour: '2-digit',
                                    minute: '2-digit'
                                });

                                const data = `${dia}/${mes}/${ano}`;

                                // Verifica se já existe alguma resposta
                                if (!respostaContainer.querySelector('.feed-item-header')) {
                                    respostaContainer.innerHTML += `<p>Respostas:</p>`;
                                }

                                respostaContainer.innerHTML += `
                                    <div class="resposta-item">
                                        <h3>${nomeJogadorLogado} (${nivelJogadorLogado})</h3>
                                        <p>Contato: ${descricaoContatoJogadorLogado}</p>
                                        <p>${textoResposta}</p>
                                        <div class="feed-item-footer">
                                            <span>${data}</span> 
                                            <span>${hora}</span>
                                        </div>
                                        <hr>
                                    </div>

                                `;
                            } else {
                                console.log("Erro ao enviar resposta.");
                            }
                        },
                        error: function(xhr, status, error) {
                            console.log("Erro na requisição:", error);
                            console.log("Detalhes:", xhr.responseText); // Veja a resposta completa da requisição
                        }
                    });

                    const respostaContainer = document.querySelector(`.resposta-container-${idPublicacao}`);
                    const botao = document.querySelector(`.botao[onclick="alternarRespostas(${idPublicacao})"]`);

                    $.ajax({
                        type: 'POST',
                        url: 'contar_respostas.php',
                        data: {
                            id: idPublicacao
                        },
                        success: function(response) {
                            quant_respostas = response;

                            // Verificar se as respostas estão sendo exibidas
                            if (respostaContainer.style.display === "none" || respostaContainer.style.display === "") {
                                botao.textContent = `Mostrar respostas (${quant_respostas})`;
                            } else {
                                botao.textContent = `Ocultar respostas (${quant_respostas})`;
                            }

                        },
                        error: function(xhr, status, error) {
                            console.error(xhr.responseText);
                            alert('Erro ao buscar respostas. Verifique o console para mais informações.');
                        }
                    });


                }
            </script>

            <script>
                $(document).ready(function() {
                    $(document).on('click', '.excluirPublicacao', function(e) {
                        e.preventDefault();
                        var codigoPublicacao = $(this).data('codigo');
                        var botaoClicado = $(this); // Armazena a referência do botão clicado

                        var confirmacao = confirm('Tem certeza que deseja excluir esta publicação?');
                        if (confirmacao) {
                            $.ajax({
                                type: 'POST',
                                url: '../admin/publicacoes/excluir_publicacao.php',
                                data: {
                                    id: codigoPublicacao
                                },
                                success: function(response) {
                                    if (response === 'success') {
                                        alert('Publicação excluída com sucesso.');
                                        // Remove o item excluído da tela sem recarregar a página
                                        botaoClicado.closest('.feed-item').remove();
                                    } else {
                                        alert('Erro ao excluir publicação.');
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error(xhr.responseText);
                                    alert('Erro ao excluir publicação. Verifique o console para mais informações.');
                                }
                            });
                        }
                    });
                });
            </script>



            <script>
                function validaCategoriaFeed() {
                    const categoriaDropdown = document.getElementById('categoria');
                    const botaoPublicacao = document.getElementById('div-botao-publicacao');
                    const publicacoesDiv = document.getElementById('publicacoes');
                    const estado = <?= json_encode($estado) ?>;
                    const cidade = <?= json_encode($cidade) ?>;

                    if (categoriaDropdown.value == "" || categoriaDropdown.value == "selecione categoria") {
                        botaoPublicacao.style.display = 'none';
                        publicacoesDiv.style.display = 'none';
                    } else {
                        const categoria = categoriaDropdown.options[categoriaDropdown.selectedIndex].text;
                        const indiceCategoria = categoriaDropdown.value;
                        if (categoria == 'Marcação de Jogos' || categoria == 'Materiais' || categoria == 'Tudo, menos tênis de mesa' || categoria == 'Divulgação de campeonatos') {
                            botaoPublicacao.style.display = 'flex';
                            botaoPublicacao.style.alignItems = 'center';
                            botaoPublicacao.style.justifyContent = 'center';
                            publicacoesDiv.style.display = 'block';
                        } else {
                            botaoPublicacao.style.display = 'none';
                            publicacoesDiv.style.display = 'none';
                        }

                        $.ajax({
                            type: 'POST',
                            url: 'carregar_publicacoes.php',
                            data: {
                                indiceCategoria: indiceCategoria,
                                estado: estado,
                                cidade: cidade
                            },
                            success: function(response) {
                                publicacoesDiv.innerHTML = response;
                            },
                            error: function(xhr, status, error) {
                                console.error(xhr.responseText);
                                alert('Erro ao excluir publicação. Verifique o console para mais informações.');
                            }
                        });
                    }
                }
            </script>

            <script>
                document.getElementById('categoria').addEventListener('change', function() {
                    validaCategoriaFeed();
                });
            </script>

            <script>
                function alternarRespostas(publicacaoId) {
                    const respostaContainer = document.querySelector(`.resposta-container-${publicacaoId}`);
                    const botao = document.querySelector(`.botao[onclick="alternarRespostas(${publicacaoId})"]`);
                    let quant_respostas = 0;
                    $.ajax({
                        type: 'POST',
                        url: 'contar_respostas.php',
                        data: {
                            id: publicacaoId
                        },
                        success: function(response) {
                            quant_respostas = response;

                            // Verificar se as respostas estão sendo exibidas
                            if (respostaContainer.style.display === "none" || respostaContainer.style.display === "") {
                                // Mostrar respostas
                                respostaContainer.style.display = "block";
                                botao.textContent = `Ocultar respostas (${quant_respostas})`;
                            } else {
                                // Ocultar respostas
                                respostaContainer.style.display = "none";
                                botao.textContent = `Mostrar respostas (${quant_respostas})`;
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error(xhr.responseText);
                            alert('Erro ao buscar respostas. Verifique o console para mais informações.');
                        }
                    });
                }
            </script>

            <script>
                const categoriaAtual = <?= json_encode($categoria_atual) ?>;
                const categoriaDropdown = document.getElementById('categoria');

                if (categoriaAtual != '') {
                    categoriaDropdown.value = categoriaAtual;
                    categoriaDropdown.dispatchEvent(new Event('change'));
                }
            </script>

            <script>
                $(document).ready(function() {
                    $("#formResultadoChave").submit(function(event) {
                        event.preventDefault();

                        var botao = $("#btnResultadoChave");

                        botao.prop('disabled', true); // desativa botão

                        $.ajax({
                            type: "POST",
                            url: "../organizadores/processar_resultado_chaves.php",
                            data: $(this).serialize(),
                            dataType: "json",
                            success: function(response) {
                                if (response.status === "sucesso") {
                                    alert(response.mensagem);
                                } else {
                                    alert(response.mensagem);
                                    botao.prop('disabled', false); // libera botão se deu erro
                                }
                            },
                            error: function() {
                                alert("Erro ao processar o resultado da chave.");
                                botao.prop('disabled', false); // libera botão se deu erro
                            }
                        });
                    });
                });
            </script>


    </body>

    </html>