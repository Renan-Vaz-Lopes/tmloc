<?php
include('../conexao.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ctId'])) {
    $ct = $mysqli->real_escape_string($_POST['ctId']);
    $i = 0;
    $y = 0;
    $titulos_descricao = [];

    $sql_code = "SELECT * from pessoas WHERE ct = '$ct' AND perfil = 2";
    $result_pessoas = $mysqli->query($sql_code);

    if ($result_pessoas && $result_pessoas->num_rows > 0) {
        $jogadores = array();

        $sql_code = "SELECT id, descricao FROM titulos WHERE perfil = 2";
        $result_titulos_existentes = $mysqli->query($sql_code);

        if ($result_titulos_existentes && $result_titulos_existentes->num_rows > 0) {
            $titulos_existentes = array();
            while ($row = $result_titulos_existentes->fetch_assoc()) {
                $titulos_existentes[$row['id']] = $row['descricao'];
            }
        }

        while ($row = $result_pessoas->fetch_assoc()) {
            $jogadores[] = $row;
            $jogadores[$i]['perfil'] = "Jogador";

            $anos_titulos = [];

            $sql_code = "SELECT ati.id_pessoa AS id_pessoa, t.id, t.descricao AS titulo_desc, ati.ano AS anos 
                         FROM anos_titulos ati
                         JOIN titulos t ON ati.id_titulo = t.id
                         WHERE ati.id_pessoa = {$jogadores[$i]['id']}";
            $result_anos_titulos = $mysqli->query($sql_code);

            if ($result_anos_titulos && $result_anos_titulos->num_rows > 0) {
                while ($row_anos_titulos = $result_anos_titulos->fetch_assoc()) {
                    $anos_titulos[] = $row_anos_titulos;
                }
            }

            if (!empty($anos_titulos)) {
                usort($anos_titulos, function ($a, $b) {
                    return intval($a['anos']) <=> intval($b['anos']);
                });
            }

            $jogadores[$i]['titulo'] = '';

            foreach ($anos_titulos as $titulo_info) {
                $descricao_titulo = isset($titulos_existentes[$titulo_info['id']]) ? $titulos_existentes[$titulo_info['id']] : 'Desconhecido';
                $jogadores[$i]['titulo'] .= $descricao_titulo . " (" . $titulo_info['anos'] . "), ";
            }

            $jogadores[$i]['titulo'] = rtrim($jogadores[$i]['titulo'], ', ');
            $i++;
        }
        echo json_encode($jogadores);
    } else {
        echo json_encode([]);
    }
} else {
    echo json_encode(array('message' => 'Nenhum jogador encontrado para este CT.'));
}
