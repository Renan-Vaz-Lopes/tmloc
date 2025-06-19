<?php
include('../conexao.php');

if (!isset($_POST['categoria'])) {
    echo json_encode([]);
    exit;
}

$categoria = $mysqli->real_escape_string($_POST['categoria']);

$query = "
    SELECT DISTINCT j.id, j.nome,
        (
            SELECT 
                CASE 
                    WHEN g.id_jogador1 = j.id THEN g.id_jogador2 
                    WHEN g.id_jogador2 = j.id THEN g.id_jogador1 
                    ELSE NULL
                END
            FROM jogos g
            INNER JOIN jogadores_campeonatos jc2 ON 
                (jc2.id_jogador = g.id_jogador1 OR jc2.id_jogador = g.id_jogador2)
            WHERE g.fase = 'chave'
              AND g.status = 'Pendente'
              AND (g.id_jogador1 = j.id OR g.id_jogador2 = j.id)
              AND jc2.categoria = '$categoria'
              AND jc2.id_jogador = j.id
            LIMIT 1
        ) AS parceiro_id
    FROM jogadores j
    INNER JOIN jogadores_campeonatos jc ON jc.id_jogador = j.id
    WHERE jc.passar_resultado_chave = 1
      AND jc.categoria = '$categoria'
";


$result = $mysqli->query($query);

$jogadores = [];
while ($row = $result->fetch_assoc()) {
    $jogadores[] = [
        'id' => $row['id'],
        'nome' => $row['nome'],
        'parceiro_id' => $row['parceiro_id']
    ];
}

echo json_encode($jogadores);
