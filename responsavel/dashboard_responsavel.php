<?php

include('../protect.php');
include('../conexao.php');

$consulta = "SELECT * FROM pessoas WHERE ct = {$_SESSION['ct']}";
$con = $mysqli->query($consulta) or die($mysqli->error);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../css/dashboard_responsavel.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Dashboard do Responsável</title>
</head>

<body>
    <a type="button" class="meio botao" href="../logout.php">Deslogar</a>
    <br><br><br>
    <div class="meio">
        <a type="button" class="botao" href="cadastrar_pessoa.php">Adicionar Pessoa ao seu CT</a>
    </div>

    <?php
    if ($con->num_rows == 0) {
    ?>
        <table class="tabela">
            <tr>
                <td class="meio">Pessoas</td>
            </tr>

            <tr>
                <td>Não há pessoas ainda</td>
            </tr>
        </table>

    <?php
    } else {
    ?>
        <table class="tabela">
            <tr>
                <td>Nome</td>
                <td>Perfil</td>
                <td class="meio">Ação</td>
            </tr>
            <?php
            while ($dado = $con->fetch_array()) {
                $perfil = $dado['perfil'];
                $sql_code = "SELECT descricao FROM perfis WHERE id = $perfil";
                $result = $mysqli->query($sql_code);
                if ($result && $result->num_rows > 0) {
                    $perfis = array();
                    $perfis[] = $result->fetch_assoc();
                    $perfil = $perfis[0]['descricao'];
                }
            ?>

                <tr>
                    <td><?php echo $dado["nome"]; ?></td>
                    <td><?php echo $perfil; ?></td>
                    <td class="acao">
                        <a href="atualizar_pessoa.php?codigo=<?php echo $dado["id"]; ?>">
                            <img width="30" height="30" src="https://img.icons8.com/ios-glyphs/30/change--v1.png" alt="change--v1" />
                        </a>
                        <a href="#" class="excluirPessoa" data-codigo=<?php echo $dado["id"]; ?>>
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

        $('.excluirPessoa').click(function(e) {
            e.preventDefault();
            var codigoPessoa = $(this).data('codigo');
            console.log(codigoPessoa);
            var confirmacao = confirm('Tem certeza que deseja excluir esta pessoa?');
            if (confirmacao) {
                console.log('Confirmação recebida, enviando solicitação AJAX...');
                $.ajax({
                    type: 'POST',
                    url: 'excluir_pessoa.php',
                    data: {
                        id: codigoPessoa
                    },
                    success: function(response) {
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