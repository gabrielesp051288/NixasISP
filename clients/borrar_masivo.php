<?php
session_start();
require_once __DIR__ . '/../includes/check_license.php';
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') {
    die("Acceso denegado.");
}

if (isset($_POST['cliente_ids']) && is_array($_POST['cliente_ids'])) {
    $ids_a_archivar = $_POST['cliente_ids'];
    
    // Preparamos las sentencias UPDATE
    $stmt_archivar = $conexion->prepare("UPDATE clientes SET estado = 'archivado' WHERE id = ?");
    $stmt_cancelar_servicios = $conexion->prepare("UPDATE cliente_servicios SET estado = 'cancelado' WHERE cliente_id = ?");
    
    $archivados_count = 0;
    foreach ($ids_a_archivar as $id) {
        $id_sanitizado = (int)$id;
        
        // Iniciamos transacción para asegurar que ambas cosas ocurran
        $conexion->begin_transaction();
        try {
            // 1. Cambiar estado del cliente a 'archivado'
            $stmt_archivar->bind_param("i", $id_sanitizado);
            $stmt_archivar->execute();

            // 2. Cambiar estado de sus servicios a 'cancelado'
            $stmt_cancelar_servicios->bind_param("i", $id_sanitizado);
            $stmt_cancelar_servicios->execute();

            $conexion->commit();
            $archivados_count++;
        } catch (Exception $e) {
            $conexion->rollback();
            // Podríamos registrar el error si quisiéramos
        }
    }
    $stmt_archivar->close();
    $stmt_cancelar_servicios->close();
    $_SESSION['mensaje_borrado'] = "$archivados_count clientes han sido archivados con éxito.";
} else {
    $_SESSION['mensaje_borrado'] = "No se seleccionó ningún cliente para archivar.";
}

header("Location: index.php");
exit();
?>