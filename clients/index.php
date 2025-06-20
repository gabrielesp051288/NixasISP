<?php
// Se encarga de la licencia y la conexión a la BD. Debe ser la primera línea.
require_once __DIR__ . '/../includes/check_license.php';

session_start();

$page_title = 'Gestión de Clientes';
// La inclusión del header.php ahora es lo último antes de empezar a mostrar contenido.
include '../includes/header.php';

// --- GESTIÓN DE MENSAJES DE SESIÓN ---
$reporte_importacion = $_SESSION['reporte_importacion'] ?? null;
unset($_SESSION['reporte_importacion']);
$mensaje_borrado = $_SESSION['mensaje_borrado'] ?? null;
unset($_SESSION['mensaje_borrado']);
$mensaje_exito = $_SESSION['mensaje_exito'] ?? null;
unset($_SESSION['mensaje_exito']);
$mensaje_error = $_SESSION['mensaje_error'] ?? null;
unset($_SESSION['mensaje_error']);

// --- LÓGICA DE ORDENACIÓN ---
$columnas_permitidas = ['numero_de_cliente', 'nombre_completo', 'email', 'estado'];
$columna_orden = $_GET['sort'] ?? 'nombre_completo';
$orden = $_GET['order'] ?? 'ASC';
if (!in_array($columna_orden, $columnas_permitidas)) {
    $columna_orden = 'nombre_completo';
}
$orden_sql = strtoupper($orden) === 'DESC' ? 'DESC' : 'ASC';
$siguiente_orden = ($orden === 'ASC') ? 'DESC' : 'ASC';

// --- LÓGICA DE PAGINACIÓN ---
$registros_por_pagina = 25;
$pagina_actual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// --- LÓGICA DE BÚSQUEDA Y FILTRADO ---
$filtro_estado = $_GET['status'] ?? 'activos';
$termino_busqueda = $_GET['q'] ?? '';
$params = [];
$types = '';

if ($filtro_estado == 'archivados') {
    $sql_where = "WHERE estado = 'archivado' ";
} else {
    $sql_where = "WHERE estado != 'archivado' ";
}
if (!empty($termino_busqueda)) {
    $sql_where .= "AND (nombre_completo LIKE ? OR email LIKE ? OR numero_de_cliente LIKE ?)";
    $search_param = "%" . $termino_busqueda . "%";
    array_push($params, $search_param, $search_param, $search_param);
    $types .= 'sss';
}

// --- CONTAR TOTAL DE REGISTROS ---
$sql_count = "SELECT COUNT(*) as total FROM clientes $sql_where";
$stmt_count = $conexion->prepare($sql_count);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_registros = $stmt_count->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);
$stmt_count->close();

// --- OBTENER DATOS (con ordenación y paginación) ---
$sql_data = "SELECT id, numero_de_cliente, nombre_completo, email, telefono, estado FROM clientes $sql_where ORDER BY $columna_orden $orden_sql LIMIT ? OFFSET ?";
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
<h1>Gestión de Clientes</h1>

