<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/*
|--------------------------------------------------------------------------
| CONEXIÓN CENTRALIZADA
|--------------------------------------------------------------------------
*/
require_once 'conexion.php';

/*
|--------------------------------------------------------------------------
| OBTENCIÓN DINÁMICA DE ID_MARCA RESPALDO
|--------------------------------------------------------------------------
*/
$id_marca_defecto = 1; 
try {
    $res_marca = $conn->query("SELECT id_marca FROM marca LIMIT 1");
    if ($res_marca && $res_marca->num_rows > 0) {
        $row_marca = $res_marca->fetch_assoc();
        $id_marca_defecto = (int)$row_marca['id_marca'];
    } else {
        $conn->query("INSERT INTO marca (nombre_marca) VALUES ('Genérica')");
        $id_marca_defecto = $conn->insert_id;
    }
} catch (Exception $e) {}

/*
|--------------------------------------------------------------------------
| CREAR PRODUCTO
|--------------------------------------------------------------------------
*/
if(isset($_POST['crear_producto'])){

    $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
    $precio = mysqli_real_escape_string($conn, $_POST['precio']);
    $stock = mysqli_real_escape_string($conn, $_POST['stock']);
    $descripcion = mysqli_real_escape_string($conn, $_POST['descripcion']);
    $estado = mysqli_real_escape_string($conn, $_POST['estado']);
    
    $categorias_seleccionadas = isset($_POST['categorias']) ? $_POST['categorias'] : [];

    if (empty($categorias_seleccionadas)) {
        echo "<script>alert('Error: Debes seleccionar al menos una Categoría.'); window.history.back();</script>";
        exit;
    }

    $id_categoria_directo = (int)$categorias_seleccionadas[0];

    $rutas_imagenes = [];
    if (isset($_FILES['imagenes']['name']) && is_array($_FILES['imagenes']['name'])) {
        if (!file_exists('uploads')) {
            mkdir('uploads', 0777, true);
        }
        foreach ($_FILES['imagenes']['name'] as $index => $name) {
            if ($_FILES['imagenes']['error'][$index] == UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['imagenes']['tmp_name'][$index];
                $nuevo_nombre = time() . "_" . basename($name);
                $destino = "uploads/" . $nuevo_nombre;
                
                if (move_uploaded_file($tmp_name, $destino)) {
                    $rutas_imagenes[] = $destino;
                }
            }
        }
    }

    $imagen_string = implode(",", $rutas_imagenes);

    $sql = "
        INSERT INTO producto (nombre, precio, stock, descripcion, imagen, estado, id_categoria, id_marca)
        VALUES ('$nombre', '$precio', '$stock', '$descripcion', '$imagen_string', '$estado', $id_categoria_directo, $id_marca_defecto)
    ";

    if ($conn->query($sql)) {
        $id_nuevo_producto = $conn->insert_id;
        foreach ($categorias_seleccionadas as $id_cat) {
            $id_c = (int)$id_cat;
            try {
                $conn->query("INSERT INTO producto_categoria (id_producto, id_categoria) VALUES ($id_nuevo_producto, $id_c)");
            } catch (Exception $e) {}
        }
    }

    header("Location: admin.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| ELIMINAR PRODUCTO (BORRADO LÓGICO PARA PROTEGER HISTORIAL DE VENTAS)
|--------------------------------------------------------------------------
*/
if(isset($_GET['eliminar_producto'])){
    $id = (int) $_GET['eliminar_producto'];
    $conn->query("UPDATE producto SET estado = '0' WHERE id_producto = $id");
    header("Location: admin.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| ELIMINAR USUARIO
|--------------------------------------------------------------------------
*/
if(isset($_GET['eliminar_usuario'])){
    $id = (int) $_GET['eliminar_usuario'];
    $conn->query("DELETE FROM usuario WHERE id_usuario = $id");
    header("Location: admin.php?tab=usuarios");
    exit;
}

/*
|--------------------------------------------------------------------------
| EDITAR PRODUCTO
|--------------------------------------------------------------------------
*/
if(isset($_POST['editar_producto'])){
    $id = (int) $_POST['id_producto'];

    $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
    $precio = mysqli_real_escape_string($conn, $_POST['precio']);
    $stock = mysqli_real_escape_string($conn, $_POST['stock']);
    $descripcion = mysqli_real_escape_string($conn, $_POST['descripcion']);
    $imagen = mysqli_real_escape_string($conn, $_POST['imagen']);
    $estado = mysqli_real_escape_string($conn, $_POST['estado']);
    
    $categorias_seleccionadas = isset($_POST['categorias']) ? $_POST['categorias'] : [];

    if (empty($categorias_seleccionadas)) {
        echo "<script>alert('Error: El producto debe tener al menos una Categoría.'); window.history.back();</script>";
        exit;
    }

    $id_categoria_directo = (int)$categorias_seleccionadas[0];

    $sql = "
        UPDATE producto
        SET
            nombre = '$nombre',
            precio = '$precio',
            stock = '$stock',
            descripcion = '$descripcion',
            imagen = '$imagen',
            estado = '$estado',
            id_categoria = $id_categoria_directo,
            id_marca = $id_marca_defecto
        WHERE id_producto = $id
    ";
    $conn->query($sql);

    try {
        $conn->query("DELETE FROM producto_categoria WHERE id_producto = $id");
        foreach ($categorias_seleccionadas as $id_cat) {
            $id_c = (int)$id_cat;
            $conn->query("INSERT INTO producto_categoria (id_producto, id_categoria) VALUES ($id, $id_c)");
        }
    } catch(Exception $e){}

    header("Location: admin.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| FILTROS Y CONSULTAS GENERALES
|--------------------------------------------------------------------------
*/
$search = $_GET['search'] ?? '';
$tab = $_GET['tab'] ?? 'productos';

// Consulta de productos
$sql_prod = "SELECT * FROM producto WHERE 1=1";
if (!empty($search) && $tab == 'productos') {
    $search_clean = $conn->real_escape_string($search);
    $sql_prod .= " AND nombre LIKE '%$search_clean%'";
}
$sql_prod .= " ORDER BY id_producto DESC";
$games = $conn->query($sql_prod);

// Consulta de usuarios
$usuarios = $conn->query("SELECT * FROM usuario ORDER BY id_usuario DESC");

// Consulta de historial de compras con lógica de búsqueda integrada
$query_compras_global = "
    SELECT 
        dv.*,
        p.nombre AS nombre_producto,
        p.imagen AS imagen_producto,
        u.nombre AS nombre_usuario,
        u.correo AS correo_usuario
    FROM detalle_venta dv
    LEFT JOIN producto p ON dv.id_producto = p.id_producto
    LEFT JOIN venta v ON dv.id_venta = v.id_venta
    LEFT JOIN usuario u ON v.id_usuario = u.id_usuario
";

if (!empty($search) && $tab == 'compras') {
    $search_clean = $conn->real_escape_string($search);
    $query_compras_global .= " WHERE p.nombre LIKE '%$search_clean%' 
                               OR u.nombre LIKE '%$search_clean%' 
                               OR u.correo LIKE '%$search_clean%'";
}

$query_compras_global .= " ORDER BY dv.id_detalle DESC";

try {
    $ventas = $conn->query($query_compras_global);
} catch (Exception $e) {
    $ventas = $conn->query("SELECT * FROM detalle_venta ORDER BY id_detalle DESC");
}

$totalGames = $conn->query("SELECT COUNT(*) total FROM producto")->fetch_assoc()['total'];
$totalUsers = $conn->query("SELECT COUNT(*) total FROM usuario")->fetch_assoc()['total'];
$totalOrders = $conn->query("SELECT COUNT(*) total FROM detalle_venta")->fetch_assoc()['total'];

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Gamer Store</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body{ background:#050816; color:white; font-family:Arial,sans-serif; }
        .scroll::-webkit-scrollbar{ width:5px; }
        .scroll::-webkit-scrollbar-thumb{ background:#9333ea; }
    </style>
</head>

<body>

<div class="flex min-h-screen">

    <aside class="w-[260px] bg-[#0b1020] border-r border-purple-900/20 p-5 hidden lg:block">
        <div class="flex items-center gap-3 mb-10">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-r from-purple-600 to-fuchsia-600 flex items-center justify-center text-2xl">🎮</div>
            <div>
                <h1 class="text-2xl font-bold">GAMER</h1>
                <p class="text-purple-400">ADMIN PANEL</p>
            </div>
        </div>
        <nav class="flex flex-col gap-4">
            <a href="?tab=productos" class="<?php echo $tab == 'productos' ? 'bg-purple-600' : 'bg-[#121a30]' ?> hover:bg-purple-700 px-5 py-4 rounded-2xl transition-all">Productos</a>
            <a href="?tab=usuarios" class="<?php echo $tab == 'usuarios' ? 'bg-purple-600' : 'bg-[#121a30]' ?> hover:bg-purple-700 px-5 py-4 rounded-2xl transition-all">Usuarios</a>
            <a href="?tab=compras" class="<?php echo $tab == 'compras' ? 'bg-purple-600' : 'bg-[#121a30]' ?> hover:bg-purple-700 px-5 py-4 rounded-2xl transition-all">Compras</a>
        </nav>
    </aside>

    <main class="flex-1 p-6 overflow-hidden">

        <div class="flex flex-col lg:flex-row justify-between gap-5 items-center mb-8">
            <form method="GET" class="flex-1 w-full flex gap-4">
                <input type="hidden" name="tab" value="<?php echo $tab ?>">
                <input type="text" name="search" 
                       placeholder="<?php echo $tab == 'compras' ? 'Buscar compras por cliente, correo o juego...' : 'Buscar productos...' ?>" 
                       value="<?php echo htmlspecialchars($search) ?>" 
                       class="flex-1 bg-[#0b1020] border border-purple-900/20 rounded-2xl py-4 px-5 outline-none focus:border-purple-500 text-white">
                <button type="submit" class="bg-gradient-to-r from-purple-600 to-fuchsia-600 px-6 rounded-2xl font-bold hover:opacity-90 transition-all">Buscar</button>
                <?php if(!empty($search)): ?>
                    <a href="?tab=<?php echo $tab ?>" class="bg-zinc-800 hover:bg-zinc-700 px-5 flex items-center justify-center rounded-2xl font-semibold text-sm">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
            <div class="bg-[#0b1020] rounded-3xl p-6 border border-purple-900/20">
                <p class="text-gray-400">Productos</p>
                <h2 class="text-5xl font-black mt-3"><?php echo $totalGames ?></h2>
            </div>
            <div class="bg-[#0b1020] rounded-3xl p-6 border border-purple-900/20">
                <p class="text-gray-400">Usuarios</p>
                <h2 class="text-5xl font-black mt-3"><?php echo $totalUsers ?></h2>
            </div>
            <div class="bg-[#0b1020] rounded-3xl p-6 border border-purple-900/20">
                <p class="text-gray-400">Compras</p>
                <h2 class="text-5xl font-black mt-3"><?php echo $totalOrders ?></h2>
            </div>
        </div>

        <?php if($tab == "productos") { ?>
        <section>
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-4xl font-black">Gestión de Productos</h2>
                <button onclick="document.getElementById('modal-crear').classList.remove('hidden'); document.getElementById('modal-crear').classList.add('flex');" class="bg-green-600 hover:bg-green-700 px-6 py-3 rounded-2xl font-bold transition-all text-sm">+ Añadir Juego</button>
            </div>

            <div class="bg-[#0b1020] rounded-[30px] border border-purple-900/20 overflow-hidden">
                <div class="overflow-x-auto scroll">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-purple-900/20 text-gray-400 text-sm bg-[#0e1426]">
                                <th class="p-4 pl-6">Imagen</th>
                                <th class="p-4">Nombre del Juego</th>
                                <th class="p-4">Precio</th>
                                <th class="p-4">Stock</th>
                                <th class="p-4">Estado</th>
                                <th class="p-4 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if($games->num_rows > 0) {
                            while($game = $games->fetch_assoc()) { 
                                $id_p = $game['id_producto'];
                                $array_imgs = explode(",", $game['imagen']);
                                $primera_imagen = !empty($array_imgs[0]) ? $array_imgs[0] : 'https://via.placeholder.com/80x50';
                        ?>
                            <tr class="border-b border-purple-900/10 hover:bg-[#121a30] transition-all">
                                <td class="p-4 pl-6">
                                    <img src="<?php echo $primera_imagen ?>" class="w-16 h-10 object-cover rounded-xl border border-purple-500/20">
                                </td>
                                <td class="p-4 font-bold text-white max-w-[200px] truncate">
                                    <?php echo htmlspecialchars($game['nombre']) ?>
                                </td>
                                <td class="p-4 text-green-400 font-bold">
                                    $<?php echo number_format($game['precio'], 2) ?>
                                </td>
                                <td class="p-4 text-gray-300">
                                    <?php echo $game['stock'] ?> uds
                                </td>
                                <td class="p-4">
                                    <?php if($game['estado'] == 1 || $game['estado'] == 'activo') { ?>
                                        <span class="text-xs bg-green-500/10 text-green-400 px-3 py-1 rounded-full border border-green-500/20">Activo</span>
                                    <?php } else { ?>
                                        <span class="text-xs bg-red-500/10 text-red-400 px-3 py-1 rounded-full border border-red-500/20">Inactivo</span>
                                    <?php } ?>
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <button onclick="abrirModalVer('<?php echo htmlspecialchars($game['nombre'], ENT_QUOTES); ?>', '<?php echo $primera_imagen; ?>', '<?php echo number_format($game['precio'], 2); ?>', '<?php echo $game['stock']; ?>', '<?php echo htmlspecialchars($game['descripcion'], ENT_QUOTES); ?>')" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-xl text-xs font-bold transition-all">Ver</button>
                                        <button onclick="document.getElementById('modal-editar-<?php echo $id_p ?>').classList.remove('hidden'); document.getElementById('modal-editar-<?php echo $id_p ?>').classList.add('flex');" class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded-xl text-xs font-bold transition-all">Editar</button>
                                        <a href="?eliminar_producto=<?php echo $id_p ?>" onclick="return confirm('¿Seguro que deseas desactivar este producto de la tienda?')" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-xl text-xs font-bold transition-all">Borrar</a>
                                    </div>
                                </td>
                            </tr>

                            <div id="modal-editar-<?php echo $id_p ?>" class="hidden fixed inset-0 z-50 bg-black/70 backdrop-blur-sm items-center justify-center p-4">
                                <div class="bg-[#0b1020] border-2 border-purple-500/30 rounded-[30px] p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto scroll text-white text-left">
                                    <div class="flex justify-between items-center mb-6">
                                        <h3 class="text-2xl font-bold text-purple-400">Modificar Videojuego</h3>
                                        <button type="button" onclick="document.getElementById('modal-editar-<?php echo $id_p ?>').classList.add('hidden');" class="text-gray-400 hover:text-white text-xl">&times;</button>
                                    </div>
                                    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <input type="hidden" name="id_producto" value="<?php echo $id_p ?>">
                                        <div class="md:col-span-2">
                                            <label class="text-sm text-gray-400 block mb-2">Rutas de Imágenes (Separadas por coma)</label>
                                            <input type="text" name="imagen" value="<?php echo htmlspecialchars($game['imagen']) ?>" class="w-full bg-[#121a30] border border-purple-900/20 rounded-2xl p-3 outline-none focus:border-purple-500 text-xs text-gray-400">
                                        </div>
                                        <div>
                                            <label class="text-sm text-gray-400 block mb-2">Nombre del Juego</label>
                                            <input type="text" name="nombre" value="<?php echo htmlspecialchars($game['nombre']) ?>" required class="w-full bg-[#121a30] border border-purple-900/20 rounded-2xl p-3 outline-none focus:border-purple-500 text-white">
                                        </div>
                                        <div>
                                            <label class="text-sm text-gray-400 block mb-2">Precio</label>
                                            <input type="number" step="0.01" name="precio" value="<?php echo $game['precio'] ?>" required class="w-full bg-[#121a30] border border-purple-900/20 rounded-2xl p-3 outline-none focus:border-purple-500 text-white">
                                        </div>
                                        <div>
                                            <label class="text-sm text-gray-400 block mb-2">Stock</label>
                                            <input type="number" name="stock" value="<?php echo $game['stock'] ?>" required class="w-full bg-[#121a30] border border-purple-900/20 rounded-2xl p-3 outline-none focus:border-purple-500 text-white">
                                        </div>
                                        <div>
                                            <label class="text-sm text-gray-400 block mb-2">Estado</label>
                                            <select name="estado" class="w-full bg-[#121a30] border border-purple-900/20 rounded-2xl p-3 outline-none focus:border-purple-500 text-white">
                                                <option value="1" <?php if($game['estado'] == 1 || $game['estado'] == 'activo') echo 'selected'; ?>>Activo</option>
                                                <option value="0" <?php if($game['estado'] == 0 || $game['estado'] == 'inactivo') echo 'selected'; ?>>Inactivo</option>
                                            </select>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="text-sm text-gray-400 block mb-2">Categoría Relacionada</label>
                                            <div class="bg-[#121a30] border border-purple-900/20 rounded-2xl p-4 max-h-[120px] overflow-y-auto scroll grid grid-cols-2 gap-2">
                                                <?php 
                                                $cats = $conn->query("SELECT id_categoria, nombre_categoria FROM categoria ORDER BY nombre_categoria ASC");
                                                while($cat = $cats->fetch_assoc()) {
                                                    // Evitamos fallos si id_categoria no está mapeado directamente en la fila actual
                                                    $checked = (isset($game['id_categoria']) && $cat['id_categoria'] == $game['id_categoria']) ? 'checked' : '';
                                                    echo "<label class='flex items-center gap-2 text-xs text-gray-300 cursor-pointer'><input type='radio' name='categorias[]' value='".$cat['id_categoria']."' $checked class='accent-purple-600'> ".htmlspecialchars($cat['nombre_categoria'])."</label>";
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="text-sm text-gray-400 block mb-2">Descripción</label>
                                            <textarea name="descripcion" rows="4" required class="w-full bg-[#121a30] border border-purple-900/20 rounded-2xl p-3 outline-none focus:border-purple-500 text-white"><?php echo htmlspecialchars($game['descripcion']) ?></textarea>
                                        </div>
                                        <div class="md:col-span-2 flex gap-3 justify-end mt-2">
                                            <button type="button" onclick="document.getElementById('modal-editar-<?php echo $id_p ?>').classList.add('hidden');" class="bg-gray-700 hover:bg-gray-600 px-5 py-2.5 rounded-2xl font-bold transition-all text-sm">Cancelar</button>
                                            <button type="submit" name="editar_producto" class="bg-purple-600 hover:bg-purple-700 px-5 py-2.5 rounded-2xl font-bold transition-all text-sm">Guardar Cambios</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php } 
                        } else { ?>
                            <tr>
                                <td colspan="6" class="p-8 text-center text-gray-500 text-sm">No se encontraron productos coincidentes en la tienda.</td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="modal-ver" class="hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-sm items-center justify-center p-4">
                <div class="bg-[#0b1020] border-2 border-blue-500/30 rounded-[30px] p-6 w-full max-w-lg text-white relative">
                    <button onclick="document.getElementById('modal-ver').classList.add('hidden'); document.getElementById('modal-ver').classList.remove('flex');" class="absolute top-4 right-4 text-gray-400 hover:text-white text-2xl">&times;</button>
                    <h3 id="ver-title" class="text-2xl font-black text-blue-400 mb-4"></h3>
                    <img id="ver-img" src="" class="w-full h-48 object-cover rounded-2xl mb-4 border border-purple-500/20">
                    <div class="space-y-2 text-sm bg-[#121a30] p-4 rounded-2xl border border-purple-900/10">
                        <p><span class="text-gray-400">Precio:</span> <span id="ver-price" class="text-green-400 font-bold"></span></p>
                        <p><span class="text-gray-400">Stock Disponible:</span> <span id="ver-stock"></span> unidades</p>
                        <p><span class="text-gray-400">Descripción:</span></p>
                        <p id="ver-desc" class="text-gray-300 leading-relaxed text-xs"></p>
                    </div>
                </div>
            </div>

            <div id="modal-crear" class="hidden fixed inset-0 z-50 bg-black/70 backdrop-blur-sm items-center justify-center p-4">
                <div class="bg-[#0b1020] border-2 border-green-500/30 rounded-[30px] p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto scroll text-white">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-2xl font-bold text-green-400">Registrar Nuevo Videojuego</h3>
                        <button type="button" onclick="document.getElementById('modal-crear').classList.add('hidden'); document.getElementById('modal-crear').classList.remove('flex');" class="text-gray-400 hover:text-white text-xl">&times;</button>
                    </div>
                    <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm text-gray-400 block mb-2">Nombre del Juego</label>
                            <input type="text" name="nombre" required class="w-full bg-[#121a30] border border-purple-900/20 rounded-2xl p-3 outline-none focus:border-purple-500 text-white">
                        </div>
                        <div>
                            <label class="text-sm text-gray-400 block mb-2">Precio</label>
                            <input type="number" step="0.01" name="precio" required class="w-full bg-[#121a30] border border-purple-900/20 rounded-2xl p-3 outline-none focus:border-purple-500 text-white">
                        </div>
                        <div>
                            <label class="text-sm text-gray-400 block mb-2">Stock Inicial</label>
                            <input type="number" name="stock" required class="w-full bg-[#121a30] border border-purple-900/20 rounded-2xl p-3 outline-none focus:border-purple-500 text-white">
                        </div>
                        <div>
                            <label class="text-sm text-gray-400 block mb-2">Estado</label>
                            <select name="estado" class="w-full bg-[#121a30] border border-purple-900/20 rounded-2xl p-3 outline-none focus:border-purple-500 text-white">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-sm text-gray-400 block mb-2">Categorías</label>
                            <div class="bg-[#121a30] border border-purple-900/20 rounded-2xl p-4 max-h-[120px] overflow-y-auto scroll grid grid-cols-2 gap-2">
                                <?php 
                                $cats_crear = $conn->query("SELECT id_categoria, nombre_categoria FROM categoria ORDER BY nombre_categoria ASC");
                                if($cats_crear && $cats_crear->num_rows > 0) {
                                    while($cat = $cats_crear->fetch_assoc()) {
                                        echo "<label class='flex items-center gap-2 text-sm text-gray-200 cursor-pointer'><input type='checkbox' name='categorias[]' value='".$cat['id_categoria']."' class='accent-purple-600'> ".htmlspecialchars($cat['nombre_categoria'])."</label>";
                                    }
                                } else {
                                    echo "<p class='text-xs text-gray-500 col-span-2 text-center py-2'>No hay categorías registradas.</p>";
                                }
                                ?>
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-sm text-gray-400 block mb-2">Adjuntar Imágenes</label>
                            <input type="file" name="imagenes[]" multiple required accept="image/*" class="w-full bg-[#121a30] border border-purple-900/20 rounded-2xl p-3 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-purple-600 file:text-white hover:file:bg-purple-700 cursor-pointer">
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-sm text-gray-400 block mb-2">Descripción</label>
                            <textarea name="descripcion" rows="3" required class="w-full bg-[#121a30] border border-purple-900/20 rounded-2xl p-3 outline-none focus:border-purple-500 text-white"></textarea>
                        </div>
                        <div class="md:col-span-2 flex gap-3 justify-end mt-4">
                            <button type="button" onclick="document.getElementById('modal-crear').classList.add('hidden'); document.getElementById('modal-crear').classList.remove('flex');" class="bg-gray-700 hover:bg-gray-600 px-6 py-3 rounded-2xl font-bold transition-all">Cancelar</button>
                            <button type="submit" name="crear_producto" class="bg-green-600 hover:bg-green-700 px-6 py-3 rounded-2xl font-bold transition-all">Insertar en DB</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
        <?php } ?>

        <?php if($tab == "usuarios") { ?>
        <section class="bg-[#0b1020] rounded-[30px] border border-purple-900/20 p-6 overflow-auto scroll">
            <h2 class="text-3xl font-bold mb-6">Gestión de Usuarios</h2>
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-purple-900/20 text-gray-400">
                        <th class="pb-4">ID</th>
                        <th class="pb-4">Nombre</th>
                        <th class="pb-4">Correo</th>
                        <th class="pb-4">Rol</th>
                        <th class="pb-4">Fecha Registro</th>
                        <th class="pb-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($user = $usuarios->fetch_assoc()) { ?>
                    <tr class="border-b border-purple-900/10 hover:bg-[#121a30] transition-all">
                        <td class="py-5"><?php echo $user['id_usuario'] ?></td>
                        <td><?php echo htmlspecialchars($user['nombre']) ?></td>
                        <td><?php echo htmlspecialchars($user['correo']) ?></td>
                        <td>
                            <?php if($user['rol'] == 'admin') { ?>
                                <span class="bg-red-500/20 text-red-400 px-4 py-2 rounded-xl text-sm">ADMIN</span>
                            <?php } else { ?>
                                <span class="bg-blue-500/20 text-blue-400 px-4 py-2 rounded-xl text-sm">CLIENTE</span>
                            <?php } ?>
                        </td>
                        <td class="text-gray-300"><?php echo date("d/m/Y H:i", strtotime($user['fecha_registro'])); ?></td>
                        <td>
                            <?php if($user['rol'] != 'admin') { ?>
                            <a href="?tab=usuarios&eliminar_usuario=<?php echo $user['id_usuario'] ?>" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded-xl" onclick="return confirm('¿Eliminar usuario?')">Eliminar</a>
                            <?php } else { ?>
                                <span class="text-gray-500">Protegido</span>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </section>
        <?php } ?>

        <?php if($tab == "compras") { ?>
        <section class="bg-[#0b1020] rounded-[30px] border border-purple-900/20 p-6 overflow-auto scroll">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-4xl font-black">Historial de Ventas Global</h2>
            </div>
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-purple-900/20 text-gray-400">
                        <th class="pb-4">Detalle ID</th>
                        <th class="pb-4">Cliente</th>
                        <th class="pb-4">Producto</th>
                        <th class="pb-4">Cantidad</th>
                        <th class="pb-4">Precio U.</th>
                        <th class="pb-4">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                <?php if($ventas && $ventas->num_rows > 0) { 
                    while($venta = $ventas->fetch_assoc()) { 
                        // Aquí protegemos por si la columna viene mapeada como 'precio' o 'precio_unitario'
                        $p_unitario = $venta['precio_unitario'] ?? $venta['precio'] ?? 0;
                        $cantidad = $venta['cantidad'] ?? 1;
                        $subtotal = $p_unitario * $cantidad;
                ?>
                    <tr class="border-b border-purple-900/10 hover:bg-[#121a30] transition-all">
                        <td class="py-4"><?php echo $venta['id_detalle']; ?></td>
                        <td>
                            <div class="text-sm font-bold"><?php echo htmlspecialchars($venta['nombre_usuario'] ?? 'N/A'); ?></div>
                            <div class="text-xs text-gray-400"><?php echo htmlspecialchars($venta['correo_usuario'] ?? ''); ?></div>
                        </td>
                        <td><?php echo htmlspecialchars($venta['nombre_producto'] ?? 'Producto Eliminado/Inactivo'); ?></td>
                        <td><?php echo $cantidad; ?> uds</td>
                        <td class="text-green-400">$<?php echo number_format($p_unitario, 2); ?></td>
                        <td class="text-purple-400 font-bold">$<?php echo number_format($subtotal, 2); ?></td>
                    </tr>
                <?php } 
                } else { ?>
                    <tr>
                        <td colspan="6" class="p-8 text-center text-gray-500 text-sm">No hay registros de ventas que coincidan.</td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </section>
        <?php } ?>

    </main>
</div>

<script>
function abrirModalVer(nombre, imagen, precio, stock, descripcion) {
    document.getElementById('ver-title').innerText = nombre;
    document.getElementById('ver-img').src = imagen;
    document.getElementById('ver-price').innerText = '$' + precio;
    document.getElementById('ver-stock').innerText = stock;
    document.getElementById('ver-desc').innerText = descripcion;
    
    let modal = document.getElementById('modal-ver');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
</script>

</body>
</html>