<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/*
|--------------------------------------------------------------------------
| CONEXIÓN MYSQL
|--------------------------------------------------------------------------
*/

$host = "127.0.0.1";
$user = "root";
$password = "";
$database = "tienda_web";
$port = 3307;

try {

    $conn = new mysqli(
        $host,
        $user,
        $password,
        $database,
        $port
    );

    $conn->set_charset("utf8");

} catch (Exception $e) {

    die("
    <h1>Error MySQL</h1>
    <pre>".$e->getMessage()."</pre>
    ");
}

/*
|--------------------------------------------------------------------------
| FILTROS
|--------------------------------------------------------------------------
*/

$search = $_GET['search'] ?? '';
$order = $_GET['order'] ?? 'id_producto DESC';

/*
|--------------------------------------------------------------------------
| CONSULTA PRODUCTOS
|--------------------------------------------------------------------------
*/

$sql = "SELECT * FROM producto WHERE 1=1";

if (!empty($search)) {

    $search = $conn->real_escape_string($search);

    $sql .= " AND nombre LIKE '%$search%'";
}

$sql .= " ORDER BY $order";

$games = $conn->query($sql);

/*
|--------------------------------------------------------------------------
| ESTADÍSTICAS
|--------------------------------------------------------------------------
*/

$totalGames = 0;
$totalUsers = 0;
$totalOrders = 0;

try {

    $totalGames = $conn
        ->query("SELECT COUNT(*) total FROM producto")
        ->fetch_assoc()['total'];

} catch(Exception $e) {}

try {

    $totalUsers = $conn
        ->query("SELECT COUNT(*) total FROM usuario")
        ->fetch_assoc()['total'];

} catch(Exception $e) {}

try {

    $totalOrders = $conn
        ->query("SELECT COUNT(*) total FROM detalle_venta")
        ->fetch_assoc()['total'];

} catch(Exception $e) {}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Gamer Store</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>

        body {
            background: #050816;
            color: white;
            font-family: Arial, sans-serif;
        }

        .scroll::-webkit-scrollbar {
            width: 5px;
        }

        .scroll::-webkit-scrollbar-thumb {
            background: #9333ea;
        }

    </style>

</head>

<body>

<div class="flex min-h-screen">

    <!-- SIDEBAR -->

    <aside class="w-[260px] bg-[#0b1020] border-r border-purple-900/20 p-5 hidden lg:block">

        <div class="flex items-center gap-3 mb-10">

            <div class="w-14 h-14 rounded-2xl bg-gradient-to-r from-purple-600 to-fuchsia-600 flex items-center justify-center text-2xl">
                🎮
            </div>

            <div>
                <h1 class="text-2xl font-bold">GAMER</h1>
                <p class="text-purple-400">ADMIN PANEL</p>
            </div>

        </div>

    </aside>

    <!-- MAIN -->

    <main class="flex-1 p-6 overflow-hidden">

        <!-- TOP -->

        <div class="flex flex-col lg:flex-row justify-between gap-5 items-center mb-8">

            <form method="GET" class="flex-1 w-full flex gap-4">

                <input
                    type="text"
                    name="search"
                    placeholder="Buscar productos..."
                    value="<?php echo $search ?>"
                    class="flex-1 bg-[#0b1020] border border-purple-900/20 rounded-2xl py-4 px-5 outline-none focus:border-purple-500"
                >

                <button class="bg-gradient-to-r from-purple-600 to-fuchsia-600 px-6 rounded-2xl font-bold">
                    Buscar
                </button>

            </form>

        </div>

        <!-- STATS -->

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">

            <div class="bg-[#0b1020] rounded-3xl p-6 border border-purple-900/20">

                <p class="text-gray-400">Productos</p>

                <h2 class="text-5xl font-black mt-3">
                    <?php echo $totalGames ?>
                </h2>

            </div>

            <div class="bg-[#0b1020] rounded-3xl p-6 border border-purple-900/20">

                <p class="text-gray-400">Usuarios</p>

                <h2 class="text-5xl font-black mt-3">
                    <?php echo $totalUsers ?>
                </h2>

            </div>

            <div class="bg-[#0b1020] rounded-3xl p-6 border border-purple-900/20">

                <p class="text-gray-400">Ventas</p>

                <h2 class="text-5xl font-black mt-3">
                    <?php echo $totalOrders ?>
                </h2>

            </div>

        </div>

        <!-- TABLA -->

        <section class="bg-[#0b1020] rounded-[30px] border border-purple-900/20 p-6 overflow-auto scroll">

            <div class="flex justify-between items-center mb-6">

                <h2 class="text-3xl font-bold">
                    Gestión de Productos
                </h2>

            </div>

            <table class="w-full text-left min-w-[1000px]">

                <thead>

                    <tr class="border-b border-purple-900/20 text-gray-400">

                        <th class="pb-4">Imagen</th>
                        <th class="pb-4">Nombre</th>
                        <th class="pb-4">Categoría</th>
                        <th class="pb-4">Marca</th>
                        <th class="pb-4">Precio</th>
                        <th class="pb-4">Stock</th>
                        <th class="pb-4">Descripción</th>
                        <th class="pb-4">Estado</th>

                    </tr>

                </thead>

                <tbody>

                <?php while($game = $games->fetch_assoc()) { ?>

                    <tr class="border-b border-purple-900/10 hover:bg-[#121a30] transition-all">

                        <td class="py-5">

                            <img
                                src="<?php echo $game['imagen'] ?>"
                                class="w-20 h-20 object-cover rounded-2xl"
                            >

                        </td>

                        <td class="font-bold text-lg">
                            <?php echo $game['nombre'] ?>
                        </td>

                        <td>
                            <?php echo $game['id_categoria'] ?>
                        </td>

                        <td>
                            <?php echo $game['id_marca'] ?>
                        </td>

                        <td class="text-green-400 font-bold">
                            Bs. <?php echo $game['precio'] ?>
                        </td>

                        <td>
                            <?php echo $game['stock'] ?>
                        </td>

                        <td class="max-w-[250px] text-gray-400 text-sm">
                            <?php echo substr($game['descripcion'], 0, 100) ?>...
                        </td>

                        <td>

                            <?php if($game['estado'] == 1) { ?>

                                <span class="bg-green-500/20 text-green-400 px-4 py-2 rounded-xl text-sm">
                                    Activo
                                </span>

                            <?php } else { ?>

                                <span class="bg-red-500/20 text-red-400 px-4 py-2 rounded-xl text-sm">
                                    Inactivo
                                </span>

                            <?php } ?>

                        </td>

                    </tr>

                <?php } ?>

                </tbody>

            </table>

        </section>

    </main>

</div>

</body>
</html>