<?php

session_start();

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
| API RAWG
|--------------------------------------------------------------------------
*/

$apiKey = "87e8aea689ab4258b2cf63a7ac56e986";

/*
|--------------------------------------------------------------------------
| CATEGORIA SELECCIONADA
|--------------------------------------------------------------------------
*/

$genre = $_GET['genre'] ?? 'action';

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
| OBTENER GENEROS
|--------------------------------------------------------------------------
*/

$genresUrl = "https://api.rawg.io/api/genres?key=$apiKey";

$genresJson = file_get_contents($genresUrl);

$genresData = json_decode($genresJson, true);

/*
|--------------------------------------------------------------------------
| OBTENER JUEGOS POR CATEGORIA
|--------------------------------------------------------------------------
*/

$url = "https://api.rawg.io/api/games?key=$apiKey&genres=$genre&page_size=20";

$curl = curl_init();

curl_setopt_array($curl,[

    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false

]);

$response = curl_exec($curl);

curl_close($curl);

$data = json_decode($response,true);

/*
|--------------------------------------------------------------------------
| NOMBRE CATEGORIA
|--------------------------------------------------------------------------
*/

$categoryName = ucfirst($genre);

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?php echo $categoryName; ?> | Gamer Universe</title>

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

<aside class="w-[280px] bg-[#0b1020] border-r border-purple-900/20 hidden lg:flex flex-col">

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
            class="flex items-center gap-4 hover:bg-[#151d34] px-5 py-4 rounded-2xl transition-all"
        >
            <i class="fa-solid fa-house"></i>
            Inicio
        </a>

        <a
            href="popular.php"
            class="flex items-center gap-4 hover:bg-[#151d34] px-5 py-4 rounded-2xl transition-all"
        >
            <i class="fa-solid fa-fire"></i>
            Juegos Populares
        </a>

        <a
            href="gratisgame.php"
            class="flex items-center gap-4 hover:bg-[#151d34] px-5 py-4 rounded-2xl transition-all"
        >
            <i class="fa-solid fa-gift"></i>
            Juegos Gratis
        </a>

        <!-- CATEGORIAS -->

        <button
            onclick="toggleCategories()"
            class="w-full flex items-center justify-between bg-gradient-to-r from-purple-600 to-fuchsia-600 px-5 py-4 rounded-2xl transition-all"
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
            class="category-menu active ml-5 space-y-2"
        >

            <?php

            if(isset($genresData['results'])){

                usort($genresData['results'], function($a, $b){

                    return $b['games_count'] - $a['games_count'];

                });

                $topGenres = array_slice($genresData['results'], 0, 5);

                foreach($topGenres as $g){

                    echo '

                    <a
                        href="categoria.php?genre='.$g['slug'].'"
                        class="block px-4 py-2 rounded-xl hover:bg-[#151d34] text-gray-300 text-sm transition-all"
                    >

                        <div class="flex items-center justify-between">

                            <span>'.$g['name'].'</span>

                            <span class="text-purple-400 text-xs">
                                '.$g['games_count'].'
                            </span>

                        </div>

                    </a>

                    ';
                }
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

</aside>

<!-- MAIN -->

<main class="flex-1 p-6">

    <!-- TOPBAR -->

    <div class="flex justify-between items-center mb-10">

        <div>

            <h1 class="text-5xl font-black">

                <?php echo strtoupper($categoryName); ?>

            </h1>

            <p class="text-gray-400 mt-2">
                Juegos disponibles de esta categoría
            </p>

        </div>

        <!-- ICONOS -->

        <div class="flex items-center gap-5">

            <!-- FAVORITOS -->

            <a
                href="favorito.php"
                class="text-xl hover:text-pink-400 relative"
            >

                <i class="fa-solid fa-heart"></i>

                <span
                    id="favCount"
                    class="absolute -top-2 -right-2 bg-pink-600 text-xs w-5 h-5 rounded-full flex items-center justify-center"
                >

                    <?php echo count($_SESSION['favoritos']); ?>

                </span>

            </a>

            <!-- CARRITO -->

            <a
                href="carrito.php"
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

        </div>

    </div>

    <!-- CARDS -->

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6">

        <?php if(isset($data['results'])): ?>

            <?php foreach($data['results'] as $game): ?>

                <?php

                $precio = rand(80,400);

                ?>

                <!-- CARD -->

                <div
                    onclick="window.location='card.php?id=<?php echo $game['id']; ?>'"
                    class="card cursor-pointer bg-[#0b1020] rounded-[30px] overflow-hidden border border-purple-900/20 hover:border-purple-500"
                >

                    <!-- IMAGEN -->

                    <div class="relative h-[260px] overflow-hidden">

                        <img
                            src="<?php echo $game['background_image']; ?>"
                            class="w-full h-full object-cover hover:scale-110 transition-all duration-500"
                        >

                        <!-- FAVORITO -->

                        <button
                            onclick="event.stopPropagation(); toggleFavorite(<?php echo $game['id']; ?>, this)"
                            class="absolute top-4 right-4 bg-black/60 backdrop-blur-md w-11 h-11 rounded-full hover:bg-pink-600 transition-all flex items-center justify-center"
                        >

                            <i class="fa-solid fa-heart <?php echo in_array($game['id'], $_SESSION['favoritos']) ? 'text-pink-500' : 'text-white'; ?>"></i>

                        </button>

                    </div>

                    <!-- CONTENIDO -->

                    <div class="p-5">

                        <div class="flex justify-between items-start gap-3">

                            <div>

                                <h3 class="text-2xl font-bold leading-tight">

                                    <?php echo $game['name']; ?>

                                </h3>

                                <p class="text-gray-400 mt-2 text-sm">

                                    📅 <?php echo $game['released']; ?>

                                </p>

                            </div>

                            <div class="bg-purple-600/20 text-purple-400 px-3 py-1 rounded-xl text-sm whitespace-nowrap">

                                ⭐ <?php echo $game['rating']; ?>

                            </div>

                        </div>

                        <!-- GENEROS -->

                        <div class="flex flex-wrap gap-2 mt-4">

                            <?php

                            if(isset($game['genres'])){

                                foreach($game['genres'] as $genreItem){

                                    echo "
                                    <span class='bg-[#131c35] text-gray-300 px-3 py-1 rounded-xl text-xs'>
                                        {$genreItem['name']}
                                    </span>
                                    ";
                                }

                            }

                            ?>

                        </div>

                        <!-- PRECIO -->

                        <div class="mt-5">

                            <p class="text-gray-400 text-sm">
                                Precio
                            </p>

                            <h2 class="text-3xl font-black text-green-400">

                                Bs. <?php echo $precio; ?>

                            </h2>

                        </div>

                        <!-- BOTON CARRITO -->

                        <div class="mt-6">

                            <button
                                onclick="event.stopPropagation(); addCart(<?php echo $game['id']; ?>)"
                                class="w-full py-3 rounded-2xl bg-gradient-to-r from-purple-600 to-fuchsia-600 font-semibold hover:scale-105 transition-all"
                            >

                                <i class="fa-solid fa-cart-shopping mr-2"></i>

                                Agregar al carrito

                            </button>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</main>

</div>

<!-- SCRIPT -->

<script>

/*
|--------------------------------------------------------------------------
| FAVORITOS AJAX
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| CARRITO AJAX
|--------------------------------------------------------------------------
*/

function addCart(id){

    fetch(`?ajaxCart=${id}`)

    .then(res => res.json())

    .then(data => {

        document.getElementById("cartCount").innerText = data.count;
    });
}

/*
|--------------------------------------------------------------------------
| MENU CATEGORIAS
|--------------------------------------------------------------------------
*/

function toggleCategories(){

    const menu = document.getElementById("categoryMenu");

    menu.classList.toggle("active");
}

</script>

</body>
</html>