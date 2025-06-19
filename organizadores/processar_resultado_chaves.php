<?php
include('../conexao.php');
require '../vendor/autoload.php';
require '../config.php';
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

session_start();
ini_set('log_errors', 1);
ini_set('error_log', 'C:/xampp/php/logs/php_error_log');
header('Content-Type: application/json');

// Validações iniciais
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "erro", "mensagem" => "Requisição inválida."]);
    exit;
}

$idJogador1 = $_POST['jogador1'] ?? $_SESSION['id'];
$idJogador2 = $_POST['jogador2'] ?? $_SESSION['id'];
$setsJogador1 = $_POST['sets_jogador1'] ?? null;
$setsJogador2 = $_POST['sets_jogador2'] ?? null;

if (!$idJogador1 || !$idJogador2 || $setsJogador1 === null || $setsJogador2 === null) {
    echo json_encode(["status" => "erro", "mensagem" => "Dados incompletos."]);
    exit;
}

if ($idJogador1 == $idJogador2) {
    echo json_encode(["status" => "erro", "mensagem" => "Jogadores não podem ser iguais."]);
    exit;
}

$queryInfo = "
    SELECT categoria, id_campeonato 
    FROM jogadores_campeonatos 
    WHERE id_jogador = '$idJogador1'
    LIMIT 1
";

$resultInfo = $mysqli->query($queryInfo);
if (!$resultInfo || $resultInfo->num_rows === 0) {
    echo json_encode(["status" => "erro", "mensagem" => "Jogador não associado a campeonato."]);
    exit;
}

$dadosInfo = $resultInfo->fetch_assoc();
$categoria = $dadosInfo['categoria'];
$idCampeonato = $dadosInfo['id_campeonato'];

$queryJogo = "SELECT * FROM jogos 
              WHERE fase = 'chave'
                AND ((id_jogador1 = '$idJogador1' AND id_jogador2 = '$idJogador2') 
                     OR (id_jogador1 = '$idJogador2' AND id_jogador2 = '$idJogador1'))
                AND `status` = 'Pendente'
              LIMIT 1";
$resultJogo = $mysqli->query($queryJogo);


if (!$resultJogo || $resultJogo->num_rows === 0) {
    echo json_encode(["status" => "erro", "mensagem" => "Este jogo já foi finalizado, você não tem mais permissão para registrar o resultado. Atualize a página!"]);
    exit;
}

$jogo = $resultJogo->fetch_assoc();
$idJogo = $jogo['id'];
$mesaFinalizada = $jogo['mesa'];

// Valida placar
if (!(
    ($setsJogador1 == 3 && $setsJogador2 <= 2) ||
    ($setsJogador2 == 3 && $setsJogador1 <= 2)
)) {
    echo json_encode(["status" => "erro", "mensagem" => "Placar inválido. Um jogador deve vencer com exatamente 3 sets."]);
    exit;
}

// Atualiza o jogo
$updateJogo = "UPDATE jogos 
               SET sets_jogador1 = CASE WHEN id_jogador1 = '$idJogador1' THEN '$setsJogador1' ELSE '$setsJogador2' END,
                   sets_jogador2 = CASE WHEN id_jogador2 = '$idJogador2' THEN '$setsJogador2' ELSE '$setsJogador1' END,
                   status = 'Finalizado'
               WHERE id = '$idJogo'";

$mysqli->query($updateJogo);

