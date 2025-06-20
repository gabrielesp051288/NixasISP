<?php
// /invoices/editar_factura.php (Código Completo)
session_start();
require_once __DIR__ . '/../includes/check_license.php';
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') {
    die("Acceso denegado.");
}

$factura_id = $_GET['id'] ?? 0;
$stmt = $conexion->prepare("SELECT * FROM facturas WHERE id = ?");
$stmt->bind_param("i", $factura_id);
$stmt->execute();
$factura = $stmt->get_result()->fetch_assoc();
if (!$factura) die("Factura no encontrada.");

$page_title = "Editar Fechas de Factura #" . $factura_id;
include '../includes/header.php';
?>
<h1>Editar Fechas de Factura #<?= $factura_id ?></h1>

<div class="dashboard-block">
    <form action="guardar_factura.php" method="post">
        <input type="hidden" name="factura_id" value="<?= $factura['id'] ?>">
        
        <label for="fecha_emision">Fecha de Emisión:</label>
        <input type="date" name="fecha_emision" id="fecha_emision" value="<?= htmlspecialchars($factura['fecha_emision']) ?>" required>

        <label for="fecha_vencimiento">Fecha de Vencimiento:</label>
        <input type="date" name="fecha_vencimiento" id="fecha_vencimiento" value="<?= htmlspecialchars($factura['fecha_vencimiento']) ?>" required>
        
        <br><br>
        <button type="submit">Guardar Cambios</button>
        <a href="ver_factura.php?id=<?= $factura['id'] ?>" style="margin-left: 15px;">Cancelar</a>
    </form>
</div>

<?php 
$stmt->close();
$conexion->close();
include '../includes/footer.php';
?> 
