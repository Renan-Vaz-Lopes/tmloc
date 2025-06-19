<?php
include('../conexao.php');
include('../protect.php');
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require '../config.php';
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "erro", "mensagem" => "Método inválido."]);
    exit;
}

$id_jogador1 = isset($_POST['jogador1']) ? $_POST['jogador1'] : $_SESSION['id'];
$id_jogador2 = isset($_POST['jogador2']) ? $_POST['jogador2'] : $_POST['adversario'];
$sets_jogador1 = isset($_POST['sets_jogador1']) ? $_POST['sets_jogador1'] : $_POST['sets_jogador'];
$sets_jogador2 = isset($_POST['sets_jogador2']) ? $_POST['sets_jogador2'] : $_POST['sets_adversario'];

// **Validações**
if (!isset($id_jogador1, $id_jogador2, $sets_jogador1, $sets_jogador2)) {
    echo json_encode(["status" => "erro", "mensagem" => "Dados incompletos."]);
    exit;
}

// **Buscar o jogo entre os dois jogadores**
$queryJogo = "SELECT id, id_grupo, `status` FROM jogos 
              WHERE (id_jogador1 = '$id_jogador1' AND id_jogador2 = '$id_jogador2') 
                 OR (id_jogador1 = '$id_jogador2' AND id_jogador2 = '$id_jogador1') 
              LIMIT 1";

$resultJogo = $mysqli->query($queryJogo);

if ($resultJogo->num_rows === 0) {
    echo json_encode(["status" => "erro", "mensagem" => "Jogo não encontrado."]);
    exit;
}

$jogo = $resultJogo->fetch_assoc();
$idJogo = $jogo['id'];
$idGrupo = $jogo['id_grupo'];

$queryDadosGrupos = "SELECT categoria, mesa, id_campeonato FROM grupos WHERE id = '$idGrupo' LIMIT 1";
$resultDadosGrupos = $mysqli->query($queryDadosGrupos);
$dados = $resultDadosGrupos->fetch_assoc();
$categoria = $dados['categoria'];
$mesa = $dados['mesa'];
$idCampeonato = $dados['id_campeonato'];
$uploadDir = "../uploads/";
$arquivoOrdemJogos = $uploadDir . "ORDEM1JOGOS.xlsx";
$arquivoJogadores = $uploadDir . "jogadores.xlsx";
$arquivoGrupos = $uploadDir . "GRUPOS.xlsx";
$arquivoChaves = $uploadDir . "chaves/";

if (!file_exists($arquivoOrdemJogos) || !file_exists($arquivoJogadores) || !file_exists($arquivoGrupos)) {
    echo json_encode(["status" => "erro", "mensagem" => "Erro: Um dos arquivos não foi encontrado."]);
    exit;
}

if ($jogo['status'] !== 'Pendente') {
    echo json_encode(["status" => "erro", "mensagem" => "Esse jogo já foi finalizado."]);
    exit;
}

// **Validar placar (um jogador precisa ganhar com pelo menos 3 sets)**
if ($sets_jogador1 < 3 && $sets_jogador2 < 3) {
    echo json_encode(["status" => "erro", "mensagem" => "Alguém precisa vencer com pelo menos 3 sets!"]);
    exit;
}

if ($sets_jogador1 == 3 && $sets_jogador2 == 3) {
    echo json_encode(["status" => "erro", "mensagem" => "Placar inválido! Apenas um jogador pode vencer com 3 sets."]);
    exit;
}

// **Atualizar o jogo com o resultado**
$queryUpdateJogo = "UPDATE jogos 
                    SET sets_jogador1 = CASE WHEN id_jogador1 = '$id_jogador1' THEN '$sets_jogador1' ELSE '$sets_jogador2' END,
                        sets_jogador2 = CASE WHEN id_jogador2 = '$id_jogador2' THEN '$sets_jogador2' ELSE '$sets_jogador1' END,
                        status = 'Finalizado'
                    WHERE id = '$idJogo'";

$mysqli->query($queryUpdateJogo);

