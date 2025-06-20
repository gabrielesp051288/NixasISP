<?php
// Se encarga de la licencia, la conexión a la BD y de cargar config.php
require_once __DIR__ . '/includes/check_license.php';

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$page_title = 'Dashboard';
include 'includes/header.php';

// --- QUERIES PARA LAS TARJETAS DE RESUMEN ---
$mes_actual = date('m');
$anho_actual = date('Y');

// 1. Total Facturado este Mes
$stmt_facturado = $conexion->prepare("SELECT SUM(total) as total FROM facturas WHERE MONTH(fecha_emision) = ? AND YEAR(fecha_emision) = ?");
$stmt_facturado->bind_param("ss", $mes_actual, $anho_actual);
$stmt_facturado->execute();
$total_facturado = $stmt_facturado->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_facturado->close();

// 2. Total Cobrado este Mes (basado en transacciones)
$stmt_cobrado = $conexion->prepare("SELECT SUM(monto) as total FROM transacciones WHERE MONTH(fecha) = ? AND YEAR(fecha) = ?");
$stmt_cobrado->bind_param("ss", $mes_actual, $anho_actual);
$stmt_cobrado->execute();
$total_cobrado = $stmt_cobrado->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_cobrado->close();

// 3. Cantidad de Clientes Activos (no archivados)
$resultado_clientes = $conexion->query("SELECT COUNT(id) as total FROM clientes WHERE estado != 'archivado'");
$clientes_activos = $resultado_clientes->fetch_assoc()['total'] ?? 0;

// 4. Cantidad de Servicios Activos
$resultado_servicios = $conexion->query("SELECT COUNT(id) as total FROM cliente_servicios WHERE estado = 'activo'");
$servicios_activos = $resultado_servicios->fetch_assoc()['total'] ?? 0;

// Consulta para el log del cron job
$ultimo_cron = $conexion->query("SELECT fecha_ejecucion, estado, resumen FROM cron_log WHERE script_name = 'generacion_facturas' ORDER BY id DESC LIMIT 1")->fetch_assoc();

// Mensajes de sesión para el reseteo
$reset_exito = $_SESSION['reset_exito'] ?? null;
unset($_SESSION['reset_exito']);
$reset_error = $_SESSION['reset_error'] ?? null;
unset($_SESSION['reset_error']);
?>

<h1>Dashboard</h1>

<?php if ($reset_exito): ?>
    <div class="dashboard-block" style="border-color: var(--color-exito); background-color: #e6ffed;">
        <p style="font-weight:bold; color: var(--color-exito); margin:0;"><?= $reset_exito ?></p>
    </div>
<?php endif; ?>
<?php if ($reset_error): ?>
    <div class="dashboard-block" style="border-color: var(--color-error); background-color: #ffe6e6;">
        <p style="font-weight:bold; color: var(--color-error); margin:0;"><?= $reset_error ?></p>
    </div>
<?php endif; ?>

<div class="summary-card-container">
    <div class="summary-card">
        <div class="card-title">TOTAL FACTURADO (ESTE MES)</div>
        <div class="card-number">$<?= number_format($total_facturado, 2, ',', '.') ?></div>
    </div>
    <div class="summary-card cobrado">
        <div class="card-title">TOTAL COBRADO (ESTE MES)</div>
        <div class="card-number">$<?= number_format($total_cobrado, 2, ',', '.') ?></div>
    </div>
    <div class="summary-card">
        <div class="card-title">CLIENTES ACTIVOS</div>
        <div class="card-number"><?= $clientes_activos ?></div>
    </div>
    <div class="summary-card">
        <div class="card-title">SERVICIOS ACTIVOS</div>
        <div class="card-number"><?= $servicios_activos ?></div>
    </div>
</div>


<div class="dashboard-block">
    <h2 class="collapsible-header" id="cron-log-header">Estado del Sistema</h2>
    <div id="cron-log-content">
        <div class="dashboard-block" style="box-shadow: none; padding: 15px 0 0 0; border: none;">
            <h3>Facturación Automática</h3>
            <?php if ($ultimo_cron): ?>
                <p>
                    Última ejecución: <strong><?= htmlspecialchars($ultimo_cron['fecha_ejecucion']) ?></strong>
                </p>
                <p>
                    Estado: <strong style="text-transform: uppercase; color: <?= $ultimo_cron['estado'] == 'Exitoso' ? 'green' : 'red' ?>;"><?= htmlspecialchars($ultimo_cron['estado']) ?></strong>
                </p>
                <h4>Resumen de la última ejecución:</h4>
                <pre class="log-output"><?= htmlspecialchars($ultimo_cron['resumen']) ?></pre>
            <?php else: ?>
                <p>Aún no se ha ejecutado ninguna tarea de facturación automática.</p>
            <?php endif; ?>
            <div style="margin-top: 20px;">
                <a href="herramientas/ejecutar_cron.php" target="_blank" class="action-btn btn-primary">
                    Ejecutar Tarea Manualmente
                </a>
                <small style="display: block; margin-top: 5px;">(Esto abrirá una nueva pestaña para mostrar el resultado en vivo)</small>
            </div>
        </div>
    </div>
</div>


<?php 
// La visibilidad ahora depende de la constante DEV_MODE en config.php
if (defined('DEV_MODE') && DEV_MODE === true && $_SESSION['usuario_rol'] === 'administrador'): 
?>
<div class="dashboard-block" style="border: 2px solid var(--color-error); background-color: #fff5f5;">
    <h2 style="color: var(--color-error);">Herramientas de Desarrollo (¡Peligro!)</h2>
    <p>La siguiente acción borrará todos los datos de clientes, servicios, facturas y transacciones de la base de datos de forma irreversible. También reseteará la configuración.</p>
    
    <form action="herramientas/reset_bd.php" method="post" onsubmit="return confirm('ATENCIÓN: Estás a punto de borrar TODOS los datos. Esta acción no se puede deshacer. ¿Estás seguro de continuar?');">
        <button type="submit" style="background-color: var(--color-error); color: white; font-size: 1.1em; padding: 15px; border:none; border-radius: 5px; cursor:pointer;" onclick="return confirm('CONFIRMACIÓN FINAL: ¿ESTÁS COMPLETAMENTE SEGURO DE QUE QUIERES VACIAR LA BASE DE DATOS?');">
            Resetear Base de Datos
        </button>
    </form>
</div>
<?php endif; ?>


<?php 
include 'includes/footer.php'; 
$conexion->close();
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const header = document.getElementById('cron-log-header');
    const content = document.getElementById('cron-log-content');
    if (header && content) {
        header.addEventListener('click', function() {
            content.classList.toggle('is-hidden');
            header.classList.toggle('collapsed');
        });
    }
});
</script>