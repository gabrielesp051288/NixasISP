<?php
session_start();
require_once __DIR__ . '/../includes/check_license.php';
if (!isset($_SESSION['usuario_id'])) die("Acceso denegado.");

$factura_id = $_GET['id'] ?? 0;
if ($factura_id == 0) {
    header("Location: index.php");
    exit();
}

// Obtener datos de la factura y del cliente
$sql = "SELECT f.*, c.nombre_completo, c.numero_de_cliente, c.address, c.ciudad, c.provincia 
        FROM facturas f 
        JOIN clientes c ON f.cliente_id = c.id 
        WHERE f.id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $factura_id);
$stmt->execute();
$factura_resultado = $stmt->get_result();
$factura = $factura_resultado->fetch_assoc();
if (!$factura) {
    die("Factura no encontrada.");
}

// Obtener items de la factura
$sql_items = "SELECT * FROM facturas_items WHERE factura_id = ?";
$stmt_items = $conexion->prepare($sql_items);
$stmt_items->bind_param("i", $factura_id);
$stmt_items->execute();
$items = $stmt_items->get_result();

$page_title = "Factura #" . htmlspecialchars($factura['id']);
include '../includes/header.php';
?>

<div class="invoice-box">
    <table cellpadding="0" cellspacing="0" style="width: 100%;">
        <tr class="top">
            <td colspan="2" style="padding-bottom: 20px; border-bottom: 1px solid #eee;">
                <h2>Factura #<?= htmlspecialchars($factura['id']) ?></h2>
                <b>Fecha de Emisión:</b> <?= date("d/m/Y", strtotime($factura['fecha_emision'])) ?><br>
                <b>Fecha de Vencimiento:</b> <?= date("d/m/Y", strtotime($factura['fecha_vencimiento'])) ?><br>
                <b>Estado:</b> <span style="text-transform:uppercase; font-weight:bold;"><?= htmlspecialchars($factura['estado']) ?></span>
            </td>
        </tr>
        <tr class="information">
            <td colspan="2" style="padding-top: 20px; padding-bottom: 40px;">
                <h3>Cliente</h3>
                <p>
                    <b><?= htmlspecialchars($factura['nombre_completo']) ?></b><br>
                    N° Cliente: <?= htmlspecialchars($factura['numero_de_cliente']) ?><br>
                    <?= htmlspecialchars($factura['address']) ?><br>
                    <?= htmlspecialchars($factura['ciudad']) . ", " . htmlspecialchars($factura['provincia']) ?>
                </p>
            </td>
        </tr>
    </table>

    <h3>Detalle de Facturación</h3>
    <table>
        <thead>
            <tr style="background-color:#f2f2f2;">
                <th style="padding: 8px; text-align:left;">Descripción</th>
                <th style="padding: 8px; text-align:right;">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php while($item = $items->fetch_assoc()): ?>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee;"><?= htmlspecialchars($item['descripcion']) ?></td>
                <td style="padding: 8px; text-align:right; border-bottom: 1px solid #eee;">$<?= number_format($item['subtotal'], 2, ',', '.') ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot>
            <tr style="font-weight:bold;">
                <td style="padding: 10px 8px; text-align:right; border-top: 2px solid #eee;">Total a Pagar:</td>
                <td style="padding: 10px 8px; text-align:right; border-top: 2px solid #eee;">$<?= number_format($factura['total'], 2, ',', '.') ?></td>
            </tr>
        </tfoot>
    </table>
</div>

<div style="max-width: 800px; margin: 20px auto; text-align: right;">
    <a href="generar_pdf.php?id=<?= $factura_id ?>" target="_blank" class="action-btn btn-primary">Descargar PDF</a>
    
    <?php if ($_SESSION['usuario_rol'] === 'administrador'): ?>
        <a href="editar_factura.php?id=<?= $factura_id ?>" class="action-btn btn-secondary">Editar Fechas</a>
    <?php endif; ?>

    <?php if ($factura['estado'] == 'pendiente' || $factura['estado'] == 'vencida'): ?>
        <a href="registrar_pago.php?factura_id=<?= $factura_id ?>" class="action-btn btn-success">Registrar Pago</a>
    <?php endif; ?>
</div>

<?php 
$stmt->close();
$stmt_items->close();
$conexion->close();
include '../includes/footer.php';
?>