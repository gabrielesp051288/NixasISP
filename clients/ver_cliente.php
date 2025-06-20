<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}
require_once __DIR__ . '/../includes/check_license.php';

// --- 1. OBTENER DATOS PARA TODOS LOS BLOQUES ---
$cliente_id = $_GET['id'] ?? 0;
if ($cliente_id <= 0) { 
    header("Location: index.php"); 
    exit(); 
}

// Bloque 1: Datos del Cliente
$stmt_cliente = $conexion->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt_cliente->bind_param("i", $cliente_id);
$stmt_cliente->execute();
$cliente_resultado = $stmt_cliente->get_result();
$cliente = $cliente_resultado->fetch_assoc();
if (!$cliente) {
    die("Cliente no encontrado.");
}

// Bloque 2: Servicios Contratados
$stmt_servicios = $conexion->prepare("SELECT cs.id, p.nombre, cs.fecha_activacion, cs.precio_pactado, cs.estado FROM cliente_servicios cs JOIN planes p ON cs.plan_id = p.id WHERE cs.cliente_id = ? ORDER BY cs.fecha_activacion DESC");
$stmt_servicios->bind_param("i", $cliente_id);
$stmt_servicios->execute();
$servicios_contratados = $stmt_servicios->get_result();

// Bloque 3: Facturas del Cliente
$stmt_facturas = $conexion->prepare("SELECT id, fecha_emision, fecha_vencimiento, total, estado FROM facturas WHERE cliente_id = ? ORDER BY fecha_emision DESC");
$stmt_facturas->bind_param("i", $cliente_id);
$stmt_facturas->execute();
$facturas_cliente = $stmt_facturas->get_result();

// Bloque 4: Transacciones del Cliente
$stmt_transacciones = $conexion->prepare("SELECT fecha, descripcion, metodo_pago, monto FROM transacciones WHERE cliente_id = ? ORDER BY fecha DESC");
$stmt_transacciones->bind_param("i", $cliente_id);
$stmt_transacciones->execute();
$transacciones_cliente = $stmt_transacciones->get_result();

// NUEVA CONSULTA: Obtener las notas de este cliente, junto con el nombre del admin que la escribió
$stmt_notas = $conexion->prepare(
    "SELECT n.nota, n.fecha_creacion, u.nombre_completo 
     FROM cliente_notas n 
     JOIN usuarios u ON n.usuario_id = u.id 
     WHERE n.cliente_id = ? 
     ORDER BY n.fecha_creacion DESC"
);
$stmt_notas->bind_param("i", $cliente_id);
$stmt_notas->execute();
$notas_cliente = $stmt_notas->get_result();

// Datos para el formulario de Asignar Servicio
$planes_disponibles = $conexion->query("SELECT * FROM planes ORDER BY nombre ASC");

$page_title = "Dashboard de " . htmlspecialchars($cliente['nombre_completo']);
include '../includes/header.php';
?>

<h1>Dashboard del Cliente: <?= htmlspecialchars($cliente['nombre_completo']) ?></h1>
<p><strong>N° Cliente:</strong> <?= htmlspecialchars($cliente['numero_de_cliente']) ?> | <strong>Email:</strong> <?= htmlspecialchars($cliente['email']) ?></p>
<hr>

