<?php
include('../conexao.php');
include('../protect.php');

$consulta = "SELECT * FROM titulos";
$con = $mysqli->query($consulta) or die($mysqli->error);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../css/admin_titulos.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Menu de Titulos</title>
</head>

<body>
    <a href="dashboard_admin.php" class="botao">Voltar</a>
    <br><br>

    <div class="meio">
        <a href="titulos/cadastrar_titulo.php" class="botao">Cadastrar título</a>
    </div>

    <?php
    if ($con->num_rows == 0) {
    ?>
        <table class="tabela">
            <tr>
                <td class="meio">Títulos</td>
            </tr>

            <tr>
                <td>Não há títulos ainda</td>
            </tr>
        </table>

    <?php
    } else {
    ?>
        <table class="tabela">
            <tr>
                <td>Descrição</td>
                <td>Perfil</td>
                <td class="meio">Ação</td>
            </tr>
            <?php
            while ($dado = $con->fetch_array()) { ?>
                <tr>
                    <td><?php echo $dado["descricao"]; ?></td>

                    <?php
                    if ($dado["perfil"] == 2) {
                        $dado['perfil'] = 'Jogador';
                    } else if ($dado["perfil"] == 3) {
                        $dado['perfil'] = 'Técnico';
                    } else if ($dado["perfil"] == 4) {
                        $dado['perfil'] = 'Responsavel';
                    } else $dado['perfil'] = 'perfil novo';
                        ?>

                    <td><?php echo $dado["perfil"]; ?></td>
                    <td class="acao">
                        <a class="tirarUnderline" href="titulos/atualizar_titulo.php?codigo=<?php echo $dado["id"]; ?>">
                            <img width="30" height="30" src="https://img.icons8.com/ios-glyphs/30/change--v1.png" alt="change--v1" />
                        </a>
                        <a href="#" class="excluirTitulo" data-codigo=<?php echo $dado["id"]; ?>>
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

        $('.excluirTitulo').click(function(e) {
            e.preventDefault();
            var codigoTitulo = $(this).data('codigo');

            var confirmacao = confirm('Tem certeza que deseja excluir este título?');
            if (confirmacao) {
                $.ajax({
                    type: 'POST',
                    url: 'titulos/excluir_titulo.php',
                    data: {
                        id: codigoTitulo
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