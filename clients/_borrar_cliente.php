<?php
session_start();
include '../conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    die("Acceso denegado.");
}

$id_a_borrar = $_GET['id'] ?? 0;

if ($id_a_borrar > 0) {
    $stmt = $conexion->prepare("DELETE FROM clientes WHERE id = ?");
    $stmt->bind_param("i", $id_a_borrar);
    $stmt->execute();
}

header("Location: index.php?exito=borrado");
exit();
?>