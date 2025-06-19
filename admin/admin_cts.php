<?php
include('../conexao.php');
include('../protect.php');

$consulta = "SELECT * FROM cts";
$con = $mysqli->query($consulta) or die($mysqli->error);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../css/admin_cts.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Menu de Cts</title>
</head>

<body>
    <a href="dashboard_admin.php" class="botao">Voltar</a>
    <br><br>

    <div class="meio">
        <a href="cts/cadastrar_ct.php" class="botao">Cadastrar CT</a>
    </div>

    <?php
    if ($con->num_rows == 0) {
    ?>
        <table class="tabela">
            <tr>
                <td class="meio">CTs</td>
            </tr>

            <tr>
                <td>Não há cts ainda</td>
            </tr>
        </table>

    <?php
    } else {
    ?>
        <table class="tabela">
            <tr>
                <td>Nome</td>
                <td>Endereço</td>
                <td>Telefone</td>
                <td class="meio">Ação</td>
            </tr>
            <?php
            while ($dado = $con->fetch_array()) { ?>
                <tr>
                    <td><?php echo $dado["nome"]; ?></td>
                    <td><?php echo $dado["endereco"]; ?></td>
                    <td><?php echo $dado["telefone"]; ?></td>
                    <td class="acao">
                        <a class="tirarUnderline" href="cts/atualizar_ct.php?codigo=<?php echo $dado["id"]; ?>">
                            <img width="30" height="30" src="https://img.icons8.com/ios-glyphs/30/change--v1.png" alt="change--v1" />
                        </a>
                        <a href="#" class="excluirCt" data-codigo=<?php echo $dado["id"]; ?>>
                            <img width="30" height="30" src="https://img.icons8.com/material-sharp/30/filled-trash.png" alt="filled-trash" />
                        </a>
                    </td>
                </tr>
        <?php }
        }
        ?>
        <br><br>
        </table>
</body>

<script>
    $(document).ready(function() {

        $('.excluirCt').click(function(e) {
            e.preventDefault();
            var codigoCT = $(this).data('codigo');

            var confirmacao = confirm('Tem certeza que deseja excluir este CT?');
            if (confirmacao) {
                console.log('Confirmação recebida, enviando solicitação AJAX...');
                $.ajax({
                    type: 'POST',
                    url: 'cts/excluir_ct.php',
                    data: {
                        id: codigoCT
                    },
                    success: function(response) {
                        alert(response);
                        location.reload();
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                        alert('Erro ao excluir ct. Verifique o console para mais informações.');
                    }
                });
            }
        });
    });
</script>

</html>