// Verifica se todos os jogos de um grupo foram jogados
$queryJogosPendentes = "SELECT id FROM jogos WHERE id_grupo = '$idGrupo' AND status = 'Pendente'";
$resultPendentes = $mysqli->query($queryJogosPendentes);

if ($resultPendentes->num_rows === 0) {

    //verifica se tem mesa com status 'Aguardando Mesa' em uma determinada categoria
    $queryProximoGrupo = "
        SELECT id, grupo, mesa 
        FROM grupos 
        WHERE id_campeonato = $idCampeonato
        AND categoria = '$categoria'
        AND mesa = 'Aguardando Mesa' 
        ORDER BY CAST(SUBSTRING_INDEX(grupo, ' ', -1) AS UNSIGNED) DESC
        LIMIT 1";

    $resultProximoGrupo = $mysqli->query($queryProximoGrupo);

    if ($resultProximoGrupo->num_rows > 0) {
        $proximoGrupo = $resultProximoGrupo->fetch_assoc();
        $idProximoGrupo = $proximoGrupo['id'];
        $nomeGrupo = $proximoGrupo['grupo'];

        $novaMesa = $mesa;

        //atualizando as mesas da tabela de grupos
        $mysqli->query("UPDATE grupos SET `status` = 'Finalizado' WHERE id = '$idGrupo'");
        $mysqli->query("UPDATE grupos SET `mesa` = '$novaMesa', `status` = 'Jogando' WHERE id = '$idProximoGrupo'");

        //atualizando as mesas da tabela de jogos
        $mysqli->query("UPDATE jogos SET mesa = 'Finalizado' WHERE id_grupo = '$idGrupo'");
        $mysqli->query("UPDATE jogos SET mesa = '$novaMesa' WHERE id_grupo = '$idProximoGrupo'");

        // Atualiza o grupo antigo (jogadores não podem mais passar resultado)
        $jogadoresGrupoAntigo = obterIdsJogadoresDoGrupo($mysqli, $idGrupo);
        foreach ($jogadoresGrupoAntigo as $idJogador) {
            $mysqli->query("UPDATE jogadores_campeonatos
            SET passar_resultado_grupo = 0
            WHERE id_jogador = '$idJogador'");
        }

        // Atualiza o grupo novo (agora podem passar resultado)
        $jogadoresGrupoNovo = obterIdsJogadoresDoGrupo($mysqli, $idProximoGrupo);
        foreach ($jogadoresGrupoNovo as $idJogador) {
            $mysqli->query("UPDATE jogadores_campeonatos
            SET passar_resultado_grupo = 1
            WHERE id_jogador = '$idJogador'");
        }

        $queryJogadoresProximoGrupo = "
            SELECT DISTINCT j.email, j.nome
            FROM jogadores j
            WHERE j.id IN (
                SELECT id_jogador1 FROM jogos WHERE id_grupo = '$idProximoGrupo'
                UNION
                SELECT id_jogador2 FROM jogos WHERE id_grupo = '$idProximoGrupo'
            )";

        $resultJogadores = $mysqli->query($queryJogadoresProximoGrupo);
        while ($row = $resultJogadores->fetch_assoc()) {
            $emailJogador = $row['email'];
            $nomeJogador = $row['nome'];

            if ($emailJogador) {
                enviarEmail($emailJogador, "Liga - Sua Mesa!", "<p>Olá, <b>{$nomeJogador}</b>!</p><p>Seu grupo está na <b>{$novaMesa}</b>.</p><p>Boa sorte!</p>");
            }
        }
    } else {
        //atualiza status dos jogadores do grupo
        $jogadoresGrupoAntigo = obterIdsJogadoresDoGrupo($mysqli, $idGrupo);
        foreach ($jogadoresGrupoAntigo as $idJogador) {
            $mysqli->query("UPDATE jogadores_campeonatos SET passar_resultado_grupo = 0 WHERE id_jogador = '$idJogador'");
        }

        //atualiza o status dos ultimos grupos
        $mysqli->query("UPDATE grupos SET `status` = 'Finalizado' WHERE id = '$idGrupo'");
        $mysqli->query("UPDATE jogos SET mesa = 'Finalizado' WHERE id_grupo = '$idGrupo'");

        $queryTodosGrupos = "
        SELECT COUNT(*) as total 
        FROM grupos 
        WHERE categoria = '$categoria' 
          AND id_campeonato = (
              SELECT id_campeonato FROM grupos WHERE id = '$idGrupo' LIMIT 1
          )";

        $queryJogaram = "
        SELECT COUNT(*) as jogaram
        FROM grupos
        WHERE categoria = '$categoria'
          AND status = 'Finalizado' AND id_campeonato = (
            SELECT id_campeonato FROM grupos WHERE id = '$idGrupo' LIMIT 1
          )";

        $totalGrupos = $mysqli->query($queryTodosGrupos)->fetch_assoc()['total'];
        $jogaramGrupos = $mysqli->query($queryJogaram)->fetch_assoc()['jogaram'];

        // Verifica se todos os grupos da categoria já jogaram
        if ($totalGrupos == $jogaramGrupos && $totalGrupos > 1) {
            //atualiza status das mesas pra disponíveis
            $queryAtualizaMesas = "
                UPDATE mesas
                SET status = 'disponível'
                WHERE id_campeonato = (
                    SELECT id_campeonato FROM grupos WHERE id = '$idGrupo' LIMIT 1
                )
                AND mesa IN (
                    SELECT DISTINCT mesa FROM grupos WHERE categoria = '$categoria'
                )";

            $mysqli->query($queryAtualizaMesas);

            // Buscar os grupos e seus classificados
            $queryGrupos = "SELECT id, grupo FROM grupos 
                        WHERE categoria = '$categoria' AND id_campeonato = (
                            SELECT id_campeonato FROM grupos WHERE id = '$idGrupo'
                        )
                        ORDER BY CAST(SUBSTRING_INDEX(grupo, ' ', -1) AS UNSIGNED)";
            $resultGrupos = $mysqli->query($queryGrupos);

            $grupos = [];
            while ($g = $resultGrupos->fetch_assoc()) {
                $idGrupoAtual = $g['id'];
                $nomeGrupo = $g['grupo'];

                // Pegando os jogadores com mais vitórias (ordena por sets vencidos)
                $queryClassificados = "
                SELECT j.id, j.nome,
                    SUM(CASE WHEN g.id_jogador1 = j.id THEN g.sets_jogador1 ELSE g.sets_jogador2 END) AS sets_vencidos,
                    SUM(CASE WHEN g.id_jogador1 = j.id THEN g.sets_jogador2 ELSE g.sets_jogador1 END) AS sets_sofridos,
                    (SUM(CASE WHEN g.id_jogador1 = j.id THEN g.sets_jogador1 ELSE g.sets_jogador2 END) -
                    SUM(CASE WHEN g.id_jogador1 = j.id THEN g.sets_jogador2 ELSE g.sets_jogador1 END)) AS saldo_sets

                FROM jogos g
                JOIN jogadores j ON j.id = g.id_jogador1 OR j.id = g.id_jogador2
                WHERE g.id_grupo = '$idGrupoAtual'
                  AND g.status = 'Finalizado'
                GROUP BY j.id
                ORDER BY sets_vencidos DESC, saldo_sets DESC
                LIMIT 2
            ";

                $resultClassificados = $mysqli->query($queryClassificados);
                $jogadoresGrupo = [];
                while ($row = $resultClassificados->fetch_assoc()) {
                    $jogadoresGrupo[] = $row;
                }

                if (count($jogadoresGrupo) == 2) {
                    $grupos[] = [
                        'grupo' => $nomeGrupo,
                        'primeiro' => $jogadoresGrupo[0],
                        'segundo' => $jogadoresGrupo[1]
                    ];
                }
            }

            // Formar confrontos
            $confrontos = [];
            for ($i = 0; $i < count($grupos) - 1; $i += 2) {
                $grupoA = $grupos[$i];
                $grupoB = $grupos[$i + 1];

                $confrontos[] = [
                    'jogador1' => $grupoA['primeiro']['nome'],
                    'jogador2' => $grupoB['segundo']['nome']
                ];
                $confrontos[] = [
                    'jogador1' => $grupoB['primeiro']['nome'],
                    'jogador2' => $grupoA['segundo']['nome']
                ];
            }

            // Gerar Excel (salvar no servidor)

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle("Chaves - $categoria");
            $sheet->setCellValue('A1', 'Jogador 1');
            $sheet->setCellValue('B1', 'Jogador 2');

            $linha = 2;
            foreach ($confrontos as $c) {
                $sheet->setCellValue("A$linha", $c['jogador1']);
                $sheet->setCellValue("B$linha", $c['jogador2']);
                $linha++;
            }

            // Cria nome seguro do arquivo
            $nomeLimpo = preg_replace('/[^A-Za-z0-9_\-]/', '_', $categoria);
            $nomeLimpo = preg_replace('/_+/', '_', $nomeLimpo);
            $nomeLimpo = trim($nomeLimpo, '_');

            $nomeArquivo = $nomeLimpo . ".xlsx";
            $caminhoArquivo = "../uploads/chaves/" . $nomeArquivo;

            $spreadsheet->getActiveSheet()->setTitle('Chave');
            $writer = new Xlsx($spreadsheet);
            $writer->save($caminhoArquivo);

            // Primeiro: insere todos os jogos da chave
            $jaAlocados = [];

            foreach ($confrontos as $c) {
                $jogador1 = $c['jogador1'];
                $jogador2 = $c['jogador2'];

                // Pula se algum dos jogadores já estiver alocado
                if (in_array($jogador1, $jaAlocados) || in_array($jogador2, $jaAlocados)) {
                    continue;
                }

                $res1 = $mysqli->query("SELECT id FROM jogadores WHERE nome = '$jogador1' LIMIT 1");
                $res2 = $mysqli->query("SELECT id FROM jogadores WHERE nome = '$jogador2' LIMIT 1");

                if ($res1->num_rows > 0 && $res2->num_rows > 0) {
                    $id1 = $res1->fetch_assoc()['id'];
                    $id2 = $res2->fetch_assoc()['id'];

                    // Verifica se o jogo já existe
                    $verifica = $mysqli->query("
                    SELECT 1 FROM jogos 
                    WHERE id_campeonato = '$idCampeonato'
                    AND (
                        (id_jogador1 = '$id1' AND id_jogador2 = '$id2') OR
                        (id_jogador1 = '$id2' AND id_jogador2 = '$id1')
                    )
                    AND fase = 'chave'
                    LIMIT 1
                    ");

                    if ($verifica->num_rows === 0) {
                        $mysqli->query("
                        INSERT INTO jogos (id_campeonato, id_jogador1, id_jogador2, mesa, status, fase)
                        VALUES ('$idCampeonato', '$id1', '$id2','Aguardando', 'Pendente', 'chave')
                        ");

                        // Marca os jogadores como já alocados
                        $jaAlocados[] = $jogador1;
                        $jaAlocados[] = $jogador2;
                    }
                }
            }


            // Depois: libera os confrontos de acordo com o número de mesas disponíveis
            $queryMesasDisponiveis = "SELECT mesa FROM mesas WHERE id_campeonato = '$idCampeonato' AND status = 'disponível'";
            $mesasDisponiveis = $mysqli->query($queryMesasDisponiveis);
            $quantidadeMesasDisponiveis = $mesasDisponiveis->num_rows;
            $liberacoesFeitas = 0;

            foreach ($confrontos as $c) {
                if ($liberacoesFeitas >= $quantidadeMesasDisponiveis) break;
                $jogador1 = $c['jogador1'];
                $jogador2 = $c['jogador2'];

                $res1 = $mysqli->query("SELECT id FROM jogadores WHERE nome = '$jogador1' LIMIT 1");
                $res2 = $mysqli->query("SELECT id FROM jogadores WHERE nome = '$jogador2' LIMIT 1");

                if ($res1->num_rows > 0 && $res2->num_rows > 0) {
                    $id1 = $res1->fetch_assoc()['id'];
                    $id2 = $res2->fetch_assoc()['id'];

                    $verifica = $mysqli->query("
                    SELECT status FROM jogos 
                    WHERE id_campeonato = '$idCampeonato'
                    AND (
                        (id_jogador1 = '$id1' AND id_jogador2 = '$id2') OR
                        (id_jogador1 = '$id2' AND id_jogador2 = '$id1')
                    )
                    AND fase = 'chave'
                    LIMIT 1
                    ");

                    if ($verifica->num_rows > 0) {
                        $status = $verifica->fetch_assoc()['status'];
                        if ($status === 'Pendente') {
                            $mysqli->query("
                            UPDATE jogadores_campeonatos 
                            SET passar_resultado_chave = 1 
                            WHERE id_jogador IN ('$id1', '$id2') AND id_campeonato = '$idCampeonato'
                            ");

                            $queryMesaDisponivel = "
                                SELECT mesa 
                                FROM mesas 
                                WHERE id_campeonato = '$idCampeonato' 
                                AND status = 'disponível' 
                                LIMIT 1
                                ";

                            $resultMesaDisponivel = $mysqli->query($queryMesaDisponivel);

                            if ($resultMesaDisponivel->num_rows > 0) {
                                $mesaDisponivel = $resultMesaDisponivel->fetch_assoc()['mesa'];

                                $result1 = $mysqli->query("SELECT email FROM jogadores WHERE nome = '$jogador1' LIMIT 1");
                                $result2 = $mysqli->query("SELECT email FROM jogadores WHERE nome = '$jogador2' LIMIT 1");

                                $email1 = $result1->fetch_assoc()['email'] ?? null;
                                $email2 = $result2->fetch_assoc()['email'] ?? null;

                                $mensagem = "<p>Olá, <b>%s</b>!<br>Você foi chamado para jogar na <b>$mesaDisponivel</b>!<br>Boa sorte!</p>";

                                if ($email1 && $email2) {
                                    enviarEmail($email1, "Liga - Novo Jogo!", sprintf($mensagem, $jogador1));
                                    enviarEmail($email2, "Liga - Novo Jogo!", sprintf($mensagem, $jogador2));

                                    $mysqli->query("
                                    UPDATE jogos 
                                    SET mesa = '$mesaDisponivel' 
                                    WHERE id_campeonato = '$idCampeonato'
                                    AND fase = 'chave'
                                    AND (
                                        (id_jogador1 = '$id1' AND id_jogador2 = '$id2') OR
                                        (id_jogador1 = '$id2' AND id_jogador2 = '$id1')
                                    )
                                    AND mesa = 'Aguardando'
                                    ");

                                    $mysqli->query("UPDATE mesas SET status = 'ocupada' WHERE mesa = '$mesaDisponivel' AND id_campeonato = '$idCampeonato'");
                                }
                            }
                            $liberacoesFeitas++;
                        }
                    }
                }
            }
        }
    }
}

echo json_encode(["status" => "sucesso", "mensagem" => "Resultado atualizado com sucesso."]);
exit;

function enviarEmail($emailDestino, $assunto, $mensagem)
{
    $mail = new PHPMailer(true);
    $CONFIG = getConfig();

    try {
        $mail->isSMTP();
        $mail->Host = $CONFIG['email']['host'];
        $mail->SMTPAuth = true;
        $mail->Port = $CONFIG['email']['port'];
        $mail->SMTPSecure = $CONFIG['email']['SMTPSecure'];
        $mail->Username = $CONFIG['email']['username'];
        $mail->Password = $CONFIG['email']['password'];

        $mail->setFrom($CONFIG['email']['username'], 'TmLoc');
        $mail->addAddress($emailDestino);

        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body = $mensagem;

        $mail->send();
    } catch (Exception $e) {
        error_log("Erro ao enviar para {$emailDestino}: " . $mail->ErrorInfo);
    }
}

function obterIdsJogadoresDoGrupo($mysqli, $idGrupo)
{
    $ids = [];
    $query = "SELECT id_jogador1, id_jogador2 FROM jogos WHERE id_grupo = '$idGrupo'";
    $result = $mysqli->query($query);
    while ($row = $result->fetch_assoc()) {
        $ids[] = $row['id_jogador1'];
        $ids[] = $row['id_jogador2'];
    }
    return array_unique($ids);
}
