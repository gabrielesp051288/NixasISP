<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}
require_once __DIR__ . '/../includes/check_license.php';
$page_title = 'Listado de Facturas';
include '../includes/header.php';

// --- TAREA 0: ACTUALIZACIÓN DE ESTADOS EN TIEMPO REAL ---
$hoy_fecha = date('Y-m-d');
$conexion->query("UPDATE facturas SET estado = 'vencida' WHERE fecha_vencimiento < '$hoy_fecha' AND estado = 'pendiente'");

// --- TAREA 1: LÓGICA DE ORDENACIÓN ---
$columnas_permitidas = ['id', 'nombre_completo', 'fecha_emision', 'fecha_vencimiento', 'total', 'estado'];
$columna_orden = $_GET['sort'] ?? 'id';
$orden = $_GET['order'] ?? 'DESC';
if (!in_array($columna_orden, $columnas_permitidas)) {
    $columna_orden = 'id';
}
$orden_sql = strtoupper($orden) === 'ASC' ? 'ASC' : 'DESC';
$siguiente_orden = ($orden === 'ASC') ? 'DESC' : 'ASC';

// --- TAREA 2: LÓGICA DE PAGINACIÓN, BÚSQUEDA Y FILTRADO ---
$registros_por_pagina = 25;
$pagina_actual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

$filtro_estado = $_GET['status'] ?? 'todos';
$termino_busqueda = $_GET['q'] ?? '';
$params = [];
$types = '';
$sql_where = "WHERE 1=1 ";

if ($filtro_estado != 'todos') {
    $sql_where .= "AND f.estado = ? ";
    $params[] = $filtro_estado;
    $types .= 's';
}
if (!empty($termino_busqueda)) {
    $sql_where .= "AND (c.nombre_completo LIKE ? OR c.numero_de_cliente LIKE ? OR f.id LIKE ?) ";
    $search_param = "%" . $termino_busqueda . "%";
    array_push($params, $search_param, $search_param, $search_param);
    $types .= 'sss';
}

// --- TAREA 3: CONTAR REGISTROS ---
$sql_count = "SELECT COUNT(f.id) as total FROM facturas f JOIN clientes c ON f.cliente_id = c.id $sql_where";
$stmt_count = $conexion->prepare($sql_count);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_registros = $stmt_count->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);
$stmt_count->close();

// --- TAREA 4: OBTENER DATOS ---
$sql_data = "SELECT f.id, f.fecha_emision, f.fecha_vencimiento, f.total, f.estado, c.nombre_completo, c.numero_de_cliente 
             FROM facturas f JOIN clientes c ON f.cliente_id = c.id 
             $sql_where
             ORDER BY $columna_orden $orden_sql
             LIMIT ? OFFSET ?";
$params_data = $params;
$params_data[] = $registros_por_pagina;
$params_data[] = $offset;
$types_data = $types . 'ii';

$stmt_data = $conexion->prepare($sql_data);
if (!empty($params)) {
    $stmt_data->bind_param($types_data, ...$params_data);
} else {
    $stmt_data->bind_param('ii', $registros_por_pagina, $offset);
}
$stmt_data->execute();
$resultado = $stmt_data->get_result();
?>
<h1>Listado de Facturas</h1>

