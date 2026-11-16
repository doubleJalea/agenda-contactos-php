<?php
// Datos de configuración de la BD
$host = 'localhost';
$dbname = 'agenda_contactos';
$username = 'root'; // Usuario por defecto en XAMPP
$password = ''; // Sin contraseña por defecto en XAMPP

try {
    // Configuración de la conexión usando PDO
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    // Instancia de PDO
    $pdo = new PDO($dsn, $username, $password, $opciones);
    
} catch (PDOException $e) {
    // Si hay error en la conexión, se detiene la ejecución y muestra el mensaje
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>