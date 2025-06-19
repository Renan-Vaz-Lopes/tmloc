<?php
include('../conexao.php');
include('../protect.php');

if (isset($_POST['indiceCategoria'], $_POST['estado'], $_POST['cidade'])) {
    $indiceCategoria = $_POST['indiceCategoria'];
    $estado = $_POST['estado'];
    $cidade = $_POST['cidade'];

    // Ajusta a consulta SQL conforme a categoria selecionada
    if ($indiceCategoria == '1') { 
        // Para "Marcação de Jogos", filtrar por estado e cidade
        $consulta_publicacao = "SELECT * FROM publicacoes 
                                WHERE estado = '$estado' 
                                AND cidade = '$cidade' 
                                AND categoria = '$indiceCategoria'
                                ORDER BY id DESC";
    } else {
        // Para as demais categorias, mostrar todas as publicações
        $consulta_publicacao = "SELECT * FROM publicacoes 
                                WHERE categoria = '$indiceCategoria'
                                ORDER BY id DESC";
    }

    $con_publicacao = $mysqli->query($consulta_publicacao);

    if ($con_publicacao->num_rows > 0) {
        $html = '<div class="feed-container">';
        $html .= '<p class="explicacao">Publicações:</p>';

        while ($dados = $con_publicacao->fetch_array(MYSQLI_ASSOC)) {
            $id_publicacao = $dados['id'];

            $texto_nivel = match ($dados['nivel'] ?? '') {
                '1' => 'Iniciante',
                '2' => 'Intermediário',
                '3' => 'Avançado',
                default => 'Não definido',
            };

            // Consulta para buscar respostas da publicação
            $consulta_respostas = "SELECT * FROM respostas WHERE id_publicacao = $id_publicacao";
            $con_respostas = $mysqli->query($consulta_respostas);

            $consulta_qnt_respostas = "SELECT COUNT(id) as qnt_respostas FROM respostas WHERE id_publicacao = '$id_publicacao'";
            $con_quant_respostas = $mysqli->query($consulta_qnt_respostas);

            $quant_respostas = 0;
            if ($con_quant_respostas) {
                $dados_quant_respostas = $con_quant_respostas->fetch_assoc();
                $quant_respostas = $dados_quant_respostas['qnt_respostas'];
            }

            $html .= '<div class="feed-item">';
            $html .= '<div class="feed-item-header">';
            $html .= '<div>';
            $html .= '<h2>' . htmlspecialchars($dados['nome']) . ' (' . htmlspecialchars($texto_nivel) . ')</h2>';
            $html .= '<p>Contato: ' . htmlspecialchars($dados['descricao_contato']) . '</p>';
            $html .= '<p>' . htmlspecialchars($dados['publi']) . '</p>';
            if (date('d/m/Y', strtotime($dados['data_jogo'])) != '30/11/-0001') {
                $html .= '<p>Data do Evento: ' . date('d/m/Y', strtotime($dados['data_jogo'])) . '</p>';
            }
            $html .= '</div>';

            // Verifica se o jogador pode editar/excluir a publicação
            if ($dados['id_jogador'] == $_SESSION['id_jogador']) {
                $html .= '<div class="botoes-ud">';
                $html .= '<a class="tirarUnderline" href="atualizar_publicacao.php?codigo=' . $dados["id"] . '">';
                $html .= '<img width="30" height="30" src="https://img.icons8.com/ios-glyphs/30/change--v1.png" alt="Editar"/>';
                $html .= '</a>';
                $html .= '<a href="#" class="excluirPublicacao" data-codigo="' . $dados["id"] . '">';
                $html .= '<img width="30" height="30" src="https://img.icons8.com/material-sharp/30/filled-trash.png" alt="Excluir"/>';
                $html .= '</a>';
                $html .= '</div>';
            }

            $html .= '</div>'; // Fim do feed-item-header
            $html .= '<div class="feed-item-footer">';
            $html .= '<span>' . htmlspecialchars($dados['data_publi']) . '</span>';
            $html .= '<span>' . htmlspecialchars($dados['hora_publi']) . '</span>';
            $html .= '</div>';
            $html .= '<br>';

            $html .= '<div class="enviar-resposta-container">';
            $html .= '<textarea id="texto-resposta-' . $dados['id'] . '" rows="5" cols="35" placeholder="Escreva sua resposta..."></textarea>';
            $html .= '<br><br>';
            $html .= '<button onclick="enviarResposta(' . $id_publicacao . ')" class="botao-enviar">Enviar</button>';
            $html .= '<br>';
            $html .= '<button onclick="alternarRespostas(' . $dados['id'] . ')" class="botao">Mostrar respostas (' . $quant_respostas . ')</button>';
            $html .= '</div>';

            // Exibição de respostas
            $html .= '<div class="resposta-container-' . $dados['id'] . '" style="display: none;">';

            $primeiraVez = true;
            while ($resposta = $con_respostas->fetch_array(MYSQLI_ASSOC)) {
                if ($primeiraVez) {
                    $html .= '<p>Respostas:</p>';
                    $primeiraVez = false;
                }
                $consulta_jogador = "SELECT * FROM jogadores WHERE id = '{$resposta['id_jogador']}'";
                $con_jogador = $mysqli->query($consulta_jogador);

                if ($con_jogador && $con_jogador->num_rows > 0) {
                    $jogador_que_respondeu = $con_jogador->fetch_array(MYSQLI_ASSOC);
                } else {
                    $jogador_que_respondeu = null;
                }

                if ($jogador_que_respondeu) {
                    $nivel = match ($jogador_que_respondeu['nivel'] ?? '') {
                        '1' => 'Iniciante',
                        '2' => 'Intermediário',
                        '3' => 'Avançado',
                        default => 'Não definido',
                    };
                } else {
                    $nivel = 'Não definido';
                }

                $html .= '<div class="feed-item-header">';
                $html .= '<div>';
                $html .= '<h3>' . htmlspecialchars($jogador_que_respondeu['nome']) . ' (' . htmlspecialchars($nivel) . ')</h3>';
                $html .= '<p>Contato: ' . htmlspecialchars($jogador_que_respondeu['descricao_contato']) . '</p>';
                $html .= '<p>' . htmlspecialchars($resposta['texto_resposta']) . '</p>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '<div class="feed-item-footer">';
                $html .= '<span>' . htmlspecialchars($resposta['data_resposta']) . '</span>';
                $html .= '<span>' . htmlspecialchars($resposta['hora_resposta']) . '</span>';
                $html .= '</div>';
                $html .= '<hr>';
            }

            $html .= '</div>'; // Fim do resposta-container
            $html .= '</div>'; // Fim do feed-item
            $html .= '<br><br>';
        }
        $html .= '</div>'; // Fim do resposta-container
        echo $html;
    } else {
        echo '<p class="meio letraMaior">Não há publicações nesta categoria.</p>';
    }
} else {
    http_response_code(400);
    echo "Dados insuficientes para processar a requisição.";
}
