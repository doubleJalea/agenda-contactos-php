<?php
session_start();
require 'conexion.php';

// 1. Verificación de sesión activa
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$id_usuario = $_SESSION['usuario_id'];
$nombre_usuario = $_SESSION['nombre_usuario'];

// Obtener el correo del usuario logueado
$stmt = $pdo->prepare("SELECT correo FROM usuarios WHERE id = :id");
$stmt->execute(['id' => $id_usuario]);
$correo_usuario = $stmt->fetchColumn();

$mensaje = '';

// 2. Menú lógico para eliminar contacto
if (isset($_GET['eliminar'])) {
    $id_eliminar = $_GET['eliminar'];
    // Seguridad: Se borra únicamente si el contacto pertenece al usuario logueado
    $stmt = $pdo->prepare("DELETE FROM contactos WHERE id = :id AND id_usuario = :id_usuario");
    if ($stmt->execute(['id' => $id_eliminar, 'id_usuario' => $id_usuario])) {
        header("Location: panel.php");
        exit;
    }
}

// 3. Lógica para agregar o editar contacto
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_contacto = $_POST['id_contacto'];
    $nombre = trim($_POST['nombre']);
    $telefono = trim($_POST['telefono']);
    $correo = trim($_POST['correo']);
    $ciudad = trim($_POST['ciudad']);

    // APARTADO DE VALIDACIÓN EN EL SERVIDOR
    if (empty($nombre) || empty($telefono) || empty($correo) || empty($ciudad)) {
        $mensaje = "<span style='color:red;'>Error: Todos los campos son obligatorios.</span>";
    } else {
        if (empty($id_contacto)) {
            // AGREGAR
            $stmt = $pdo->prepare("INSERT INTO contactos (id_usuario, nombre, telefono, correo, ciudad) VALUES (:id_usuario, :nombre, :telefono, :correo, :ciudad)");
            if ($stmt->execute(['id_usuario' => $id_usuario, 'nombre' => $nombre, 'telefono' => $telefono, 'correo' => $correo, 'ciudad' => $ciudad])) {
                $mensaje = "Contacto agregado correctamente.";
            }
        } else {
            // ACTUALIZAR (Seguridad: Se actualiza solo si pertenece al usuario)
            $stmt = $pdo->prepare("UPDATE contactos SET nombre = :nombre, telefono = :telefono, correo = :correo, ciudad = :ciudad WHERE id = :id AND id_usuario = :id_usuario");
            if ($stmt->execute(['nombre' => $nombre, 'telefono' => $telefono, 'correo' => $correo, 'ciudad' => $ciudad, 'id' => $id_contacto, 'id_usuario' => $id_usuario])) {
                $mensaje = "Contacto actualizado correctamente.";
            }
        }
    }
}

// Si se hizo clic en Editar, cargamos los datos en el formulario
$contacto_editar = null;
if (isset($_GET['editar'])) {
    $id_editar = $_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM contactos WHERE id = :id AND id_usuario = :id_usuario");
    $stmt->execute(['id' => $id_editar, 'id_usuario' => $id_usuario]);
    $contacto_editar = $stmt->fetch();
}

// 4. Listar Contactos
$stmt = $pdo->prepare("SELECT * FROM contactos WHERE id_usuario = :id_usuario");
$stmt->execute(['id_usuario' => $id_usuario]);
$contactos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Agenda Personal</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; margin: 0; padding: 20px; }
        .header { background: #0056b3; color: white; padding: 15px 20px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 20px; }
        .btn-rojo { background-color: #dc3545; color: white; text-decoration: none; padding: 8px 12px; border-radius: 4px; }
        .contenedor { display: flex; gap: 20px; }
        .caja { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .formulario { flex: 1; }
        .lista { flex: 2; }
        input { width: 100%; padding: 8px; margin: 8px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .acciones a { padding: 5px 10px; text-decoration: none; color: white; border-radius: 3px; font-size: 13px; }
        .btn-editar { background-color: #ffc107; color: black !important; }
        .btn-eliminar { background-color: #dc3545; }
        .exito { color: green; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <div>
            <h1>Bienvenido, <?php echo htmlspecialchars($nombre_usuario); ?></h1>
            <small><?php echo htmlspecialchars($correo_usuario); ?></small>
        </div>
        <a href="logout.php" class="btn-rojo">Cerrar Sesión</a>
    </div>

    <div class="contenedor">
        <!-- Formulario -->
        <div class="caja formulario">
            <h3><?php echo $contacto_editar ? 'Editar Contacto' : 'Nuevo Contacto'; ?></h3>
            <?php if ($mensaje): ?>
                <p class="exito"><?php echo $mensaje; ?></p>
            <?php endif; ?>
            <form method="POST" action="panel.php">
                <input type="hidden" name="id_contacto" value="<?php echo $contacto_editar['id'] ?? ''; ?>">
                <input type="text" name="nombre" placeholder="Nombre completo" value="<?php echo htmlspecialchars($_POST['nombre'] ?? $contacto_editar['nombre'] ?? ''); ?>" required>
                <input type="text" name="telefono" placeholder="Teléfono" value="<?php echo htmlspecialchars($_POST['telefono'] ?? $contacto_editar['telefono'] ?? ''); ?>" required>
                <input type="email" name="correo" placeholder="Correo electrónico" value="<?php echo htmlspecialchars($_POST['correo'] ?? $contacto_editar['correo'] ?? ''); ?>" required>
                <input type="text" name="ciudad" placeholder="Ciudad de residencia" value="<?php echo htmlspecialchars($_POST['ciudad'] ?? $contacto_editar['ciudad'] ?? ''); ?>" required>
                <button type="submit"><?php echo $contacto_editar ? 'Actualizar Guardado' : 'Guardar Contacto'; ?></button>
                <?php if ($contacto_editar): ?>
                    <a href="panel.php" style="display:block; text-align:center; margin-top:10px; color:gray;">Cancelar edición</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tabla de Contactos -->
        <div class="caja lista">
            <h3>Tus Contactos</h3>
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>Ciudad</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($contactos) > 0): ?>
                        <?php foreach ($contactos as $c): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($c['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($c['telefono']); ?></td>
                                <td><?php echo htmlspecialchars($c['correo']); ?></td>
                                <td><?php echo htmlspecialchars($c['ciudad']); ?></td>
                                <td class="acciones">
                                    <a href="panel.php?editar=<?php echo $c['id']; ?>" class="btn-editar">Editar</a>
                                    <a href="panel.php?eliminar=<?php echo $c['id']; ?>" class="btn-eliminar" onclick="return confirm('¿Seguro que deseas eliminar este contacto?');">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">No tienes contactos registrados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>