<div class="dashboard-block">
    <h2>Servicios</h2>
    <table>
        <thead><tr><th>Plan</th><th>Activación</th><th>Precio</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
            <?php while($servicio = $servicios_contratados->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($servicio['nombre']) ?></td>
                <td><?= htmlspecialchars($servicio['fecha_activacion']) ?></td>
                <td>$<?= number_format($servicio['precio_pactado'], 2, ',', '.') ?></td>
                <td style="text-transform:uppercase; font-weight:bold;"><?= htmlspecialchars($servicio['estado']) ?></td>
                <td>
                    <?php if ($servicio['estado'] == 'activo'): ?>
                        <form action="../invoices/generar_factura.php" method="post" style="display:inline-block; margin-right: 5px;">
                            <input type="hidden" name="cliente_id" value="<?= $cliente['id'] ?>">
                            <input type="hidden" name="cliente_servicio_id" value="<?= $servicio['id'] ?>">
                            <button type="submit" class="action-btn btn-primary">Facturar</button>
                        </form>
                        <form action="cancelar_servicio.php" method="post" style="display:inline-block;">
                            <input type="hidden" name="cliente_servicio_id" value="<?= $servicio['id'] ?>">
                            <input type="hidden" name="cliente_id" value="<?= $cliente['id'] ?>">
                            <button type="submit" class="action-btn btn-danger" onclick="return confirm('¿Seguro?');">Cancelar</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php if ($servicios_contratados->num_rows === 0): ?><tr><td colspan="5">No hay servicios contratados.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<div class="dashboard-block">
    <h2>Facturas</h2>
    <table>
        <thead><tr><th># Factura</th><th>Emisión</th><th>Vencimiento</th><th>Total</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
            <?php while($factura = $facturas_cliente->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($factura['id']) ?></td>
                <td><?= htmlspecialchars($factura['fecha_emision']) ?></td>
                <td><?= htmlspecialchars($factura['fecha_vencimiento']) ?></td>
                <td>$<?= number_format($factura['total'], 2, ',', '.') ?></td>
                <td style="text-transform:uppercase; font-weight:bold;"><?= htmlspecialchars($factura['estado']) ?></td>
                <td>
                    <a href="../invoices/ver_factura.php?id=<?= $factura['id'] ?>" class="action-btn btn-primary">Ver</a>
                    <?php if ($factura['estado'] == 'pendiente' || $factura['estado'] == 'vencida'): ?>
                        <a href="../invoices/registrar_pago.php?factura_id=<?= $factura['id'] ?>" class="action-btn btn-success">Registrar Pago</a>
                    <?php endif; ?>
                    <?php if ($_SESSION['usuario_rol'] === 'administrador'): ?>
                        <a href="../invoices/borrar_factura.php?id=<?= $factura['id'] ?>" class="action-btn btn-danger" onclick="return confirm('¿Seguro?');">Borrar</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php if ($facturas_cliente->num_rows === 0): ?><tr><td colspan="6">No hay facturas para este cliente.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<div class="dashboard-block">
    <h2>Transacciones</h2>
    <table>
        <thead><tr><th>Fecha</th><th>Descripción</th><th>Método</th><th>Monto</th></tr></thead>
        <tbody>
            <?php while($transaccion = $transacciones_cliente->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($transaccion['fecha']) ?></td>
                <td><?= htmlspecialchars($transaccion['descripcion']) ?></td>
                <td><?= htmlspecialchars($transaccion['metodo_pago']) ?></td>
                <td>$<?= number_format($transaccion['monto'], 2, ',', '.') ?></td>
            </tr>
            <?php endwhile; ?>
            <?php if ($transacciones_cliente->num_rows === 0): ?><tr><td colspan="4">No hay transacciones registradas.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<div class="dashboard-block">
    <h2>Notas Internas (Solo visible para administradores)</h2>
    <form action="guardar_nota.php" method="post" style="margin-bottom: 20px;">
        <input type="hidden" name="cliente_id" value="<?= $cliente['id'] ?>">
        <label for="nota">Añadir nueva nota:</label>
        <textarea name="nota" id="nota" rows="3" placeholder="Escribe aquí un comentario, recordatorio o detalle de la conversación..." required></textarea>
        <button type="submit" style="margin-top: 10px;">Guardar Nota</button>
    </form>
    <hr>
    <h4>Historial de Notas</h4>
    <?php if ($notas_cliente->num_rows > 0): ?>
        <?php while($nota = $notas_cliente->fetch_assoc()): ?>
            <div style="border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 10px;">
                <p><strong><?= nl2br(htmlspecialchars($nota['nota'])) ?></strong></p>
                <small style="color: #6c7a89;">
                    Añadido por: <strong><?= htmlspecialchars($nota['nombre_completo']) ?></strong> el <?= date("d/m/Y H:i", strtotime($nota['fecha_creacion'])) ?>
                </small>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No hay notas para este cliente.</p>
    <?php endif; ?>
</div>

<div class="dashboard-block">
    <h2>Asignar Nuevo Servicio</h2>
    <form action="asignar_servicio.php" method="post">
        <input type="hidden" name="cliente_id" value="<?= $cliente['id'] ?>">
        <label for="plan_id">Seleccionar Plan:</label>
        <select name="plan_id" id="plan_id" required>
            <option value="">-- Elige un plan --</option>
            <?php mysqli_data_seek($planes_disponibles, 0); ?>
            <?php while($plan = $planes_disponibles->fetch_assoc()): ?>
                <option value="<?= $plan['id'] ?>" data-precio="<?= $plan['precio'] ?>">
                    <?= htmlspecialchars($plan['nombre']) ?> ($<?= number_format($plan['precio'], 2) ?>)
                </option>
            <?php endwhile; ?>
        </select>
        <label for="precio_pactado">Precio a Facturar:</label>
        <input type="number" step="0.01" name="precio_pactado" id="precio_pactado" required>
        <label for="fecha_activacion">Fecha de Activación:</label>
        <input type="date" name="fecha_activacion" value="<?= date('Y-m-d') ?>" required><br><br>
        <button type="submit">Asignar Servicio</button>
    </form>
</div>

<script>
    const planSelect = document.getElementById('plan_id');
    const precioInput = document.getElementById('precio_pactado');
    function updatePrecio() {
        if (planSelect.value === "") {
            precioInput.value = ""; return;
        }
        const selectedOption = planSelect.options[planSelect.selectedIndex];
        precioInput.value = selectedOption.dataset.precio;
    }
    planSelect.addEventListener('change', updatePrecio);
</script>

<?php
$stmt_cliente->close();
$stmt_servicios->close();
$stmt_facturas->close();
$stmt_transacciones->close();
$stmt_notas->close();
$conexion->close();
include '../includes/footer.php'; 
?>