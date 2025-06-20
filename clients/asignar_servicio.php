<?php
session_start();
require_once __DIR__ . '/../includes/check_license.php';
if (!isset($_SESSION['usuario_id'])) die("Acceso denegado.");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cliente_id = $_POST['cliente_id'];
    $plan_id = $_POST['plan_id'];
    $precio_pactado = $_POST['precio_pactado'];
    $fecha_activacion = $_POST['fecha_activacion'];
    
    $stmt = $conexion->prepare("INSERT INTO cliente_servicios (cliente_id, plan_id, precio_pactado, fecha_activacion, estado) VALUES (?, ?, ?, ?, 'activo')");
    $stmt->bind_param("iids", $cliente_id, $plan_id, $precio_pactado, $fecha_activacion);
    
    if ($stmt->execute()) {
        // Redirigimos de vuelta a la página de detalle del cliente para que vea el nuevo servicio
        header("Location: ver_cliente.php?id=" . $cliente_id);
    } else {
        echo "Error al asignar el servicio: " . $stmt->error;
    }
    
    $stmt->close();
    $conexion->close();
}
?>