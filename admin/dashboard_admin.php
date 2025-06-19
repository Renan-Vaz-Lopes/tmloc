<?php

include('../protect.php');

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../css/dashboard_admin.css">
    <title>Dashboard do Admin</title>
</head>

<body>
    <div class="container-botoes">
        <div class="botoes-conteudo">
            <a type="button" href="admin_responsaveis.php">Responsáveis por CTs</a>
            <a type="button" href="admin_cts.php">CTs</a>
            <a type="button" href="admin_perfis.php">Perfis</a>
            <a type="button" href="admin_titulos.php">Títulos</a>
            <a type="button" href="admin_jogadores.php">Jogadores</a>
            <a type="button" href="admin_organizadores.php">Organizadores</a>
            <a type="button" href="admin_publicacoes.php">Publicacões</a>
            <a type="button" href="admin_feedbacks.php">Feedbacks</a>
            <br>
            <a type="button" class="meio" href="../logout.php">Deslogar</a>
        </div>
    </div>
</body>

</html>