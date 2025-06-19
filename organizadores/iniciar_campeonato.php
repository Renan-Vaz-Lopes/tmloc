<?php
include('../conexao.php');
include('../protect.php');
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

require '../config.php';
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

header('Content-Type: application/json');

if (!isset($_POST['id'])) {
    echo json_encode(["status" => "erro", "mensagem" => "Erro: ID do campeonato não recebido."]);
    exit;
}

function lerJogadoresExcel($caminho)
{
    $spreadsheet = IOFactory::load($caminho);
    $sheet = $spreadsheet->getActiveSheet();
    $dados = $sheet->toArray();

    $jogadores = [];
    foreach ($dados as $row) {
        if (!empty($row[0]) && !empty($row[1]) && !empty($row[2]) && !empty($row[3])) {
            $nome = $row[0];
            $dataNascimento = $row[1];
            $sexo = $row[2];

            $anoNascimento = substr($dataNascimento, -4);
            $categoria = calcularCategoria($anoNascimento, $sexo);
            $jogadores[] = [
                'nome' => $nome,
                'ano_nascimento' => $anoNascimento,
                'sexo' => $sexo,
                'categoria' => $categoria
            ];
        }
    }

    return $jogadores;
}

function calcularCategoria($anoNascimento, $sexo)
{
    $anoAtual = date("Y");
    $idade = (int)$anoAtual - (int)$anoNascimento;

    if ($sexo == 'M') {
        if ($idade < 9) {
            return "Sub-09 Masc";
        } elseif ($idade < 11) {
            return "Sub-11 Masc";
        } elseif ($idade < 12) {
            return "Mirim 1 Masc";
        } elseif ($idade < 13) {
            return "Mirim 2 Masc";
        } elseif ($idade < 14) {
            return "Infantil 1 Masc";
        } elseif ($idade < 15) {
            return "Infantil 2 Masc";
        } elseif ($idade < 17) {
            return "Juvenil 1 Masc";
        } elseif ($idade < 18) {
            return "Juvenil 2 Masc";
        } elseif ($idade < 21) {
            return "Sub-21 Masc";
        } elseif ($idade < 30) {
            return "Adulto Masc";
        } elseif ($idade < 40) {
            return "Sênior 30";
        } elseif ($idade < 50) {
            return "Veterano 40";
        } else {
            return "Veterano 50";
        }
    } else {
        if ($idade <= 13) {
            return "Sub-13 Fem";
        } elseif ($idade <= 15) {
            return "Sub-15 Fem";
        } elseif ($idade <= 19) {
            return "Sub-19 Fem";
        } else {
            return "Lady 30";
        }
    }
}

$idCampeonato = $_POST['id'];
$uploadDir = "../uploads/";
$arquivoOrdemJogos = $uploadDir . "ORDEM1JOGOS.xlsx";
$arquivoJogadores = $uploadDir . "jogadores.xlsx";
$arquivoGrupos = $uploadDir . "GRUPOS.xlsx";
$jogadores = lerJogadoresExcel('../uploads/jogadores.xlsx');

if (!file_exists($arquivoOrdemJogos) || !file_exists($arquivoJogadores) || !file_exists($arquivoGrupos)) {
    echo json_encode(["status" => "erro", "mensagem" => "Erro: Um dos arquivos não foi encontrado."]);
    exit;
}

$spreadsheetOrdemJogos = IOFactory::load($arquivoOrdemJogos);
$spreadsheetJogadores = IOFactory::load($arquivoJogadores);
$spreadsheetGrupos = IOFactory::load($arquivoGrupos);
// Carrega a lista de categorias válidas
$caminhoCategoriasValidas = $uploadDir . "categorias_validas.json";
if (!file_exists($caminhoCategoriasValidas)) {
    echo json_encode(["status" => "erro", "mensagem" => "Arquivo de categorias válidas não encontrado."]);
    exit;
}

// Gera categorias válidas direto dos jogadores
$categoriasValidas = [];

foreach ($jogadores as $jogador) {
    if (!in_array($jogador['categoria'], $categoriasValidas)) {
        $categoriasValidas[] = $jogador['categoria'];
    }
}

error_log("Categorias válidas carregadas do JSON:");
foreach ($categoriasValidas as $cat) {
    error_log("Categoria válida: [$cat]");
}

$jogadoresEmails = obterEmailsJogadores($spreadsheetJogadores);
$jogadoresGrupos = obterGruposDosJogadores($spreadsheetGrupos);

$sheetOrdemJogos = $spreadsheetOrdemJogos->getActiveSheet();
$dadosOrdemJogos = $sheetOrdemJogos->toArray();

$erros = [];
$categoriaAtual = "";
$mesas = [];
$gruposChamados = [];

