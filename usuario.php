<?php

session_start();
include("conexion.php");

if(!isset($_SESSION['usuario'])){

    header("Location: index.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

/*
|--------------------------------------------------------------------------
| FAVORITOS
|--------------------------------------------------------------------------
*/

if(!isset($_SESSION['favoritos'])){

    $_SESSION['favoritos'] = [];
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
| AJAX FAVORITOS
|--------------------------------------------------------------------------
*/

if(isset($_GET['ajaxFav'])){

    $id = intval($_GET['ajaxFav']);

    if($id > 0){

        if(in_array($id, $_SESSION['favoritos'])){

            $_SESSION['favoritos'] = array_diff(
                $_SESSION['favoritos'],
                [$id]
            );

            $active = false;

        }else{

            $_SESSION['favoritos'][] = $id;

            $active = true;
        }

        echo json_encode([

            "success" => true,
            "active" => $active,
            "count" => count($_SESSION['favoritos'])

        ]);
    }

    exit;
}

/*
|--------------------------------------------------------------------------
| AJAX CARRITO (CON VALIDACIÓN DE STOCK)
|--------------------------------------------------------------------------
*/

if(isset($_GET['ajaxCart'])){

    $id = intval($_GET['ajaxCart']);

    if($id > 0){
        
        // Verificar si el producto tiene stock disponible antes de agregarlo
        $sql_verificar_stock = "SELECT stock FROM producto WHERE id_producto = $id";
        $res_stock = $conn->query($sql_verificar_stock);
        
        if($res_stock && $prod_stock = $res_stock->fetch_assoc()){
            $stock_actual = intval($prod_stock['stock']);
            $cantidad_en_carrito = isset($_SESSION['carrito'][$id]) ? $_SESSION['carrito'][$id] : 0;

            if($stock_actual <= 0 || $cantidad_en_carrito >= $stock_actual){
                echo json_encode([
                    "success" => false,
                    "message" => "Límite de stock alcanzado."
                ]);
                exit;
            }
        }

        // CONTROL DE SESIÓN DEL CARRITO
        if(isset($_SESSION['carrito'][$id])){

            $_SESSION['carrito'][$id]++;

        }else{

            $_SESSION['carrito'][$id] = 1;
        }

        echo json_encode([

            "success" => true,
            "count" => array_sum($_SESSION['carrito'])

        ]);
    }

    exit;
}

/*
|--------------------------------------------------------------------------
| FILTROS Y LIMPIEZA SQL
|--------------------------------------------------------------------------
*/

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$orden = isset($_GET['orden']) ? $_GET['orden'] : '';
$categoria = isset($_GET['categoria']) ? $conn->real_escape_string($_GET['categoria']) : '';

// Consulta base seleccionando desde producto (Asegura traer el campo stock)
$sql = "SELECT * FROM producto WHERE (estado='activo' OR estado='1')";

if(!empty($search)){

    $sql .= " AND nombre LIKE '%$search%'";
}

if(!empty($categoria)){
    $sql .= " AND id_categoria = (SELECT id_categoria FROM categoria WHERE nombre_categoria = '$categoria' LIMIT 1)";
}

switch($orden){

    case "precio_asc":
        $sql .= " ORDER BY precio ASC";
    break;

    case "precio_desc":
        $sql .= " ORDER BY precio DESC";
    break;

    case "popularidad":
        $sql .= " ORDER BY id_producto DESC"; 
    break;

    case "fecha":
        $sql .= " ORDER BY id_producto DESC";
    break;

    default:
        $sql .= " ORDER BY id_producto DESC";
}

$resultado = $conn->query($sql);

if (!$resultado) {
    die("<div class='p-5 bg-red-600 text-white rounded-xl m-5 font-bold text-center'>
            Error en la consulta SQL: " . $conn->error . "<br>
            Consulta ejecutada: <span class='text-yellow-300 font-mono text-sm'>".$sql."</span>
         </div>");
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

<title>Gamer Universe</title>

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
    overflow-x:hidden;
}

.card{
    transition:.3s;
}

.card:hover:not(.sin-stock-card){
    transform:translateY(-7px);
    border-color:#9333ea;
}

.hero-shadow{
    background:linear-gradient(
        to right,
        rgba(0,0,0,.95),
        rgba(0,0,0,.6),
        rgba(0,0,0,.2)
    );
}

.glow{
    box-shadow:0 0 30px rgba(168,85,247,.4);
}

.category-menu{
    max-height:0;
    overflow:hidden;
    transition:all .4s ease;
}

.category-menu.active{
    max-height:500px;
}

</style>

</head>

<body>

<div class="min-h-screen flex flex-col bg-[#050816] text-white">

<div class="flex flex-1">
<aside class="w-[280px] bg-[#0b1020] border-r border-purple-900/20 hidden lg:flex flex-col justify-between">

<div>

<div class="p-6 border-b border-purple-900/20">

<div class="flex items-center gap-4">

<div class="w-16 h-16 rounded-3xl bg-gradient-to-r from-purple-600 to-fuchsia-600 flex items-center justify-center glow">

<i class="fa-solid fa-gamepad text-3xl"></i>

</div>

<div>

<h1 class="text-3xl font-black">
GAMER
</h1>

<p class="text-purple-400 text-sm">
UNIVERSE
</p>

</div>

</div>

</div>

<nav class="p-5 space-y-3">

<a
href="usuario.php"
class="flex items-center gap-4 bg-gradient-to-r from-purple-600 to-fuchsia-600 px-5 py-4 rounded-2xl"
>
<i class="fa-solid fa-house"></i>
Inicio
</a>

<a
href="?orden=popularidad"
class="flex items-center gap-4 hover:bg-[#151d34] px-5 py-4 rounded-2xl transition-all"
>
<i class="fa-solid fa-fire"></i>
Juegos Populares
</a>

<a
href="?orden=precio_asc"
class="flex items-center gap-4 hover:bg-[#151d34] px-5 py-4 rounded-2xl transition-all"
>
<i class="fa-solid fa-tag"></i>
Ofertas
</a>

<button
onclick="toggleCategories()"
class="w-full flex items-center justify-between hover:bg-[#151d34] px-5 py-4 rounded-2xl transition-all"
>

<div class="flex items-center gap-4">

<i class="fa-solid fa-layer-group"></i>
Categorías

</div>

<i class="fa-solid fa-chevron-down"></i>

</button>

<div
id="categoryMenu"
class="category-menu ml-5 space-y-2"
>

<?php

$categorias = [
    "Action",
    "Adventure",
    "Indie",
    "RPG",
    "Shooter",
    "Platform",
    "Puzzle",
    "Horror",
    "Survival",
    "Battle Royale",
];

foreach($categorias as $cat){

    echo '
    <a
        href="?categoria='.$cat.'"
        class="block px-4 py-2 rounded-xl hover:bg-[#151d34] text-gray-300 text-sm transition-all"
    >
        '.$cat.'
    </a>
    ';
}

?>

</div>

<a
href="favorito.php"
class="flex items-center gap-4 hover:bg-[#151d34] px-5 py-4 rounded-2xl transition-all"
>
<i class="fa-solid fa-heart"></i>
Favoritos
</a>

<a
href="carrito.php"
class="flex items-center gap-4 hover:bg-[#151d34] px-5 py-4 rounded-2xl transition-all"
>
<i class="fa-solid fa-cart-shopping"></i>
Carrito
</a>

</nav>

</div>

</aside>

<main class="flex-1 p-6 overflow-hidden">

<div class="flex flex-col lg:flex-row justify-between items-center gap-5 mb-8">

<form method="GET" class="relative flex-1 w-full flex gap-4">

<input
type="text"
name="search"
value="<?php echo htmlspecialchars($search); ?>"
placeholder="Buscar juegos..."
class="w-full bg-[#0b1020] border border-purple-900/20 rounded-2xl py-4 px-6 outline-none focus:border-purple-500"
>

<select
name="orden"
class="bg-[#0b1020] border border-purple-900/20 rounded-2xl px-4"
>

<option value="">
Ordenar
</option>

<option value="fecha" <?php echo $orden == 'fecha' ? 'selected' : ''; ?>>
Fecha
</option>

<option value="popularidad" <?php echo $orden == 'popularidad' ? 'selected' : ''; ?>>
Popularidad
</option>

<option value="precio_asc" <?php echo $orden == 'precio_asc' ? 'selected' : ''; ?>>
Menor Precio
</option>

<option value="precio_desc" <?php echo $orden == 'precio_desc' ? 'selected' : ''; ?>>
Mayor Precio
</option>

</select>

<button
class="absolute right-5 top-4 text-gray-400"
>

<i class="fa-solid fa-magnifying-glass"></i>

</button>

</form>

<div class="flex items-center gap-5">

<a href="favorito.php" class="text-xl hover:text-pink-400 relative">

<i class="fa-solid fa-heart"></i>

<span
id="favCount"
class="absolute -top-2 -right-2 bg-pink-600 text-xs w-5 h-5 rounded-full flex items-center justify-center"
>

<?php echo count($_SESSION['favoritos']); ?>

</span>

</a>

<a href="carrito.php"
class="text-xl hover:text-purple-400 relative"
>

<i class="fa-solid fa-cart-shopping"></i>

<span
id="cartCount"
class="absolute -top-2 -right-2 bg-purple-600 text-xs w-5 h-5 rounded-full flex items-center justify-center"
>

<?php echo array_sum($_SESSION['carrito']); ?>

</span>

</a>

<button
onclick="openProfile()"
class="w-14 h-14 rounded-full bg-gradient-to-r from-purple-600 to-fuchsia-600 flex items-center justify-center font-black text-2xl hover:scale-110 transition-all"
>

<?php echo strtoupper(substr($_SESSION['usuario'] ?? 'U',0,1)); ?>

</button>

</div>

</div>

<section class="relative overflow-hidden rounded-[35px] h-[420px] border border-purple-900/20 bg-[#0b1020] mb-10">

<img
id="slideImage"
src="banner.png"
class="absolute inset-0 w-full h-full object-cover transition-all duration-700"
>

<div class="absolute inset-0 hero-shadow"></div>

<div class="relative z-10 p-10 max-w-[600px]">

<span class="bg-purple-600 px-5 py-2 rounded-full text-sm">
NUEVA TEMPORADA
</span>

<h1 class="text-7xl font-black mt-6 leading-none">

GAMER

<span class="text-purple-500 block">
UNIVERSE
</span>

</h1>

<p class="mt-5 text-gray-300 text-xl">
Explora videojuegos agregados por el administrador.
</p>

</div>

</section>

<section class="mb-10">

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6">

<?php while($game = $resultado->fetch_assoc()): 
    $array_imgs = explode(",", $game['imagen']);
    $imagen_mostrar = !empty($array_imgs[0]) ? $array_imgs[0] : 'https://via.placeholder.com/300x260';

    $mostrar_categoria = $categoria ? $categoria : 'General';
    $mostrar_popularidad = $game['popularidad'] ?? '0';
    
    // Evaluar si el producto cuenta con stock actual
    $stock_actual = isset($game['stock']) ? intval($game['stock']) : 0;
    $tiene_stock = $stock_actual > 0;

    // Preparamos el array y lo codificamos con flags de protección de comillas para JavaScript
    $datos_juego = [
        'nombre' => $game['nombre'],
        'imagen' => $imagen_mostrar,
        'categoria' => $mostrar_categoria,
        'popularidad' => $mostrar_popularidad,
        'stock' => $stock_actual,
        'precio' => number_format($game['precio'], 2)
    ];
    $json_seguro = htmlspecialchars(json_encode($datos_juego, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
?>

<div class="card bg-[#0b1020] rounded-[30px] overflow-hidden border <?php echo $tiene_stock ? 'border-purple-900/20 hover:border-purple-500' : 'border-red-900/40 sin-stock-card opacity-60'; ?>">

<div class="relative h-[260px] overflow-hidden cursor-pointer" onclick="openGameModal(<?php echo $json_seguro; ?>)">

<img
src="<?php echo $imagen_mostrar; ?>"
class="w-full h-full object-cover <?php echo $tiene_stock ? 'hover:scale-110' : ''; ?> transition-all duration-500"
>

<?php if(!$tiene_stock){ ?>
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center">
        <span class="bg-red-600 text-white font-black px-6 py-2 rounded-full text-sm tracking-wider uppercase shadow-lg shadow-red-600/30">
            Sin Stock
        </span>
    </div>
<?php } ?>

<button
onclick="event.stopPropagation(); toggleFavorite(<?php echo $game['id_producto']; ?>, this)"
class="absolute top-4 right-4 bg-black/60 backdrop-blur-md w-11 h-11 rounded-full hover:bg-pink-600 transition-all flex items-center justify-center z-10"
>

<i class="fa-solid fa-heart <?php echo in_array($game['id_producto'], $_SESSION['favoritos']) ? 'text-pink-500' : 'text-white'; ?>"></i>

</button>

</div>

<div class="p-5">

<h3 class="text-2xl font-bold leading-tight cursor-pointer hover:text-purple-400 transition-colors" onclick="openGameModal(<?php echo $json_seguro; ?>)">

<?php echo htmlspecialchars($game['nombre']); ?>

</h3>

<p class="text-gray-400 mt-2 text-sm">

    🎮  <?php echo htmlspecialchars($mostrar_categoria); ?>

</p>

<p class="text-gray-400 text-sm">

🔥 Popularidad: <?php echo htmlspecialchars($mostrar_popularidad); ?>

</p>

<div class="mt-5">

<h2 class="text-3xl font-black <?php echo $tiene_stock ? 'text-green-400' : 'text-gray-500'; ?>">

Bs. <?php echo number_format($game['precio'], 2); ?>

</h2>

</div>

<div class="flex gap-3 mt-6">

<?php if($tiene_stock){ ?>
    <button
    onclick="addCart(<?php echo $game['id_producto']; ?>, this)"
    class="flex-1 text-center py-3 rounded-2xl bg-gradient-to-r from-purple-600 to-fuchsia-600 font-semibold hover:scale-105 transition-all"
    >
    Agregar al carrito
    </button>
<?php } else { ?>
    <button
    disabled
    class="flex-1 text-center py-3 rounded-2xl bg-[#191f35] text-gray-500 font-semibold cursor-not-allowed"
    >
    No disponible
    </button>
<?php } ?>

</div>

</div>

</div>

<?php endwhile; ?>

</div>

</section>

</main>
</div>

<!-- FOOTER INTEGRADO -->
<footer class="mt-auto border-t border-purple-900/20 bg-[#0b1020] py-10 px-6">
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-6">
        <div class="text-center md:text-left">
            <h2 class="text-2xl font-black text-white">GAMER <span class="text-purple-500">UNIVERSE</span></h2>
            <p class="text-gray-500 text-sm mt-2">Tu destino definitivo para los mejores videojuegos.</p>
        </div>
        <div class="flex gap-8 text-sm text-gray-400">
            <a href="usuario.php" class="hover:text-purple-400 transition-colors">Inicio</a>
            <a href="favorito.php" class="hover:text-purple-400 transition-colors">Favoritos</a>
            <a href="carrito.php" class="hover:text-purple-400 transition-colors">Carrito</a>
        </div>
        <div class="text-gray-600 text-xs">
            &copy; <?php echo date("Y"); ?> Gamer Universe. Todos los derechos reservados.
        </div>
    </div>
</footer>

</div> <!-- Fin del container principal -->

<div id="gameModal" class="fixed inset-0 bg-black/80 hidden items-center justify-center z-50 backdrop-blur-sm p-4">
    <div class="bg-[#0b1020] w-full max-w-[460px] rounded-[30px] border border-purple-500/30 overflow-hidden relative shadow-2xl glow">
        <button onclick="closeGameModal()" class="absolute top-4 right-4 z-10 bg-black/60 text-gray-400 hover:text-white text-3xl w-10 h-10 rounded-full flex items-center justify-center">×</button>
        
        <div class="h-[250px] w-full relative">
            <img id="modalGameImg" src="" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-[#0b1020] via-[#0b1020]/30 to-transparent"></div>
        </div>

        <div class="p-6 -mt-8 relative z-10">
            <h2 id="modalGameTitle" class="text-3xl font-black mb-2 text-white leading-tight"></h2>
            <span id="modalGameCategory" class="bg-purple-600/20 text-purple-400 border border-purple-500/20 px-4 py-1 rounded-full text-xs font-bold tracking-wider uppercase"></span>
            
            <div class="grid grid-cols-2 gap-4 mt-6">
                <div class="bg-[#131c35] p-4 rounded-2xl border border-purple-900/10">
                    <p class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">🔥 Popularidad</p>
                    <h3 id="modalGamePop" class="font-black text-xl text-fuchsia-400"></h3>
                </div>
                <div class="bg-[#131c35] p-4 rounded-2xl border border-purple-900/10">
                    <p class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">📦 Stock Actual</p>
                    <h3 id="modalGameStock" class="font-black text-xl"></h3>
                </div>
            </div>
            
            <div class="mt-5 flex justify-between items-center bg-[#131c35]/40 p-4 rounded-2xl border border-purple-900/10">
                <span class="text-gray-400 font-bold">Precio Unitario:</span>
                <span id="modalGamePrice" class="text-2xl font-black text-green-400"></span>
            </div>
        </div>
    </div>
</div>

<div
id="profileModal"
class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50"
>

<div class="bg-[#0b1020] w-[400px] rounded-[30px] border border-purple-900/20 p-8 relative">

<button
onclick="closeProfile()"
class="absolute top-5 right-5 text-gray-400 hover:text-white text-2xl"
>
×
</button>

<div class="flex justify-center mb-5">

<div class="w-24 h-24 rounded-full bg-gradient-to-r from-purple-600 to-fuchsia-600 flex items-center justify-center text-4xl font-black">

<?php echo strtoupper(substr($_SESSION['usuario'] ?? 'U',0,1)); ?>

</div>

</div>

<h2 class="text-3xl font-black text-center mb-2">

<?php echo htmlspecialchars($_SESSION['usuario'] ?? 'Invitado'); ?>

</h2>

<p class="text-center text-purple-400 mb-8">

Perfil del Usuario

</p>

<div class="space-y-4">

<div class="bg-[#131c35] p-4 rounded-2xl">

<p class="text-gray-400 text-sm">
Correo
</p>

<h3 class="font-bold">

<?php echo htmlspecialchars($_SESSION['correo'] ?? 'Sin correo'); ?>

</h3>

</div>

<div class="bg-[#131c35] p-4 rounded-2xl">

<p class="text-gray-400 text-sm">
Rol
</p>

<h3 class="font-bold capitalize">

<?php echo htmlspecialchars($_SESSION['rol'] ?? 'usuario'); ?>

</h3>

</div>

</div>

<a
href="index.php"
class="mt-8 block text-center bg-gradient-to-r from-red-600 to-pink-600 py-4 rounded-2xl font-bold hover:scale-105 transition-all"
>

Cerrar Sesión

</a>

</div>

</div>

<script>

const images = [
"banner.png",
"banner2.png",
"banner3.png",
"banner4.png"
];

let current = 0;

const slide = document.getElementById("slideImage");

if (slide) {
    setInterval(()=>{
        current++;
        if(current >= images.length){
            current = 0;
        }
        slide.src = images[current];
    },4000);
}

function openGameModal(data) {
    document.getElementById("modalGameTitle").innerText = data.nombre;
    document.getElementById("modalGameImg").src = data.imagen;
    document.getElementById("modalGameCategory").innerText = "🎮 " + data.categoria;
    document.getElementById("modalGamePop").innerText = data.popularidad + " pts";
    document.getElementById("modalGamePrice").innerText = "Bs. " + data.precio;
    
    const stockContainer = document.getElementById("modalGameStock");
    if(parseInt(data.stock) <= 0) {
        stockContainer.innerText = "Agotado";
        stockContainer.className = "font-black text-xl text-red-500";
    } else {
        stockContainer.innerText = data.stock + " u.";
        stockContainer.className = "font-black text-xl text-green-400";
    }

    document.getElementById("gameModal").classList.remove("hidden");
    document.getElementById("gameModal").classList.add("flex");
}

function closeGameModal() {
    document.getElementById("gameModal").classList.remove("flex");
    document.getElementById("gameModal").classList.add("hidden");
}

function toggleFavorite(id, button){

fetch(`?ajaxFav=${id}`)
.then(res => res.json())
.then(data => {

const icon = button.querySelector("i");

if(data.active){
    icon.classList.remove("text-white");
    icon.classList.add("text-pink-500");
}else{
    icon.classList.remove("text-pink-500");
    icon.classList.add("text-white");
}

document.getElementById("favCount").innerText = data.count;

});
}

function addCart(id, button){

    fetch(`?ajaxCart=${id}`)
    .then(res => res.json())
    .then(data => {
        if(data.success){
            document.getElementById("cartCount").innerText = data.count;
            
            const textoOriginal = button.innerText;
            button.innerText = "¡Agregado! 🎮";
            button.classList.remove("from-purple-600", "to-fuchsia-600");
            button.classList.add("bg-green-600");
            
            setTimeout(() => {
                button.innerText = textoOriginal;
                button.classList.add("from-purple-600", "to-fuchsia-600");
                button.classList.remove("bg-green-600");
            }, 1500);
        } else {
            alert(data.message || "No se pudo agregar el producto por falta de stock.");
        }
    })
    .catch(error => {
        console.error("Error al procesar el carrito:", error);
    });
}

function toggleCategories(){

const menu = document.getElementById("categoryMenu");
menu.classList.toggle("active");
}

function openProfile(){

document.getElementById("profileModal").classList.remove("hidden");
document.getElementById("profileModal").classList.add("flex");
}

function closeProfile(){

document.getElementById("profileModal").classList.remove("flex");
document.getElementById("profileModal").classList.add("hidden");
}

</script>

</body>
</html>