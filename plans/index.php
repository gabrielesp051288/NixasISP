<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') {
    header("Location: ../index.php");
    exit();
}
require_once __DIR__ . '/../includes/check_license.php';
$page_title = 'Planes de Servicios';
include '../includes/header.php';

$sql = "SELECT * FROM planes ORDER BY precio ASC";
$resultado = $conexion->query($sql);
?>
<h1>Planes de Servicios</h1>

<div class="dashboard-block">
    <a href="crear_plan.php" style="display:inline-block; margin-bottom:20px; background-color: #28a745; color: white; padding: 10px; border-radius: 5px;">+ Añadir Nuevo Plan</a>
    <table>
        <thead>
            <tr>
                <th>Nombre del Plan</th>
                <th>Descripción</th>
                <th>Precio</th>
                <th>Ciclo de Facturación</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while($plan = $resultado->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($plan['nombre']) ?></td>
                <td><?= htmlspecialchars($plan['descripcion']) ?></td>
                <td>$<?= number_format($plan['precio'], 2, ',', '.') ?></td>
                <td><?= htmlspecialchars(ucfirst($plan['ciclo_facturacion'])) ?></td>
                <td>
                    <a href="editar_plan.php?id=<?= $plan['id'] ?>">Editar</a> | 
                    <a href="borrar_plan.php?id=<?= $plan['id'] ?>" style="color:red;" onclick="return confirm('¿Estás seguro? Borrar un plan puede causar problemas si hay clientes asignados a él.');">Borrar</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php 
include '../includes/footer.php'; 
$conexion->close(); 
?>