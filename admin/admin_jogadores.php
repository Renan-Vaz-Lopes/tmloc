<?php
include('../protect.php');
include('../conexao.php');

$consulta = "SELECT * FROM jogadores";
$con = $mysqli->query($consulta) or die($mysqli->error);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../css/admin_jogadores.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Lista de jogadores</title>
</head>

<body>
    <a class="botao" href="dashboard_admin.php">Voltar</a>
    <br><br><br>

    <?php
    if ($con->num_rows == 0) {
    ?>
        <table class="tabela">
            <tr>
                <td class="meio">Jogadores</td>
            </tr>

            <tr>
                <td>Não há jogadores ainda</td>
            </tr>
        </table>

    <?php
    } else {
    ?>
        <table class="tabela">
            <tr>
                <td>Nome</td>
                <td>Número</td>
                <td class="meio">Ação</td>
            </tr>
            <?php
            while ($dados = $con->fetch_array()) {
                $nome = $dados['nome'];
                $descricao_contato = $dados['descricao_contato'];
            ?>
                <tr>
                    <td><?php echo $nome; ?></td>
                    <td><?php echo $descricao_contato; ?></td>
                    <td class="acao">
                        <a class="tirarUnderline" href="jogadores/atualizar_jogador.php?codigo=<?php echo $dados["id"]; ?>">
                            <img width="30" height="30" src="https://img.icons8.com/ios-glyphs/30/change--v1.png" alt="change--v1" />
                        </a>
                        <a href="#" class="excluirCt" data-codigo=<?php echo $dados["id"]; ?>>
                            <img width="30" height="30" src="https://img.icons8.com/material-sharp/30/filled-trash.png" alt="filled-trash" />
                        </a>
                    </td>
                </tr>
        <?php }
        }
        ?>
        <br><br>
        </table>

        <script>
            $(document).ready(function() {

                $('.excluirCt').click(function(e) {
                    e.preventDefault();
                    var codigoJogador = $(this).data('codigo');

                    var confirmacao = confirm('Tem certeza que deseja excluir este jogador? As publicações associadas a ele também serão excluídas');
                    if (confirmacao) {
                        console.log('Confirmação recebida, enviando solicitação AJAX...');
                        $.ajax({
                            type: 'POST',
                            url: 'jogadores/excluir_jogador.php',
                            data: {
                                id: codigoJogador
                            },
                            success: function(response) {
                                alert(response);
                                location.reload();
                            },
                            error: function(xhr, status, error) {
                                console.error(xhr.responseText);
                                alert('Erro ao excluir jogador. Verifique o console para mais informações.');
                            }
                        });
                    }
                });
            });
        </script>
</body>

</html>