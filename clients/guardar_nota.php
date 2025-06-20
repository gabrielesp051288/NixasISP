<?php
// /clients/guardar_nota.php

session_start();
require_once __DIR__ . '/../includes/check_license.php';

// Verificamos que el usuario esté logueado
if (!isset($_SESSION['usuario_id'])) {
    die("Acceso denegado.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validamos que los datos necesarios estén presentes
    if (isset($_POST['cliente_id']) && isset($_POST['nota']) && !empty($_POST['nota'])) {
        
        $cliente_id = $_POST['cliente_id'];
        $usuario_id = $_SESSION['usuario_id']; // El ID del admin que está escribiendo la nota
        $nota_texto = $_POST['nota'];

        // Usamos sentencias preparadas para seguridad
        $stmt = $conexion->prepare("INSERT INTO cliente_notas (cliente_id, usuario_id, nota) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $cliente_id, $usuario_id, $nota_texto);
        $stmt->execute();
        $stmt->close();
    }

    // Redirigimos de vuelta a la página del cliente para ver la nueva nota
    header("Location: ver_cliente.php?id=" . $cliente_id);
    exit();

} else {
    // Si se intenta acceder directamente, redirigimos a la lista de clientes
    header("Location: index.php");
    exit();
}
?> 
