<?php
    if (!isset($_SESSION)) {
        session_start();
    }

    if (!isset($_SESSION['id'])) {
        die("<p style='text-align: center;'>Você não pode acessar esta página porque não está logado.</p>
        <p style='text-align: center;'>
        <a href=\"../login.php\" style='display: inline-block; padding: 10px 20px; background-color: black; color: #fff; text-decoration: none; border-radius: 5px; cursor: pointer;'>Logar</a>
        </p>");
    }
?>
