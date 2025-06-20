<?php
session_start();
require_once __DIR__ . '/../../includes/check_license.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') {
    die("Acceso denegado.");
}

$action = $_POST['action'] ?? '';

if ($action == 'create') {
    $_SESSION['form_data'] = $_POST;
    $nombre = $_POST['nombre_completo'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password_plano = $_POST['password'];
    $rol = $_POST['rol'];
    $password_hasheado = password_hash($password_plano, PASSWORD_DEFAULT);
    $stmt = $conexion->prepare("INSERT INTO usuarios (nombre_completo, username, email, password, rol) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $nombre, $username, $email, $password_hasheado, $rol);
    if ($stmt->execute()) {
        unset($_SESSION['form_data']);
        header("Location: index.php?exito=creado");
    } else {
        if ($conexion->errno === 1062) {
            if (strpos($stmt->error, 'username')) {
                header("Location: crear_usuario.php?error=username_duplicado");
            } elseif (strpos($stmt->error, 'email')) {
                header("Location: crear_usuario.php?error=email_duplicado");
            }
        } else {
            header("Location: crear_usuario.php?error=db");
        }
    }
    $stmt->close();
    exit();
} elseif ($action == 'edit') {
    $id = $_POST['id'];
    $nombre = $_POST['nombre_completo'];
    $email = $_POST['email'];
    $password_plano = $_POST['password'];
    $rol = $_POST['rol'];
    if (!empty($password_plano)) {
        $password_hasheado = password_hash($password_plano, PASSWORD_DEFAULT);
        $stmt = $conexion->prepare("UPDATE usuarios SET nombre_completo = ?, email = ?, password = ?, rol = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $nombre, $email, $password_hasheado, $rol, $id);
    } else {
        $stmt = $conexion->prepare("UPDATE usuarios SET nombre_completo = ?, email = ?, rol = ? WHERE id = ?");
        $stmt->bind_param("sssi", $nombre, $email, $rol, $id);
    }
    $stmt->execute();
    $stmt->close();
    header("Location: index.php?exito=editado");
    exit();
}
$conexion->close();
header("Location: index.php");
exit();
?>