<div class="dashboard-block">
    <h2>Importar Clientes por CSV</h2>
    <p>Sube un archivo CSV con las siguientes columnas en este orden: <br>
    <small><code>numero_de_cliente, nombre_completo, email, telefono, address, ciudad, provincia, codigo_postal, pais, nombre_del_servicio</code></small>
    </p>
    <p><a href="descargar_plantilla.php" style="font-weight: bold;">Descargar Plantilla de Ejemplo (.csv)</a></p>
    <hr>
    <form action="importar_clientes.php" method="post" enctype="multipart/form-data">
        <label for="archivo_csv">Seleccionar archivo CSV para importar:</label><br>
        <input type="file" name="archivo_csv" id="archivo_csv" accept=".csv" required style="margin-top:10px;">
        <br><br>
        <button type="submit">Importar Clientes</button>
    </form>
    <?php if ($reporte_importacion): ?>
        <div style="margin-top: 15px; padding: 10px; border: 1px solid #ccc; border-radius: 5px; background-color: #f8f9fa;">
            <strong>Reporte de importación:</strong><br>
            - Clientes creados con éxito: <?= $reporte_importacion['exitosos'] ?><br>
            - Filas con errores: <?= $reporte_importacion['fallidos'] ?><br>
            <?php foreach($reporte_importacion['errores'] as $error): ?>
                <small style="color:red;">- <?= $error ?></small><br>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="dashboard-block">
    <h2>Listado de Clientes (<?= htmlspecialchars(ucfirst($filtro_estado)) ?>)</h2>
    <?php if ($mensaje_borrado): ?> <p style="font-weight:bold; color:blue;"><?= $mensaje_borrado ?></p> <?php endif; ?>
    <?php if ($mensaje_exito): ?> <p style="font-weight:bold; color:green;"><?= $mensaje_exito ?></p> <?php endif; ?>
    <?php if ($mensaje_error): ?> <p style="font-weight:bold; color:red;"><?= $mensaje_error ?></p> <?php endif; ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 20px;">
        <form action="index.php" method="get" style="display: flex; gap: 10px;">
            <input type="hidden" name="status" value="<?= htmlspecialchars($filtro_estado) ?>">
            <input type="text" name="q" placeholder="Buscar por nombre, email, n° cliente..." value="<?= htmlspecialchars($termino_busqueda) ?>" style="width: 300px; margin-bottom: 0;">
            <button type="submit">Buscar</button>
            <a href="index.php?status=<?= htmlspecialchars($filtro_estado) ?>" style="align-self: center;">Limpiar</a>
        </form>
        <div>
            <strong>Ver clientes:</strong>
            <a href="index.php?q=<?= urlencode($termino_busqueda) ?>&status=activos" style="margin: 0 5px;">Activos</a> | 
            <a href="index.php?q=<?= urlencode($termino_busqueda) ?>&status=archivados" style="margin: 0 5px;">Archivados</a>
        </div>
    </div>
    
    <form action="<?= ($filtro_estado == 'archivados') ? 'restaurar_masivo.php' : 'borrar_masivo.php' ?>" method="post" id="form-masivo">
        <div style="margin-bottom: 20px; display:flex; justify-content: space-between; align-items:center;">
            <div>
                <a href="crear_cliente.php" style="background-color: #28a745; color: white; padding: 10px; border-radius: 5px; text-decoration:none;">+ Añadir Nuevo Cliente</a>
                <?php if ($resultado && $resultado->num_rows > 0): ?>
                    <?php if ($filtro_estado == 'archivados'): ?>
                        <button type="submit" class="action-btn btn-success" style="margin-left: 20px;">Restaurar Seleccionados</button>
                    <?php else: ?>
                        <button type="submit" class="action-btn btn-danger" style="margin-left: 20px;">Archivar Seleccionados</button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div>
                <a href="exportar.php?status=<?= htmlspecialchars($filtro_estado) ?>&q=<?= urlencode($termino_busqueda) ?>" class="action-btn btn-primary">Exportar a CSV</a>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th><input type="checkbox" id="seleccionar-todos"></th>
                    <th><a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'numero_de_cliente', 'order' => $columna_orden === 'numero_de_cliente' ? $siguiente_orden : 'ASC'])) ?>">N° Cliente <?= $columna_orden === 'numero_de_cliente' ? ($orden === 'ASC' ? '▲' : '▼') : '' ?></a></th>
                    <th><a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'nombre_completo', 'order' => $columna_orden === 'nombre_completo' ? $siguiente_orden : 'ASC'])) ?>">Nombre Completo <?= $columna_orden === 'nombre_completo' ? ($orden === 'ASC' ? '▲' : '▼') : '' ?></a></th>
                    <th><a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'email', 'order' => $columna_orden === 'email' ? $siguiente_orden : 'ASC'])) ?>">Email <?= $columna_orden === 'email' ? ($orden === 'ASC' ? '▲' : '▼') : '' ?></a></th>
                    <th><a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'estado', 'order' => $columna_orden === 'estado' ? $siguiente_orden : 'ASC'])) ?>">Estado <?= $columna_orden === 'estado' ? ($orden === 'ASC' ? '▲' : '▼') : '' ?></a></th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado && $resultado->num_rows > 0): while($fila = $resultado->fetch_assoc()): ?>
                <tr>
                    <td><input type="checkbox" name="cliente_ids[]" value="<?= $fila['id'] ?>" class="checkbox-cliente"></td>
                    <td><?= htmlspecialchars($fila['numero_de_cliente']) ?></td>
                    <td><a href="ver_cliente.php?id=<?= $fila['id'] ?>"><?= htmlspecialchars($fila['nombre_completo']) ?></a></td>
                    <td><?= htmlspecialchars($fila['email']) ?></td>
                    <td style="text-transform:capitalize; font-weight:bold;"><?= htmlspecialchars($fila['estado']) ?></td>
                    <td>
                        <?php if ($fila['estado'] == 'archivado'): ?>
                            <a href="restaurar_cliente.php?id=<?= $fila['id'] ?>" class="action-btn btn-success" onclick="return confirm('¿Restaurar este cliente?');">Restaurar</a>
                        <?php else: ?>
                            <a href="editar_cliente.php?id=<?= $fila['id'] ?>" class="action-btn btn-primary">Editar</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="6">No se encontraron clientes que coincidan con los criterios.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </form>
    
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

<script>
if(document.getElementById('seleccionar-todos')) {
    document.getElementById('seleccionar-todos').addEventListener('change', function(e) {
        var checkboxes = document.querySelectorAll('.checkbox-cliente');
        for (var checkbox of checkboxes) { checkbox.checked = e.target.checked; }
    });
}
if(document.getElementById('form-masivo')) {
    document.getElementById('form-masivo').addEventListener('submit', function(e) {
        var filtro = '<?= $filtro_estado ?>';
        var mensaje = (filtro == 'archivados') 
            ? '¿Estás seguro de que quieres restaurar los clientes seleccionados?'
            : '¿Estás seguro de que quieres archivar los clientes seleccionados?';
        var algunoSeleccionado = Array.from(document.querySelectorAll('.checkbox-cliente')).some(c => c.checked);
        if (!algunoSeleccionado) {
            alert('Por favor, selecciona al menos un cliente.');
            e.preventDefault();
            return;
        }
        if (!confirm(mensaje)) { e.preventDefault(); }
    });
}
</script>

<?php 
$stmt_data->close();
$conexion->close(); 
include '../includes/footer.php'; 
?>