<?php
require 'conexion.php';
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $password = $_POST['password'];

    // Validación en el servidor (Requisito de rúbrica)
    if (empty($nombre) || empty($correo) || empty($password)) {
        $mensaje = "Error: Todos los campos son obligatorios.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "Error: El formato del correo electrónico no es válido.";
    } else {
        // 1. Valida que el correo no exista previamente
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo = :correo");
        $stmt->execute(['correo' => $correo]);
        
        if ($stmt->fetch()) {
            $mensaje = "Error: El correo electrónico ya está registrado.";
        } else {
            // 2. Encripta la contraseña
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // 3. Guarda el nuevo usuario
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, correo, password) VALUES (:nombre, :correo, :password)");
            if ($stmt->execute(['nombre' => $nombre, 'correo' => $correo, 'password' => $password_hash])) {
                header("Location: index.php?exito=1");
                exit;
            } else {
                $mensaje = "Ocurrió un error al registrar el usuario.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Usuario</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .contenedor { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { text-align: center; color: #333; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background-color: #0056b3; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #004494; }
        .error { color: red; text-align: center; font-size: 14px; font-weight: bold; }
        .link { display: block; text-align: center; margin-top: 15px; text-decoration: none; color: #0056b3; }
    </style>
</head>
<body>
    <div class="contenedor">
        <h2>Crear Cuenta</h2>
        <?php if ($mensaje): ?>
            <p class="error"><?php echo $mensaje; ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="text" name="nombre" placeholder="Nombre completo" value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>" required>
            <input type="email" name="correo" placeholder="Correo electrónico" value="<?php echo htmlspecialchars($_POST['correo'] ?? ''); ?>" required>
            <input type="password" name="password" placeholder="Contraseña" value="<?php echo htmlspecialchars($_POST['password'] ?? ''); ?>"    required>
            <button type="submit">Registrarse</button>
        </form>
        <a href="index.php" class="link">¿Ya tienes cuenta? Inicia sesión aquí</a>
    </div>
</body>
</html>