// **Passo 1: Coletar os grupos dos primeiros jogos**
foreach ($dadosOrdemJogos as $row) {

    if (!empty($row[0]) && strpos($row[0], 'Mesa') === false) {
        $categoriaAtual = trim($row[0]);
        continue;
    }

    if (!empty($row[0]) && !empty($row[1])) {
        $mesa = trim($row[0]);
        $grupo = trim($row[1]);
        $gruposChamados[$categoriaAtual][$grupo] = $mesa;
    }
}

// Passo 1.5: Cadastrar mesas disponíveis com base nos grupos chamados
$mesasRegistradas = [];
foreach ($gruposChamados as $gruposPorCategoria) {
    foreach ($gruposPorCategoria as $mesa) {
        if (!in_array($mesa, $mesasRegistradas)) {
            $mesasRegistradas[] = $mesa;
            $mysqli->query("INSERT INTO mesas (id_campeonato, mesa, `status`) VALUES ('$idCampeonato', '$mesa', 'ocupada')");
        }
    }
}


// **Passo 2: Criar os grupos no banco de dados e adicionar jogadores**
foreach ($jogadoresGrupos as $categoria => $grupos) {
    if (!in_array($categoria, $categoriasValidas)) {
        error_log("⛔ Categoria ignorada (não tá no JSON): [$categoria]");
        continue;
    }

    foreach ($grupos as $grupo => $jogadores) {
        $mesa = $gruposChamados[$categoria][$grupo] ?? "Aguardando Mesa";
        if (empty($categoria) || empty($grupo) || empty($mesa)) {
            continue; // Garante que não insira valores vazios
        }

        // **Inserir grupo no banco**
        $queryGrupo = "INSERT INTO grupos (id_campeonato, categoria, grupo, mesa) VALUES ('$idCampeonato', '$categoria', '$grupo', '$mesa')";
        if (!$mysqli->query($queryGrupo)) {
            $erros[] = "Erro ao inserir $grupo na categoria $categoria: " . $mysqli->error;
            continue;
        }
        $idGrupo = $mysqli->insert_id;

        $jogadoresIds = [];

        foreach ($jogadores as $jogadorNome) {
            $categoriaSegura = $mysqli->real_escape_string($categoria);
            $jogadorNomeSegura = $mysqli->real_escape_string($jogadorNome);

            $resultado = $mysqli->query("SELECT id FROM jogadores WHERE nome = '$jogadorNomeSegura' LIMIT 1");
            if ($resultado && $resultado->num_rows > 0) {
                $idJogador = $resultado->fetch_assoc()['id'];

                $check = $mysqli->query("SELECT id FROM jogadores_campeonatos 
                    WHERE id_jogador = '$idJogador' 
                    AND id_campeonato = '$idCampeonato' 
                    AND categoria = '$categoriaSegura' 
                    LIMIT 1");

                if ($check->num_rows == 0) {
                    $inserido = $mysqli->query("INSERT INTO jogadores_campeonatos 
                        (id_jogador, id_campeonato, passar_resultado_grupo, categoria) 
                        VALUES ('$idJogador', '$idCampeonato', 0, '$categoriaSegura')");

                    if (!$inserido) {
                        error_log("Erro ao inserir jogador $jogadorNome na categoria $categoriaSegura: " . $mysqli->error);
                    }
                }

                $jogadoresIds[] = $idJogador;
            } else {
                error_log("Jogador $jogadorNome não encontrado.");
            }
        }

        // **Criar os jogos para o grupo**
        $totalJogadores = count($jogadoresIds);
        for ($i = 0; $i < $totalJogadores - 1; $i++) {
            for ($j = $i + 1; $j < $totalJogadores; $j++) {
                $idJogador1 = $jogadoresIds[$i];
                $idJogador2 = $jogadoresIds[$j];

                $queryJogo = "INSERT INTO jogos (id_campeonato, id_grupo, id_jogador1, id_jogador2, mesa, sets_jogador1, sets_jogador2, status, fase) 
                              VALUES ('$idCampeonato', '$idGrupo', '$idJogador1', '$idJogador2', '$mesa', NULL, NULL, 'Pendente', 'grupos')";
                $mysqli->query($queryJogo);
            }
        }
    }
}



// **Passo 3: Atualizar jogadores dos primeiros jogos para passar_resultado_grupo = 1 e enviar e-mails**
foreach ($gruposChamados as $categoria => $grupos) {
    foreach ($grupos as $grupo => $mesa) {
        foreach ($jogadoresGrupos[$categoria][$grupo] as $jogadorNome) {
            $resultado = $mysqli->query("SELECT id FROM jogadores WHERE nome = '$jogadorNome' LIMIT 1");
            if ($resultado && $resultado->num_rows > 0) {
                $idJogador = $resultado->fetch_assoc()['id'];

                // Atualiza passar_resultado_grupo para 1
                $mysqli->query("UPDATE jogadores_campeonatos SET passar_resultado_grupo = 1 WHERE id_jogador = '$idJogador' AND id_campeonato = '$idCampeonato'");

                // Enviar e-mail somente para os jogadores chamados nos primeiros jogos
                $emailEncontrado = null;
                foreach ($jogadoresEmails as $nomeSalvo => $email) {
                    if (normaliza($nomeSalvo) == normaliza($jogadorNome)) {
                        $emailEncontrado = $email;
                        break;
                    }
                }

                if ($emailEncontrado !== null) {
                    $assunto = "Liga - Mesa do seu grupo!";
                    $mensagem = "<p>Olá, <b>{$jogadorNome}</b>!</p><p>Seu grupo está na <b>{$mesa}</b>.</p><p>Boa sorte!</p>";
                
                    error_log("📩 Enviando e-mail para: {$emailEncontrado} (Jogador: {$jogadorNome})");
                    $resultadoEnvio = enviarEmail($emailEncontrado, $assunto, $mensagem);
                    error_log("📨 Resultado do envio: " . json_encode($resultadoEnvio));
                } else {
                    error_log("❌ Não encontrou e-mail para o jogador: {$jogadorNome}");
                }
                
            }
        }
    }
}

if (empty($erros)) {
    echo json_encode(["status" => "sucesso", "mensagem" => "O campeonato começou! (E-mails enviados aos jogadores dos primeiros jogos)"]);
} else {
    echo json_encode(["status" => "erro", "mensagem" => "Alguns e-mails falharam.", "detalhes" => $erros]);
}
exit;





/**
 * Função para enviar e-mails com PHPMailer
 */
function enviarEmail($emailDestino, $assunto, $mensagem)
{
    $mail = new PHPMailer(true);
    $CONFIG = getConfig();


    try {
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        // Configurações do servidor
        $mail->isSMTP();
        $mail->Host = $CONFIG['email']['host'];
        $mail->SMTPAuth = true;
        $mail->Port = $CONFIG['email']['port'];
        $mail->SMTPSecure = $CONFIG['email']['SMTPSecure'];
        $mail->Username = $CONFIG['email']['username'];
        $mail->Password = $CONFIG['email']['password'];

        // Configuração de charset
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($CONFIG['email']['username'], 'TmLoc');
        $mail->addAddress($emailDestino);

        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body = $mensagem;

        $mail->send();
        return ["status" => "sucesso", "mensagem" => "E-mail enviado para $emailDestino"];
    } catch (Exception $e) {
        return ["status" => "erro", "mensagem" => "Erro ao enviar para $emailDestino: " . $mail->ErrorInfo];
    }
}

/**
 * Função para obter e-mails dos jogadores do arquivo Excel
 */
function obterEmailsJogadores($spreadsheetJogadores)
{
    $sheet = $spreadsheetJogadores->getActiveSheet();
    $dados = $sheet->toArray();

    $jogadoresEmails = [];
    foreach ($dados as $row) {
        if (!empty($row[0]) && !empty($row[3])) {
            $nome = trim($row[0]); // Nome do jogador
            $email = trim($row[3]); // E-mail do jogador

            // Armazena o e-mail com o nome do jogador como chave
            $jogadoresEmails[$nome] = $email;
        }
    }
    return $jogadoresEmails;
}

function normaliza($str)
{
    return mb_strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', '', iconv('UTF-8', 'ASCII//TRANSLIT', $str)));
}


/**
 * Função para obter os grupos dos jogadores a partir do arquivo GRUPOS.xlsx
 */
function obterGruposDosJogadores($spreadsheetGrupos)
{
    $jogadoresGrupos = [];

    foreach ($spreadsheetGrupos->getSheetNames() as $sheetNameOriginal) {
        $categoria = trim($sheetNameOriginal); // garante que não tem espaço no nome da categoria

        $sheet = $spreadsheetGrupos->getSheetByName($sheetNameOriginal);
        $dados = $sheet->toArray();

        $grupoAtual = "";
        foreach ($dados as $row) {
            if (!empty($row[0]) && strpos($row[0], "Grupo") !== false) {
                $grupoAtual = trim($row[0]); // Exemplo: "Grupo 1"
                continue;
            }

            if (!empty($row[0]) && $grupoAtual) {
                $jogadoresGrupos[$categoria][$grupoAtual][] = trim($row[0]);
            }
        }
    }

    return $jogadoresGrupos;
}
