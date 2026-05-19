<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors',1);

/*
|--------------------------------------------------------------------------
| FAVORITOS
|--------------------------------------------------------------------------
*/

if(!isset($_SESSION['favoritos'])){

    $_SESSION['favoritos'] = [];
}

$favoritos = $_SESSION['favoritos'];

/*
|--------------------------------------------------------------------------
| API RAWG
|--------------------------------------------------------------------------
*/

$apiKey = "87e8aea689ab4258b2cf63a7ac56e986";

/*
|--------------------------------------------------------------------------
| ELIMINAR FAVORITO
|--------------------------------------------------------------------------
*/

if(isset($_GET['remove'])){

    $id = intval($_GET['remove']);

    $key = array_search($id, $_SESSION['favoritos']);

    if($key !== false){

        unset($_SESSION['favoritos'][$key]);

        $_SESSION['favoritos'] = array_values($_SESSION['favoritos']);
    }

    header("Location: favorito.php");
    exit;
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
| AGREGAR AL CARRITO Y REDIRECCIONAR
|--------------------------------------------------------------------------
*/

if(isset($_GET['add'])){

    $id = intval($_GET['add']);

    if($id > 0){

        if(isset($_SESSION['carrito'][$id])){

            $_SESSION['carrito'][$id]++;

        }else{

            $_SESSION['carrito'][$id] = 1;
        }
    }

    header("Location: carrito.php");
    exit;
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

<title>Favoritos Gamer</title>

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
    border-color:#ec4899;
}

</style>

</head>

<body>

<div class="min-h-screen p-8">

    <!-- HEADER -->

    <div class="flex justify-between items-center mb-10">

        <div>

            <h1 class="text-5xl font-black flex items-center gap-4">

                <i class="fa-solid fa-heart text-pink-500"></i>

                Favoritos

            </h1>

            <p class="text-gray-400 mt-2">
                Tus videojuegos favoritos
            </p>

        </div>

        <!-- VOLVER -->

        <a

        href="usuario.php"

        class="bg-[#131c35] hover:bg-pink-600 px-6 py-3 rounded-2xl transition-all"
        >

            Volver

        </a>

    </div>

    <!-- CONTENIDO -->

    <?php if(empty($favoritos)): ?>

        <!-- VACIO -->

        <div class="bg-[#0b1020] rounded-[35px] p-20 text-center border border-pink-900/20">

            <i class="fa-solid fa-heart text-8xl text-pink-500"></i>

            <h2 class="text-4xl font-black mt-6">
                No tienes favoritos
            </h2>

            <p class="text-gray-400 mt-4 text-lg">
                Agrega videojuegos a tu lista de favoritos
            </p>

        </div>

    <?php else: ?>

        <!-- GRID -->

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6">

            <?php

            foreach($favoritos as $id){

                $id = intval($id);

                if($id <= 0){

                    continue;
                }

                $url = "https://api.rawg.io/api/games/$id?key=$apiKey";

                $response = @file_get_contents($url);

                if($response === false){

                    continue;
                }

                $game = json_decode($response,true);

                if(!$game || isset($game['detail'])){

                    continue;
                }

                $precio = rand(80,400);

            ?>

            <!-- CARD -->

            <div class="card bg-[#0b1020] rounded-[30px] overflow-hidden border border-pink-900/20">

                <!-- IMAGEN -->

                <div class="relative h-[260px] overflow-hidden">

                    <img
                        src="<?php echo $game['background_image']; ?>"
                        class="w-full h-full object-cover hover:scale-110 transition-all duration-500"
                    >

                    <!-- CORAZON -->

                    <div class="absolute top-4 right-4 bg-pink-600 w-11 h-11 rounded-full flex items-center justify-center">

                        <i class="fa-solid fa-heart"></i>

                    </div>

                </div>

                <!-- CONTENIDO -->

                <div class="p-5">

                    <!-- TITULO -->

                    <div class="flex justify-between items-start gap-3">

                        <div>

                            <h2 class="text-2xl font-bold leading-tight">

                                <?php echo $game['name']; ?>

                            </h2>

                            <p class="text-gray-400 mt-2 text-sm">

                                📅 <?php echo $game['released']; ?>

                            </p>

                        </div>

                        <!-- RATING -->

                        <div class="bg-pink-600/20 text-pink-400 px-3 py-1 rounded-xl text-sm">

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

                    <!-- DESCRIPCION -->

                    <div class="mt-4">

                        <p class="text-gray-400 text-sm">

                            Videojuego épico con gráficos increíbles y aventuras impresionantes.

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

                    <!-- BOTON CARRITO -->

                    <div class="mt-6">

                        <a

                        href="?add=<?php echo $id; ?>"

                        class="w-full flex items-center justify-center gap-3 py-3 rounded-2xl bg-gradient-to-r from-purple-600 to-pink-600 font-semibold hover:scale-105 transition-all"
                        >

                            <i class="fa-solid fa-cart-shopping"></i>

                            Agregar al carrito

                        </a>

                    </div>

                    <!-- ELIMINAR -->

                    <a

                    href="?remove=<?php echo $id; ?>"

                    class="mt-4 w-full block text-center bg-red-500 hover:bg-red-600 py-3 rounded-2xl transition-all"
                    >

                        Eliminar de favoritos

                    </a>

                </div>

            </div>

            <?php } ?>

        </div>

    <?php endif; ?>

</div>

</body>
</html>