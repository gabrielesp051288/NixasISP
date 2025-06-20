<?php
session_start();
require_once __DIR__ . '/../includes/check_license.php';

// Solo los administradores pueden borrar facturas
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') {
    die("Acceso denegado.");
}

$factura_id = $_GET['id'] ?? 0;

if ($factura_id > 0) {
    // Gracias a la regla ON DELETE CASCADE, al borrar la factura, 
    // se borrarán automáticamente los items asociados en 'facturas_items'.
    $stmt = $conexion->prepare("DELETE FROM facturas WHERE id = ?");
    $stmt->bind_param("i", $factura_id);
    $stmt->execute();
    $stmt->close();
}

$conexion->close();

// Redirigimos de vuelta a la lista de facturas
header("Location: index.php?exito=borrado");
exit();
?> 
