<?php
session_start();
require_once __DIR__ . '/../includes/check_license.php';
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') die("Acceso denegado.");

$action = $_POST['action'] ?? '';

if ($action == 'create') {
    $stmt = $conexion->prepare("INSERT INTO planes (nombre, descripcion, precio, ciclo_facturacion) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssds", $_POST['nombre'], $_POST['descripcion'], $_POST['precio'], $_POST['ciclo_facturacion']);
    if (!$stmt->execute() && $conexion->errno === 1062) {
        header("Location: crear_plan.php?error=duplicado");
        exit();
    }
} elseif ($action == 'edit') {
    $stmt = $conexion->prepare("UPDATE planes SET nombre = ?, descripcion = ?, precio = ?, ciclo_facturacion = ? WHERE id = ?");
    $stmt->bind_param("ssdsi", $_POST['nombre'], $_POST['descripcion'], $_POST['precio'], $_POST['ciclo_facturacion'], $_POST['id']);
    $stmt->execute();
}

header("Location: index.php");
exit();
?>