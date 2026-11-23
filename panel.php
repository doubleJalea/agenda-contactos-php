<?php
ini_set('session.use_only_cookies', 1);
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();
require 'conexion.php';

// 1. Verificación de sesión activa
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$id_usuario = $_SESSION['usuario_id'];
$nombre_usuario = $_SESSION['nombre_usuario'];

// Obtener correo del usuario logueado
$stmt = $pdo->prepare("SELECT correo FROM usuarios WHERE id = :id");
$stmt->execute(['id' => $id_usuario]);
$correo_usuario = $stmt->fetchColumn();

// Cargar catálogo de categorías
$categorias = $pdo->query("SELECT id, nombre FROM categorias ORDER BY id ASC")->fetchAll();

$mensaje = '';
$tipo_mensaje = 'exito';

if (isset($_GET['msg'])) {
    $mensajes_ok = [
        'agregado' => 'Contacto agregado correctamente.',
        'actualizado' => 'Contacto actualizado correctamente.',
        'eliminado' => 'Contacto eliminado correctamente.'
    ];
    if (isset($mensajes_ok[$_GET['msg']])) {
        $mensaje = $mensajes_ok[$_GET['msg']];
    }
}

// 2. Procesar peticiones POST (Eliminar/Agregar/Editar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? 'guardar';

    if ($accion === 'eliminar') {
        $id_eliminar = filter_input(INPUT_POST, 'id_eliminar', FILTER_VALIDATE_INT);
        if ($id_eliminar) {
            $stmt = $pdo->prepare("DELETE FROM contactos WHERE id = :id AND id_usuario = :id_usuario");
            $stmt->execute(['id' => $id_eliminar, 'id_usuario' => $id_usuario]);
        }
        header("Location: panel.php?msg=eliminado");
        exit;
    }

    // Guardar o actualizar contacto
    $id_contacto = $_POST['id_contacto'] ?? '';
    $nombre = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $id_categoria = filter_input(INPUT_POST, 'id_categoria', FILTER_VALIDATE_INT);
    $favorito = isset($_POST['favorito']) ? 1 : 0;

    // Validaciones dentro del servidor
    if (empty($nombre) || empty($telefono) || empty($correo) || empty($ciudad) || !$id_categoria) {
        $mensaje = "Error: Todos los campos son obligatorios.";
        $tipo_mensaje = 'error';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "Error: El formato del correo electrónico no es válido.";
        $tipo_mensaje = 'error';
    } else {
        if (empty($id_contacto)) {
            $stmt = $pdo->prepare("INSERT INTO contactos (id_usuario, id_categoria, nombre, telefono, correo, ciudad, favorito) VALUES (:id_usuario, :id_categoria, :nombre, :telefono, :correo, :ciudad, :favorito)");
            $stmt->execute([
                'id_usuario' => $id_usuario,
                'id_categoria' => $id_categoria,
                'nombre' => $nombre,
                'telefono' => $telefono,
                'correo' => $correo,
                'ciudad' => $ciudad,
                'favorito' => $favorito
            ]);
            header("Location: panel.php?msg=agregado");
            exit;
        } else {
            $stmt = $pdo->prepare("UPDATE contactos SET id_categoria = :id_categoria, nombre = :nombre, telefono = :telefono, correo = :correo, ciudad = :ciudad, favorito = :favorito WHERE id = :id AND id_usuario = :id_usuario");
            $stmt->execute([
                'id_categoria' => $id_categoria,
                'nombre' => $nombre,
                'telefono' => $telefono,
                'correo' => $correo,
                'ciudad' => $ciudad,
                'favorito' => $favorito,
                'id' => $id_contacto,
                'id_usuario' => $id_usuario
            ]);
            header("Location: panel.php?msg=actualizado");
            exit;
        }
    }
}

// 3. Cargar contacto para editar
$contacto_editar = null;
if (isset($_GET['editar'])) {
    $id_editar = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);
    if ($id_editar) {
        $stmt = $pdo->prepare("SELECT * FROM contactos WHERE id = :id AND id_usuario = :id_usuario");
        $stmt->execute(['id' => $id_editar, 'id_usuario' => $id_usuario]);
        $contacto_editar = $stmt->fetch();
    }
}

