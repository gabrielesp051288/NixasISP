<?php
// /invoices/exportar.php

session_start();
// Solo usuarios logueados pueden exportar
if (!isset($_SESSION['usuario_id'])) {
    die("Acceso denegado.");
}
require_once __DIR__ . '/../includes/check_license.php';

// --- REUTILIZAMOS LA MISMA LÓGICA DE BÚSQUEDA Y FILTRADO DE index.php ---
$filtro_estado = $_GET['status'] ?? 'todos';
$termino_busqueda = $_GET['q'] ?? '';
$params = [];
$types = '';
$sql_where = "WHERE 1=1 ";

if ($filtro_estado != 'todos') {
    $sql_where .= "AND f.estado = ? ";
    $params[] = $filtro_estado;
    $types .= 's';
}
if (!empty($termino_busqueda)) {
    $sql_where .= "AND (c.nombre_completo LIKE ? OR c.numero_de_cliente LIKE ? OR f.id LIKE ?) ";
    $search_param = "%" . $termino_busqueda . "%";
    array_push($params, $search_param, $search_param, $search_param);
    $types .= 'sss';
}

// --- OBTENEMOS TODOS LOS DATOS (SIN LÍMITE NI PAGINACIÓN) ---
$sql_data = "SELECT 
                f.id as 'N_Factura', 
                c.numero_de_cliente as 'N_Cliente',
                c.nombre_completo as 'Cliente',
                f.fecha_emision as 'Fecha_Emision',
                f.fecha_vencimiento as 'Fecha_Vencimiento',
                f.total as 'Total',
                f.estado as 'Estado'
             FROM facturas f 
             JOIN clientes c ON f.cliente_id = c.id 
             $sql_where
             ORDER BY f.id DESC";

$stmt_data = $conexion->prepare($sql_data);
if (!empty($params)) {
    $stmt_data->bind_param($types, ...$params);
}
$stmt_data->execute();
$resultado = $stmt_data->get_result();

// --- GENERACIÓN Y DESCARGA DEL ARCHIVO CSV ---
$nombre_archivo = "export_facturas_" . date('Y-m-d') . ".csv";

// Cabeceras para forzar la descarga
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');

$output = fopen('php://output', 'w');

// BOM para UTF-8 (Asegura que Excel lea bien los acentos y ñ)
fputs($output, "\xEF\xBB\xBF");

// Escribimos las cabeceras del CSV
fputcsv($output, ['Nro Factura', 'Nro Cliente', 'Nombre Cliente', 'Fecha Emision', 'Fecha Vencimiento', 'Total', 'Estado']);

// Escribimos los datos de las facturas
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
