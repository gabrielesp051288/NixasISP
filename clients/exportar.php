<?php
// /clients/exportar.php

session_start();
if (!isset($_SESSION['usuario_id'])) {
    die("Acceso denegado.");
}
require_once __DIR__ . '/../includes/check_license.php';

// --- REUTILIZAMOS LA MISMA LÓGICA DE BÚSQUEDA Y FILTRADO DE index.php ---
$filtro_estado = $_GET['status'] ?? 'activos';
$termino_busqueda = $_GET['q'] ?? '';
$params = [];
$types = '';

if ($filtro_estado == 'archivados') {
    $sql_where = "WHERE estado = 'archivado' ";
} else {
    $sql_where = "WHERE estado != 'archivado' ";
}
if (!empty($termino_busqueda)) {
    $sql_where .= "AND (nombre_completo LIKE ? OR email LIKE ? OR numero_de_cliente LIKE ?)";
    $search_param = "%" . $termino_busqueda . "%";
    array_push($params, $search_param, $search_param, $search_param);
    $types .= 'sss';
}

// --- OBTENEMOS TODOS LOS DATOS (SIN LÍMITE NI PAGINACIÓN) ---
$sql_data = "SELECT numero_de_cliente, nombre_completo, email, telefono, address, ciudad, provincia, codigo_postal, pais, estado, fecha_alta FROM clientes $sql_where ORDER BY nombre_completo ASC";
$stmt_data = $conexion->prepare($sql_data);

if (!empty($params)) {
    $stmt_data->bind_param($types, ...$params);
}
$stmt_data->execute();
$resultado = $stmt_data->get_result();

// --- GENERACIÓN Y DESCARGA DEL ARCHIVO CSV ---
$nombre_archivo = "export_clientes_" . date('Y-m-d') . ".csv";

// Cabeceras para forzar la descarga
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');

// Puntero de salida
$output = fopen('php://output', 'w');

// BOM para UTF-8 (Asegura que Excel lea bien los acentos y ñ)
fputs($output, "\xEF\xBB\xBF");

// Cabeceras del CSV
fputcsv($output, ['N° Cliente', 'Nombre Completo', 'Email', 'Teléfono', 'Dirección', 'Ciudad', 'Provincia', 'Cód. Postal', 'País', 'Estado', 'Fecha de Alta']);

// Escribimos los datos de los clientes
if ($resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        fputcsv($output, $fila);
    }
}

$stmt_data->close();
$conexion->close();
fclose($output);
exit();
?> 
