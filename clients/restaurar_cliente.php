<?php
session_start();
require_once __DIR__ . '/../includes/check_license.php';
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') {
    die("Acceso denegado.");
}

$id_a_restaurar = $_GET['id'] ?? 0;

if ($id_a_restaurar > 0) {
    // Cambiamos el estado del cliente de 'archivado' a 'activo'
    $stmt = $conexion->prepare("UPDATE clientes SET estado = 'activo' WHERE id = ?");
    $stmt->bind_param("i", $id_a_restaurar);
    $stmt->execute();
    $stmt->close();
}

$conexion->close();

// Redirigimos de vuelta a la lista de archivados para que vea que el cliente ya no está ahí
header("Location: index.php?status=archivados&exito=restaurado");
exit();
?> 
