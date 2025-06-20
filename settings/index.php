<?php
// Se encarga de la licencia y la conexión a la BD. Debe ser la primera línea.
require_once __DIR__ . '/../includes/check_license.php';

session_start();
// Solo los administradores pueden acceder a la configuración
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') {
    header("Location: " . BASE_PATH . "index.php");
    exit();
}

// La línea "include '../config.php';" se elimina, porque check_license ya lo carga a través de la conexión.
// La variable $conexion ya está disponible globalmente.

$mensaje_exito = '';
$mensaje_error = '';

// --- PROCESAR EL FORMULARIO SI SE ENVÍA ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conexion->begin_transaction();
    try {
        // Lista de ajustes de texto
        $settings_texto = [
            'dia_vencimiento_factura' => $_POST['dia_vencimiento'] ?? 10,
            'company_name' => $_POST['company_name'] ?? '',
            'company_address' => $_POST['company_address'] ?? '',
            'company_tax_id' => $_POST['company_tax_id'] ?? '',
            'company_phone' => $_POST['company_phone'] ?? '',
            'email_from_address' => $_POST['email_from_address'] ?? '',
            'email_from_name' => $_POST['email_from_name'] ?? '',
            'email_smtp_host' => $_POST['email_smtp_host'] ?? '',
            'email_smtp_port' => $_POST['email_smtp_port'] ?? '',
            'email_smtp_secure' => $_POST['email_smtp_secure'] ?? 'tls',
            'email_smtp_user' => $_POST['email_smtp_user'] ?? ''
        ];
        
        $stmt_update = $conexion->prepare("UPDATE configuracion SET valor = ? WHERE clave = ?");
        foreach ($settings_texto as $clave => $valor) {
            $stmt_update->bind_param("ss", $valor, $clave);
            $stmt_update->execute();
        }
        $stmt_update->close();

        if (!empty($_POST['email_smtp_pass'])) {
            $stmt_pass = $conexion->prepare("UPDATE configuracion SET valor = ? WHERE clave = 'email_smtp_pass'");
            $stmt_pass->bind_param("s", $_POST['email_smtp_pass']);
            $stmt_pass->execute();
            $stmt_pass->close();
        }

        if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] == 0) {
            $directorio_subida = '../assets/uploads/';
            if (!file_exists($directorio_subida)) {
                mkdir($directorio_subida, 0777, true);
            }
            $nombre_archivo_original = basename($_FILES['company_logo']['name']);
            $tipo_imagen = strtolower(pathinfo($nombre_archivo_original, PATHINFO_EXTENSION));
            $nombre_archivo = 'logo_' . time() . '.' . $tipo_imagen;
            $ruta_completa = $directorio_subida . $nombre_archivo;
            
            $tipos_permitidos = ["jpg", "png", "jpeg", "gif"];
            if (!in_array($tipo_imagen, $tipos_permitidos)) {
                throw new Exception("Solo se permiten archivos JPG, JPEG, PNG y GIF.");
            }
            
            if (move_uploaded_file($_FILES['company_logo']['tmp_name'], $ruta_completa)) {
                $stmt_logo = $conexion->prepare("UPDATE configuracion SET valor = ? WHERE clave = 'company_logo'");
                $stmt_logo->bind_param("s", $nombre_archivo);
                $stmt_logo->execute();
                $stmt_logo->close();
            } else {
                throw new Exception("Hubo un error al subir el archivo del logo.");
            }
        }
        
        $conexion->commit();
        $mensaje_exito = "¡Configuración actualizada con éxito!";

    } catch (Exception $e) {
        $conexion->rollback();
        $mensaje_error = "Error: " . $e->getMessage();
    }
}

// --- OBTENER TODOS LOS AJUSTES DE LA BASE DE DATOS PARA MOSTRARLOS ---
$configuracion_db = $conexion->query("SELECT clave, valor FROM configuracion");
$settings = [];
while($row = $configuracion_db->fetch_assoc()) {
    $settings[$row['clave']] = $row['valor'];
}

