<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors',1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/*
|--------------------------------------------------------------------------
| CONEXIÓN A LA BASE DE DATOS
|--------------------------------------------------------------------------
*/
require_once 'conexion.php';

$conn->set_charset("utf8");

/*
|--------------------------------------------------------------------------
| IDENTIFICACIÓN AUTOMÁTICA DEL ID DE USUARIO
|--------------------------------------------------------------------------
*/
// Primero verificamos si hay una sesión activa de usuario
if(!isset($_SESSION['usuario'])){
    // Si no ha iniciado sesión, lo mandamos al index para proteger la página
    header("Location: index.php");
    exit();
}

// Identificamos el ID de forma automática desde las variables de sesión comunes
if (isset($_SESSION['id_usuario'])) {
    $id_usuario = intval($_SESSION['id_usuario']);
} elseif (isset($_SESSION['id'])) {
    $id_usuario = intval($_SESSION['id']);
} else {
    // Si la sesión existe pero no encuentras la variable del ID en tu script de login,
    // puedes forzar temporalmente un ID válido que tengas en phpMyAdmin (ej: 2) para que no falle.
    $id_usuario = 2; 
}

/*
|--------------------------------------------------------------------------
| AGREGAR O QUITAR FAVORITO
|--------------------------------------------------------------------------
*/

