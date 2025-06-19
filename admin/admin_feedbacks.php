<?php
include('../protect.php');
include('../conexao.php');

$consulta = "SELECT * FROM feedbacks";
$con = $mysqli->query($consulta) or die($mysqli->error);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../css/admin_feedbacks.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Lista de Feedbacks</title>
</head>

<body>
    <a class="botao" href="dashboard_admin.php">Voltar</a>
    <br><br><br>

    <?php
    if ($con->num_rows == 0) {
    ?>
        <table class="tabela">
            <tr>
                <td class="meio">Feedbacks</td>
            </tr>

            <tr>
                <td>Não há feedbacks ainda</td>
            </tr>
        </table>

    <?php
    } else {
    ?>
        <table class="tabela">
            <tr>
                <td>Nome</td>
                <td>Feedback</td>
                <td class="meio">Ação</td>
            </tr>
            <?php
            while ($dados = $con->fetch_array()) {
                $nome = $dados['nome_jogador'];
                $feedback = $dados['feedback'];
            ?>
                <tr>
                    <td><?php echo $nome; ?></td>
                    <td><?php echo $feedback; ?></td>
                    <td class="acao">
                        <a class="tirarUnderline" href="feedbacks/atualizar_feedback.php?codigo=<?php echo $dados["id"]; ?>">
                            <img width="30" height="30" src="https://img.icons8.com/ios-glyphs/30/change--v1.png" alt="change--v1" />
                        </a>
                        <a href="#" class="excluirFeedback" data-codigo=<?php echo $dados["id"]; ?>>
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

                $('.excluirFeedback').click(function(e) {
                    e.preventDefault();
                    var codigoFeedback = $(this).data('codigo');

                    var confirmacao = confirm('Tem certeza que deseja excluir este Feedback?');
                    if (confirmacao) {
                        console.log('Confirmação recebida, enviando solicitação AJAX...');
                        $.ajax({
                            type: 'POST',
                            url: 'feedbacks/excluir_feedback.php',
                            data: {
                                id: codigoFeedback
                            },
                            success: function(response) {
                                alert(response);
                                location.reload();
                            },
                            error: function(xhr, status, error) {
                                console.error(xhr.responseText);
                                alert('Erro ao excluir feedback. Verifique o console para mais informações.');
                            }
                        });
                    }
                });
            });
        </script>
</body>

</html>