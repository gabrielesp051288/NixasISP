<?php
// /install/procesar_instalacion.php

// Verificamos que se haya accedido a través del formulario
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit("Acceso no permitido.");
}

// Recibimos los datos de la base de datos del formulario
$db_host = $_POST['db_host'];
$db_name = $_POST['db_name'];
$db_user = $_POST['db_user'];
$db_pass = $_POST['db_pass'];

// 1. Probamos la conexión con los datos proporcionados
// El @ suprime los warnings de PHP si la conexión falla, lo manejaremos nosotros.
$test_conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($test_conn->connect_error) {
    // Si falla, volvemos al formulario con un mensaje de error claro.
    $error_msg = urlencode("No se pudo conectar a la base de datos: " . $test_conn->connect_error);
    header("Location: index.php?error=" . $error_msg);
    exit();
}
$test_conn->close();

// 2. Si la conexión es exitosa, creamos los archivos de configuración
// Detectar la BASE_PATH dinámicamente
$base_path = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\') . '/';

// Contenido para config.php
$config_content = "<?php
// /config.php (Generado automáticamente por el instalador)

// Por defecto, se instala en modo producción (sin herramientas de desarrollo visibles).
define('DEV_MODE', false);

define('BASE_PATH', '$base_path');
define('MASTER_LICENSE', 'MASTER-TEST-1234-DEMO');
define('LICENSE_SALT', 'EsteEsUnSecretoParaElHashDeLicenciasISP');
?>";

// ========= CONTENIDO MEJORADO Y FINAL PARA conexion.php =========
$conexion_content = "<?php
// /conexion.php (Generado automáticamente por el instalador)

if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

// Se establece la zona horaria para todas las funciones de fecha de PHP
date_default_timezone_set('America/Argentina/Buenos_Aires');

