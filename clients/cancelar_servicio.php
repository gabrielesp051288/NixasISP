<?php
session_start();
require_once __DIR__ . '/../includes/check_license.php';
if (!isset($_SESSION['usuario_id'])) die("Acceso denegado.");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cliente_servicio_id = $_POST['cliente_servicio_id'];
    $cliente_id = $_POST['cliente_id']; // Para saber a dónde volver

    if (!empty($cliente_servicio_id)) {
        // Actualizamos el estado del servicio a 'cancelado' en lugar de borrarlo
        $stmt = $conexion->prepare("UPDATE cliente_servicios SET estado = 'cancelado' WHERE id = ?");
        $stmt->bind_param("i", $cliente_servicio_id);
        $stmt->execute();
        $stmt->close();
    }
    
    $conexion->close();
    
    // Redirigimos de vuelta a la página de detalle del cliente
    header("Location: ver_cliente.php?id=" . $cliente_id);
    exit();

} else {
    // Si no es por POST, redirigimos a la lista principal de clientes
    header("Location: index.php");
    exit();
}
?> 
