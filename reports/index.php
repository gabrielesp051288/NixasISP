<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') {
    header("Location: ../index.php");
    exit();
}
require_once __DIR__ . '/../includes/check_license.php'; 
$page_title = 'Reportes';
include '../includes/header.php';

// --- LÓGICA DE FILTRADO POR FECHA ---
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-01-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');

// --- 1. DATOS PARA REPORTE DE INGRESOS MENSUALES (con filtro) ---
$sql_ingresos = "SELECT 
                    SUM(monto) AS total,
                    DATE_FORMAT(fecha, '%Y-%m') AS mes_anio,
                    CONCAT(UCASE(LEFT(DATE_FORMAT(fecha, '%M'), 1)), SUBSTRING(DATE_FORMAT(fecha, '%M'), 2), ' ', YEAR(fecha)) AS mes_nombre 
                 FROM transacciones 
                 WHERE fecha BETWEEN ? AND ? 
                 GROUP BY mes_anio, mes_nombre 
                 ORDER BY mes_anio ASC";
$stmt_ingresos = $conexion->prepare($sql_ingresos);
$stmt_ingresos->bind_param("ss", $fecha_desde, $fecha_hasta);
$stmt_ingresos->execute();
$resultado_ingresos = $stmt_ingresos->get_result();
$ingresos_reporte = [];
while($fila = $resultado_ingresos->fetch_assoc()) $ingresos_reporte[] = $fila;
$labels_ingresos = json_encode(array_column($ingresos_reporte, 'mes_nombre'));
$data_ingresos = json_encode(array_column($ingresos_reporte, 'total'));

// --- 2. DATOS PARA REPORTE DE CRECIMIENTO DE CLIENTES (con filtro) ---
$sql_clientes = "SELECT 
                    COUNT(id) AS total, 
                    DATE_FORMAT(fecha_alta, '%Y-%m') AS mes_anio, 
                    CONCAT(UCASE(LEFT(DATE_FORMAT(fecha_alta, '%M'), 1)), SUBSTRING(DATE_FORMAT(fecha_alta, '%M'), 2), ' ', YEAR(fecha_alta)) AS mes_nombre 
                 FROM clientes 
                 WHERE estado != 'archivado' AND fecha_alta BETWEEN ? AND ? 
                 GROUP BY mes_anio, mes_nombre 
                 ORDER BY mes_anio ASC";
$stmt_clientes = $conexion->prepare($sql_clientes);
$stmt_clientes->bind_param("ss", $fecha_desde, $fecha_hasta);
$stmt_clientes->execute();
$resultado_clientes = $stmt_clientes->get_result();
$clientes_reporte = [];
while($fila = $resultado_clientes->fetch_assoc()) $clientes_reporte[] = $fila;
$labels_clientes = json_encode(array_column($clientes_reporte, 'mes_nombre'));
$data_clientes = json_encode(array_column($clientes_reporte, 'total'));

// --- 3. DATOS PARA REPORTE DE PLANES POPULARES (sin filtro de fecha) ---
$sql_planes = "SELECT p.nombre, COUNT(cs.id) AS total FROM cliente_servicios cs JOIN planes p ON cs.plan_id = p.id WHERE cs.estado = 'activo' GROUP BY p.nombre ORDER BY total DESC";
$resultado_planes = $conexion->query($sql_planes);
$planes_reporte = [];
while($fila = $resultado_planes->fetch_assoc()) $planes_reporte[] = $fila;
$labels_planes = json_encode(array_column($planes_reporte, 'nombre'));
$data_planes = json_encode(array_column($planes_reporte, 'total'));
?>

<h1>Reportes del Sistema</h1>

<div class="dashboard-block">
    <h2>Filtrar Reportes por Fecha</h2>
    <p>Los filtros de fecha se aplicarán a los reportes de Ingresos y Crecimiento de Clientes.</p>
    <form action="index.php" method="get" style="display: flex; gap: 20px; align-items: flex-end;">
        <div>
            <label for="fecha_desde">Desde</label>
            <input type="date" name="fecha_desde" id="fecha_desde" value="<?= htmlspecialchars($fecha_desde) ?>">
        </div>
        <div>
            <label for="fecha_hasta">Hasta</label>
            <input type="date" name="fecha_hasta" id="fecha_hasta" value="<?= htmlspecialchars($fecha_hasta) ?>">
        </div>
        <div>
            <button type="submit">Filtrar</button>
            <a href="index.php" style="margin-left: 10px;">Limpiar Filtros</a>
        </div>
    </form>
