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
| BUSCADOR
|--------------------------------------------------------------------------
*/

$search = $_GET['search'] ?? '';

/*
|--------------------------------------------------------------------------
| API JUEGOS POPULARES
|--------------------------------------------------------------------------
*/

$url = "https://api.rawg.io/api/games?key=$apiKey&page_size=20&ordering=-metacritic";

if(!empty($search)){

    $url .= "&search=" . urlencode($search);
}

$curl = curl_init();

curl_setopt_array($curl,[

    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false

]);

$response = curl_exec($curl);

curl_close($curl);

$data = json_decode($response,true);

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>

<title>Juegos Populares</title>

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
                class="flex items-center gap-4 hover:bg-[#151d34] px-5 py-4 rounded-2xl transition-all"
            >
                <i class="fa-solid fa-house"></i>
                Inicio
            </a>

            <a
                href="popular.php"
                class="flex items-center gap-4 bg-gradient-to-r from-purple-600 to-fuchsia-600 px-5 py-4 rounded-2xl"
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

        <form method="GET" class="relative flex-1 w-full">

            <input
                type="text"
                name="search"
                list="games"
                value="<?php echo htmlspecialchars($search); ?>"
                placeholder="Buscar juegos populares..."
                class="w-full bg-[#0b1020] border border-purple-900/20 rounded-2xl py-4 px-6 outline-none focus:border-purple-500"
            >

            <datalist id="games">

                <?php

                if(isset($data['results'])){

                    foreach($data['results'] as $g){

                        echo "<option value='{$g['name']}'>";
                    }
                }

                ?>

            </datalist>

            <button
                class="absolute right-5 top-4 text-gray-400"
            >
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>

        </form>

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

    <!-- TITULO -->

    <div class="mb-10">

        <h1 class="text-5xl font-black">
            🔥 Juegos Populares
        </h1>

        <p class="text-gray-400 mt-3">
            Descubre los videojuegos más populares y mejor valorados.
        </p>

    </div>

    <!-- JUEGOS -->

    <section class="mb-10">

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6">

            <?php if(isset($data['results'])): ?>

                <?php foreach($data['results'] as $game): ?>

                    <?php

                    $precio = rand(150,500);

                    ?>

                    <div class="card bg-[#0b1020] rounded-[30px] overflow-hidden border border-purple-900/20 hover:border-purple-500">

                        <!-- IMAGEN -->

                        <div class="relative h-[260px] overflow-hidden">

                            <img
                                src="<?php echo $game['background_image']; ?>"
                                class="w-full h-full object-cover hover:scale-110 transition-all duration-500"
                            >

                            <!-- FAVORITO -->

                            <button
                                onclick="toggleFavorite(<?php echo $game['id']; ?>, this)"
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

                                <div class="bg-orange-600/20 text-orange-400 px-3 py-1 rounded-xl text-sm whitespace-nowrap">

                                    ⭐ <?php echo $game['rating']; ?>

                                </div>

                            </div>

                            <!-- GENEROS -->

                            <div class="flex flex-wrap gap-2 mt-4">

                                <?php

                                if(isset($game['genres'])){

                                    foreach($game['genres'] as $genre){

                                        echo "
                                        <span class='bg-[#131c35] text-gray-300 px-3 py-1 rounded-xl text-xs'>
                                            {$genre['name']}
                                        </span>
                                        ";
                                    }

                                }

                                ?>

                            </div>

                            <!-- DESCRIPCIÓN -->

                            <div class="mt-4">

                                <p class="text-gray-400 text-sm">

                                    Uno de los videojuegos más populares del momento con excelentes valoraciones.

                                </p>

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

                            <!-- BOTONES -->

                            <div class="flex gap-3 mt-6">

                                <!-- VER JUEGO -->

                                <a
                                    href="card.php?id=<?php echo $game['id']; ?>"
                                    class="flex-1 text-center py-3 rounded-2xl bg-gradient-to-r from-purple-600 to-fuchsia-600 font-semibold hover:scale-105 transition-all"
                                >
                                    Ver Juego
                                </a>

                                <!-- CARRITO -->

                                <button
                                    onclick="addCart(<?php echo $game['id']; ?>)"
                                    class="w-14 flex items-center justify-center rounded-2xl bg-[#131c35] hover:bg-purple-600 transition-all"
                                >

                                    <i class="fa-solid fa-cart-shopping"></i>

                                </button>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="text-red-400 text-2xl font-bold">

                    Error cargando la API RAWG

                </div>

            <?php endif; ?>

        </div>

    </section>

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

        window.location.href = "carrito.php";
    });
}

</script>

</body>
</html>