if(isset($_GET['favorito'])){

    $id_producto = intval($_GET['favorito']);

    /*
    |--------------------------------------------------------------------------
    | VERIFICAR SI YA EXISTE EN TU TABLA
    |--------------------------------------------------------------------------
    */

    $verificar = $conn->query("
        SELECT *
        FROM favorito
        WHERE id_usuario = $id_usuario
        AND id_producto = $id_producto
    ");

    /*
    |--------------------------------------------------------------------------
    | ACCIÓN: ELIMINAR SI YA EXISTE / INSERTAR SI NO EXISTE
    |--------------------------------------------------------------------------
    */

    if($verificar->num_rows == 0){

        $conn->query("
            INSERT INTO favorito(
                id_usuario,
                id_producto
            )
            VALUES(
                $id_usuario,
                $id_producto
            )
        ");
    } else {
        // Si ya era favorito, al pulsar el corazón lo removemos de la lista
        $conn->query("
            DELETE FROM favorito 
            WHERE id_usuario = $id_usuario 
            AND id_producto = $id_producto
        ");
    }

    // Redirecciona de vuelta a favorito.php para refrescar la tienda
    header("Location: favorito.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| OBTENER FAVORITOS EN UN ARRAY
|--------------------------------------------------------------------------
*/

$favoritos_usuario = [];

$queryFavoritos = $conn->query("
    SELECT id_producto
    FROM favorito
    WHERE id_usuario = $id_usuario
");

while($fav = $queryFavoritos->fetch_assoc()){

    $favoritos_usuario[] = $fav['id_producto'];
}

/*
|--------------------------------------------------------------------------
| CARRITO
|--------------------------------------------------------------------------
*/

if(!isset($_SESSION['carrito'])){

    $_SESSION['carrito'] = [];
}

/*
|--------------------------------------------------------------------------
| AGREGAR AL CARRITO
|--------------------------------------------------------------------------
*/

if(isset($_GET['cart'])){

    $id = intval($_GET['cart']);

    if(isset($_SESSION['carrito'][$id])){

        $_SESSION['carrito'][$id]++;

    }else{

        $_SESSION['carrito'][$id] = 1;
    }

    // Redirecciona de vuelta a favorito.php para no salir de la página
    header("Location: favorito.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| CONSULTAR PRODUCTOS UNIDOS CON SU CATEGORÍA REAL
|--------------------------------------------------------------------------
*/

// Agregamos LEFT JOIN para traer el nombre legible de la categoría desde su tabla
$queryProductos = $conn->query("
    SELECT p.*, c.nombre_categoria 
    FROM producto p
    LEFT JOIN categoria c ON p.id_categoria = c.id_categoria
    WHERE p.estado = 'activo' OR p.estado = '1'
    ORDER BY p.id_producto DESC
");

$games = [];
while($row = $queryProductos->fetch_assoc()){
    $games[] = $row;
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>

<title>Game Store</title>

<script src="https://cdn.tailwindcss.com"></script>

<link
rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
/>

<style>

body{
    background:#050816;
    color:white;
    font-family:Arial;
}

.card{
    transition:.3s;
}

.card:hover{
    transform:translateY(-6px);
}

</style>

</head>

<body>

<div class="min-h-screen p-8">

    <div class="flex justify-between items-center mb-10">

        <div>

            <h1 class="text-5xl font-black">
                🎮 GAME STORE
            </h1>

            <p class="text-gray-400 mt-2">
                Explora videojuegos increíbles
            </p>

        </div>

        <div class="flex gap-4">

            <a
            href="favorito.php"
            class="bg-pink-600 hover:bg-pink-700 px-6 py-3 rounded-2xl transition-all"
            >
                ❤️ Favoritos
            </a>

            <a
            href="carrito.php"
            class="bg-purple-600 hover:bg-purple-700 px-6 py-3 rounded-2xl transition-all"
            >
                🛒 Carrito
            </a>

        </div>

    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">

        <?php foreach($games as $game): ?>

        <?php
        $id_game = $game['id_producto'];

        // Comprobar si el ID del producto está en tus favoritos
        $esFavorito = in_array(
            $id_game,
            $favoritos_usuario
        );

        $nombre = $game['nombre'];
        $descripcion = !empty($game['descripcion']) ? $game['descripcion'] : 'Sin descripción disponible.';
        $precio = $game['precio'];
        $stock = $game['stock'];
        
        // Separamos imágenes si el admin subió una lista separada por comas
        $array_imgs = explode(",", $game['imagen']);
        $img_raw = !empty($array_imgs[0]) ? trim($array_imgs[0]) : '';

        if (empty($img_raw)) {
            $imagen = 'https://via.placeholder.com/400x250';
        } elseif (filter_var($img_raw, FILTER_VALIDATE_URL)) {
            $imagen = $img_raw;
        } else {
            $imagen = $img_raw; 
        }

        // Si el LEFT JOIN trajo el nombre lo usamos, sino ponemos el número como respaldo
        $nombre_cat = !empty($game['nombre_categoria']) ? $game['nombre_categoria'] : 'Cat: ' . $game['id_categoria'];
        ?>

        <div class="card bg-[#0b1020] rounded-[30px] overflow-hidden border border-purple-900/20">

            <div class="relative h-[260px] overflow-hidden">

                <img
                    src="<?php echo $imagen; ?>"
                    alt="<?php echo htmlspecialchars($nombre); ?>"
                    class="w-full h-full object-cover hover:scale-110 transition-all duration-500"
                    onerror="this.onerror=null; this.src='https://via.placeholder.com/400x250';"
                >

                <a
                href="?favorito=<?php echo $id_game; ?>"
                class="absolute top-4 right-4 w-11 h-11 rounded-full flex items-center justify-center transition-all
                <?php echo $esFavorito
                ? 'bg-pink-600 text-white'
                : 'bg-black/50 text-gray-300 hover:text-white'; ?>"
                >

                    <i class="fa-solid fa-heart"></i>

                </a>

            </div>

            <div class="p-5">

                <div class="flex justify-between items-start gap-3">

                    <div>

                        <h2 class="text-2xl font-bold leading-tight">
                            <?php echo htmlspecialchars($nombre); ?>
                        </h2>

                        <p class="text-gray-400 mt-2 text-sm">
                            📦 Stock: <?php echo $stock; ?> u.
                        </p>

                    </div>

                    <div class="bg-purple-600/20 text-purple-400 px-3 py-1 rounded-xl text-sm font-bold">
                        ⭐ 5.0
                    </div>

                </div>

                <div class="flex flex-wrap gap-2 mt-4">

                    <span class='bg-[#131c35] text-purple-400 font-medium px-3 py-1 rounded-xl text-xs border border-purple-500/20'>
                        🎮 <?php echo htmlspecialchars($nombre_cat); ?>
                    </span>

                    <span class='bg-[#1a1335] text-fuchsia-400 font-medium px-3 py-1 rounded-xl text-xs border border-fuchsia-500/20'>
                        🏷️ Marca: <?php echo htmlspecialchars($game['id_marca']); ?>
                    </span>

                </div>

                <div class="mt-4">

                    <p class="text-gray-400 text-sm line-clamp-3">
                        <?php echo htmlspecialchars($descripcion); ?>
                    </p>

                </div>

                <div class="mt-5">

                    <p class="text-gray-400 text-sm">
                        Precio
                    </p>

                    <h2 class="text-3xl font-black text-green-400">
                        Bs. <?php echo number_format($precio, 2); ?>
                    </h2>

                </div>

                <?php if($stock > 0): ?>

                    <a
                    href="?cart=<?php echo $id_game; ?>"
                    class="mt-6 w-full flex items-center justify-center gap-3 py-3 rounded-2xl bg-gradient-to-r from-purple-600 to-pink-600 font-semibold hover:scale-105 transition-all"
                    >

                        <i class="fa-solid fa-cart-shopping"></i>
                        Agregar al carrito

                    </a>

                <?php else: ?>

                    <button
                    disabled
                    class="mt-6 w-full flex items-center justify-center gap-3 py-3 rounded-2xl bg-gray-700 text-gray-400 font-semibold cursor-not-allowed"
                    >
                        Agotado
                    </button>

                <?php endif; ?>

            </div>

        </div>

        <?php endforeach; ?>

    </div>

</div>

</body>
</html>