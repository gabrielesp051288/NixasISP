<?php
session_start();
require_once __DIR__ . '/../includes/check_license.php';
if (!isset($_SESSION['usuario_id'])) die("Acceso denegado.");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $factura_id = $_POST['factura_id'];
    $return_status = $_POST['return_status'] ?? 'todos'; // Para volver al filtro anterior

    if (!empty($factura_id)) {
        // Actualizamos el estado de la factura a 'pagada'
        $stmt = $conexion->prepare("UPDATE facturas SET estado = 'pagada' WHERE id = ?");
        $stmt->bind_param("i", $factura_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Redirigimos de vuelta a la lista de facturas, manteniendo el filtro
    header("Location: index.php?status=" . urlencode($return_status));
    exit();

} else {
    // Si no es por POST, simplemente redirigimos a la lista principal
    header("Location: index.php");
    exit();
}
?> 