</div>

<div class="dashboard-block">
    <h2>Ingresos Mensuales (<?= date("d/m/Y", strtotime($fecha_desde)) ?> - <?= date("d/m/Y", strtotime($fecha_hasta)) ?>)</h2>
    <div class="report-layout">
        <div class="chart-container">
            <div class="chart-wrapper"><canvas id="graficoIngresos"></canvas></div>
        </div>
        <div class="table-container">
            <table>
                <thead><tr><th>Mes</th><th>Total Ingresado</th></tr></thead>
                <tbody>
                    <?php if (count($ingresos_reporte) > 0): ?>
                        <?php foreach (array_reverse($ingresos_reporte) as $fila): ?>
                        <tr><td><?= htmlspecialchars($fila['mes_nombre']) ?></td><td>$<?= number_format($fila['total'], 2, ',', '.') ?></td></tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2">No hay datos de ingresos en el período seleccionado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="dashboard-block">
    <h2>Crecimiento de Clientes (<?= date("d/m/Y", strtotime($fecha_desde)) ?> - <?= date("d/m/Y", strtotime($fecha_hasta)) ?>)</h2>
    <div class="report-layout">
        <div class="chart-container">
            <div class="chart-wrapper" style="max-height: 350px;"><canvas id="graficoCrecimiento"></canvas></div>
        </div>
        <div class="table-container">
            <table>
                <thead><tr><th>Mes</th><th>Nuevos Clientes</th></tr></thead>
                <tbody>
                    <?php if (count($clientes_reporte) > 0): ?>
                        <?php foreach (array_reverse($clientes_reporte) as $fila): ?>
                        <tr><td><?= htmlspecialchars($fila['mes_nombre']) ?></td><td><?= htmlspecialchars($fila['total']) ?></td></tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2">No hay clientes nuevos en el período seleccionado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="dashboard-block">
    <h2>Distribución de Planes Activos (Hoy)</h2>
    <div class="report-layout">
        <div class="chart-container" style="max-height: 400px; min-height: 300px;">
            <canvas id="graficoPlanes"></canvas>
        </div>
        <div class="table-container">
            <table>
                <thead><tr><th>Plan</th><th>Servicios Activos</th></tr></thead>
                <tbody>
                    <?php if (count($planes_reporte) > 0): ?>
                        <?php foreach ($planes_reporte as $fila): ?>
                        <tr><td><?= htmlspecialchars($fila['nombre']) ?></td><td><?= htmlspecialchars($fila['total']) ?></td></tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2">No hay servicios activos para mostrar.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('graficoIngresos').getContext('2d'), {
    type: 'bar', data: { labels: <?= $labels_ingresos ?>, datasets: [{ label: 'Ingresos ($)', data: <?= $data_ingresos ?>, backgroundColor: 'rgba(93, 156, 236, 0.7)' }] },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
});
new Chart(document.getElementById('graficoCrecimiento').getContext('2d'), {
    type: 'line', data: { labels: <?= $labels_clientes ?>, datasets: [{ label: 'Nuevos Clientes', data: <?= $data_clientes ?>, fill: true, borderColor: 'rgb(75, 192, 192)', tension: 0.1 }] },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});
new Chart(document.getElementById('graficoPlanes').getContext('2d'), {
    type: 'doughnut', data: { labels: <?= $labels_planes ?>, datasets: [{ label: 'Distribución', data: <?= $data_planes ?>,
        backgroundColor: [ 'rgba(255, 99, 132, 0.7)', 'rgba(54, 162, 235, 0.7)', 'rgba(255, 206, 86, 0.7)', 'rgba(75, 192, 192, 0.7)', 'rgba(153, 102, 255, 0.7)', 'rgba(255, 159, 64, 0.7)' ],
    }] },
    options: { responsive: true, maintainAspectRatio: false }
});
</script>

<?php 
$stmt_ingresos->close();
$stmt_clientes->close();
include '../includes/footer.php'; 
$conexion->close(); 
?>