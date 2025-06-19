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

        $sql_code = "SELECT id,descricao FROM titulos WHERE perfil = 2";
        $result_titulos_existentes = $mysqli->query($sql_code);

        if ($result_titulos_existentes && $result_titulos_existentes->num_rows > 0) {
            $titulos_existentes = array();

            while ($row = $result_titulos_existentes->fetch_assoc()) {
                $titulos_existentes[] = $row;
            }
        }

        $sql_code = "SELECT titulo FROM pessoas WHERE ct = '$ct' AND perfil = 2";
        $result_titulos_requisitados = $mysqli->query($sql_code);

        if ($result_titulos_requisitados && $result_titulos_requisitados->num_rows > 0) {
            $titulos_requisitados = array();

            while ($row = $result_titulos_requisitados->fetch_assoc()) {
                $titulos_requisitados[] = $row;

                if (strpos($titulos_requisitados[$y]['titulo'], ',') !== false) {
                    $varios_titulos = explode(',', $titulos_requisitados[$y]['titulo']);

                    foreach ($varios_titulos as $chave => $titulo) {
                        $i = 0;

                        while ($titulo != $titulos_existentes[$i]['id']) {
                            $i++;
                        }
                        $subvetor_desc_titulos[] = $titulos_existentes[$i]['descricao'];
                    }

                    $titulos_descricao[$y] = $subvetor_desc_titulos;

                    $subvetor_desc_titulos = [];
                    $y++;
                    $i = 0;
                } else {
                    while ($titulos_requisitados[$y]['titulo'] != $titulos_existentes[$i]['id']) {
                        $i++;
                    }
                    $titulos_descricao[$y] = $titulos_existentes[$i]['descricao'];
                    $y++;
                    $i = 0;
                }
            }
        }

        $anos_titulos = array();
        $ajd = 0;
        while ($row = $result_pessoas->fetch_assoc()) {
            $jogadores[] = $row;
            $jogadores[$i]['perfil'] = "Jogador";

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

            if (preg_match('/\d/', $jogadores[$i]['titulo'])) {
                $jogadores[$i]['titulo'] = '';
            }

            if (isset($titulos_descricao[$i])) {
                if (is_array($titulos_descricao[$i])) {

                    foreach ($titulos_descricao[$i] as $titulo_desc) {

                        $jogadores[$i]['titulo'] .= $titulo_desc . "(" . $anos_titulos[$ajd]['anos'] . "), ";

                        $ajd++;
                    }
                    $jogadores[$i]['titulo'] = rtrim($jogadores[$i]['titulo'], ', ');
                    $i++;
                } else {
                    if ($titulos_descricao[$i] == 'Nenhum') {
                        $jogadores[$i]['titulo'] = 'Nenhum';
                        $i++;
                    } else {
                        $jogadores[$i]['titulo'] = $anos_titulos[$ajd]['titulo_desc'] . "(" . $anos_titulos[$ajd]['anos'] . ")";
                        $i++;
                        $ajd++;
                    }
                }
            }
        }
        echo json_encode($jogadores);
    } else {
        echo json_encode([]);
    }
} else {
    echo json_encode(array('message' => 'Nenhum jogador encontrado para este CT.'));
}
