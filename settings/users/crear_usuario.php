<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') {
    header("Location: ../../index.php");
    exit();
}
$page_title = 'Crear Nuevo Usuario';
include '../../includes/header.php';

$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);
?>
<h1>Crear Nuevo Usuario</h1>
<div class="dashboard-block">
    <?php
    if (isset($_GET['error'])):
        echo '<p style="color:red; font-weight:bold;">';
        if ($_GET['error'] == 'username_duplicado') {
            echo 'El nombre de usuario ya está en uso. Por favor, elige otro.';
        } elseif ($_GET['error'] == 'email_duplicado') {
            echo 'El email ya está registrado. Por favor, utiliza otro.';
        } else {
            echo 'Ha ocurrido un error al guardar. Por favor, inténtalo de nuevo.';
        }
        echo '</p>';
    endif;
    ?>
    <form action="guardar_usuario.php" method="post">
        <input type="hidden" name="action" value="create">
        <label for="nombre_completo">Nombre Completo:</label>
        <input type="text" id="nombre_completo" name="nombre_completo" value="<?= htmlspecialchars($form_data['nombre_completo'] ?? '') ?>" required>
        <label for="username">Nombre de Usuario:</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($form_data['username'] ?? '') ?>" required>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($form_data['email'] ?? '') ?>" required>
        <label for="password">Contraseña:</label>
        <input type="password" id="password" name="password" required>
        <label for="rol">Rol:</label>
        <select id="rol" name="rol" required>
            <option value="administrador" <?= (($form_data['rol'] ?? '') == 'administrador') ? 'selected' : '' ?>>Administrador</option>
            <option value="soporte" <?= (($form_data['rol'] ?? '') == 'soporte') ? 'selected' : '' ?>>Soporte</option>
        </select>
        <br><br>
        <button type="submit">Guardar Usuario</button>
        <a href="index.php" style="margin-left: 15px;">Cancelar</a>
    </form>
</div>
<?php include '../../includes/footer.php'; ?>