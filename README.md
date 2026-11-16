# Agenda Personal de Contactos con Autenticación en PHP

## 1. Descripción del proyecto
Esta aplicación web es un sistema de gestión de contactos personales que implementa un mecanismo de autenticación seguro. Permite a los usuarios registrarse, iniciar sesión, y administrar (crear, leer, actualizar y eliminar) su propia agenda de contactos. El sistema garantiza la privacidad de la información restringiendo el acceso únicamente a usuarios autenticados y asegurando que cada usuario solo pueda visualizar y gestionar exclusivamente sus propios registros, aplicando buenas prácticas de seguridad (como el hash de contraseñas y validación de sesiones).

## 2. Tecnologías utilizadas
* **Frontend:** HTML5 y CSS3 (diseño responsivo integrado).
* **Backend:** PHP.
* **Base de Datos:** MySQL
* **Conexión a BD:** PDO (PHP Data Objects) para mayor seguridad contra inyecciones SQL.
* **Seguridad:** password_hash() y password_verify() nativos de PHP.
* **Entorno de desarrollo:** Visual Studio Code y XAMPP (Apache + MySQL).

## 3. Requisitos para su ejecución
Para ejecutar este proyecto en un entorno local, se requiere:
* Un servidor web local como **XAMPP**
* **PHP** en su versión 7.4 o superior
* Sistema gestor de bases de datos **MySQL**
* Un navegador web moderno (Google Chrome, Mozilla Firefox, Edge, etc.).

## 4. Pasos para instalar la aplicación
Sigue estos pasos para desplegar la aplicación en tu entorno local:

1. **Clonar o descargar el proyecto:** 
   Descarga los archivos del repositorio y colócalos dentro de la carpeta raíz de tu servidor web (en el caso de XAMPP, la ruta predeterminada es `C:\xampp\htdocs\agenda_contactos`).

2. **Iniciar los servicios:**
   Abre el panel de control de tu servidor (en XAMPP) e inicia los servicios de **Apache** y **MySQL**.

3. **Configurar la base de datos:**
   * Abre el navegador y dirígete a `http://localhost/phpmyadmin`.
   * Ve a la pestaña **SQL**, copia el contenido del archivo `agenda.sql` incluido en el proyecto, pégalo en la caja de texto y haz clic en **Continuar**. Esto creará automáticamente la base de datos `agenda_contactos` y las tablas `usuarios` y `contactos`.

4. **Verificar la conexión:**
   Si tu servidor local tiene una configuración de credenciales distinta a la predeterminada de XAMPP (usuario `root` y sin contraseña), abre el archivo `conexion.php` y actualiza las variables `$username` y `$password`.

5. **Ejecutar la aplicación:**
   Abre una nueva pestaña en tu navegador web y escribe la siguiente URL:
   `http://localhost/agenda_contactos/`
   
