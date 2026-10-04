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
    
    ['origem' => 'Pinheiros',  'destino' => 'São Mateus', 'distancia_km' => 71,  'tempo_min' => 76],
    ['origem' => 'São Mateus', 'destino' => 'Linhares',   'distancia_km' => 00,  'tempo_min' => 00],
    ['origem' => 'Linhares',   'destino' => 'Ibiraçu',    'distancia_km' => 00,  'tempo_min' => 00],
    ['origem' => 'Ibiraçu',    'destino' => 'Serra',      'distancia_km' => 00,  'tempo_min' => 00],
    ['origem' => 'Serra',      'destino' => 'Vitória',    'distancia_km' => 00,  'tempo_min' => 00],
    ['origem' => 'Vitória',    'destino' => 'Vila Velha', 'distancia_km' => 00,  'tempo_min' => 00],
    ['origem' => 'Vitória',    'destino' => 'Vila Velha', 'distancia_km' => 00,  'tempo_min' => 00],
    ['origem' => 'Ibiraçu',    'destino' => 'Serra',      'distancia_km' => 00,  'tempo_min' => 00],
    ['origem' => 'Serra',      'destino' => 'Vitória',    'distancia_km' => 00,  'tempo_min' => 00],
    ['origem' => 'Vitória',    'destino' => 'Vila Velha', 'distancia_km' => 00,  'tempo_min' => 00],

    
    ['origem' => 'Pinheiros',  'destino' => 'Linhares',   'distancia_km' => 00, 'tempo_min' => 00],
    ['origem' => 'Pinheiros',  'destino' => 'Ibiraçu',    'distancia_km' => 00, 'tempo_min' => 00],
    ['origem' => 'Pinheiros',  'destino' => 'Serra',      'distancia_km' => 260, 'tempo_min' => 267],
    ['origem' => 'Pinheiros',  'destino' => 'Vitória',    'distancia_km' => 280, 'tempo_min' => 300],
    ['origem' => 'Pinheiros',  'destino' => 'Vila Velha', 'distancia_km' => 00, 'tempo_min' => 00],

    ['origem' => 'São Mateus', 'destino' => 'Ibiraçu',    'distancia_km' => 00, 'tempo_min' => 00],
    ['origem' => 'São Mateus', 'destino' => 'Serra',      'distancia_km' => 00, 'tempo_min' => 00],
    ['origem' => 'São Mateus', 'destino' => 'Vitória',    'distancia_km' => 00, 'tempo_min' => 00],
    ['origem' => 'São Mateus', 'destino' => 'Vila Velha', 'distancia_km' => 00, 'tempo_min' => 00],

    ['origem' => 'Linhares',   'destino' => 'Ibiraçu',    'distancia_km' => 00, 'tempo_min' => 00],
    ['origem' => 'Linhares',   'destino' => 'Serra',      'distancia_km' => 00, 'tempo_min' => 00],
    ['origem' => 'Linhares',   'destino' => 'Vitória',    'distancia_km' => 00, 'tempo_min' => 00],
    ['origem' => 'Linhares',   'destino' => 'Vila Velha', 'distancia_km' => 00, 'tempo_min' => 00],

    ['origem' => 'Ibiraçu',    'destino' => 'Serra',      'distancia_km' => 00,  'tempo_min' => 00],
    ['origem' => 'Ibiraçu',    'destino' => 'Vitória',    'distancia_km' => 00,  'tempo_min' => 00],
    ['origem' => 'Ibiraçu',    'destino' => 'Vila Velha', 'distancia_km' => 00,  'tempo_min' => 00],
    ['origem' => 'São Mateus', 'destino' => 'Serra',      'distancia_km' => 00, 'tempo_min' => 00],
    ['origem' => 'São Mateus', 'destino' => 'Vitória',    'distancia_km' => 00, 'tempo_min' => 00],
    ['origem' => 'São Mateus', 'destino' => 'Vila Velha', 'distancia_km' => 00, 'tempo_min' => 00],

    ['origem' => 'Linhares',   'destino' => 'Serra',      'distancia_km' => 00, 'tempo_min' => 00],
    ['origem' => 'Linhares',   'destino' => 'Vitória',    'distancia_km' => 00, 'tempo_min' => 00],
    ['origem' => 'Linhares',   'destino' => 'Vila Velha', 'distancia_km' => 00, 'tempo_min' => 00],

    ['origem' => 'Ibiraçu',    'destino' => 'Vitória',    'distancia_km' => 00,  'tempo_min' => 00],
    ['origem' => 'Ibiraçu',    'destino' => 'Vila Velha', 'distancia_km' => 00,  'tempo_min' => 00],
    ['origem' => 'Serra',      'destino' => 'Vila Velha', 'distancia_km' => 00,  'tempo_min' => 00],
    ['origem' => 'Serra',      'destino' => 'Vitória',    'distancia_km' => 00,  'tempo_min' => 00],
    ['origem' => 'Vitória',    'destino' => 'Vila Velha', 'distancia_km' => 00,  'tempo_min' => 00],
    ['origem' => 'Serra',      'destino' => 'Vitória',    'distancia_km' => 0,  'tempo_min' => 00],

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