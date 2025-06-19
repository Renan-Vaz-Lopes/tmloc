<?php
include('../conexao.php');
include('../protect.php');
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

$erro = [];
$mensagem_erro = '';
$arquivo_jogadores_grupos = '';
$arquivo_ordem_jogos = '';
$id_organizador = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['descricao'])) {

    $descricao = $mysqli->real_escape_string($_POST['descricao']);

    if (empty($descricao)) {
        $erro[] = "Por favor, preencha o campo descrição.";
    }

    $ConsultaExisteOCampeonato = "SELECT * FROM campeonatos WHERE descricao = '$descricao'";
    $campeonato = $mysqli->query($ConsultaExisteOCampeonato) or die($mysqli->error);

    if (!empty($_FILES['categorias']['name']) && !empty($_FILES['jogadores']['name'])) {
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $categorias_path = $upload_dir . basename($_FILES['categorias']['name']);
        $jogadores_path = $upload_dir . basename($_FILES['jogadores']['name']);

        if (
            move_uploaded_file($_FILES['categorias']['tmp_name'], $categorias_path) &&
            move_uploaded_file($_FILES['jogadores']['tmp_name'], $jogadores_path)
        ) {

            $sql_code = "INSERT INTO campeonatos (id_organizador, descricao) VALUES ('$id_organizador', '$descricao')";
            $confirma = $mysqli->query($sql_code);

            if ($confirma) {

                $jogadores = lerJogadoresExcel($jogadores_path);
                shuffle($jogadores);

                $gruposPorCategoria = distribuirEmGruposPorCategoria($jogadores);
                gerarArquivosPorCategoria($gruposPorCategoria, $upload_dir);
                // Salva a lista de categorias reais para uso posterior
                file_put_contents($upload_dir . "categorias_validas.json", json_encode(array_keys($gruposPorCategoria)));

                $arquivo_final = gerarArquivoFinal($gruposPorCategoria, $upload_dir);

                $ordem1jogos = gerarOrdemPrimeirosJogos($arquivo_final, $categorias_path, 'ORDEM1JOGOS.xlsx', $upload_dir);

                $msg_sucesso = "Campeonato cadastrado com sucesso!
                    Arquivos disponíveis para download abaixo:
                    ";
            } else {
                $erro[] = "Erro ao cadastrar no banco de dados.";
            }
        } else {
            $erro[] = "Erro ao fazer upload dos arquivos.";
        }
    } else {
        $erro[] = "Todos os arquivos devem ser enviados.";
    }

    if (!empty($erro)) {
        $mensagem_erro = "<ul>";
        foreach ($erro as $mensagem) {
            $mensagem_erro .= "<li>$mensagem</li>";
        }
        $mensagem_erro .= "</ul>";
    }
}

