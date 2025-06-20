<?php
session_start();
require_once __DIR__ . '/../includes/check_license.php';

if (!isset($_SESSION['usuario_id'])) {
    die("Acceso denegado.");
}

$action = $_POST['action'] ?? '';

if ($action == 'create') {
    $_SESSION['form_data'] = $_POST;

    $stmt = $conexion->prepare("INSERT INTO clientes (numero_de_cliente, nombre_completo, email, telefono, address, ciudad, provincia, codigo_postal, pais, estado, fecha_alta) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())");
    $stmt->bind_param("ssssssssss", 
        $_POST['numero_de_cliente'], $_POST['nombre_completo'], $_POST['email'], $_POST['telefono'], 
        $_POST['address'], $_POST['ciudad'], $_POST['provincia'], $_POST['codigo_postal'], $_POST['pais'], $_POST['estado']
    );
    
    if ($stmt->execute()) {
        unset($_SESSION['form_data']);
        header("Location: index.php?exito=creado");
    } else {
        if ($conexion->errno === 1062) {
            header("Location: crear_cliente.php?error=duplicado");
        } else {
            header("Location: crear_cliente.php?error=db");
        }
    }
    exit();

} elseif ($action == 'edit') {
    $stmt = $conexion->prepare("UPDATE clientes SET nombre_completo = ?, email = ?, telefono = ?, address = ?, ciudad = ?, provincia = ?, codigo_postal = ?, pais = ?, estado = ? WHERE id = ?");
    $stmt->bind_param("sssssssssi", 
        $_POST['nombre_completo'], $_POST['email'], $_POST['telefono'], 
        $_POST['address'], $_POST['ciudad'], $_POST['provincia'], $_POST['codigo_postal'], $_POST['pais'], $_POST['estado'],
        $_POST['id']
    );
    $stmt->execute();
    header("Location: index.php?exito=editado");
    exit();
}
?>