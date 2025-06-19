<?php
require_once __DIR__ . '/vendor/autoload.php';

include('conexao.php');

$con = null;
$consulta = "SELECT * FROM cts";
$con = $mysqli->query($consulta) or die($mysqli->error);
$cts = $con->fetch_all(MYSQLI_ASSOC);

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);

$dotenv->safeLoad();

$googleMapsApiKey = $_ENV['GOOGLE_MAPS_API_KEY'];

?>


<!DOCTYPE html>
<html lang="en">


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/index.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script
        src="https://maps.googleapis.com/maps/api/js?key=<?= htmlspecialchars($googleMapsApiKey) ?>&callback=initMap&v=weekly&libraries=marker,places" defer>
    </script>
    <style>
        #map {
            height: 400px;
            width: 100%;
        }
    </style>
    <title>Tmloc</title>
</head>


<body>
    <div class="carrossel">
        <div class="carrossel-texto">O SESC da sua cidade deve ter mesa de tenis de mesa, é uma boa ideia ir jogar lá!</div>
        <div class="carrossel-texto">Talvez existam clubes gratuitos com entrada sob indicação do técnico que tem tênis de mesa, procure os clubes na sua cidade usando o google mapas para saber mais informações</div>
    </div>
    <br>
    <div class="div_login">
        <a class="botao" href="login.php">Login</a>
    </div>
    <div class='texto_maps'>CTs que estão perto de você, selecione um CT para ver as informações dele:</div><br>
    <div id="map"></div>
    <p id="ct-anchor"></p>
    <p class="meio">
        <label>Estado</label><br>
        <select name="estado" id="estado" class="estado-dropdown select-style">
            <option class="select-style option" value="">Selecione um estado</option>
            <?php
            $url = 'https://servicodados.ibge.gov.br/api/v1/localidades/estados';
            $response = file_get_contents($url);
            if ($response !== false) {
                $estados = json_decode($response, true);
                if (!empty($estados)) {
                    foreach ($estados as $estado) {
                        echo '<option value="' . $estado['id'] . '" ' . '>' . $estado['nome'] . ' - ' . $estado['sigla'] . '</option>';
                    }
                } else {
                    echo 'Nenhum estado encontrado.';
                }
            } else {
                echo 'Erro ao obter os dados dos estados.';
            }
            ?>
        </select>
    </p>
    <p class="meio">
        <label>Cidade</label><br>
        <select name="cidade" id="cidade" class="cidade-dropdown select-style">
            <option class="select-style option" value="">Selecione primeiro estado</option>
        </select>
    </p>


    <p class="meio">
        <button id="mostrarCtsBtn" class="botao" type="button" name="mostrar_cts" onclick="validaEstadoCidade()">Mostrar CTs</button>
    </p>


    <p class="meio msg-cts"></p>
    <table id="tabela_cts" class="tabela-cts"></table>
    <br>
    <p class="meio select-cts">
        <br>
    <p class="meio mostrar-pessoas-btn"></p>

    <div>
        <p id="ct-anchor2"></p>
        <p class="historia-ct" id="historia-ct"></p>
        <br>
        <table id="tabelaJogadores" class="div_tabelas tabela-cts"></table>
        <br>
        <table id="tabelaTecnico" class="div_tabelas tabela-cts"></table>
        <br>
        <table id="tabelaResponsavel" class="div_tabelas tabela-cts"></table>
        <br>
    </div>

    <script>
        function validaEstadoCidade() {
            const estadoDropdown = document.getElementById('estado');
            const cidadeDropdown = document.getElementById('cidade');
            const ctAnchor = document.getElementById('ct-anchor');
            const tabelaJogadores = document.getElementById('tabelaJogadores');
            const tabelaTecnico = document.getElementById('tabelaTecnico');
            const tabelaResponsavel = document.getElementById('tabelaResponsavel');
            const historiaCt = document.getElementById('historia-ct');
            const selectCt = document.getElementById('ct');

            if (selectCt) {
                selectCt.innerHTML = `<option class="select-style option" value="">Selecione um CT</option>`;
            }

            if (tabelaJogadores.innerHTML != '' || tabelaTecnico.innerHTML != '' || tabelaResponsavel.innerHTML != '') {
                tabelaJogadores.innerHTML = '';
                tabelaTecnico.innerHTML = '';
                tabelaResponsavel.innerHTML = '';
            }

            if (historiaCt.innerHTML != '') {
                historiaCt.innerHTML = '';
            }

            if (estadoDropdown.value == "" || cidadeDropdown.value == "") {
                alert("Estado e cidade devem ser preenchidos.");
            } else {
                mostrarCTs();
                defineCentroPelaCidade(cidadeDropdown.options[cidadeDropdown.selectedIndex].text);
                setTimeout(() => {
                    ctAnchor.scrollIntoView({
                        behavior: 'smooth'
                    });
                }, 500);
            }

            initMap();
        }

        function mostrarHistoriaDoCt(resp) {
            const historiaCt = document.getElementById('historia-ct');

            historiaCt.innerHTML = 'Apresentação do CT: ' + resp;
        }

        function fetchCt(ctId) {
            const formData = new FormData();
            formData.append('ctId', ctId);
            fetch('responsavel/carregar_historia_ct.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(resp => {
                    mostrarHistoriaDoCt(resp);
                })
                .catch(error => console.error('Erro ao buscar pessoas:', error));
        }


        function carregarHistoriaDoCt() {
            const mostrarPessoasBtn = document.getElementById('mostrarPessoasBtn');
            if (mostrarPessoasBtn) {
                const ctDropdown = document.getElementById('ct');
                const ctId = ctDropdown ? ctDropdown.value : null;
                if (ctId) {
                    fetchCt(ctId);
                } else {
                    alert("Por favor, selecione um CT.");
                }
            }
        }


        function validaCt() {
            const ctDropdown = document.getElementById('ct');
            const ctAnchor2 = document.getElementById('ct-anchor2');
            if (ctDropdown.value == "") {
                alert("Ct deve ser preenchido.");
            } else {
                carregarHistoriaDoCt();
                mostrarPessoas();
                setTimeout(() => {
                    ctAnchor2.scrollIntoView({
                        behavior: 'smooth'
                    });
                }, 40);
            }
        }


        function mostrarCTs() {
            const estadoDropdown = document.getElementById('estado');
            const cidadeDropdown = document.getElementById('cidade');
            const cidadeId = cidadeDropdown.value;
            const estadoId = estadoDropdown.value;

            const formData = new FormData();
            formData.append('cidade', cidadeId);
            formData.append('estado', estadoId);

            fetch('admin/cts/carregar_cts.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    criarTabelaCTs(data);
                    const ctDropdown = document.getElementById('ct');
                    data.forEach(ct => {
                        ctDropdown.innerHTML += `<option value="${ct.id}">${ct.nome}</option>`;
                    });


                })
                .catch(error => console.error('Erro ao carregar CTs:', error));
        }

        function fetchPessoas(ctId) {
            const formData = new FormData();
            formData.append('ctId', ctId);
            fetch('responsavel/carregar_jogadores.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(jog => {
                    mostrarJogadores(jog);
                })
                .catch(error => console.error('Erro ao buscar pessoas:', error));

            fetch('responsavel/carregar_tecnico.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(tec => {
                    mostrarTecnico(tec);
                })
                .catch(error => console.error('Erro ao buscar pessoas:', error));


            fetch('responsavel/carregar_responsavel.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(resp => {
                    mostrarResponsavel(resp);
                })
                .catch(error => console.error('Erro ao buscar pessoas:', error));

        }



        function mostrarPessoas() {
            const mostrarPessoasBtn = document.getElementById('mostrarPessoasBtn');
            if (mostrarPessoasBtn) {
                const ctDropdown = document.getElementById('ct');
                const ctId = ctDropdown ? ctDropdown.value : null;
                if (ctId) {
                    fetchPessoas(ctId);
                } else {
                    alert("Por favor, selecione um CT.");
                }
            }
        }


        function mostrarJogadores(jog) {

            if (jog.length == 0) {
                tabelaJogadores.innerHTML = `
                <tr>
                    <td colspan="12" class="meio">Jogadores</td>
                </tr>

                <tr>
                    <td class="meio">Ainda não há jogadores</td>
                </tr>`;
            } else {

                tabelaJogadores.innerHTML = '';

                tabelaJogadores.innerHTML += `
                <tr>
                    <td class="meio" colspan=12>Jogadores</td>
                </tr>

                <tr>
                    <td>Nome</td>
                    <td>Perfil</td>
                    <td class="meio">Títulos</td>
                </tr>`;

                jog.forEach(jogador => {
                    tabelaJogadores.innerHTML += `
                <tr>
                    <td>${jogador.nome}</td>
                    <td>${jogador.perfil}</td>
                    <td>${jogador.titulo}</td>
                </tr>`;
                });
            }
        }

        function mostrarTecnico(tec) {

            if (tec.length == 0) {
                tabelaTecnico.innerHTML = `
                <tr>
                    <td colspan="12" class="meio">Técnicos</td>
                </tr>

                <tr>
                    <td class="meio">Ainda não há técnico</td>
                </tr>`;
            } else {
                tabelaTecnico.innerHTML = '';
                tabelaTecnico.innerHTML += `
                <tr>
                    <td class="meio" colspan=12>Técnicos</td>
                </tr>

                <tr>
                    <td>Nome</td>
                    <td>Perfil</td>
                    <td class="meio">Títulos</td>
                </tr>`;

                tec.forEach(tecnico => {
                    tabelaTecnico.innerHTML += `
                <tr>
                    <td>${tecnico.nome}</td>
                    <td>${tecnico.perfil}</td>
                    <td>${tecnico.titulo}</td>
                </tr>`;
                });
            }
        }

        function mostrarResponsavel(resp) {
            if (resp.length == 0) {
                tabelaResponsavel.innerHTML = `
                <tr>
                    <td colspan="12" class="meio">Responsáveis</td>
                </tr>

                <tr>
                    <td class="meio">Ainda não há responsável</td>
                </tr>`;
            } else {
                tabelaResponsavel.innerHTML = '';
                tabelaResponsavel.innerHTML += `
            <tr>
                <td class="meio" colspan=12>Responsáveis</td>
            </tr>

            <tr>
                <td>Nome</td>
                <td>Perfil</td>
                <td class="meio">Títulos</td>
            </tr>`;
                resp.forEach(responsavel => {
                    tabelaResponsavel.innerHTML += `
                <tr>
                    <td>${responsavel.nome}</td>
                    <td>${responsavel.perfil}</td>
                    <td>${responsavel.titulo}</td>
                </tr>`;
                });
            }
        }

        let map;
        let cts = <?php echo json_encode($cts); ?>;
        let geocoder;

        function defineCentroPelaCidade(cidade) {
            geocoder.geocode({
                'address': cidade
            }, function(results, status) {
                if (status === 'OK') {
                    map.setCenter(results[0].geometry.location);
                    map.setZoom(11);
                } else {
                    alert('Não tem cts no maps pra essa localidade');
                }
            });
        }

        function initMap() {
            const initialPosition = {
                lat: -14.235,
                lng: -51.9253
            };

            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 12.3,
                center: initialPosition,
                mapId: "DEMO_MAP_ID",
            });

            geocoder = new google.maps.Geocoder();

            navigator.geolocation.getCurrentPosition(function(position) {
                const pos = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                map.setCenter(pos);
            });

            buscarLocaisTenisDeMesa(map);
        }

        function buscarLocaisTenisDeMesa(map) {
            const cidadeDropdown = document.getElementById('cidade');
            const cidadeSelecionada = cidadeDropdown.options[cidadeDropdown.selectedIndex].text;
            let request;

            if (cidadeSelecionada === 'Selecione primeiro estado') {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const pos = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };

                    request = {
                        location: pos,
                        radius: '10000',
                        name: 'Tênis de mesa'
                    };


                    const service = new google.maps.places.PlacesService(map);
                    service.nearbySearch(request, function(results, status) {
                        if (status === google.maps.places.PlacesServiceStatus.OK) {
                            results.forEach(result => createMarker(result, map));
                        }
                    });
                });
            } else {
                request = {
                    location: map.getCenter(),
                    radius: '10000',
                    name: `Tênis de mesa ${cidadeSelecionada}`
                };


                const service = new google.maps.places.PlacesService(map);
                service.nearbySearch(request, function(results, status) {
                    if (status === google.maps.places.PlacesServiceStatus.OK) {
                        results.forEach(result => createMarker(result, map));
                    }
                });
            }
        }

        function createMarker(place, map) {
            const marker = new google.maps.marker.AdvancedMarkerElement({
                map: map,
                position: place.geometry.location,
                title: place.name
            });

            const infowindow = new google.maps.InfoWindow({
                content: place.name
            });

            marker.addListener('click', function() {
                infowindow.open(map, marker);

                const ctAnchor2 = document.getElementById('ct-anchor2');
                const matchedCt = cts.find(ct => ct.nome === place.name);

                if (matchedCt) {
                    fetchPessoas(matchedCt.id);
                    mostrarHistoriaDoCt(matchedCt.apresentacao);
                    setTimeout(() => {
                        ctAnchor2.scrollIntoView({
                            behavior: 'smooth'
                        });
                    }, 900);
                } else {
                    setTimeout(() => {
                        alert('Ainda não temos as pessoas desse CT.');
                    }, 100);
                }
            });
        }


        function adicionarCT(nomeCt) {
            const matchedCt = cts.find(ct => ct.nome === nomeCt);
            const cidadeDropdown = document.getElementById('cidade');
            const cidadeSelecionada = cidadeDropdown.options[cidadeDropdown.selectedIndex].text;

            if (matchedCt) {
                geocoder.geocode({
                    address: `${matchedCt.endereco}, ${cidadeSelecionada}`
                }, function(results, status) {
                    if (status === 'OK') {
                        const latLng = results[0].geometry.location;

                        const marker = new google.maps.marker.AdvancedMarkerElement({
                            map: map,
                            position: latLng,
                            title: matchedCt.nome
                        });

                        map.setCenter(latLng);

                        const infowindow = new google.maps.InfoWindow({
                            content: matchedCt.nome
                        });

                        marker.addListener('click', function() {
                            infowindow.open(map, marker);
                        });
                    } else {
                        alert('O endereço associado a esse nome não consta no maps');
                    }
                });
            } else {
                alert('CT não encontrado.');
            }
        }

        function criarTabelaCTs(cts) {
            const msgCts = document.querySelector('.msg-cts');
            const tabelaCts = document.getElementById('tabela_cts');
            const selectCts = document.querySelector('.select-cts');
            const paragrafoMostrarPessoas = document.querySelector('.mostrar-pessoas-btn')

            msgCts.textContent = 'CTs cadastrados no Sistema:';

            if (cts.length == 0) {
                tabelaCts.innerHTML = `
                <tr>
                    <td colspan="12" class="meio">CTs</td>
                </tr>

                <tr>
                    <td>Ainda não há cts</td>
                </tr>`;
            } else {
                tabelaCts.innerHTML = '';
                tabelaCts.innerHTML += `
                <tr>
                    <td colspan="12" class="meio">CTs</td>
                </tr>

                <tr>
                    <td>Nome</td>
                    <td>Endereço</td>
                    <td>Telefone</td>
                </tr>`;


                cts.forEach(ct => {
                    tabelaCts.innerHTML += `
                <tr>
                    <td>${ct.nome}</td>
                    <td><a class="link-end" href="#" onclick="adicionarCT('${ct.nome}')">${ct.endereco}</a></td>
                    <td>${ct.telefone}</td>
                </tr>`;
                });
                tabelaCts.innerHTML += `</table>`;


                selectCts.innerHTML = `
                    <select class="select-style" name="ct" id="ct" class="ct-dropdown">
                        <option class="select-style option" value="">Selecione um CT</option>
                    </select>
                    `;

                paragrafoMostrarPessoas.innerHTML = `
                        <button id="mostrarPessoasBtn" onclick="validaCt()" class="botao" type="button" name="mostrar_pessoas" data-ct="">Mostrar Detalhes Do CT</button>
                    `;
            }
        }
    </script>

    <script>
        const textos = document.querySelectorAll('.carrossel-texto');
        let currentIndex = 0;

        textos[currentIndex].style.display = 'block';

        function MostraProximoTexto() {

            textos[currentIndex].style.display = 'none';

            currentIndex++;

            if (currentIndex >= textos.length) {
                currentIndex = 0;
            }

            textos[currentIndex].style.display = 'block';
        }

        setInterval(MostraProximoTexto, 6000);
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const estadoDropdown = document.getElementById('estado');
            const cidadeDropdown = document.getElementById('cidade');
            const tabelaJogadores = document.getElementById('tabelaJogadores');
            const tabelaTecnico = document.getElementById('tabelaTecnico');
            const tabelaResponsavel = document.getElementById('tabelaResponsavel');

            estadoDropdown.addEventListener('change', function() {
                const estadoId = this.value;
                if (estadoId) {
                    fetch(`https://servicodados.ibge.gov.br/api/v1/localidades/estados/${estadoId}/municipios`)
                        .then(response => response.json())
                        .then(data => {
                            cidadeDropdown.innerHTML = '<option value="">Selecione uma cidade</option>';
                            data.forEach(cidade => {
                                cidadeDropdown.innerHTML += `<option value="${cidade.id}">${cidade.nome}</option>`;
                            });
                        })
                        .catch(error => console.error('Erro ao carregar as cidades:', error));
                } else {
                    cidadeDropdown.innerHTML = '<option value="">Selecione primeiro estado</option>';
                }
            });

            initMap();
        });
    </script>

</body>

</html>