function lerJogadoresExcel($caminho)
{
    $spreadsheet = IOFactory::load($caminho);
    $sheet = $spreadsheet->getActiveSheet();
    $dados = $sheet->toArray();

    $jogadores = [];
    foreach ($dados as $row) {
        if (!empty($row[0]) && !empty($row[1]) && !empty($row[2])) {
            $nome = $row[0];
            $dataNascimento = $row[1];
            $sexo = $row[2];
            $email = $row[3] ?? ''; // Email opcional

            $anoNascimento = substr($dataNascimento, -4);
            $categoria = calcularCategoria($anoNascimento, $sexo);

            $jogadores[] = [
                'nome' => $nome,
                'ano_nascimento' => $anoNascimento,
                'sexo' => $sexo,
                'categoria' => $categoria,
                'email' => $email,
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

function distribuirEmGruposPorCategoria($jogadores)
{
    $gruposPorCategoria = [];

    foreach ($jogadores as $jogador) {
        $categoria = $jogador['categoria'];
        $gruposPorCategoria[$categoria][] = $jogador;
    }

    return $gruposPorCategoria;
}


function gerarArquivosPorCategoria($dadosCategorias, $upload_dir)
{
    foreach ($dadosCategorias as $categoria => $jogadores) {
        // Define o tamanho dos grupos
        $tamanhoGrupo = 3;

        // Distribuir os jogadores em grupos
        $grupos = distribuirEmGrupos($jogadores, $tamanhoGrupo);

        // Criar um novo arquivo Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $linha = 1;
        $grupoAtual = 1;

        foreach ($grupos as $grupo) {
            // Se o grupo não está vazio, adiciona o título
            if (!empty($grupo)) {
                $sheet->setCellValue("A$linha", "Grupo $grupoAtual");
                $sheet->getStyle("A$linha")->getFont()->setBold(true);
                $linha++;
                $grupoAtual++;
            }

            // Adiciona os jogadores ao grupo
            foreach ($grupo as $jogador) {
                $sheet->setCellValue("A$linha", $jogador['nome']);
                $linha++;
            }
        }

        // Definir o caminho para salvar o arquivo no diretório de upload
        $fileName = $upload_dir . "categoria_{$categoria}.xlsx";

        // Salvar arquivo Excel
        $writer = new Xlsx($spreadsheet);
        $writer->save($fileName);
    }
}

function distribuirEmGrupos($jogadores, $tamanhoGrupoDesejado)
{
    $grupos = [];
    $totalJogadores = count($jogadores);

    if ($totalJogadores == 0) {
        return $grupos;
    }

    $grupoAtual = 1;
    $index = 0;

    while ($index < $totalJogadores) {
        $restantes = $totalJogadores - $index;

        if ($restantes == 1 && $grupoAtual > 1) {
            // Se sobrar 1 jogador, adiciona no penúltimo grupo
            $grupos[$grupoAtual - 1][] = $jogadores[$index];
            break;
        } elseif ($restantes == 2) {
            // Se sobrar 2, cria um grupo de 2
            $grupos[$grupoAtual] = array_slice($jogadores, $index, 2);
            break;
        } else {
            // Cria grupo normal de 3
            $grupos[$grupoAtual] = array_slice($jogadores, $index, $tamanhoGrupoDesejado);
            $index += $tamanhoGrupoDesejado;
            $grupoAtual++;
        }
    }

    return $grupos;
}




function gerarArquivoFinal($gruposPorCategoria, $upload_dir)
{
    $arquivo_final = $upload_dir . "GRUPOS.xlsx";

    // Criando a planilha principal
    $spreadsheet = new Spreadsheet();

    // Adicionando categorias
    foreach ($gruposPorCategoria as $categoria => $jogadoresDaCategoria) {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($categoria);

        // Nome da categoria no topo em negrito
        $sheet->setCellValue('A1', $categoria);
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Distribuir corretamente os jogadores em grupos
        $grupos = distribuirEmGrupos($jogadoresDaCategoria, 3);
        $linha = 3;
        $grupoAtual = 1;

        foreach ($grupos as $grupo) {
            if (!empty($grupo)) {
                $sheet->setCellValue("A$linha", "Grupo $grupoAtual");
                $sheet->getStyle("A$linha")->getFont()->setBold(true);
                $linha++;
                $grupoAtual++;
            }

            foreach ($grupo as $jogador) {
                $sheet->setCellValue("A$linha", $jogador['nome']);
                $linha++;
            }
        }
    }

    // Agora que já criamos todas as planilhas, removemos a primeira vazia
    $spreadsheet->removeSheetByIndex(0);

    // Salvar o arquivo final
    $writer = new Xlsx($spreadsheet);
    $writer->save($arquivo_final);

    return $arquivo_final;
}



function gerarOrdemPrimeirosJogos($arquivoGrupos, $arquivoCategorias, $arquivoSaida, $upload_dir)
{
    $arquivoSaida = $upload_dir . "ORDEM1JOGOS.xlsx";

    // Carregar os arquivos Excel
    $spreadsheetGrupos = IOFactory::load($arquivoGrupos);
    $spreadsheetCategorias = IOFactory::load($arquivoCategorias);

    // Criar uma nova planilha para o arquivo de saída
    $spreadsheetSaida = new Spreadsheet();
    $sheet = $spreadsheetSaida->getActiveSheet();

    $linha = 1;

    // **1. Ler as mesas disponíveis para cada categoria no arquivo Categorias.xlsx**
    $mesasPorCategoria = [];
    $categoriaSheet = $spreadsheetCategorias->getActiveSheet();
    foreach ($categoriaSheet->getRowIterator(1) as $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(TRUE);

        $cells = [];
        foreach ($cellIterator as $cell) {
            $cells[] = $cell->getValue();
        }

        if (!empty($cells[0]) && !empty($cells[1])) {
            $categoriaNome = trim($cells[0]);
            $mesas = array_map('trim', explode(',', $cells[1])); // Divide as mesas e remove espaços extras

            $mesasPorCategoria[$categoriaNome] = $mesas;
        }
    }

    // **2. Percorrer cada categoria e associar grupos às mesas**
    foreach ($spreadsheetGrupos->getSheetNames() as $categoria) {
        if (!isset($mesasPorCategoria[$categoria])) {
            continue; // Se a categoria não tem mesas definidas, pula
        }

        // Pega os grupos dentro da categoria
        $planilhaGrupo = $spreadsheetGrupos->getSheetByName($categoria);
        if (!$planilhaGrupo) {
            continue;
        }

        $grupos = [];
        foreach ($planilhaGrupo->getRowIterator() as $row) {
            $cell = $row->getCellIterator('A')->current();
            if ($cell) {
                $valor = trim($cell->getValue());
                if (strpos($valor, 'Grupo') !== false) {
                    // Extrai o número do grupo (exemplo: "Grupo 4" -> pega o 4)
                    preg_match('/\d+/', $valor, $matches);
                    if (!empty($matches)) {
                        $numeroGrupo = (int)$matches[0];
                        $grupos[$numeroGrupo] = $valor;
                    }
                }
            }
        }

        if (empty($grupos)) {
            continue; // Se não há grupos, pula essa categoria
        }

        // **3. Ordenar os grupos do maior número para o menor**
        krsort($grupos); // Ordena as chaves (números dos grupos) em ordem decrescente

        // **4. Escrever no ORDEM1JOGOS.xlsx**
        $sheet->setCellValue("A$linha", $categoria);
        $linha++;

        $mesasDisponiveis = $mesasPorCategoria[$categoria];
        $numMesas = count($mesasDisponiveis);
        $mesaIndex = 0;

        foreach ($grupos as $grupoNumero => $grupoNome) {
            if ($mesaIndex >= $numMesas) {
                break; // Para de alocar grupos se já usou todas as mesas
            }

            $mesa = $mesasDisponiveis[$mesaIndex];
            $sheet->setCellValue("A$linha", "Mesa $mesa");
            $sheet->setCellValue("B$linha", $grupoNome);
            $linha++;

            $mesaIndex++; // Vai para a próxima mesa
        }

        $linha++; // Linha extra entre categorias
    }

    // **5. Salvar o arquivo**
    $writer = new Xlsx($spreadsheetSaida);
    $writer->save($arquivoSaida);

    return $arquivoSaida;
}

?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Campeonato</title>
    <link rel="stylesheet" type="text/css" href="../css/cadastrar_campeonato.css">
</head>

<body>
    <a class="button" href="dashboard_organizador.php">Voltar</a>
    <div class="form-container">
        <div>
            <?php if (!empty($mensagem_erro)) { ?>
                <div class="erro">
                    <?php echo $mensagem_erro; ?>
                </div>
            <?php } ?>
        </div>

        <div>
            <?php if (!empty($msg_sucesso)) { ?>
                <div class="sucesso">
                    <?php echo $msg_sucesso; ?>
                </div>
            <?php } ?>
        </div>

        <?php if (!empty($arquivo_final)) { ?>
            <div class="downloads">
                <h3>Arquivos:</h3>
                <a class="lista" href="<?= $arquivo_final ?>" download>GRUPOS</a><br>
                <a class="lista" href="<?= $ordem1jogos ?>" download>ORDEM DOS 1°JOGOS</a><br>
            </div>
        <?php } ?>

        <div>
            <form action="" id="formCadastrar" method="POST" enctype="multipart/form-data">
                <p>
                    <label>Descrição</label><br>
                    <input class="input-text" type="text" name="descricao" value="<?= isset($descricao) ? $descricao : '' ?>">
                </p>

                <p>
                    <label for="">Lista de Categorias(<a href="#" class="aviso" onclick="abrirModalCategorias()"> Como deve ser o arquivo? </a>)</label> <br>
                    <input type="file" name="categorias" accept=".xlsx,.xls">
                </p>

                <div id="modal-categorias" class="modal">
                    <div class="modal-content">
                        <span class="close" onclick="fecharModalCategorias()">&times;</span>
                        <h2>O arquivo aceito é do tipo Excel</h2>
                        <p>
                            A lista de categorias deve conter na primeira célula: descrição da categoria e na 2º célula quais mesas vão ser destinadas,
                            exemplo: “Adulto” na 1º célula e “12,13,14” na célula ao lado, o que irá compor a 1º linha, depois se deve repetir o processo para a
                            próxima linha.
                        </p>
                    </div>
                </div>

                <p>
                    <label for="">Lista de Jogadores(<a href="#" class="aviso" onclick="abrirModalJogadores()"> Como deve ser o arquivo? </a>)</label> <br>
                    <input type="file" name="jogadores" accept=".xlsx,.xls">
                </p>

                <div id="modal-jogadores" class="modal">
                    <div class="modal-content">
                        <span class="close" onclick="fecharModalJogadores()">&times;</span>
                        <h2>O arquivo aceito é do tipo Excel</h2>
                        <p>
                            A lista de jogadores deve conter na 1ºcelula: Nome, na 2ºcélula: Data de Nascimento, na 3ºcélula: Sexo e na 4º célula: email.
                            o que irá compor a 1º linha, depois se deve repetir o processo para a próxima linha.
                        </p>
                    </div>
                </div>

                <p class="meio">
                    <button type="submit" class="semBorda" id="btnCadastrar">Cadastrar</button>
                </p>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('formCadastrar').addEventListener('submit', function() {
            document.getElementById('btnCadastrar').disabled = true;
        });
    </script>

    <script>
        function abrirModalCategorias() {
            document.getElementById("modal-categorias").style.display = "block";
        }

        function abrirModalJogadores() {
            document.getElementById("modal-jogadores").style.display = "block";
        }

        function fecharModalCategorias() {
            document.getElementById("modal-categorias").style.display = "none";
        }

        function fecharModalJogadores() {
            document.getElementById("modal-jogadores").style.display = "none";
        }

        window.onclick = function(event) {
            const modalCategorias = document.getElementById("modal-categorias");
            const modalJogadores = document.getElementById("modal-jogadores");

            if (event.target === modalCategorias) {
                modalCategorias.style.display = "none";
            } else if (event.target === modalJogadores) {
                modalJogadores.style.display = "none";
            }
        }
    </script>
</body>

</html>