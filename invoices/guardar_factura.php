<?php
// /invoices/guardar_factura.php (Código Completo)
session_start();
require_once __DIR__ . '/../includes/check_license.php';
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') {
    die("Acceso denegado.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $factura_id = $_POST['factura_id'];
    $fecha_emision = $_POST['fecha_emision'];
    $fecha_vencimiento = $_POST['fecha_vencimiento'];

    $stmt = $conexion->prepare("UPDATE facturas SET fecha_emision = ?, fecha_vencimiento = ? WHERE id = ?");
    $stmt->bind_param("ssi", $fecha_emision, $fecha_vencimiento, $factura_id);
    $stmt->execute();
    $stmt->close();

    // Redirigimos de vuelta a la vista de la factura para ver los cambios
    header("Location: ver_factura.php?id=" . $factura_id);
    exit();
}
?> 
