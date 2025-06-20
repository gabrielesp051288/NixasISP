<?php
session_start();
require_once __DIR__ . '/../includes/check_license.php';
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') die("Acceso denegado.");

$id_a_borrar = $_GET['id'] ?? 0;
if ($id_a_borrar > 0) {
    $stmt = $conexion->prepare("DELETE FROM planes WHERE id = ?");
    $stmt->bind_param("i", $id_a_borrar);
    $stmt->execute();
}

header("Location: index.php");
exit();
?>