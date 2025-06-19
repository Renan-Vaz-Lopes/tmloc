<?php
include('../conexao.php');
include('../protect.php');

$consulta = "SELECT * FROM perfis";
$con = $mysqli->query($consulta) or die($mysqli->error);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../css/admin_perfis.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Menu de Perfis</title>
</head>

<body>
    <a href="dashboard_admin.php" class="botao">Voltar</a>
    <br><br>

    <div class="meio">
        <a href="perfis/cadastrar_perfil.php" class="botao">Cadastrar perfil</a>
    </div>

    <?php
    if ($con->num_rows == 0) {
    ?>
        <table class="tabela">
            <tr>
                <td class="meio">Perfis</td>
            </tr>

            <tr>
                <td>Não há perfis ainda</td>
            </tr>
        </table>

    <?php
    } else {
    ?>
        <table class="tabela">
            <tr>
                <td>Descrição</td>
                <td class = "meio">Ação</td>
            </tr>
            <?php
            while ($dado = $con->fetch_array()) { ?>
                <tr>
                    <td><?php echo $dado["descricao"]; ?></td>
                    <td class="acao">
                        <a class="tirarUnderline" href="perfis/atualizar_perfil.php?codigo=<?php echo $dado["id"]; ?>">
                    <img width="30" height="30" src="https://img.icons8.com/ios-glyphs/30/change--v1.png" alt="change--v1" />
                </a>
                        <a href="#" class="excluirPerfil" data-codigo=<?php echo $dado["id"]; ?>>
                        <img width="30" height="30" src="https://img.icons8.com/material-sharp/30/filled-trash.png" alt="filled-trash"/>
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

        $('.excluirPerfil').click(function(e) {
            e.preventDefault();
            var codigoPerfil = $(this).data('codigo');

            var confirmacao = confirm('Tem certeza que deseja excluir este perfil?');
            if (confirmacao) {
                $.ajax({
                    type: 'POST',
                    url: 'perfis/excluir_perfil.php',
                    data: {
                        id: codigoPerfil
                    },
                    success: function(response) {
                        alert(response);
                        location.reload();
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                        alert('Erro ao excluir . Verifique o console para mais informações.');
                    }
                });
            }
        });
    });
</script>

</html>