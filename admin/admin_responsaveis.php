<?php
include('../conexao.php');
include('../protect.php');

$consulta = "SELECT * FROM responsaveis WHERE email != 'admin@gmail.com'";
$con = $mysqli->query($consulta) or die($mysqli->error);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../css/admin_responsaveis.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Menu de Responsaveis</title>
</head>

<body>
    <a class="botao" href="dashboard_admin.php">Voltar</a>
    <br><br>

    <div class="meio">
        <a class="botao" href="responsaveis/cadastrar_responsavel.php">Cadastrar Responsável por um CT</a>
    </div>

    <?php
    if ($con->num_rows == 0) {
    ?>
        <table class="tabela">
            <tr>
                <td class="meio">Responsáveis por CTs</td>
            </tr>

            <tr>
                <td>Não há Responsáveis por CTs ainda</td>
            </tr>
        </table>

    <?php
    } else {
    ?>
        <table class="tabela">
            <tr>
                <td>Email</td>
                <td class="meio">Ação</td>
            </tr>
            <?php
            while ($dado = $con->fetch_array()) { ?>
                <tr>
                    <td><?php echo $dado["email"]; ?></td>
                    <td class="acao">
                        <a class="tirarUnderline" href="responsaveis/atualizar_responsavel.php?codigo=<?php echo $dado["id"]; ?>">
                            <img width="30" height="30" src="https://img.icons8.com/ios-glyphs/30/change--v1.png" alt="change--v1" />
                        </a>
                        <a href="#" class="excluirResponsavel" data-codigo="<?php echo $dado["id"]; ?>">
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
        console.log('Documento pronto!');

        $('.excluirResponsavel').click(function(e) {
            e.preventDefault();
            var codigoResponsavel = $(this).data('codigo');
            console.log('ID do responsável:', codigoResponsavel);

            var confirmacao = confirm('Tem certeza que deseja excluir este responsável?');
            if (confirmacao) {
                console.log('Confirmação recebida, enviando solicitação AJAX...');
                $.ajax({
                    type: 'POST',
                    url: 'responsaveis/excluir_responsavel.php',
                    data: {
                        id: codigoResponsavel
                    },
                    success: function(response) {
                        location.reload();
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                        alert('Erro ao excluir responsável. Verifique o console para mais informações.');
                    }
                });
            } else {
                console.log('Exclusão cancelada pelo responsável.');
            }
        });
    });
</script>


</html>