function getConexion() {
    static \$conexion = null;
    if (\$conexion === null) {
        \$servidor = '$db_host';
        \$usuario = '$db_user';
        \$password = '$db_pass';
        \$base_de_datos = '$db_name';
        @\$conexion = new mysqli(\$servidor, \$usuario, \$password, \$base_de_datos);
        if (\$conexion->connect_error) {
            return false;
        }
        
        // Se establece la zona horaria también para la conexión a la base de datos
        \$conexion->query(\"SET time_zone = '-03:00'\");

        \$conexion->set_charset('utf8mb4');
    }
    return \$conexion;
}
?>";
// ========= FIN DEL CONTENIDO MEJORADO =========

// Escribimos los archivos en la carpeta raíz (un nivel arriba de /install)
$root_path = __DIR__ . '/../';
file_put_contents($root_path . 'config.php', $config_content);
file_put_contents($root_path . 'conexion.php', $conexion_content);

// 3. Incluimos los nuevos archivos y creamos todas las tablas
include $root_path . 'conexion.php';
$conexion = getConexion();

// VARIABLE $sql CON TODAS LAS SENTENCIAS PARA CREAR LA BASE DE DATOS
$sql = "
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT, `nombre_completo` varchar(255) NOT NULL, `username` varchar(100) NOT NULL, `email` varchar(255) NOT NULL, `password` varchar(255) NOT NULL, `rol` varchar(50) NOT NULL, `created_at` timestamp NOT NULL DEFAULT current_timestamp(), `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(), PRIMARY KEY (`id`), UNIQUE KEY `username` (`username`), UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `planes` (
  `id` int(11) NOT NULL AUTO_INCREMENT, `nombre` varchar(255) NOT NULL, `descripcion` text DEFAULT NULL, `precio` decimal(10,2) NOT NULL, `ciclo_facturacion` varchar(50) NOT NULL, `created_at` timestamp NOT NULL DEFAULT current_timestamp(), `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(), PRIMARY KEY (`id`), UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT, `numero_de_cliente` varchar(255) NOT NULL, `nombre_completo` varchar(255) NOT NULL, `email` varchar(255) DEFAULT NULL, `telefono` varchar(100) DEFAULT NULL, `address` varchar(255) DEFAULT NULL, `ciudad` varchar(100) DEFAULT NULL, `provincia` varchar(100) DEFAULT NULL, `codigo_postal` varchar(50) DEFAULT NULL, `pais` varchar(100) DEFAULT NULL, `fecha_alta` date DEFAULT NULL, `estado` varchar(50) NOT NULL DEFAULT 'activo', `created_at` timestamp NOT NULL DEFAULT current_timestamp(), `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(), PRIMARY KEY (`id`), UNIQUE KEY `numero_de_cliente` (`numero_de_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `cliente_servicios` (
  `id` int(11) NOT NULL AUTO_INCREMENT, `cliente_id` int(11) NOT NULL, `plan_id` int(11) NOT NULL, `fecha_activacion` date NOT NULL, `precio_pactado` decimal(10,2) NOT NULL, `estado` varchar(50) NOT NULL DEFAULT 'activo', `created_at` timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY (`id`), KEY `cliente_id` (`cliente_id`), KEY `plan_id` (`plan_id`),
  CONSTRAINT `cs_cliente_fk` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cs_plan_fk` FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `facturas` (
  `id` int(11) NOT NULL AUTO_INCREMENT, `cliente_id` int(11) NOT NULL, `cliente_servicio_id` int(11) DEFAULT NULL, `fecha_emision` date NOT NULL, `fecha_vencimiento` date NOT NULL, `total` decimal(10,2) NOT NULL, `estado` varchar(50) NOT NULL DEFAULT 'pendiente', `created_at` timestamp NOT NULL DEFAULT current_timestamp(), `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(), PRIMARY KEY (`id`), KEY `cliente_id` (`cliente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `facturas_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT, `factura_id` int(11) NOT NULL, `plan_id` int(11) DEFAULT NULL, `descripcion` varchar(255) NOT NULL, `cantidad` int(11) NOT NULL DEFAULT 1, `precio_unitario` decimal(10,2) NOT NULL, `subtotal` decimal(10,2) NOT NULL, PRIMARY KEY (`id`), KEY `factura_id` (`factura_id`), KEY `plan_id` (`plan_id`),
  CONSTRAINT `fi_factura_fk` FOREIGN KEY (`factura_id`) REFERENCES `facturas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fi_plan_fk` FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `transacciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT, `cliente_id` int(11) NOT NULL, `factura_id` int(11) DEFAULT NULL, `fecha` datetime NOT NULL, `descripcion` varchar(255) NOT NULL, `monto` decimal(10,2) NOT NULL, `metodo_pago` varchar(100) DEFAULT NULL, `created_at` timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY (`id`), KEY `cliente_id` (`cliente_id`), KEY `factura_id` (`factura_id`),
  CONSTRAINT `t_cliente_fk` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `t_factura_fk` FOREIGN KEY (`factura_id`) REFERENCES `facturas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `cliente_notas` (
  `id` int(11) NOT NULL AUTO_INCREMENT, `cliente_id` int(11) NOT NULL, `usuario_id` int(11) NOT NULL, `nota` text NOT NULL, `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY (`id`), KEY `cliente_id` (`cliente_id`), KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `cn_cliente_fk` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cn_usuario_fk` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `configuracion` (
  `id` int(11) NOT NULL AUTO_INCREMENT, `clave` varchar(100) NOT NULL, `valor` varchar(255) NOT NULL, `descripcion` text DEFAULT NULL, `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(), PRIMARY KEY (`id`), UNIQUE KEY `clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `cron_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT, `script_name` varchar(255) NOT NULL, `fecha_ejecucion` datetime NOT NULL, `estado` varchar(50) NOT NULL, `resumen` text DEFAULT NULL, `created_at` timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `configuracion` (`clave`, `valor`, `descripcion`) VALUES
('company_address', '', 'La dirección fiscal de tu empresa.'),
('company_logo', '', 'La ruta al archivo del logo de la empresa.'),
('company_name', 'Nombre de tu Empresa', 'El nombre de tu ISP que aparecerá en las facturas.'),
('company_phone', '', 'El teléfono de contacto de tu empresa.'),
('company_tax_id', '', 'El CUIT o identificador fiscal de tu empresa.'),
('dia_vencimiento_factura', '10', 'Día del mes en que vencen las facturas (ej: 10).'),
('email_from_address', 'no-reply@tuempresa.com', 'La dirección de email desde la que se enviarán los correos.'),
('email_from_name', 'Mi Empresa ISP', 'El nombre que aparecerá como remitente.'),
('email_smtp_host', 'smtp.example.com', 'El servidor SMTP. Ej: smtp.gmail.com'),
('email_smtp_pass', 'tu_password_smtp', 'La contraseña para el usuario SMTP.'),
('email_smtp_port', '587', 'El puerto SMTP. Comúnmente 587 (TLS) o 465 (SSL).'),
('email_smtp_secure', 'tls', 'El tipo de seguridad: tls o ssl.'),
('email_smtp_user', 'tu_usuario_smtp', 'El usuario para autenticarse en el servidor SMTP.'),
('license_holder_id', '', 'El DNI del titular de la licencia.'),
('license_key', '', 'La clave de licencia introducida por el usuario.'),
('license_status', 'unlicensed', 'El estado de la licencia del sistema. Puede ser unlicensed o licensed.');
";

// mysqli_multi_query nos permite ejecutar múltiples sentencias SQL a la vez
if ($conexion->multi_query($sql)) {
    // Es importante limpiar los resultados de cada query
    while ($conexion->next_result()) {
        if ($res = $conexion->store_result()) {
            $res->free();
        }
    }
    // Si todo va bien, redirigimos al siguiente paso: crear el admin.
    header('Location: crear_admin.php');
    exit();
} else {
    $error_msg = urlencode("Las credenciales de la BD son correctas, pero falló la creación de las tablas: " . $conexion->error);
    header("Location: index.php?error=" . $error_msg);
    exit();
}
?>