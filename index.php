<?php
ini_set('session.use_only_cookies', 1);
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();
require 'conexion.php';
$error = '';
$exito = '';

// De aquí en adelante, el código maneja la lógica de inicio de sesión y redirección según el estado de la sesión del usuario.

// Si el usuario ya tiene sesión activa, lo mandamos directo al panel
if (isset($_SESSION['usuario_id'])) {
    header("Location: panel.php");
    exit;
}

// Mensaje si viene redirigido desde un registro exitoso
if (isset($_GET['exito'])) {
    $exito = "Registro exitoso. Ahora puedes iniciar sesión.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $correo = trim($_POST['correo']);
    $password = $_POST['password'];

    // Buscar al usuario por su correo
    $stmt = $pdo->prepare("SELECT id, nombre, password FROM usuarios WHERE correo = :correo");
    $stmt->execute(['correo' => $correo]);
    $usuario = $stmt->fetch();

    // Validar contraseña 
    if ($usuario && password_verify($password, $usuario['password'])) {
        session_regenerate_id(true);
        // Crear las variables de sesión
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['nombre_usuario'] = $usuario['nombre'];
        
        // Redirigir al panel principal
        header("Location: panel.php");
        exit;
    } else {
        $error = "Correo o contraseña incorrectos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .contenedor { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { text-align: center; color: #333; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #218838; }
        .error { color: red; text-align: center; font-size: 14px; }
        .exito { color: green; text-align: center; font-size: 14px; }
        .link { display: block; text-align: center; margin-top: 15px; text-decoration: none; color: #0056b3; }
    </style>
</head>
<body>
    <div class="contenedor">
        <h2>Iniciar Sesión</h2>
        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <?php if ($exito): ?>
            <p class="exito"><?php echo $exito; ?></p>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="email" name="correo" placeholder="Correo electrónico" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">Entrar</button>
        </form>
        <a href="registro.php" class="link">¿No tienes cuenta? Regístrate aquí</a>
    </div>
</body>
</html>