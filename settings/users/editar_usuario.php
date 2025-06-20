<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') {
    header("Location: ../../index.php");
    exit();
}
require_once __DIR__ . '/../../includes/check_license.php';
$page_title = 'Editar Usuario';
include '../../includes/header.php';

$usuario_id = $_GET['id'] ?? 0;
if ($usuario_id <= 0) {
    header("Location: index.php");
    exit();
}
$stmt = $conexion->prepare("SELECT id, nombre_completo, username, email, rol FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();
if (!$usuario) {
    header("Location: index.php");
    exit();
}
?>
<h1>Editar Usuario: <?= htmlspecialchars($usuario['nombre_completo']) ?></h1>
<div class="dashboard-block">
    <form action="guardar_usuario.php" method="post">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" value="<?= $usuario['id'] ?>">
        <label for="nombre_completo">Nombre Completo:</label>
        <input type="text" id="nombre_completo" name="nombre_completo" value="<?= htmlspecialchars($usuario['nombre_completo']) ?>" required>
        <label for="username">Nombre de Usuario:</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($usuario['username']) ?>" readonly>
        <small>(El nombre de usuario no se puede cambiar)</small><br><br>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required>
        <label for="password">Nueva Contraseña:</label>
        <input type="password" id="password" name="password">
        <small>(Dejar en blanco para no cambiar la contraseña)</small><br><br>
        <label for="rol">Rol:</label>
        <select id="rol" name="rol" required>
            <option value="administrador" <?= ($usuario['rol'] == 'administrador') ? 'selected' : '' ?>>Administrador</option>
            <option value="soporte" <?= ($usuario['rol'] == 'soporte') ? 'selected' : '' ?>>Soporte</option>
        </select><br><br>
        <button type="submit">Actualizar Usuario</button>
        <a href="index.php" style="margin-left: 15px;">Cancelar</a>
    </form>
</div>
<?php 
$stmt->close();
$conexion->close();
include '../../includes/footer.php'; 
?>