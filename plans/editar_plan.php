<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') {
    header("Location: ../index.php");
    exit();
}
require_once __DIR__ . '/../includes/check_license.php';
$page_title = 'Editar Plan';
include '../includes/header.php';

$id = $_GET['id'] ?? 0;
$stmt = $conexion->prepare("SELECT * FROM planes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$plan = $stmt->get_result()->fetch_assoc();
if (!$plan) die("<h1>Plan no encontrado.</h1>");
?>
<h1>Editar Plan de Servicio</h1>

<div class="dashboard-block">
    <form action="guardar_plan.php" method="post">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" value="<?= $plan['id'] ?>">
        <label for="nombre">Nombre del Plan:</label>
        <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($plan['nombre']) ?>" required>
        <label for="descripcion">Descripción:</label>
        <textarea id="descripcion" name="descripcion" rows="4"><?= htmlspecialchars($plan['descripcion']) ?></textarea>
        <label for="precio">Precio (ARS):</label>
        <input type="number" id="precio" name="precio" step="0.01" value="<?= htmlspecialchars($plan['precio']) ?>" required>
        <label for="ciclo_facturacion">Ciclo de Facturación:</label>
        <select id="ciclo_facturacion" name="ciclo_facturacion" required>
            <option value="mensual" <?= ($plan['ciclo_facturacion'] == 'mensual') ? 'selected' : '' ?>>Mensual</option>
            <option value="trimestral" <?= ($plan['ciclo_facturacion'] == 'trimestral') ? 'selected' : '' ?>>Trimestral</option>
            <option value="anual" <?= ($plan['ciclo_facturacion'] == 'anual') ? 'selected' : '' ?>>Anual</option>
        </select><br><br>
        <button type="submit">Actualizar Plan</button>
        <a href="index.php" style="margin-left: 15px;">Cancelar</a>
    </form>
</div>

<?php 
$stmt->close();
$conexion->close();
include '../includes/footer.php'; 
?>