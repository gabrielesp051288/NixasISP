<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') {
    header("Location: ../../index.php");
    exit();
}
require_once __DIR__ . '/../../includes/check_license.php';
$page_title = 'Gestión de Usuarios';
include '../../includes/header.php';

$sql = "SELECT id, nombre_completo, username, email, rol FROM usuarios ORDER BY nombre_completo ASC";
$resultado = $conexion->query($sql);
?>
<h1>Gestión de Usuarios</h1>
<div class="dashboard-block">
    <a href="crear_usuario.php" style="display:inline-block; margin-bottom:20px; background-color: #28a745; color: white; padding: 10px; border-radius: 5px; text-decoration: none;">+ Añadir Nuevo Usuario</a>
    <table>
        <thead>
            <tr><th>Nombre Completo</th><th>Nombre de Usuario</th><th>Email</th><th>Rol</th><th>Acciones</th></tr>
        </thead>
        <tbody>
            <?php while($fila = $resultado->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($fila['nombre_completo']) ?></td>
                <td><?= htmlspecialchars($fila['username']) ?></td>
                <td><?= htmlspecialchars($fila['email']) ?></td>
                <td><?= htmlspecialchars(ucfirst($fila['rol'])) ?></td>
                <td>
                    <a href="editar_usuario.php?id=<?= $fila['id'] ?>" class="action-btn btn-primary">Editar</a> | 
                    <a href="borrar_usuario.php?id=<?= $fila['id'] ?>" class="action-btn btn-danger" onclick="return confirm('¿Estás seguro de que quieres eliminar a este usuario?');">Borrar</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php 
include '../../includes/footer.php'; 
$conexion->close();
?>