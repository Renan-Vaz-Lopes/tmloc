<?php
include('../conexao.php');
include('../protect.php');

$consulta = "SELECT * FROM organizadores";
$con = $mysqli->query($consulta) or die($mysqli->error);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../css/admin_organizadores.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Menu de Organizadores</title>
</head>

<body>
    <a href="dashboard_admin.php" class="botao">Voltar</a>
    <br><br>

    <!-- <div class="meio">
        <a href="organizadores/cadastrar_organizador.php" class="botao">Cadastrar Organizador</a>
    </div> -->

    <?php
    if ($con->num_rows == 0) {
    ?>
        <table class="tabela">
            <tr>
                <td class="meio">Organizadores</td>
            </tr>

            <tr>
                <td>Não há organizadores ainda</td>
            </tr>
        </table>

    <?php
    } else {
    ?>
        <table class="tabela">
            <tr>
                <td>Nome</td>
                <td class="meio">Ação</td>
            </tr>
            <?php
            while ($dado = $con->fetch_array()) { ?>
                <tr>
                    <td><?php echo $dado["nome"]; ?></td>
                    <td class="acao">
                        <a class="tirarUnderline" href="organizadores/atualizar_organizador.php?codigo=<?php echo $dado["id"]; ?>">
                            <img width="30" height="30" src="https://img.icons8.com/ios-glyphs/30/change--v1.png" alt="change--v1" />
                        </a>
                        <a href="#" class="excluirOrganizador" data-codigo=<?php echo $dado["id"]; ?>>
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

        $('.excluirOrganizador').click(function(e) {
            e.preventDefault();
            var codigoOrganizador = $(this).data('codigo');

            var confirmacao = confirm('Tem certeza que deseja excluir este Organizador?');
            if (confirmacao) {
                console.log('Confirmação recebida, enviando solicitação AJAX...');
                $.ajax({
                    type: 'POST',
                    url: 'organizadores/excluir_organizador.php',
                    data: {
                        id: codigoOrganizador
                    },
                    success: function(response) {
                        alert(response);
                        location.reload();
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                        alert('Erro ao excluir organizador. Verifique o console para mais informações.');
                    }
                });
            }
        });
    });
</script>

</html>