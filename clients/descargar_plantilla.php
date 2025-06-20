<?php
// /clients/descargar_plantilla.php

$filename = "plantilla_importacion_clientes.csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Definimos las cabeceras, añadiendo la nueva columna al final
$header = [
    'numero_de_cliente',
    'nombre_completo',
    'email',
    'telefono',
    'address',
    'ciudad',
    'provincia',
    'codigo_postal',
    'pais',
    'nombre_del_servicio' // <-- NUEVA COLUMNA
];

fputcsv($output, $header);

fclose($output);
exit();
?>