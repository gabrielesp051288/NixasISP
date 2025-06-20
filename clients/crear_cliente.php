<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}
$page_title = 'Añadir Nuevo Cliente';
include '../includes/header.php';

$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);
?>
<h1>Añadir Nuevo Cliente</h1>

<div class="dashboard-block">
    <?php
    if (isset($_GET['error'])):
        echo '<p style="color:red; font-weight:bold;">';
        if ($_GET['error'] == 'duplicado') {
            echo 'El "Número de Cliente" ya existe. Por favor, utiliza otro.';
        } else {
            echo 'Ha ocurrido un error al guardar el cliente.';
        }
        echo '</p>';
    endif;
    ?>
    <form action="guardar_cliente.php" method="post">
        <input type="hidden" name="action" value="create">
        
        <label for="numero_de_cliente">Número de Cliente:</label>
        <input type="text" id="numero_de_cliente" name="numero_de_cliente" value="<?= htmlspecialchars($form_data['numero_de_cliente'] ?? '') ?>" required>

        <label for="nombre_completo">Nombre Completo:</label>
        <input type="text" id="nombre_completo" name="nombre_completo" value="<?= htmlspecialchars($form_data['nombre_completo'] ?? '') ?>" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($form_data['email'] ?? '') ?>">

        <label for="telefono">Teléfono:</label>
        <input type="text" id="telefono" name="telefono" value="<?= htmlspecialchars($form_data['telefono'] ?? '') ?>">

        <label for="address">Dirección (Calle y Número):</label>
        <input type="text" id="address" name="address" value="<?= htmlspecialchars($form_data['address'] ?? '') ?>">

        <label for="ciudad">Ciudad:</label>
        <input type="text" id="ciudad" name="ciudad" value="<?= htmlspecialchars($form_data['ciudad'] ?? '') ?>">

        <label for="provincia">Provincia:</label>
        <input type="text" id="provincia" name="provincia" value="<?= htmlspecialchars($form_data['provincia'] ?? '') ?>">

        <label for="codigo_postal">Código Postal:</label>
        <input type="text" id="codigo_postal" name="codigo_postal" value="<?= htmlspecialchars($form_data['codigo_postal'] ?? '') ?>">

        <label for="pais">País:</label>
        <input type="text" id="pais" name="pais" value="<?= htmlspecialchars($form_data['pais'] ?? 'Argentina') ?>">

        <label for="estado">Estado:</label>
        <select id="estado" name="estado" required>
            <option value="activo" selected>Activo</option>
            <option value="suspendido">Suspendido</option>
             <option value="baja">Baja</option>
        </select><br><br>

        <button type="submit">Guardar Cliente</button>
        <a href="index.php" style="margin-left: 15px;">Cancelar</a>
    </form>
</div>

<?php include '../includes/footer.php'; ?>