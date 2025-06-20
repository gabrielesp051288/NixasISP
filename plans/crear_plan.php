<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') {
    header("Location: ../index.php");
    exit();
}
$page_title = 'Crear Nuevo Plan';
include '../includes/header.php';
?>
<h1>Crear Nuevo Plan de Servicio</h1>

<div class="dashboard-block">
    <?php if (isset($_GET['error']) && $_GET['error'] == 'duplicado'): ?>
        <p style="color:red; font-weight:bold;">El nombre del plan ya existe. Por favor, elige otro.</p>
    <?php endif; ?>
    <form action="guardar_plan.php" method="post">
        <input type="hidden" name="action" value="create">
        <label for="nombre">Nombre del Plan:</label>
        <input type="text" id="nombre" name="nombre" required>
        <label for="descripcion">Descripción:</label>
        <textarea id="descripcion" name="descripcion" rows="4"></textarea>
        <label for="precio">Precio (ARS):</label>
        <input type="number" id="precio" name="precio" step="0.01" required>
        <label for="ciclo_facturacion">Ciclo de Facturación:</label>
        <select id="ciclo_facturacion" name="ciclo_facturacion" required>
            <option value="mensual">Mensual</option>
            <option value="trimestral">Trimestral</option>
            <option value="anual">Anual</option>
        </select><br><br>
        <button type="submit">Guardar Plan</button>
        <a href="index.php" style="margin-left: 15px;">Cancelar</a>
    </form>
</div>

<?php include '../includes/footer.php'; ?>