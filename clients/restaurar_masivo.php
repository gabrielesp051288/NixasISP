<?php
// /clients/restaurar_masivo.php

session_start();
require_once __DIR__ . '/../includes/check_license.php';
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') {
    die("Acceso denegado.");
}

if (isset($_POST['cliente_ids']) && is_array($_POST['cliente_ids'])) {
    $ids_a_restaurar = $_POST['cliente_ids'];
    
    // Preparamos la sentencia UPDATE para cambiar el estado a 'activo'
    $stmt = $conexion->prepare("UPDATE clientes SET estado = 'activo' WHERE id = ? AND estado = 'archivado'");
    
    $restaurados_count = 0;
    foreach ($ids_a_restaurar as $id) {
        $id_sanitizado = (int)$id;
        $stmt->bind_param("i", $id_sanitizado);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $restaurados_count++;
        }
    }
    $stmt->close();
    $_SESSION['mensaje_exito'] = "$restaurados_count clientes han sido restaurados con éxito.";
} else {
    $_SESSION['mensaje_error'] = "No se seleccionó ningún cliente para restaurar.";
}

// Redirigimos de vuelta a la lista de archivados para ver el resultado
header("Location: index.php?status=archivados");
exit();
?>