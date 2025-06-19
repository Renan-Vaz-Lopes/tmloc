<?php
include('../protect.php');
include('../conexao.php');

if (isset($_GET['codigo'])) {
    $id = $_GET['codigo'];
    $consulta = "SELECT * FROM respostas WHERE id_publicacao=$id";
    $con = $mysqli->query($consulta) or die($mysqli->error);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../css/admin_respostas.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Lista de Respostas</title>
</head>

<body>
    <a class="botao" href="admin_publicacoes.php">Voltar</a>
    <br><br><br>

    <?php
    if (isset($con) && $con->num_rows == 0) {
    ?>
        <table class="tabela">
            <tr>
                <td class="meio">Respostas</td>
            </tr>

            <tr>
                <td>Não há respostas ainda</td>
            </tr>
        </table>

    <?php
    } else {
    ?>
        <table class="tabela">
            <tr>
                <td>Texto da resposta</td>
                <td>Data</td>
                <td>Hora</td>
                <td class="meio">Ação</td>
            </tr>
            <?php
            if (isset($con)) {
                while ($dados = $con->fetch_array()) {
                    $texto_resposta = $dados['texto_resposta'];
                    $data_resposta = $dados['data_resposta'];
                    $hora_resposta = $dados['hora_resposta'];
            ?>
                    <tr>
                        <td><?php echo $texto_resposta; ?></td>
                        <td><?php echo $data_resposta; ?></td>
                        <td><?php echo $hora_resposta; ?></td>
                        <td class="acao">
                            <a class="tirarUnderline" href="respostas/atualizar_resposta.php?codigo=<?php echo $dados["id"]; ?>">
                                <img width="30" height="30" src="https://img.icons8.com/ios-glyphs/30/change--v1.png" alt="change--v1" />
                            </a>
                            <a href="#" class="excluirResposta" data-codigo=<?php echo $dados["id"]; ?>>
                                <img width="30" height="30" src="https://img.icons8.com/material-sharp/30/filled-trash.png" alt="filled-trash" />
                            </a>
                            <a href="admin/admin_respostas.php?codigo=<?php echo $dados["id"]; ?>"></a>
                        </td>
                    </tr>
        <?php }
            }
        }
        ?>
        <br><br>
        </table>

        <script>
            $(document).ready(function() {

                $('.excluirResposta').click(function(e) {
                    e.preventDefault();
                    var codigoResposta = $(this).data('codigo');

                    var confirmacao = confirm('Tem certeza que deseja excluir esta resposta?');
                    if (confirmacao) {
                        console.log('Confirmação recebida, enviando solicitação AJAX...');
                        $.ajax({
                            type: 'POST',
                            url: 'respostas/excluir_resposta.php',
                            data: {
                                id: codigoResposta
                            },
                            success: function(response) {
                                alert(response);
                                location.reload();
                            },
                            error: function(xhr, status, error) {
                                console.error(xhr.responseText);
                                alert('Erro ao excluir resposta. Verifique o console para mais informações.');
                            }
                        });
                    }
                });
            });
        </script>
</body>

</html>