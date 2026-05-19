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
| OBTENER ID DEL JUEGO
|--------------------------------------------------------------------------
*/

$id = $_GET['id'] ?? 0;

if(!$id){

    die("Juego no encontrado");
}

/*
|--------------------------------------------------------------------------
| AJAX FAVORITOS
|--------------------------------------------------------------------------
*/

if(isset($_GET['ajaxFav'])){

    $gameId = intval($_GET['ajaxFav']);

    if($gameId > 0){

        if(in_array($gameId, $_SESSION['favoritos'])){

            $_SESSION['favoritos'] = array_diff(
                $_SESSION['favoritos'],
                [$gameId]
            );

            $active = false;

        }else{

            $_SESSION['favoritos'][] = $gameId;

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

    $gameId = intval($_GET['ajaxCart']);

    if($gameId > 0){

        if(isset($_SESSION['carrito'][$gameId])){

            $_SESSION['carrito'][$gameId]++;

        }else{

            $_SESSION['carrito'][$gameId] = 1;
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
| OBTENER DATOS DEL JUEGO
|--------------------------------------------------------------------------
*/

$url = "https://api.rawg.io/api/games/$id?key=$apiKey";

$curl = curl_init();

curl_setopt_array($curl,[

    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false

]);

$response = curl_exec($curl);

curl_close($curl);

$game = json_decode($response, true);

/*
|--------------------------------------------------------------------------
| PRECIO RANDOM
|--------------------------------------------------------------------------
*/

$precio = rand(80,400);

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>

<title><?php echo $game['name']; ?></title>

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

.glow{
    box-shadow:0 0 30px rgba(168,85,247,.4);
}

</style>

</head>

<body>

<div class="min-h-screen p-6">

    <!-- TOPBAR -->

    <div class="flex justify-between items-center mb-8">

        <!-- VOLVER -->

        <a
            href="usuario.php"
            class="flex items-center gap-3 bg-[#0b1020] hover:bg-purple-600 transition-all px-6 py-4 rounded-2xl"
        >

            <i class="fa-solid fa-arrow-left"></i>

            Volver al Inicio

        </a>

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

    <!-- CARD PRINCIPAL -->

    <div class="bg-[#0b1020] rounded-[35px] overflow-hidden border border-purple-900/20">

        <!-- IMAGEN -->

        <div class="relative h-[500px] overflow-hidden">

            <img
                src="<?php echo $game['background_image']; ?>"
                class="w-full h-full object-cover"
            >

            <div class="absolute inset-0 bg-gradient-to-t from-[#050816] via-black/30 to-transparent"></div>

            <!-- FAVORITO -->

            <button
                onclick="toggleFavorite(<?php echo $game['id']; ?>, this)"
                class="absolute top-6 right-6 bg-black/60 backdrop-blur-md w-14 h-14 rounded-full hover:bg-pink-600 transition-all flex items-center justify-center"
            >

                <i class="fa-solid fa-heart <?php echo in_array($game['id'], $_SESSION['favoritos']) ? 'text-pink-500' : 'text-white'; ?>"></i>

            </button>

        </div>

        <!-- CONTENIDO -->

        <div class="p-8">

            <!-- TITULO -->

            <div class="flex flex-col lg:flex-row justify-between gap-5">

                <div>

                    <h1 class="text-5xl font-black">

                        <?php echo $game['name']; ?>

                    </h1>

                    <p class="text-gray-400 mt-3 text-lg">

                        📅 Lanzamiento:
                        <?php echo $game['released']; ?>

                    </p>

                </div>

                <div class="bg-purple-600/20 text-purple-400 px-5 py-3 rounded-2xl h-fit text-xl">

                    ⭐ <?php echo $game['rating']; ?>

                </div>

            </div>

            <!-- GENEROS -->

            <div class="flex flex-wrap gap-3 mt-6">

                <?php

                if(isset($game['genres'])){

                    foreach($game['genres'] as $genre){

                        echo "
                        <span class='bg-[#131c35] px-4 py-2 rounded-xl text-sm text-gray-300'>
                            {$genre['name']}
                        </span>
                        ";
                    }
                }

                ?>

            </div>

            <!-- DESCRIPCION -->

            <div class="mt-8">

                <h2 class="text-3xl font-bold mb-4">
                    Descripción
                </h2>

                <div class="text-gray-300 leading-8 text-lg">

                    <?php

                    if(isset($game['description_raw'])){

                        echo nl2br(substr($game['description_raw'],0,1000));

                    }else{

                        echo "No hay descripción disponible.";
                    }

                    ?>

                </div>

            </div>

            <!-- DATOS -->

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-10">

                <div class="bg-[#131c35] rounded-2xl p-6">

                    <p class="text-gray-400 mb-2">
                        Metacritic
                    </p>

                    <h3 class="text-3xl font-black text-green-400">

                        <?php echo $game['metacritic'] ?? 'N/A'; ?>

                    </h3>

                </div>

                <div class="bg-[#131c35] rounded-2xl p-6">

                    <p class="text-gray-400 mb-2">
                        Precio
                    </p>

                    <h3 class="text-3xl font-black text-purple-400">

                        Bs. <?php echo $precio; ?>

                    </h3>

                </div>

                <div class="bg-[#131c35] rounded-2xl p-6">

                    <p class="text-gray-400 mb-2">
                        Plataforma
                    </p>

                    <h3 class="text-xl font-bold text-cyan-400">

                        PC & Consolas

                    </h3>

                </div>

            </div>

            <!-- BOTONES -->

            <div class="flex flex-col sm:flex-row gap-4 mt-10">

                <!-- AGREGAR CARRITO -->

                <button
                    onclick="addCart(<?php echo $game['id']; ?>)"
                    class="flex-1 py-4 rounded-2xl bg-gradient-to-r from-purple-600 to-fuchsia-600 font-bold text-lg hover:scale-105 transition-all"
                >

                    <i class="fa-solid fa-cart-shopping mr-2"></i>

                    Agregar al Carrito

                </button>

                <!-- FAVORITOS -->

                <button
                    onclick="toggleFavorite(<?php echo $game['id']; ?>, document.getElementById('favBtn'))"
                    id="favBtn"
                    class="flex-1 py-4 rounded-2xl bg-[#131c35] hover:bg-pink-600 transition-all font-bold text-lg"
                >

                    <i class="fa-solid fa-heart mr-2"></i>

                    Favoritos

                </button>

            </div>

        </div>

    </div>

</div>

<!-- SCRIPT -->

<script>

/*
|--------------------------------------------------------------------------
| FAVORITOS AJAX
|--------------------------------------------------------------------------
*/

function toggleFavorite(id, button){

    fetch(`?id=<?php echo $id; ?>&ajaxFav=${id}`)

    .then(res => res.json())

    .then(data => {

        document.getElementById("favCount").innerText = data.count;

        const icons = document.querySelectorAll(".fa-heart");

        icons.forEach(icon => {

            if(data.active){

                icon.classList.remove("text-white");
                icon.classList.add("text-pink-500");

            }else{

                icon.classList.remove("text-pink-500");
                icon.classList.add("text-white");
            }

        });

    });
}

/*
|--------------------------------------------------------------------------
| CARRITO AJAX
|--------------------------------------------------------------------------
*/

function addCart(id){

    fetch(`?id=<?php echo $id; ?>&ajaxCart=${id}`)

    .then(res => res.json())

    .then(data => {

        document.getElementById("cartCount").innerText = data.count;

        alert("Juego agregado al carrito");

    });
}

</script>

</body>
</html>