// 4. Búsqueda, ordenamiento y filtro de favoritos
$buscar = trim($_GET['buscar'] ?? '');
$orden = $_GET['orden'] ?? 'fecha';
$solo_favoritos = isset($_GET['solo_favoritos']) && $_GET['solo_favoritos'] === '1';

$orden_sql = match ($orden) {
    'nombre' => 'c.nombre ASC',
    'ciudad' => 'c.ciudad ASC',
    default  => 'c.fecha_creacion DESC',
};

$sql = "SELECT c.*, cat.nombre AS categoria_nombre
        FROM contactos c
        INNER JOIN categorias cat ON c.id_categoria = cat.id
        WHERE c.id_usuario = :id_usuario";
$params = ['id_usuario' => $id_usuario];

if ($buscar !== '') {
    $sql .= " AND (c.nombre LIKE :buscar1 OR c.correo LIKE :buscar2 OR c.ciudad LIKE :buscar3)";
    $termino = "%$buscar%";
    $params['buscar1'] = $termino;
    $params['buscar2'] = $termino;
    $params['buscar3'] = $termino;
}

if ($solo_favoritos) {
    $sql .= " AND c.favorito = 1";
}

$sql .= " ORDER BY $orden_sql";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$contactos = $stmt->fetchAll();

// Valores actuales para el formulario (retiene datos si hubo error en POST)
$val_id = $_POST['id_contacto'] ?? $contacto_editar['id'] ?? '';
$val_nombre = $_POST['nombre'] ?? $contacto_editar['nombre'] ?? '';
$val_telefono = $_POST['telefono'] ?? $contacto_editar['telefono'] ?? '';
$val_correo = $_POST['correo'] ?? $contacto_editar['correo'] ?? '';
$val_ciudad = $_POST['ciudad'] ?? $contacto_editar['ciudad'] ?? '';
$val_categoria = $_POST['id_categoria'] ?? $contacto_editar['id_categoria'] ?? '';
$val_favorito = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? isset($_POST['favorito'])
    : !empty($contacto_editar['favorito']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Agenda Personal</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; margin: 0; padding: 20px; }
        .header { background: #0056b3; color: white; padding: 15px 20px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
        .header h1 { margin: 0; font-size: 20px; }
        .btn-rojo { background-color: #dc3545; color: white; text-decoration: none; padding: 8px 12px; border-radius: 4px; }
        .contenedor { display: flex; gap: 20px; flex-wrap: wrap; align-items: flex-start; }
        .caja { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .formulario { flex: 1; min-width: 280px; }
        .lista { flex: 2.3; min-width: 340px; overflow-x: auto; }
        input[type="text"], input[type="email"], select { width: 100%; padding: 8px; margin: 6px 0 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .check-wrap { display: flex; align-items: center; gap: 8px; margin: 8px 0 14px; font-size: 14px; }
        .btn-guardar { width: 100%; padding: 10px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        .btn-guardar:hover { background-color: #218838; }
        .filtros { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 15px; background: #f8f9fa; padding: 12px; border-radius: 6px; border: 1px solid #e2e6ea; }
        .filtros input[type="text"] { flex: 2; min-width: 180px; margin: 0; }
        .filtros select { flex: 1; min-width: 150px; margin: 0; }
        .filtros label { display: flex; align-items: center; gap: 5px; font-size: 14px; white-space: nowrap; }
        .btn-filtrar { padding: 8px 14px; background-color: #0056b3; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .btn-limpiar { padding: 8px 12px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 4px; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 12px; background: #e9ecef; color: #333; }
        .fav-si { color: #d39e00; font-weight: bold; }
        .fav-no { color: #999; }
        .acciones { display: flex; gap: 6px; align-items: center; }
        .btn-editar { padding: 5px 10px; text-decoration: none; background-color: #ffc107; color: black; border-radius: 3px; font-size: 13px; }
        .btn-eliminar { padding: 5px 10px; background-color: #dc3545; color: white; border: none; border-radius: 3px; font-size: 13px; cursor: pointer; }
        .exito { color: green; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
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
        <!-- Formulario Crear/Editar -->
        <div class="caja formulario">
            <h3><?php echo $val_id ? 'Editar Contacto' : 'Nuevo Contacto'; ?></h3>
            <?php if ($mensaje): ?>
                <p class="<?php echo $tipo_mensaje; ?>"><?php echo htmlspecialchars($mensaje); ?></p>
            <?php endif; ?>
            <form method="POST" action="panel.php">
                <input type="hidden" name="accion" value="guardar">
                <input type="hidden" name="id_contacto" value="<?php echo htmlspecialchars($val_id); ?>">

                <input type="text" name="nombre" placeholder="Nombre completo" value="<?php echo htmlspecialchars($val_nombre); ?>" required>
                <input type="text" name="telefono" placeholder="Teléfono" value="<?php echo htmlspecialchars($val_telefono); ?>" required>
                <input type="email" name="correo" placeholder="Correo electrónico" value="<?php echo htmlspecialchars($val_correo); ?>" required>
                <input type="text" name="ciudad" placeholder="Ciudad de residencia" value="<?php echo htmlspecialchars($val_ciudad); ?>" required>

                <select name="id_categoria" required>
                    <option value="">-- Selecciona una categoría --</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($val_categoria == $cat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label class="check-wrap">
                    <input type="checkbox" name="favorito" value="1" <?php echo $val_favorito ? 'checked' : ''; ?>>
                    Marcar como contacto favorito
                </label>

                <button type="submit" class="btn-guardar"><?php echo $val_id ? 'Actualizar Contacto' : 'Guardar Contacto'; ?></button>
                <?php if ($val_id): ?>
                    <a href="panel.php" style="display:block; text-align:center; margin-top:10px; color:gray;">Cancelar edición</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Lista de Contactos + Filtros -->
        <div class="caja lista">
            <h3>Tus Contactos</h3>

            <form method="GET" action="panel.php" class="filtros">
                <input type="text" name="buscar" placeholder="Buscar por nombre, correo o ciudad..." value="<?php echo htmlspecialchars($buscar); ?>">
                <select name="orden">
                    <option value="fecha" <?php echo $orden === 'fecha' ? 'selected' : ''; ?>>Ordenar: Más recientes</option>
                    <option value="nombre" <?php echo $orden === 'nombre' ? 'selected' : ''; ?>>Ordenar: Nombre (A-Z)</option>
                    <option value="ciudad" <?php echo $orden === 'ciudad' ? 'selected' : ''; ?>>Ordenar: Ciudad (A-Z)</option>
                </select>
                <label>
                    <input type="checkbox" name="solo_favoritos" value="1" <?php echo $solo_favoritos ? 'checked' : ''; ?>>
                    Solo favoritos
                </label>
                <button type="submit" class="btn-filtrar">Aplicar</button>
                <?php if ($buscar !== '' || $solo_favoritos || $orden !== 'fecha'): ?>
                    <a href="panel.php" class="btn-limpiar">Limpiar</a>
                <?php endif; ?>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>Ciudad</th>
                        <th>Categoría</th>
                        <th>Favorito</th>
                        <th>Fecha</th>
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
                                <td><span class="badge"><?php echo htmlspecialchars($c['categoria_nombre']); ?></span></td>
                                <td>
                                    <?php if ($c['favorito']): ?>
                                        <span class="fav-si">★ Sí</span>
                                    <?php else: ?>
                                        <span class="fav-no">No</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($c['fecha_creacion'])); ?></td>
                                <td class="acciones">
                                    <a href="panel.php?editar=<?php echo $c['id']; ?>" class="btn-editar">Editar</a>
                                    <form method="POST" action="panel.php" style="margin:0;" onsubmit="return confirm('¿Seguro que deseas eliminar este contacto?');">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id_eliminar" value="<?php echo $c['id']; ?>">
                                        <button type="submit" class="btn-eliminar">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center;">No se encontraron contactos.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>