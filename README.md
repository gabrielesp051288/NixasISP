# Sistema de Gestión y Facturación para ISP v1.0

Este es un sistema de gestión web completo, construido con PHP y MySQL, diseñado para administrar las operaciones de un Proveedor de Servicios de Internet (ISP) de tamaño pequeño a mediano. La aplicación permite gestionar clientes, planes de servicios, facturación recurrente, pagos y más, a través de un panel de administración intuitivo.

## Características Principales

El sistema cuenta con un robusto conjunto de funcionalidades de nivel profesional:

### **Núcleo y Seguridad**
* **Panel de Administración:** Una interfaz central para controlar todo el sistema.
* **Login Seguro:** Autenticación de usuarios para acceder al panel.
* **Gestión de Roles:** Capacidad para tener diferentes tipos de usuarios (ej: Administrador).
* **Sistema de Licenciamiento:** El software requiere una licencia para funcionar, con una clave maestra para el desarrollador.
* **Asistente de Instalación:** Un proceso guiado para la configuración inicial en un nuevo servidor.

### **Gestión de Clientes**
* **CRUD Completo:** Creación, visualización, edición y archivado de clientes.
* **Dashboard de Cliente 360°:** Una vista detallada para cada cliente que centraliza sus servicios activos, historial de facturas, transacciones y notas internas.
* **Notas Internas (CRM):** Posibilidad de añadir comentarios privados en la ficha de cada cliente para un mejor seguimiento.
* **Gestión Masiva:**
    * **Importación por CSV:** Añade cientos de clientes de una sola vez, con asignación automática de su primer servicio.
    * **Archivado y Restauración Masiva:** Gestiona múltiples clientes a la vez mediante checkboxes.
* **Herramientas Avanzadas de Lista:**
    * Búsqueda inteligente por múltiples campos.
    * Filtros por estado (Activos, Archivados).
    * Ordenación de columnas (ascendente/descendente).
    * Paginación para manejar grandes volúmenes de datos.
* **Exportación a CSV:** Descarga la lista de clientes (respetando filtros y búsquedas) para análisis externo.

### **Facturación y Cobranzas**
* **Gestión de Planes:** CRUD completo para definir los planes de servicios, precios y ciclos de facturación.
* **Asignación de Servicios:** Vincula planes específicos a cada cliente, definiendo el precio y la fecha de activación.
* **Generación de Facturas:**
    * **Manual:** Genera una factura para un servicio específico con un solo clic.
    * **Automática:** Un script (Cron Job) se encarga de crear todas las facturas recurrentes y actualizar los estados de las vencidas diariamente.
* **Registro de Transacciones:** Registra los pagos recibidos para cada factura, con detalles como fecha, monto y método de pago.
* **Generación de PDF:** Descarga facturas en un formato PDF profesional con los datos y logo de la empresa.

### **Análisis y Reportes**
* **Dashboard Principal:** Muestra tarjetas con indicadores clave (KPIs) en tiempo real (Ingresos, Facturado, Clientes Activos, etc.) y un monitor de tareas automáticas.
* **Módulo de Reportes:**
    * Gráficos visuales para analizar la evolución de ingresos y el crecimiento de clientes.
    * Gráfico de pastel para ver los planes más populares.
    * Filtros por rango de fechas para un análisis detallado.

### **Herramientas de Administrador**
* **Panel de Configuración:** Interfaz con pestañas para gestionar reglas de negocio (día de vencimiento de facturas), datos de la empresa (logo, CUIT, etc.), configuración de email (SMTP) y estado de la licencia.
* **Modo Desarrollo:** Una opción de configuración para mostrar herramientas peligrosas, como el reseteo de la base de datos, solo en entornos de prueba.

## Tecnologías Utilizadas
* **Backend:** PHP
* **Base de Datos:** MySQL / MariaDB
* **Frontend:** HTML, CSS, JavaScript (puro)
* **Librerías Externas:**
    * **FPDF:** Para la generación de documentos PDF.
    * **PHPMailer:** Para el futuro envío de emails vía SMTP.

## Guía de Instalación

1.  **Requisitos:** Un servidor web compatible con PHP y MySQL (ej: XAMPP, LAMPP, etc.).
2.  **Base de Datos:** Crea una base de datos vacía en tu gestor (phpMyAdmin, etc.).
3.  **Archivos:** Clona este repositorio o descarga el ZIP y colócalo en tu directorio web (`htdocs`, `www`, etc.).
4.  **Ejecutar el Instalador:**
    * Abre tu navegador y navega a la carpeta del proyecto (ej: `http://localhost/gestorisp/`).
    * Serás redirigido automáticamente al asistente de instalación (`/install/`).
    * **Paso 1:** Introduce las credenciales de la base de datos que creaste y haz clic en "Continuar". El sistema creará los archivos `config.php` y `conexion.php` por ti.
    * **Paso 2:** El instalador creará todas las tablas. A continuación, crea tu cuenta de administrador principal.
    * **Paso 3:** Finalmente, activa el sistema usando la Licencia generada para un DNI específico.
5.  **Paso Post-Instalación (¡MUY IMPORTANTE!):**
    * Una vez finalizada la instalación, **ELIMINA o RENOMBRA la carpeta `/install`** de tu servidor para prevenir que sea ejecutada de nuevo por motivos de seguridad.

## Configuración Post-Instalación
1.  Inicia sesión con tu nueva cuenta de administrador.
2.  Ve a `Configuración` -> `Perfil de la Empresa` y rellena todos tus datos.
3.  Ve a `Configuración` -> `Facturación` y ajusta el día de vencimiento si lo deseas.
4.  Ve a `Planes de Servicios` y crea los planes que vas a ofrecer.
5.  ¡Listo para empezar a añadir clientes!
