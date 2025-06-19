<?php
include('../protect.php');
include('../conexao.php');

$consulta = "SELECT * FROM publicacoes";
$con = $mysqli->query($consulta) or die($mysqli->error);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../css/admin_publicacoes.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Lista de Publicações</title>
</head>

<body>
    <a class="botao" href="dashboard_admin.php">Voltar</a>
    <br><br><br>

    <?php
    if ($con->num_rows == 0) {
    ?>
        <table class="tabela">
            <tr>
                <td class="meio">Publicações</td>
            </tr>

            <tr>
                <td>Não há publicações ainda</td>
            </tr>
        </table>

    <?php
    } else {
    ?>
        <table class="tabela">
            <tr>
                <td>Nome</td>
                <td>Data</td>
                <td>Hora</td>
                <td class="meio">Ação</td>
            </tr>
            <?php
            while ($dados = $con->fetch_array()) {
                $nome = $dados['nome'];
                $data_publi = $dados['data_publi'];
                $hora_publi = $dados['hora_publi'];
            ?>
                <tr>
                    <td><?php echo $nome; ?></td>
                    <td><?php echo $data_publi; ?></td>
                    <td><?php echo $hora_publi; ?></td>
                    <td class="acao">
                        <a class="tirarUnderline" href="publicacoes/atualizar_publicacao.php?codigo=<?php echo $dados["id"]; ?>">
                            <img width="30" height="30" src="https://img.icons8.com/ios-glyphs/30/change--v1.png" alt="change--v1" />
                        </a>
                        <a href="#" class="excluirPublicacao" data-codigo=<?php echo $dados["id"]; ?>>
                            <img width="30" height="30" src="https://img.icons8.com/material-sharp/30/filled-trash.png" alt="filled-trash" />
                        </a>
                        <a href="admin_respostas.php?codigo=<?php echo $dados["id"]; ?>">
                            <img width="30" height="30" src="https://img.icons8.com/ios-glyphs/30/comments.png" alt="comments" />
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

                $('.excluirPublicacao').click(function(e) {
                    e.preventDefault();
                    var codigoPublicacao = $(this).data('codigo');

                    var confirmacao = confirm('Tem certeza que deseja excluir esta publicação?');
                    if (confirmacao) {
                        console.log('Confirmação recebida, enviando solicitação AJAX...');
                        $.ajax({
                            type: 'POST',
                            url: 'publicacoes/excluir_publicacao.php',
                            data: {
                                id: codigoPublicacao
                            },
                            success: function(response) {
                                alert(response);
                                location.reload();
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
</body>

</html>