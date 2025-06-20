<?php
// /invoices/generar_factura.php (versión simplificada)
session_start();
require_once __DIR__ . '/../includes/check_license.php';
include '../includes/funciones_facturacion.php'; // Incluimos nuestras funciones
if (!isset($_SESSION['usuario_id'])) die("Acceso denegado.");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cliente_id = $_POST['cliente_id'];
    $cliente_servicio_id = $_POST['cliente_servicio_id'];

    $resultado = crearFactura($conexion, $cliente_id, $cliente_servicio_id);

    if ($resultado['exito']) {
        header("Location: ver_factura.php?id=" . $resultado['factura_id']);
    } else {
        die("Error: " . $resultado['mensaje'] . " <a href='../clients/ver_cliente.php?id=$cliente_id'>Volver</a>");
    }
}
?>