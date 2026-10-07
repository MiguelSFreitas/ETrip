<?php

$cidades = [
    'Pinheiros',
    'São Mateus',
    'Linhares',
    'Ibiraçu',
    'Serra',
    'Vitória',
    'Vila Velha',
];


$distancias = [
    // Trajeto principal pela BR-101 (norte -> sul)
    ['origem' => 'Pinheiros',  'destino' => 'São Mateus', 'distancia_km' => 71,  'tempo_min' => 69],
    ['origem' => 'São Mateus', 'destino' => 'Linhares',   'distancia_km' => 83,  'tempo_min' => 86],
    ['origem' => 'Linhares',   'destino' => 'Ibiraçu',    'distancia_km' => 65,  'tempo_min' => 67],
    ['origem' => 'Ibiraçu',    'destino' => 'Serra',      'distancia_km' => 49,  'tempo_min' => 49],
    ['origem' => 'Serra',      'destino' => 'Vitória',    'distancia_km' => 29,  'tempo_min' => 41],
    ['origem' => 'Vitória',    'destino' => 'Vila Velha', 'distancia_km' => 11,  'tempo_min' => 15],
    ['origem' => 'Ibiraçu',    'destino' => 'Serra',      'distancia_km' => 44,  'tempo_min' => 49],
    ['origem' => 'Serra',      'destino' => 'Vitória',    'distancia_km' => 29,  'tempo_min' => 41],
    ['origem' => 'Vitória',    'destino' => 'Vila Velha', 'distancia_km' => 11,  'tempo_min' => 15],

    
    ['origem' => 'Pinheiros',  'destino' => 'Linhares',   'distancia_km' => 152, 'tempo_min' => 153],
    ['origem' => 'Pinheiros',  'destino' => 'Ibiraçu',    'distancia_km' => 217, 'tempo_min' => 215],
    ['origem' => 'Pinheiros',  'destino' => 'Serra',      'distancia_km' => 260, 'tempo_min' => 259],
    ['origem' => 'Pinheiros',  'destino' => 'Vitória',    'distancia_km' => 291, 'tempo_min' => 298],
    ['origem' => 'Pinheiros',  'destino' => 'Vila Velha', 'distancia_km' => 291, 'tempo_min' => 296],

    ['origem' => 'São Mateus', 'destino' => 'Ibiraçu',    'distancia_km' => 148, 'tempo_min' => 153],
    ['origem' => 'São Mateus', 'destino' => 'Serra',      'distancia_km' => 191, 'tempo_min' => 197],
    ['origem' => 'São Mateus', 'destino' => 'Vitória',    'distancia_km' => 222, 'tempo_min' => 240],
    ['origem' => 'São Mateus', 'destino' => 'Vila Velha', 'distancia_km' => 223, 'tempo_min' => 238],

    ['origem' => 'Linhares',   'destino' => 'Ibiraçu',    'distancia_km' => 65, 'tempo_min' => 67],
    ['origem' => 'Linhares',   'destino' => 'Serra',      'distancia_km' => 109, 'tempo_min' => 112],
    ['origem' => 'Linhares',   'destino' => 'Vitória',    'distancia_km' => 139, 'tempo_min' => 151],
    ['origem' => 'Linhares',   'destino' => 'Vila Velha', 'distancia_km' => 140, 'tempo_min' => 154],

    ['origem' => 'Ibiraçu',    'destino' => 'Serra',      'distancia_km' => 44,  'tempo_min' => 49],
    ['origem' => 'Ibiraçu',    'destino' => 'Vitória',    'distancia_km' => 75,  'tempo_min' => 82],
    ['origem' => 'Ibiraçu',    'destino' => 'Vila Velha', 'distancia_km' => 75,  'tempo_min' => 79],
    ['origem' => 'São Mateus', 'destino' => 'Serra',      'distancia_km' => 191, 'tempo_min' => 197],
    ['origem' => 'São Mateus', 'destino' => 'Vitória',    'distancia_km' => 222, 'tempo_min' => 240],
    ['origem' => 'São Mateus', 'destino' => 'Vila Velha', 'distancia_km' => 223, 'tempo_min' => 238],

    ['origem' => 'Linhares',   'destino' => 'Serra',      'distancia_km' => 109, 'tempo_min' => 113],
    ['origem' => 'Linhares',   'destino' => 'Vitória',    'distancia_km' => 139, 'tempo_min' => 151],
    ['origem' => 'Linhares',   'destino' => 'Vila Velha', 'distancia_km' => 140, 'tempo_min' => 155],

    ['origem' => 'Ibiraçu',    'destino' => 'Vitória',    'distancia_km' => 75,  'tempo_min' => 82],
    ['origem' => 'Ibiraçu',    'destino' => 'Vila Velha', 'distancia_km' => 76,  'tempo_min' => 81],
    ['origem' => 'Serra',      'destino' => 'Vila Velha', 'distancia_km' => 75,  'tempo_min' => 83],
    ['origem' => 'Serra',      'destino' => 'Vitória',    'distancia_km' => 30,  'tempo_min' => 42],
    ['origem' => 'Vitória',    'destino' => 'Vila Velha', 'distancia_km' => 11,  'tempo_min' => 15],
    ['origem' => 'Serra',      'destino' => 'Vitória',    'distancia_km' => 29,  'tempo_min' => 41],

];


function buscarDistancia($origem, $destino, $distancias) {
    foreach ($distancias as $d) {
        if (
            ($d['origem'] === $origem && $d['destino'] === $destino) ||
            ($d['origem'] === $destino && $d['destino'] === $origem)
        ) {
            return [
                'distancia_km' => $d['distancia_km'],
                'tempo_min'    => $d['tempo_min'],
            ];
        }
    }
    return null;
}

function listarCidades($cidades) {
    return $cidades;
}