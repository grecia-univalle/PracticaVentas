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
| AJAX CARRITO
|--------------------------------------------------------------------------
*/

if(isset($_GET['ajaxCart'])){

    $id = intval($_GET['ajaxCart']);

    if($id > 0){

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
| FILTROS
|--------------------------------------------------------------------------
*/

$search = $_GET['search'] ?? '';
$orden = $_GET['orden'] ?? '';
$categoria = $_GET['categoria'] ?? '';

$sql = "SELECT * FROM producto WHERE estado='activo'";

if(!empty($search)){

    $sql .= " AND nombre LIKE '%$search%'";
}

if(!empty($categoria)){

    $sql .= " AND categoria='$categoria'";
}

switch($orden){

    case "precio_asc":
        $sql .= " ORDER BY precio ASC";
    break;

    case "precio_desc":
        $sql .= " ORDER BY precio DESC";
    break;

    case "popularidad":
        $sql .= " ORDER BY popularidad DESC";
    break;

    case "fecha":
        $sql .= " ORDER BY fecha_registro DESC";
    break;

    default:
        $sql .= " ORDER BY id_producto DESC";
}

$resultado = $conn->query($sql);

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

.card:hover{
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

<div class="min-h-screen flex bg-[#050816] text-white">

<!-- SIDEBAR -->

<aside class="w-[280px] bg-[#0b1020] border-r border-purple-900/20 hidden lg:flex flex-col justify-between">

<div>

<!-- LOGO -->

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

<!-- MENU -->

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

<!-- CATEGORIAS -->

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

<!-- SUBMENU -->

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

<!-- MAIN -->

<main class="flex-1 p-6 overflow-hidden">

<!-- TOPBAR -->

<div class="flex flex-col lg:flex-row justify-between items-center gap-5 mb-8">

<!-- BUSCADOR -->

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

<option value="fecha">
Fecha
</option>

<option value="popularidad">
Popularidad
</option>

<option value="precio_asc">
Menor Precio
</option>

<option value="precio_desc">
Mayor Precio
</option>

</select>

<button
class="absolute right-5 top-4 text-gray-400"
>

<i class="fa-solid fa-magnifying-glass"></i>

</button>

</form>

<!-- ICONOS -->

<div class="flex items-center gap-5">

<!-- FAVORITOS -->

<a href="favoritos.php" class="text-xl hover:text-pink-400 relative">

<i class="fa-solid fa-heart"></i>

<span
id="favCount"
class="absolute -top-2 -right-2 bg-pink-600 text-xs w-5 h-5 rounded-full flex items-center justify-center"
>

<?php echo count($_SESSION['favoritos']); ?>

</span>

</a>

<!-- CARRITO -->

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

<!-- PERFIL -->

<button
onclick="openProfile()"
class="w-14 h-14 rounded-full bg-gradient-to-r from-purple-600 to-fuchsia-600 flex items-center justify-center font-black text-2xl hover:scale-110 transition-all"
>

<?php echo strtoupper(substr($_SESSION['usuario'],0,1)); ?>

</button>

</div>

</div>

<!-- HERO -->

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

<!-- CATALOGO -->

<section class="mb-10">

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6">

<?php while($game = $resultado->fetch_assoc()): ?>

<div class="card bg-[#0b1020] rounded-[30px] overflow-hidden border border-purple-900/20 hover:border-purple-500">

<div class="relative h-[260px] overflow-hidden">

<img
src="<?php echo $game['imagen']; ?>"
class="w-full h-full object-cover hover:scale-110 transition-all duration-500"
>

<button
onclick="toggleFavorite(<?php echo $game['id_producto']; ?>, this)"
class="absolute top-4 right-4 bg-black/60 backdrop-blur-md w-11 h-11 rounded-full hover:bg-pink-600 transition-all flex items-center justify-center"
>

<i class="fa-solid fa-heart <?php echo in_array($game['id_producto'], $_SESSION['favoritos']) ? 'text-pink-500' : 'text-white'; ?>"></i>

</button>

</div>

<div class="p-5">

<h3 class="text-2xl font-bold leading-tight">

<?php echo $game['nombre']; ?>

</h3>

<p class="text-gray-400 mt-2 text-sm">

🎮 <?php echo $game['categoria']; ?>

</p>

<p class="text-gray-400 text-sm">

🔥 Popularidad: <?php echo $game['popularidad']; ?>

</p>

<div class="mt-5">

<h2 class="text-3xl font-black text-green-400">

Bs. <?php echo $game['precio']; ?>

</h2>

</div>

<div class="flex gap-3 mt-6">

<button
onclick="addCart(<?php echo $game['id_producto']; ?>)"
class="flex-1 text-center py-3 rounded-2xl bg-gradient-to-r from-purple-600 to-fuchsia-600 font-semibold hover:scale-105 transition-all"
>

Agregar al carrito

</button>

</div>

</div>

</div>

<?php endwhile; ?>

</div>

</section>

</main>

</div>

<!-- MODAL PERFIL -->

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

<?php echo strtoupper(substr($_SESSION['usuario'],0,1)); ?>

</div>

</div>

<h2 class="text-3xl font-black text-center mb-2">

<?php echo $_SESSION['usuario']; ?>

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

<?php echo $_SESSION['correo']; ?>

</h3>

</div>

<div class="bg-[#131c35] p-4 rounded-2xl">

<p class="text-gray-400 text-sm">
Rol
</p>

<h3 class="font-bold capitalize">

<?php echo $_SESSION['rol']; ?>



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

setInterval(()=>{

current++;

if(current >= images.length){

current = 0;
}

slide.src = images[current];

},4000);

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

function addCart(id){

fetch(`?ajaxCart=${id}`)

.then(res => res.json())

.then(data => {

document.getElementById("cartCount").innerText = data.count;

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