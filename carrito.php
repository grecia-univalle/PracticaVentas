<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors',1);

/*
|--------------------------------------------------------------------------
| CARRITO
|--------------------------------------------------------------------------
*/

if(!isset($_SESSION['carrito'])){
    $_SESSION['carrito'] = [];
}

if(!isset($_SESSION['historial'])){
    $_SESSION['historial'] = [];
}

$carrito = $_SESSION['carrito'];

/*
|--------------------------------------------------------------------------
| API RAWG
|--------------------------------------------------------------------------
*/

$apiKey = "87e8aea689ab4258b2cf63a7ac56e986";

/*
|--------------------------------------------------------------------------
| ACTUALIZAR CANTIDAD MANUAL
|--------------------------------------------------------------------------
*/

if(isset($_POST['update_cart'])){

    foreach($_POST['cantidades'] as $id => $cantidad){

        $id = intval($id);
        $cantidad = intval($cantidad);

        if($cantidad <= 0){

            unset($_SESSION['carrito'][$id]);

        }else{

            $_SESSION['carrito'][$id] = $cantidad;
        }
    }

    header("Location: carrito.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| ELIMINAR PRODUCTO
|--------------------------------------------------------------------------
*/

if(isset($_GET['remove'])){

    $id = intval($_GET['remove']);

    unset($_SESSION['carrito'][$id]);

    header("Location: carrito.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| FINALIZAR COMPRA
|--------------------------------------------------------------------------
*/

if(isset($_POST['comprar'])){

    $compra = [
        "fecha" => date("d/m/Y H:i"),
        "productos" => [],
        "total" => 0
    ];

    foreach($_SESSION['carrito'] as $id => $cantidad){

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

        $subtotal = $precio * $cantidad;

        $compra['productos'][] = [
            "nombre" => $game['name'],
            "cantidad" => $cantidad,
            "precio" => $precio,
            "subtotal" => $subtotal
        ];

        $compra['total'] += $subtotal;
    }

    $_SESSION['historial'][] = $compra;

    $_SESSION['carrito'] = [];

    header("Location: carrito.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| TOTALES
|--------------------------------------------------------------------------
*/

$totalGeneral = 0;
$totalProductos = 0;

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>

<title>Carrito Gamer</title>

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

input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button{
    opacity:1;
}

</style>

</head>

<body>

<div class="min-h-screen p-8">

    <!-- TITULO -->

    <div class="flex justify-between items-center mb-10">

        <div>

            <h1 class="text-5xl font-black flex items-center gap-4">

                <i class="fa-solid fa-cart-shopping text-purple-500"></i>

                Carrito

            </h1>

            <p class="text-gray-400 mt-2">
                Tus videojuegos agregados
            </p>

        </div>

        <a

        href="usuario.php"

        class="bg-[#131c35] hover:bg-purple-600 px-6 py-3 rounded-2xl transition-all"
        >

            Volver

        </a>

    </div>

    <form method="POST">

    <!-- TABLA -->

    <div class="bg-[#0b1020] rounded-[30px] overflow-hidden border border-purple-900/20">

        <!-- HEADER -->

        <div class="grid grid-cols-5 gap-5 p-5 border-b border-purple-900/20 font-bold text-gray-400">

            <div>Juego</div>
            <div>Precio</div>
            <div>Cantidad</div>
            <div>Total</div>
            <div>Eliminar</div>

        </div>

        <!-- PRODUCTOS -->

        <?php

        if(empty($carrito)){

            echo "

            <div class='p-20 text-center'>

                <i class='fa-solid fa-cart-shopping text-7xl text-purple-500'></i>

                <h2 class='text-3xl font-bold mt-5'>
                    Tu carrito está vacío
                </h2>

            </div>

            ";

        }else{

            foreach($carrito as $id => $cantidad){

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

                /*
                |--------------------------------------------------------------------------
                | PRECIO
                |--------------------------------------------------------------------------
                */

                $precio = rand(80,400);

                $total = $precio * $cantidad;

                $totalGeneral += $total;

                $totalProductos += $cantidad;

        ?>

        <div class="grid grid-cols-5 gap-5 items-center p-5 border-b border-purple-900/10">

            <!-- JUEGO -->

            <div class="flex items-center gap-4">

                <img
                    src="<?php echo $game['background_image']; ?>"
                    class="w-24 h-24 rounded-2xl object-cover"
                >

                <div>

                    <h2 class="font-bold text-xl">

                        <?php echo $game['name']; ?>

                    </h2>

                    <p class="text-gray-400 text-sm">

                        ⭐ <?php echo $game['rating']; ?>

                    </p>

                </div>

            </div>

            <!-- PRECIO -->

            <div class="font-bold text-green-400 text-xl">

                Bs. <?php echo $precio; ?>

            </div>

            <!-- CANTIDAD -->

            <div>

                <input
                    type="number"
                    min="0"
                    name="cantidades[<?php echo $id; ?>]"
                    value="<?php echo $cantidad; ?>"
                    class="w-24 bg-[#131c35] text-center py-3 rounded-2xl text-xl font-bold outline-none border border-purple-500"
                >

                <p class="text-gray-500 text-sm mt-2">
                    0 = eliminar
                </p>

            </div>

            <!-- TOTAL -->

            <div class="font-black text-2xl">

                Bs. <?php echo $total; ?>

            </div>

            <!-- ELIMINAR -->

            <div>

                <a

                href="?remove=<?php echo $id; ?>"

                class="bg-red-500 px-5 py-3 rounded-2xl hover:bg-red-600 transition-all"
                >

                    Eliminar

                </a>

            </div>

        </div>

        <?php

            }

        }

        ?>

    </div>

    <!-- BOTON ACTUALIZAR -->

    <?php if(!empty($carrito)){ ?>

    <div class="mt-6">

        <button
            type="submit"
            name="update_cart"
            class="bg-purple-600 hover:bg-purple-700 px-8 py-4 rounded-2xl font-bold text-lg transition-all"
        >

            Actualizar carrito

        </button>

    </div>

    <?php } ?>

    </form>

    <!-- RESUMEN -->

    <div class="mt-10 flex justify-end">

        <div class="w-full max-w-[500px] bg-[#0b1020] rounded-[30px] p-8 border border-purple-900/20">

            <h2 class="text-3xl font-black mb-8">

                Resumen del pedido

            </h2>

            <!-- PRODUCTOS -->

            <div class="space-y-4 mb-8">

                <?php

                foreach($carrito as $id => $cantidad){

                    $url = "https://api.rawg.io/api/games/$id?key=$apiKey";

                    $response = @file_get_contents($url);

                    if($response === false){
                        continue;
                    }

                    $game = json_decode($response,true);

                    if(!$game || isset($game['detail'])){
                        continue;
                    }

                ?>

                <div class="flex justify-between items-center bg-[#131c35] p-4 rounded-2xl">

                    <div>

                        <h3 class="font-bold">

                            <?php echo $game['name']; ?>

                        </h3>

                        <p class="text-gray-400 text-sm">

                            Cantidad: <?php echo $cantidad; ?>

                        </p>

                    </div>

                    <span class="font-bold text-purple-400">

                        x<?php echo $cantidad; ?>

                    </span>

                </div>

                <?php } ?>

            </div>

            <!-- TOTAL PRODUCTOS -->

            <div class="flex justify-between mb-5 text-xl">

                <span class="text-gray-400">
                    Total productos
                </span>

                <span class="font-bold">

                    <?php echo $totalProductos; ?>

                </span>

            </div>

            <!-- TOTAL GENERAL -->

            <div class="flex justify-between mb-8 text-3xl font-black">

                <span>
                    Total general
                </span>

                <span class="text-green-400">

                    Bs. <?php echo $totalGeneral; ?>

                </span>

            </div>

            <!-- COMPRAR -->

            <?php if(!empty($carrito)){ ?>

            <form method="POST">

                <button

                type="submit"

                name="comprar"

                class="w-full py-4 rounded-2xl bg-gradient-to-r from-green-500 to-emerald-600 font-bold text-xl hover:scale-105 transition-all"
                >

                    Finalizar Compra

                </button>

            </form>

            <?php } ?>

        </div>

    </div>

    <!-- HISTORIAL -->

    <div class="mt-16">

        <h2 class="text-4xl font-black mb-8 flex items-center gap-4">

            <i class="fa-solid fa-clock-rotate-left text-purple-500"></i>

            Historial de compras

        </h2>

        <?php

        if(empty($_SESSION['historial'])){

            echo "

            <div class='bg-[#0b1020] p-10 rounded-[30px] text-center text-gray-400'>

                Aún no tienes compras realizadas

            </div>

            ";

        }else{

            foreach(array_reverse($_SESSION['historial']) as $compra){

        ?>

        <div class="bg-[#0b1020] rounded-[30px] p-8 mb-6 border border-purple-900/20">

            <div class="flex justify-between mb-6">

                <h3 class="text-2xl font-bold">

                    Compra realizada

                </h3>

                <span class="text-gray-400">

                    <?php echo $compra['fecha']; ?>

                </span>

            </div>

            <div class="space-y-4">

                <?php foreach($compra['productos'] as $producto){ ?>

                <div class="flex justify-between bg-[#131c35] p-4 rounded-2xl">

                    <div>

                        <h4 class="font-bold">

                            <?php echo $producto['nombre']; ?>

                        </h4>

                        <p class="text-gray-400">

                            Cantidad: <?php echo $producto['cantidad']; ?>

                        </p>

                    </div>

                    <div class="text-right">

                        <p class="text-green-400 font-bold">

                            Bs. <?php echo $producto['subtotal']; ?>

                        </p>

                    </div>

                </div>

                <?php } ?>

            </div>

            <div class="flex justify-between mt-6 text-2xl font-black">

                <span>Total</span>

                <span class="text-green-400">

                    Bs. <?php echo $compra['total']; ?>

                </span>

            </div>

        </div>

        <?php

            }

        }

        ?>

    </div>

</div>

</body>
</html>