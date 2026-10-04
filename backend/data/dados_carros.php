<?php

$carros = [
    [
        'id' => 1,
        'marca' => 'BYD',
        'modelo' => 'Dolphin Mini',
        'versao' => 'GS',
        'bateria_kwh' => 38.0,       // capacidade total da bateria
        'consumo_kwh_km' => 0.136,   // consumo médio (kWh por km)
        'autonomia_km' => 280,       // autonomia divulgada pelo fabricante
        'caminho_imagem' => 'carrosimg/byd-dolphin-mini.png',
        ],   
    [
        'id' => 2,
        'marca' => 'BYD',
        'modelo' => 'Dolphin Mini',
        'versao' => 'GL',
        'bateria_kwh' => 30.08,
        'consumo_kwh_km' => 0.096,
        'autonomia_km' => 224,
        'caminho_imagem' => 'carrosimg/byd-dolphin-mini.png',
    ],
    [
        'id' => 3,
        'marca' => 'BYD',
        'modelo' => 'Dolphin',
        'versao' => 'GS',
        'bateria_kwh' => 44.9,
        'consumo_kwh_km' => 0.154,
        'autonomia_km' => 291,
        'caminho_imagem' => 'carrosimg/byd-dolphin.png',
    ],
    [
        'id' => 4,
        'marca' => 'Geely',
        'modelo' => 'EX2',
        'versao' => 'Pro',
        'bateria_kwh' => 39.4,
        'consumo_kwh_km' => 0.136,
        'autonomia_km' => 289,
        'caminho_imagem' => 'carrosimg/geely-ex2.png'
    ],
    [
        'id' => 5,
        'marca' => 'Geely',
        'modelo' => 'EX2',
        'versao' => 'Max',
        'bateria_kwh' => 39.4,
        'consumo_kwh_km' => 0.136,
        'autonomia_km' => 289,
        'caminho_imagem' => 'carrosimg/geely-ex2.png'
    ],
    [
        'id' => 6,
        'marca' => 'Chevrolet',
        'modelo' => 'Spark EUV',
        'versao' => 'Padrão',
        'bateria_kwh' => 42.0,
        'consumo_kwh_km' => 0.163,
        'autonomia_km' => 258,
        'caminho_imagem' => 'carrosimg/chevrolet-spark-euv.png'
    ],
    [
        'id' => 7,
        'marca' => 'BYD',
        'modelo' => 'Yuan Pro',
        'versao' => 'Padrão',
        'bateria_kwh' => 45.1,
        'consumo_kwh_km' => 0.135,
        'autonomia_km' => 250,
        'caminho_imagem' => 'carrosimg/byd-yuan-pro.png'

    ],
    [
        'id' => 8,
        'marca' => 'GAC',
        'modelo' => 'Aion UT',
        'versao' => 'Premium',
        'bateria_kwh' => 44.12,      // LFP — fonte: canalve.com.br, agazeta.com.br, instacarro.com
        'consumo_kwh_km' => 0.174,   // calculado: 44.12 ÷ 253
        'autonomia_km' => 253,       // Inmetro — fonte: canalve.com.br, agazeta.com.br, instacarro.com
        'caminho_imagem' => 'carrosimg/gac-aion-ut.png',
    ],
    [
    'id' => 9,
    'marca' => 'GAC',
    'modelo' => 'Aion UT',
    'versao' => 'Elite',
    'bateria_kwh' => 60.0,       // LFP — fonte: canalve.com.br, agazeta.com.br, instacarro.com
    'consumo_kwh_km' => 0.194,   // calculado: 60.0 ÷ 310
    'autonomia_km' => 310,       // Inmetro — fonte: canalve.com.br, agazeta.com.br, instacarro.com
    'caminho_imagem' => 'carrosimg/gac-aion-ut.png',
],
 
];

function buscarCarroPorId($carroId, $carros) {
    foreach ($carros as $c) {
        if ($c['id'] == $carroId) {
            return $c;
        }
    }
    return null;
}



function listarMarcas($carros) {
    $marcas = array_unique(array_column($carros, 'marca'));
    sort($marcas);
    return array_values($marcas);
}


function listarModelosPorMarca($marca, $carros) {
    return array_values(array_filter($carros, fn($c) => $c['marca'] === $marca));
}
