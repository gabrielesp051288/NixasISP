<?php
// /install/procesar_admin.php
include '../conexion.php';
$conexion = getConexion();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['username']; // Usamos el username como nombre completo para el primer admin
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password_plano = $_POST['password'];
    $rol = 'administrador';

    $password_hasheado = password_hash($password_plano, PASSWORD_DEFAULT);
    $stmt = $conexion->prepare("INSERT INTO usuarios (nombre_completo, username, email, password, rol) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $nombre, $username, $email, $password_hasheado, $rol);
    
    if ($stmt->execute()) {
        // Al crear el admin, lo enviamos al paso final: la activación de la licencia.
        header("Location: ../activacion.php");
        exit();
    } else {
        echo "Error al crear el usuario administrador: " . $stmt->error;
    }
}
?>