//atualizar a mesa finalizada para disponível
if ($mesaFinalizada && $mesaFinalizada !== 'Aguardando') {
    $mysqli->query("UPDATE mesas 
                    SET status = 'disponível' 
                    WHERE id_campeonato = '$idCampeonato' 
                      AND mesa = '$mesaFinalizada'");

    $nomeLimpo = preg_replace('/[^A-Za-z0-9_\-]/', '_', $categoria);
    $nomeLimpo = preg_replace('/_+/', '_', $nomeLimpo);
    $nomeLimpo = trim($nomeLimpo, '_');
    $caminhoArquivo = "../uploads/chaves/" . $nomeLimpo . ".xlsx";

    if (file_exists($caminhoArquivo)) {
        $spreadsheet = IOFactory::load($caminhoArquivo);
        $sheet = $spreadsheet->getActiveSheet();
        $ultimaColuna = $sheet->getHighestColumn();
        $ultimaLinha = $sheet->getHighestRow();

        // Descobre os nomes dos jogadores
        $resNome1 = $mysqli->query("SELECT nome FROM jogadores WHERE id = '$idJogador1'");
        $resNome2 = $mysqli->query("SELECT nome FROM jogadores WHERE id = '$idJogador2'");

        if ($resNome1->num_rows > 0 && $resNome2->num_rows > 0) {
            $nome1 = trim($resNome1->fetch_assoc()['nome']);
            $nome2 = trim($resNome2->fetch_assoc()['nome']);

            // Mapear corretamente os nomes aos ids
            $id1 = $jogo['id_jogador1'];
            $id2 = $jogo['id_jogador2'];

            $sets1 = ($id1 == $idJogador1) ? $setsJogador1 : $setsJogador2;
            $sets2 = ($id2 == $idJogador2) ? $setsJogador2 : $setsJogador1;

            $nomeJogador1 = ($id1 == $idJogador1) ? $nome1 : $nome2;
            $nomeJogador2 = ($id2 == $idJogador2) ? $nome2 : $nome1;

            if ($sets1 > $sets2) {
                $vencedor = $nomeJogador1;
                $perdedor = $nomeJogador2;
            } else {
                $vencedor = $nomeJogador2;
                $perdedor = $nomeJogador1;
            }

            error_log("🏓 Nome1: $nome1 | Nome2: $nome2 | Perdedor: $perdedor");

            // Função de normalização
            function normaliza($str)
            {
                return mb_strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', '', iconv('UTF-8', 'ASCII//TRANSLIT', $str)));
            }

            for ($linha = 2; $linha <= $ultimaLinha; $linha++) {
                $coluna = 'A';
                while ($coluna <= $ultimaColuna) {
                    $valor = trim($sheet->getCell($coluna . $linha)->getValue());

                    error_log("📄 Verificando célula {$coluna}{$linha} com valor: '$valor'");

                    if (normaliza($valor) === normaliza($perdedor)) {
                        error_log("✅ Tachar: $perdedor na célula {$coluna}{$linha}");
                        $sheet->setCellValue($coluna . $linha, $valor);
                        $sheet->getStyle($coluna . $linha)->getFont()->setStrikethrough(true);
                    }

                    $coluna++;
                }
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($caminhoArquivo);
        }
    }
}

// Remove permissão para passar resultado
$mysqli->query("UPDATE jogadores_campeonatos 
                SET passar_resultado_chave = 0 
                WHERE id_jogador IN ('$idJogador1', '$idJogador2')");

// Agora, verifica se todos os jogos da chave dessa categoria acabaram
$queryVerifica = "
 SELECT COUNT(*) as total,
     SUM(CASE WHEN status = 'Finalizado' THEN 1 ELSE 0 END) as finalizados
 FROM jogos
 WHERE fase = 'chave'
 AND id_campeonato = '$idCampeonato'
 AND mesa IN (
     SELECT DISTINCT mesa FROM grupos 
     WHERE id_campeonato = '$idCampeonato' AND categoria = '$categoria'
 )
";
$resVerifica = $mysqli->query($queryVerifica);
if ($resVerifica) {
    $dados = $resVerifica->fetch_assoc();
    if ($dados['total'] > 0 && $dados['total'] == $dados['finalizados']) {
        // Categoria finalizada
        error_log("🏆 Categoria finalizada: $categoria");

        if (!file_exists($caminhoArquivo)) {
            error_log("Arquivo de chave não encontrado.");
            return;
        }

        // Coleta todos os nomes que não estão tachados
        $vencedores = [];

        for ($linha = 2; $linha <= $ultimaLinha; $linha++) {
            foreach (['A', 'B'] as $coluna) {
                $valor = trim($sheet->getCell("{$coluna}{$linha}")->getValue());
                if (!$valor) continue;

                $isTachado = $sheet->getStyle("{$coluna}{$linha}")->getFont()->getStrikethrough();
                if (!$isTachado) {
                    $vencedores[] = $valor;
                }
            }
        }

        // Agora forma confrontos com os vencedores (pares consecutivos)
        $confrontos = [];
        for ($i = 0; $i < count($vencedores) - 1; $i += 2) {
            $confrontos[] = [$vencedores[$i], $vencedores[$i + 1]];
        }

        // Atualiza o Excel com os confrontos atuais
        if (!empty($confrontos)) {
            $spreadsheet = IOFactory::load($caminhoArquivo);
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->removeRow(2, $sheet->getHighestRow() - 1);


            $sheet->setCellValue('A1', 'Jogador 1');
            $sheet->setCellValue('B1', 'Jogador 2');

            $linhaAtual = 2;

            foreach ($confrontos as $confronto) {
                [$nome1, $nome2] = $confronto;
                $sheet->setCellValue("A$linhaAtual", $nome1);
                $sheet->setCellValue("B$linhaAtual", $nome2);

                // Garante que as células estejam SEM tachado
                $sheet->getStyle("A$linhaAtual")->getFont()->setStrikethrough(false);
                $sheet->getStyle("B$linhaAtual")->getFont()->setStrikethrough(false);

                $linhaAtual++;
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($caminhoArquivo);
        }

        // Buscar mesas disponíveis
        $queryMesas = "SELECT mesa FROM mesas WHERE id_campeonato = '$idCampeonato' AND status = 'disponível'";
        $resultMesas = $mysqli->query($queryMesas);
        $mesasDisponiveis = [];
        while ($row = $resultMesas->fetch_assoc()) {
            $mesasDisponiveis[] = $row['mesa'];
        }

        $liberacoes = 0;

        foreach ($confrontos as $confronto) {
            [$nome1, $nome2] = $confronto;

            $res1 = $mysqli->query("SELECT id, email FROM jogadores WHERE nome = '$nome1' LIMIT 1");
            $res2 = $mysqli->query("SELECT id, email FROM jogadores WHERE nome = '$nome2' LIMIT 1");

            if ($res1->num_rows == 0 || $res2->num_rows == 0) continue;

            $id1 = $res1->fetch_assoc();
            $id2 = $res2->fetch_assoc();

            $idJogador1 = $id1['id'];
            $idJogador2 = $id2['id'];

            // Verifica se já existe
            $verifica = $mysqli->query("
            SELECT 1 FROM jogos 
            WHERE id_campeonato = '$idCampeonato'
            AND (
                (id_jogador1 = '$idJogador1' AND id_jogador2 = '$idJogador2') OR
                (id_jogador1 = '$idJogador2' AND id_jogador2 = '$idJogador1')
            )
            AND fase = 'chave'
            LIMIT 1
            ");
            if ($verifica->num_rows > 0) continue;

            $mesa = $mesasDisponiveis[$liberacoes] ?? 'Aguardando';
            $statusMesa = ($mesa != 'Aguardando') ? 'ocupada' : null;

            // Inserir jogo
            $mysqli->query("
            INSERT INTO jogos (id_campeonato, id_jogador1, id_jogador2, mesa, status, fase)
            VALUES ('$idCampeonato', '$idJogador1', '$idJogador2', '$mesa', 'Pendente', 'chave')
            ");

            if ($mesa != 'Aguardando') {
                // Atualiza status da mesa
                $mysqli->query("UPDATE mesas SET status = 'ocupada' WHERE id_campeonato = '$idCampeonato' AND mesa = '$mesa'");

                // Permitir passar resultado
                $mysqli->query("
                UPDATE jogadores_campeonatos 
                SET passar_resultado_chave = 1 
                WHERE id_jogador IN ('$idJogador1', '$idJogador2') AND id_campeonato = '$idCampeonato'
            ");

                // Enviar e-mails
                $msg1 = "<p>Olá, <b>{$nome1}</b>!</p><p>Você foi chamado para jogar contra <b>{$nome2}</b> na <b>$mesa</b>.</p><p>Boa sorte!</p>";
                $msg2 = "<p>Olá, <b>{$nome2}</b>!</p><p>Você foi chamado para jogar contra <b>{$nome1}</b> na <b>$mesa</b>.</p><p>Boa sorte!</p>";

                enviarEmail($id1['email'], "Liga - Novo Jogo!", $msg1);
                enviarEmail($id2['email'], "Liga - Novo Jogo!", $msg2);

                $liberacoes++;
            }
        }
    }
}

//Verifica se há outros jogos da chave aguardando liberação
$queryJogosAguardando = "
    SELECT * FROM jogos 
    WHERE fase = 'chave' 
      AND status = 'Pendente'
      AND mesa = 'Aguardando'
      AND id_campeonato = '$idCampeonato'
    LIMIT 1
";

$resultPendentes = $mysqli->query($queryJogosAguardando);

if ($resultPendentes && $resultPendentes->num_rows > 0) {
    $jogoPendente = $resultPendentes->fetch_assoc();
    $idJogoPendente = $jogoPendente['id'];
    $id1 = $jogoPendente['id_jogador1'];
    $id2 = $jogoPendente['id_jogador2'];

    // Busca uma mesa disponível
    $queryMesa = "SELECT mesa FROM mesas 
                  WHERE id_campeonato = '$idCampeonato' AND status = 'disponível' 
                  LIMIT 1";
    $mesaResult = $mysqli->query($queryMesa);

    if ($mesaResult && $mesaResult->num_rows > 0) {
        $mesa = $mesaResult->fetch_assoc()['mesa'];

        // Atualiza o jogo com a mesa disponivel
        $mysqli->query("UPDATE jogos SET mesa = '$mesa' WHERE id = '$idJogoPendente'");

        // Atualiza a mesa para ocupada
        $mysqli->query("UPDATE mesas SET status = 'ocupada' 
                        WHERE id_campeonato = '$idCampeonato' AND mesa = '$mesa'");

        // Libera os dois jogadores pra passar o resultado
        $mysqli->query("UPDATE jogadores_campeonatos 
                        SET passar_resultado_chave = 1 
                        WHERE id_jogador IN ('$id1', '$id2') AND id_campeonato = '$idCampeonato'");

        // Envia email para os dois
        $queryEmails = "
            SELECT id, nome, email FROM jogadores 
            WHERE id IN ('$id1', '$id2')
            ";
        $resEmails = $mysqli->query($queryEmails);

        $nomes = []; // id => nome
        $emails = []; // id => email

        while ($row = $resEmails->fetch_assoc()) {
            $nomes[$row['id']] = $row['nome'];
            $emails[$row['id']] = $row['email'];
        }

        // Enviar email personalizado para cada jogador
        foreach ([$id1, $id2] as $idAtual) {
            $nome = $nomes[$idAtual] ?? '';
            $email = $emails[$idAtual] ?? '';
            $adversarioId = ($idAtual == $id1) ? $id2 : $id1;
            $adversarioNome = $nomes[$adversarioId] ?? 'seu adversário';

            if ($email) {
                $mensagem = "<p>Olá, <b>{$nome}</b>!</p>
                 <p>Você foi chamado para jogar contra <b>{$adversarioNome}</b> na <b>{$mesa}</b>.</p>
                 <p>Boa sorte!</p>";

                enviarEmail($email, "Liga - Novo Jogo!", $mensagem);
            }
        }
    }
}

echo json_encode(["status" => "sucesso", "mensagem" => "Resultado da chave atualizado com sucesso."]);
exit;

// Função de envio de email
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
