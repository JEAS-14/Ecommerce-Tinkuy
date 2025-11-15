<?php
// Fixture de productos para tests unitarios
// Incluye variantes con stock para pruebas de checkout y carrito
return [
    'producto_valido' => [
        'id_producto' => 1,
        'nombre_producto' => 'Chompa de Alpaca',
        'descripcion' => 'Chompa artesanal de alpaca 100%',
        'imagen_principal' => 'chompa.jpg',
        'id_categoria' => 1,
        'variantes' => [
            [
                'id_variante' => 1,
                'talla' => 'M',
                'color' => 'Rojo',
                'precio' => 150.00,
                'stock' => 20
            ],
            [
                'id_variante' => 2,
                'talla' => 'L',
                'color' => 'Azul',
                'precio' => 160.00,
                'stock' => 15
            ]
        ]
    ],
    'producto_sin_stock' => [
        'id_producto' => 2,
        'nombre_producto' => 'Gorro Artesanal',
        'descripcion' => 'Gorro de lana de alpaca',
        'imagen_principal' => 'gorro.jpg',
        'precio_base' => 45.00,
        'id_categoria' => 1,
        'variantes' => [
            [
                'id_variante' => 3,
                'talla' => 'Única',
                'color' => 'Verde',
                'precio' => 45.00,
                'stock' => 0
            ]
        ]
    ],
    'producto_ceramica' => [
        'id_producto' => 3,
        'nombre_producto' => 'Jarrón de Cerámica',
        'descripcion' => 'Jarrón decorativo tradicional',
        'imagen_principal' => 'jarron.jpg',
        'precio_base' => 80.00,
        'id_categoria' => 2,
        'variantes' => [
            [
                'id_variante' => 4,
                'talla' => null,
                'color' => 'Natural',
                'precio' => 80.00,
                'stock' => 8
            ]
        ]
    ]
];
?>