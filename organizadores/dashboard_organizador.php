<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include('../protect.php');
include('../conexao.php');
date_default_timezone_set('America/Sao_Paulo');

// Buscar categorias disponíveis
$queryCategorias = "SELECT DISTINCT categoria FROM grupos ORDER BY categoria";
$resultCategorias = $mysqli->query($queryCategorias);
$categorias = [];
while ($row = $resultCategorias->fetch_assoc()) {
    $categorias[] = $row['categoria'];
}

$consulta = "SELECT c.* 
             FROM campeonatos c
             INNER JOIN organizadores o ON c.id_organizador = o.id
             WHERE o.email = '{$_SESSION['email']}'";

$con = $mysqli->query($consulta) or die($mysqli->error);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../css/dashboard_organizador.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Dashboard do Organizador</title>
</head>

<body>
    <a type="button" class="meio botao2" href="../logout.php">Deslogar</a>
    <br><br><br>

    <div class="meio">
        <a type="button" class="botao2" href="cadastrar_campeonato.php">Criar Campeonato</a>
    </div>

    <br>

    <div class="meio botoesResultado">
        <button class="botao" onclick="abrirModalResultadoGrupo()">Passar Resultado do Grupo</button>
        <button class="botao" onclick="abrirModalResultadoChave()">Passar Resultado da Chave</button>
    </div>

    <div id="modalResultadoGrupo" class="modal">
        <div class="modal-content-passar-resultado-grupo">
            <span class="close" onclick="fecharModalResultadoGrupo()">&times;</span>
            <h2>Passar Resultado Do Grupo</h2>
            <form id="formResultadoGrupo" method="POST" action="#">
                <label for="categoria">Categoria:</label>
                <select name="categoria" id="categoriaSelect" required>
                    <option value="">Selecione</option>
                    <?php foreach ($categorias as $categoria) {
                        echo "<option value='$categoria'>$categoria</option>";
                    } ?>
                </select>
                <br><br>

                <label for="grupo">Grupo:</label>
                <select name="grupo" id="grupoSelect" required>
                    <option value="">Selecione a categoria primeiro</option>
                </select>
                <br><br>

                <label for="jogador1">Jogador 1:</label>
                <select name="jogador1" id="jogador1Select" required></select>
                <br><br>

                <label for="jogador2">Jogador 2:</label>
                <select name="jogador2" id="jogador2Select" required></select>
                <br><br>

                <label for="sets_jogador1">Sets Jogador 1:</label>
                <input type="number" name="sets_jogador1" min="0" max="3" required>
                <br><br>

                <label for="sets_jogador2">Sets Jogador 2:</label>
                <input type="number" name="sets_jogador2" min="0" max="3" required>
                <br><br>

                <div class="meio">
                    <button type="submit" class="botao">Enviar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalResultadoChave" class="modal">
        <div class="modal-content-passar-resultado-chave">
            <span class="close" onclick="fecharModalResultadoChave()">&times;</span>
            <h2>Passar Resultado da Chave</h2>
            <form id="formResultadoChave" method="POST" action="#">

                <label for="categoriaChaveSelect">Categoria:</label>
                <select name="categoria" id="categoriaChaveSelect" required>
                    <option value="">Selecione uma categoria</option>
                    <?php foreach ($categorias as $categoria) {
                        echo "<option value='$categoria'>$categoria</option>";
                    } ?>
                </select>
                <br><br>


                <label for="jogador1_chave">Jogador 1:</label>
                <select name="jogador1" id="jogador1SelectChave" required></select>
                <br><br>

                <label for="jogador2_chave">Jogador 2:</label>
                <select name="jogador2" id="jogador2SelectChave" required></select>
                <br><br>

                <label for="sets_jogador1_chave">Sets Jogador 1:</label>
                <input type="number" name="sets_jogador1" min="0" max="3" required>
                <br><br>

                <label for="sets_jogador2_chave">Sets Jogador 2:</label>
                <input type="number" name="sets_jogador2" min="0" max="3" required>
                <br><br>

                <div class="meio">
                    <button type="submit" class="botao">Enviar</button>
                </div>
            </form>
        </div>
    </div>


    <?php
    if ($con->num_rows == 0) {
    ?>
        <table class="tabela">
            <tr>
                <td class="meio">Campeonatos</td>
            </tr>

            <br><br>

            <tr>
                <td class="meio">Não há campeonatos ainda</td>
            </tr>
        </table>

    <?php
    } else {
    ?>
        <table class="tabela">
            <tr>
                <td>Descrição</td>
                <td class="meio">Ação</td>
            </tr>
            <?php
            while ($dado = $con->fetch_array()) { ?>
                <tr>
                    <td><?php echo $dado["descricao"]; ?></td>
                    <td class="acao">
                        <button class="iniciarCampeonato botao" data-codigo="<?php echo $dado["id"]; ?>">
                            Iniciar Campeonato
                        </button>
                        <a href="#" class="excluirCampeonato" data-codigo="<?php echo $dado["id"]; ?>">
                            <img width="30" height="30" src="https://img.icons8.com/material-sharp/30/filled-trash.png" alt="filled-trash" />
                        </a>
                    </td>
                </tr>
        <?php }
        }
        ?>
        <br><br>
        </table>

        <div class="meio">
            <h3>Chaves disponíveis:</h3>
            <?php
            $caminhoChaves = '../uploads/chaves/';
            if (is_dir($caminhoChaves)) {
                $arquivos = array_diff(scandir($caminhoChaves), ['.', '..']);

                if (empty($arquivos)) {
                    echo "<p>Nenhuma chave disponível ainda.</p>";
                } else {
                    foreach ($arquivos as $arquivo) {
                        if (pathinfo($arquivo, PATHINFO_EXTENSION) === 'xlsx') {
                            echo "<a class='botao' href='$caminhoChaves$arquivo?v=" . time() . "' download>$arquivo</a><br><br>";
                        }
                    }
                }
            }
            ?>
        </div>

        <script>
            $(document).ready(function() {

                $('.excluirCampeonato').click(function(e) {
                    e.preventDefault();
                    var codigoCampeonato = $(this).data('codigo');
                    console.log('ID do campeonato:', codigoCampeonato);

                    var confirmacao = confirm('Tem certeza que deseja excluir este campeonato?');
                    if (confirmacao) {
                        $.ajax({
                            type: 'POST',
                            url: 'excluir_campeonato.php',
                            data: {
                                id: codigoCampeonato
                            },
                            success: function(response) {
                                location.reload();
                            },
                            error: function(xhr, status, error) {
                                console.error(xhr.responseText);
                                alert('Erro ao excluir campeonato. Verifique o console para mais informações.');
                            }
                        });
                    } else {
                        console.log('Exclusão cancelada pelo campeonato.');
                    }
                });


            });
        </script>

        <script>
            $(document).ready(function() {
                $('.iniciarCampeonato').click(function(e) {
                    e.preventDefault();
                    var codigoCampeonato = $(this).data('codigo');

                    if (confirm('Deseja realmente iniciar este campeonato? Os jogadores dos primeiros jogos receberão mensagens no Email.')) {
                        $.ajax({
                            type: 'POST',
                            url: 'iniciar_campeonato.php',
                            data: {
                                id: codigoCampeonato
                            },
                            dataType: "json", // Espera um JSON de resposta
                            success: function(response) {
                                if (response.status === "sucesso") {
                                    alert(response.mensagem);
                                } else {
                                    alert("Erro: " + response.mensagem);
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error(xhr.responseText);
                                alert('Erro ao iniciar campeonato. Verifique o console para mais informações.');
                            }
                        });
                    }
                });
            });
        </script>

        <script>
            function abrirModalResultadoGrupo() {
                document.getElementById("modalResultadoGrupo").style.display = "flex";
            }

            function fecharModalResultadoGrupo() {
                document.getElementById("modalResultadoGrupo").style.display = "none";
            }

            function abrirModalResultadoChave() {
                document.getElementById("modalResultadoChave").style.display = "flex";
            }

            function fecharModalResultadoChave() {
                document.getElementById("modalResultadoChave").style.display = "none";
            }

            $.post('carregar_categorias.php', function(data) {
                $('#categoriaSelect').html(data);
            });

            // Carrega os grupos ao mudar a categoria
            $('#categoriaSelect').on('change', function() {
                const categoria = $(this).val();
                $.post('carregar_grupos.php', {
                    categoria: categoria
                }, function(data) {
                    $('#grupoSelect').html(data);
                    $('#jogador1Select').html('<option value="">Selecione o grupo primeiro</option>');
                    $('#jogador2Select').html('<option value="">Selecione o grupo primeiro</option>');
                });
            });

            // Carrega os jogadores ao mudar o grupo
            $('#grupoSelect').on('change', function() {
                const grupo = $(this).val();
                $.post('carregar_jogadores_grupo.php', {
                    grupo: grupo
                }, function(data) {
                    $('#jogador1Select').html(data);
                    $('#jogador2Select').html(data);
                });
            });

            // Submeter o resultado
            $('#formResultadoGrupo').on('submit', function(e) {
                e.preventDefault();
                $.post('processar_resultado_grupos.php', $(this).serialize(), function(response) {
                    alert(response.mensagem);
                    if (response.status === 'sucesso') {
                    }
                }, 'json');
            });
        </script>

        <script>
            // Carrega os jogadores que podem passar resultado de chave
            function carregarJogadoresChave(categoria) {
                $.post('carregar_jogadores_chave.php', {
                    categoria: categoria
                }, function(data) {
                    const jogadores = JSON.parse(data);
                    console.log(jogadores)
                    const jogador1Select = $('#jogador1SelectChave');
                    const jogador2Select = $('#jogador2SelectChave');

                    jogador1Select.empty().append('<option value="">Selecione</option>');
                    jogador2Select.empty().append('<option value="">Será preenchido automaticamente</option>');

                    jogadores.forEach(function(j) {
                        jogador1Select.append(
                            '<option value="' + j.id + '" data-parceiro="' + j.parceiro_id + '">' + j.nome + '</option>');
                    });

                    jogador1Select.off('change').on('change', function() {
                        const parceiroId = $(this).find(':selected').data('parceiro');
                        const parceiro = jogadores.find(j => j.id == parceiroId);
                        if (parceiro) {
                            jogador2Select.html('<option value="' + parceiro.id + '">' + parceiro.nome + '</option>').prop('disabled', true);
                            jogador2Select.trigger('change'); // só pra garantir que o select se atualize visualmente
                        } else {
                            jogador2Select.html('<option value="">Não encontrado</option>');
                        }
                    });
                });
            }

            $('#categoriaChaveSelect').on('change', function() {
                const categoria = $(this).val();
                carregarJogadoresChave(categoria);
            });


            $('#formResultadoChave').on('submit', function(e) {
                e.preventDefault();

                $('#jogador2SelectChave').prop('disabled', false);

                $.post('processar_resultado_chaves.php', $(this).serialize(), function(response) {
                    alert(response.mensagem);
                    if (response.status === 'sucesso') {
                    }
                }, 'json');
            });
        </script>

</body>

</html>