// --- LÓGICA PARA DETERMINAR TIPO DE LICENCIA ---
$tipo_licencia = 'N/A';
if (isset($settings['license_status']) && $settings['license_status'] === 'licensed') {
    $tipo_licencia = 'Licencia Perpetua';
}

$page_title = 'Configuración';
include '../includes/header.php';
?>
<h1>Configuración del Sistema</h1>

<?php if ($mensaje_exito): ?>
    <div class="dashboard-block" style="border-color: var(--color-exito); background-color: #e6ffed;">
        <p style="font-weight:bold; color: var(--color-exito); margin:0;"><?= $mensaje_exito ?></p>
    </div>
<?php endif; ?>
<?php if ($mensaje_error): ?>
    <div class="dashboard-block" style="border-color: var(--color-error); background-color: #ffe6e6;">
        <p style="font-weight:bold; color: var(--color-error); margin:0;"><?= $mensaje_error ?></p>
    </div>
<?php endif; ?>

<div class="settings-layout">
    <aside class="settings-menu">
        <ul>
            <li><a href="#empresa" class="active">Perfil de la Empresa</a></li>
            <li><a href="#facturacion">Facturación</a></li>
            <li><a href="#email">Configuración de Email</a></li>
            <li><a href="#licencia">Licencia</a></li>
            <li><a href="#usuarios">Administración de Usuarios</a></li>
        </ul>
    </aside>

    <div class="settings-content">
        <form action="index.php" method="post" enctype="multipart/form-data">
            
            <div id="empresa" class="content-pane">
                <div class="dashboard-block">
                    <h2>Perfil de la Empresa</h2>
                    <label for="company_name">Nombre de la Empresa</label>
                    <input type="text" name="company_name" id="company_name" value="<?= htmlspecialchars($settings['company_name'] ?? '') ?>">
                    <label for="company_tax_id">CUIT / ID Fiscal</label>
                    <input type="text" name="company_tax_id" id="company_tax_id" value="<?= htmlspecialchars($settings['company_tax_id'] ?? '') ?>">
                    <label for="company_address">Dirección Fiscal</label>
                    <textarea name="company_address" id="company_address" rows="3"><?= htmlspecialchars($settings['company_address'] ?? '') ?></textarea>
                    <label for="company_phone">Teléfono de Contacto</label>
                    <input type="text" name="company_phone" id="company_phone" value="<?= htmlspecialchars($settings['company_phone'] ?? '') ?>">
                    <label for="company_logo">Logo de la Empresa</label>
                    <input type="file" name="company_logo" id="company_logo" accept="image/png, image/jpeg, image/gif">
                    <small>Sube un nuevo logo para reemplazar el actual.</small>
                    <?php if (!empty($settings['company_logo'])): ?>
                        <div style="margin-top:10px;">
                            <strong>Logo Actual:</strong><br>
                            <img src="../assets/uploads/<?= htmlspecialchars($settings['company_logo']) ?>" alt="Logo Actual" style="max-height: 80px; border: 1px solid #ddd; padding: 5px; margin-top: 5px; background-color: #f8f8f8;">
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="facturacion" class="content-pane is-hidden">
                <div class="dashboard-block">
                    <h2>Facturación</h2>
                    <label for="dia_vencimiento">Día del mes para el Vencimiento de Facturas</label>
                    <input type="number" name="dia_vencimiento" id="dia_vencimiento" min="1" max="28" value="<?= htmlspecialchars($settings['dia_vencimiento_factura'] ?? '10') ?>" required>
                    <small>Elige un número entre 1 y 28 para evitar problemas con Febrero.</small>
                </div>
            </div>
            
            <div id="email" class="content-pane is-hidden">
                <div class="dashboard-block">
                     <h2>Configuración de Email (SMTP)</h2>
                    <p>Estos datos son para que el sistema pueda enviar correos.</p>
                    <label for="email_from_address">Email Remitente</label>
                    <input type="email" name="email_from_address" id="email_from_address" value="<?= htmlspecialchars($settings['email_from_address'] ?? '') ?>">
                    <label for="email_from_name">Nombre Remitente</label>
                    <input type="text" name="email_from_name" id="email_from_name" value="<?= htmlspecialchars($settings['email_from_name'] ?? '') ?>">
                    <hr>
                    <label for="email_smtp_host">Servidor SMTP</label>
                    <input type="text" name="email_smtp_host" id="email_smtp_host" value="<?= htmlspecialchars($settings['email_smtp_host'] ?? '') ?>">
                    <label for="email_smtp_port">Puerto SMTP</label>
                    <input type="number" name="email_smtp_port" id="email_smtp_port" value="<?= htmlspecialchars($settings['email_smtp_port'] ?? '587') ?>">
                    <label for="email_smtp_secure">Seguridad SMTP</label>
                    <select name="email_smtp_secure" id="email_smtp_secure">
                        <option value="tls" <?= (($settings['email_smtp_secure'] ?? '') == 'tls') ? 'selected' : '' ?>>TLS</option>
                        <option value="ssl" <?= (($settings['email_smtp_secure'] ?? '') == 'ssl') ? 'selected' : '' ?>>SSL</option>
                        <option value="" <?= (($settings['email_smtp_secure'] ?? '') == '') ? 'selected' : '' ?>>Ninguna</option>
                    </select>
                    <label for="email_smtp_user">Usuario SMTP</label>
                    <input type="text" name="email_smtp_user" id="email_smtp_user" value="<?= htmlspecialchars($settings['email_smtp_user'] ?? '') ?>">
                    <label for="email_smtp_pass">Contraseña SMTP</label>
                    <input type="password" name="email_smtp_pass" id="email_smtp_pass" placeholder="Dejar en blanco para no cambiar">
                    <small>Nota: Si usas Gmail, necesitas una "Contraseña de aplicación".</small>
                </div>
            </div>

            <button type="submit" style="font-size: 1.1em; padding: 12px 25px; margin-top: 10px;">Guardar Configuración</button>
        </form>

        <div id="licencia" class="content-pane is-hidden">
             <div class="dashboard-block">
                <h2>Estado de la Licencia</h2>
                <?php if (isset($settings['license_status']) && $settings['license_status'] === 'licensed'): ?>
                    <p><strong>Estado:</strong> <span style="color:green; font-weight:bold;">Activado</span></p>
                    <p><strong>Tipo de Licencia:</strong> <?= htmlspecialchars($tipo_licencia) ?></p>
                    <p><strong>Clave de Licencia:</strong> <small><?= htmlspecialchars($settings['license_key']) ?></small></p>
                    <p><strong>Registrada a DNI:</strong> <?= htmlspecialchars($settings['license_holder_id']) ?></p>
                <?php else: ?>
                    <p><strong>Estado:</strong> <span style="color:red; font-weight:bold;">Sin Licencia</span></p>
                    <p>El sistema no está activado. Ve a la <a href="../activacion.php">página de activación</a>.</p>
                <?php endif; ?>
            </div>
        </div>

        <div id="usuarios" class="content-pane is-hidden">
            <div class="dashboard-block">
                <h2>Administración de Usuarios</h2>
                <p>Accede aquí para crear, editar o eliminar las cuentas de los administradores y personal.</p>
                <a href="users/" class="action-btn btn-primary" style="font-size: 1em; padding: 10px 20px; text-decoration:none;">Gestionar Usuarios</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const menuLinks = document.querySelectorAll('.settings-menu a');
    const contentPanes = document.querySelectorAll('.settings-content .content-pane');
    function showPane(hash) {
        let activeHash = hash;
        if (!activeHash || !document.getElementById(activeHash.substring(1))) {
            activeHash = menuLinks[0].hash;
        }
        contentPanes.forEach(pane => {
            if ('#' + pane.id === activeHash) pane.classList.remove('is-hidden');
            else pane.classList.add('is-hidden');
        });
        menuLinks.forEach(link => {
            if (link.hash === activeHash) link.classList.add('active');
            else link.classList.remove('active');
        });
    }
    menuLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const hash = this.hash;
            if(history.pushState) {
                history.pushState(null, null, hash);
            } else {
                location.hash = hash;
            }
            showPane(hash);
        });
    });
    showPane(window.location.hash);
});
</script>

<?php
$conexion->close();
include '../includes/footer.php';
?>