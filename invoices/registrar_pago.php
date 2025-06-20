<?php
session_start();
require_once __DIR__ . '/../includes/check_license.php';
if (!isset($_SESSION['usuario_id'])) die("Acceso denegado.");

$factura_id = $_GET['factura_id'] ?? 0;
if ($factura_id == 0) header("Location: index.php");

$stmt = $conexion->prepare("SELECT f.*, c.nombre_completo FROM facturas f JOIN clientes c ON f.cliente_id = c.id WHERE f.id = ?");
$stmt->bind_param("i", $factura_id);
$stmt->execute();
$factura = $stmt->get_result()->fetch_assoc();
if (!$factura) die("Factura no encontrada.");

$page_title = "Registrar Pago para Factura #" . $factura_id;
include '../includes/header.php';
?>
<h1>Registrar Pago para Factura #<?= $factura_id ?></h1>

<div class="dashboard-block">
    <p><strong>Cliente:</strong> <?= htmlspecialchars($factura['nombre_completo']) ?></p>
    <p><strong>Total de la Factura:</strong> $<?= number_format($factura['total'], 2) ?></p>
    <p><strong>Estado Actual:</strong> <span style="text-transform:uppercase; font-weight:bold;"><?= htmlspecialchars($factura['estado']) ?></span></p>
    <hr>
    
    <form action="procesar_pago.php" method="post">
        <input type="hidden" name="factura_id" value="<?= $factura['id'] ?>">
        <input type="hidden" name="cliente_id" value="<?= $factura['cliente_id'] ?>">
        <input type="hidden" name="monto_factura" value="<?= $factura['total'] ?>">

        <label for="monto">Monto Pagado:</label>
        <input type="number" step="0.01" name="monto" id="monto" value="<?= $factura['total'] ?>" required>

        <label for="metodo_pago">Método de Pago:</label>
        <select name="metodo_pago" id="metodo_pago" required>
            <option value="Efectivo">Efectivo</option>
            <option value="Transferencia Bancaria">Transferencia Bancaria</option>
            <option value="Mercado Pago">Mercado Pago</option>
            <option value="Otro">Otro</option>
        </select>

        <label for="fecha">Fecha del Pago:</label>
        <input type="datetime-local" name="fecha" id="fecha" value="<?= date('Y-m-d\TH:i') ?>" required>

        <label for="descripcion">Descripción / Referencia:</label>
        <input type="text" name="descripcion" id="descripcion" value="Pago Factura #<?= $factura_id ?>"><br><br>

        <button type="submit">Confirmar y Registrar Pago</button>
        <a href="index.php" style="margin-left: 15px;">Cancelar</a>
    </form>
</div>

<?php 
$stmt->close();
$conexion->close();
include '../includes/footer.php';
?>