<div class="dashboard-block">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 20px;">
        <form action="index.php" method="get" style="display: flex; gap: 10px;">
            <input type="hidden" name="status" value="<?= htmlspecialchars($filtro_estado) ?>">
            <input type="hidden" name="sort" value="<?= htmlspecialchars($columna_orden) ?>">
            <input type="hidden" name="order" value="<?= htmlspecialchars($orden) ?>">
            <input type="text" name="q" placeholder="Buscar por cliente, n° cliente, n° factura..." value="<?= htmlspecialchars($termino_busqueda) ?>" style="width: 300px; margin-bottom: 0;">
            <button type="submit">Buscar</button>
            <a href="index.php?status=<?= htmlspecialchars($filtro_estado) ?>" style="align-self: center;">Limpiar</a>
        </form>
        <div>
            <strong>Ver facturas:</strong>
            <a href="index.php?q=<?= urlencode($termino_busqueda) ?>&status=todos" style="margin: 0 5px;">Todas</a> | 
            <a href="index.php?q=<?= urlencode($termino_busqueda) ?>&status=pendiente">Pendientes</a> | 
            <a href="index.php?q=<?= urlencode($termino_busqueda) ?>&status=pagada">Pagadas</a> |
            <a href="index.php?q=<?= urlencode($termino_busqueda) ?>&status=vencida">Vencidas</a>
        </div>
    </div>
    
    <div style="margin-bottom: 20px;">
        <a href="exportar.php?status=<?= htmlspecialchars($filtro_estado) ?>&q=<?= urlencode($termino_busqueda) ?>" class="action-btn btn-primary">
            Exportar Vista a CSV
        </a>
    </div>

    <table>
        <thead>
            <tr>
                <th><a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'id', 'order' => $columna_orden === 'id' ? $siguiente_orden : 'ASC'])) ?>">N° Factura <?= $columna_orden === 'id' ? ($orden === 'ASC' ? '▲' : '▼') : '' ?></a></th>
                <th><a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'nombre_completo', 'order' => $columna_orden === 'nombre_completo' ? $siguiente_orden : 'ASC'])) ?>">Cliente <?= $columna_orden === 'nombre_completo' ? ($orden === 'ASC' ? '▲' : '▼') : '' ?></a></th>
                <th><a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'fecha_emision', 'order' => $columna_orden === 'fecha_emision' ? $siguiente_orden : 'ASC'])) ?>">Emisión <?= $columna_orden === 'fecha_emision' ? ($orden === 'ASC' ? '▲' : '▼') : '' ?></a></th>
                <th><a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'fecha_vencimiento', 'order' => $columna_orden === 'fecha_vencimiento' ? $siguiente_orden : 'ASC'])) ?>">Vencimiento <?= $columna_orden === 'fecha_vencimiento' ? ($orden === 'ASC' ? '▲' : '▼') : '' ?></a></th>
                <th><a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'total', 'order' => $columna_orden === 'total' ? $siguiente_orden : 'ASC'])) ?>">Total <?= $columna_orden === 'total' ? ($orden === 'ASC' ? '▲' : '▼') : '' ?></a></th>
                <th><a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'estado', 'order' => $columna_orden === 'estado' ? $siguiente_orden : 'ASC'])) ?>">Estado <?= $columna_orden === 'estado' ? ($orden === 'ASC' ? '▲' : '▼') : '' ?></a></th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($resultado && $resultado->num_rows > 0): while($factura = $resultado->fetch_assoc()): ?>
                <?php
                    $clase_fila = '';
                    if ($factura['estado'] == 'vencida') $clase_fila = 'estado-vencida';
                    elseif ($factura['estado'] == 'pagada') $clase_fila = 'estado-pagada';
                ?>
                <tr class="<?= $clase_fila ?>">
                    <td><a href="ver_factura.php?id=<?= $factura['id'] ?>"><?= htmlspecialchars($factura['id']) ?></a></td>
                    <td><?= htmlspecialchars($factura['nombre_completo']) ?> (<?= htmlspecialchars($factura['numero_de_cliente']) ?>)</td>
                    <td><?= htmlspecialchars($factura['fecha_emision']) ?></td>
                    <td><?= htmlspecialchars($factura['fecha_vencimiento']) ?></td>
                    <td>$<?= number_format($factura['total'], 2, ',', '.') ?></td>
                    <td style="text-transform:uppercase; font-weight:bold;"><?= htmlspecialchars($factura['estado']) ?></td>
                    <td>
                        <a href="ver_factura.php?id=<?= $factura['id'] ?>" class="action-btn btn-primary">Ver</a>
                        <?php if ($factura['estado'] == 'pendiente' || $factura['estado'] == 'vencida'): ?>
                            <a href="registrar_pago.php?factura_id=<?= $factura['id'] ?>" class="action-btn btn-success">Registrar Pago</a>
                        <?php endif; ?>
                        <?php if ($_SESSION['usuario_rol'] === 'administrador'): ?>
                            <a href="borrar_factura.php?id=<?= $factura['id'] ?>" class="action-btn btn-danger" onclick="return confirm('¿Estás seguro de que quieres eliminar esta factura permanentemente?');">Borrar</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="7">No se encontraron facturas que coincidan con los criterios.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <?php if ($total_paginas > 1): ?>
    <nav aria-label="Navegación de páginas">
        <ul class="pagination">
            <li class="page-item <?= ($pagina_actual <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $pagina_actual - 1])) ?>">Anterior</a>
            </li>
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <li class="page-item <?= ($i == $pagina_actual) ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= ($pagina_actual >= $total_paginas) ? 'disabled' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $pagina_actual + 1])) ?>">Siguiente</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php 
$stmt_data->close();
include '../includes/footer.php'; 
$conexion->close(); 
?>