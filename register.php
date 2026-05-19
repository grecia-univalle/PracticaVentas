<?php
include("conexion.php");

$mensaje = "";

if(isset($_POST['registrar'])){

    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $password = trim($_POST['password']);
    $confirmar = trim($_POST['confirmar']);

    if($password != $confirmar){

        $mensaje = "Las contraseñas no coinciden";

    }elseif(
        strlen($password) < 8 ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[a-z]/', $password) ||
        !preg_match('/[0-9]/', $password)
    ){

        $mensaje = "La contraseña debe tener mínimo 8 caracteres, una mayúscula, una minúscula y un número";

    }else{

        $verificar = "SELECT * FROM usuario WHERE correo='$correo'";
        $resultado = $conn->query($verificar);

        if($resultado->num_rows > 0){

            $mensaje = "El correo ya está registrado";

        }else{

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO usuario(nombre, correo, contraseña)
                    VALUES('$nombre','$correo','$passwordHash')";

            if($conn->query($sql)){

                header("Location: index.php");
                exit();

            }else{

                $mensaje = "Error al registrar usuario";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registro</title>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
body{
    background:#050816;
    font-family:Arial;
    color:white;
    overflow-x:hidden;
}

.glow{
    box-shadow:0 0 30px rgba(168,85,247,.4);
}

.card{
    backdrop-filter: blur(12px);
    background: rgba(11,16,32,.85);
    border:1px solid rgba(147,51,234,.2);
}
</style>

</head>

<body>

<!-- BACKGROUND -->
<div class="absolute inset-0">
    <div class="absolute w-[500px] h-[500px] bg-purple-600 rounded-full blur-[150px] opacity-20 top-[-100px] left-[-100px]"></div>
    <div class="absolute w-[500px] h-[500px] bg-fuchsia-600 rounded-full blur-[150px] opacity-20 bottom-[-100px] right-[-100px]"></div>
</div>

<div class="relative z-10 min-h-screen flex items-center justify-center px-6">

<div class="w-full max-w-6xl grid grid-cols-1 md:grid-cols-2 gap-10 items-center">

    <!-- LEFT SIDE -->
    <div class="text-center md:text-left">

        <div class="w-24 h-24 mx-auto md:mx-0 rounded-2xl bg-gradient-to-r from-purple-600 to-fuchsia-600 flex items-center justify-center glow mb-6">
            <i class="fa-solid fa-user-plus text-4xl"></i>
        </div>

        <h1 class="text-5xl font-black leading-tight">
            CREA TU
            <span class="text-purple-400 block">CUENTA GAMER</span>
        </h1>

        <p class="text-gray-300 mt-6 text-lg">
            Únete a Gamer Universe y empieza a explorar videojuegos, favoritos y carrito.
        </p>

    </div>

    <!-- RIGHT SIDE -->
    <div class="card p-10 rounded-[30px] glow">

        <h2 class="text-3xl font-black text-center mb-2">REGISTRO</h2>
        <p class="text-center text-purple-400 mb-6">Crea tu cuenta</p>

        <?php if($mensaje != ""): ?>
            <div class="bg-yellow-500/20 border border-yellow-500 text-yellow-300 p-3 rounded-2xl mb-4 text-sm">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">

            <div class="relative">
                <i class="fa-solid fa-user absolute left-4 top-4 text-gray-400"></i>
                <input type="text" name="nombre" required
                    class="w-full bg-[#0b1020] border border-purple-900/20 rounded-2xl py-4 pl-12 pr-4 outline-none focus:border-purple-500"
                    placeholder="Nombre completo">
            </div>

            <div class="relative">
                <i class="fa-solid fa-envelope absolute left-4 top-4 text-gray-400"></i>
                <input type="email" name="correo" required
                    class="w-full bg-[#0b1020] border border-purple-900/20 rounded-2xl py-4 pl-12 pr-4 outline-none focus:border-purple-500"
                    placeholder="Correo electrónico">
            </div>

            <div class="relative">
                <i class="fa-solid fa-lock absolute left-4 top-4 text-gray-400"></i>
                <input type="password" name="password" id="password" required
                    class="w-full bg-[#0b1020] border border-purple-900/20 rounded-2xl py-4 pl-12 pr-4 outline-none focus:border-purple-500"
                    placeholder="Contraseña">
            </div>

            <div class="relative">
                <i class="fa-solid fa-lock absolute left-4 top-4 text-gray-400"></i>
                <input type="password" name="confirmar" id="confirmar" required
                    class="w-full bg-[#0b1020] border border-purple-900/20 rounded-2xl py-4 pl-12 pr-4 outline-none focus:border-purple-500"
                    placeholder="Confirmar contraseña">
            </div>

            <!-- RULES -->
            <div class="text-xs text-gray-400 space-y-1">
                <p>✔ Mínimo 8 caracteres</p>
                <p>✔ Una mayúscula</p>
                <p>✔ Una minúscula</p>
                <p>✔ Un número</p>
            </div>

            <!-- SHOW PASSWORD -->
            <label class="flex items-center gap-2 text-sm text-gray-400">
                <input type="checkbox" onclick="mostrarPassword()" class="accent-purple-500">
                Mostrar contraseña
            </label>

            <button type="submit" name="registrar"
                class="w-full py-4 rounded-2xl bg-gradient-to-r from-purple-600 to-fuchsia-600 font-bold hover:scale-105 transition-all">
                REGISTRARSE
            </button>

        </form>

        <p class="text-center mt-6 text-sm text-gray-400">
            ¿Ya tienes cuenta?
            <a href="index.php" class="text-purple-400 hover:underline">Inicia sesión</a>
        </p>

    </div>

</div>

</div>

<script>
function mostrarPassword(){

    let pass = document.getElementById("password");
    let confirmar = document.getElementById("confirmar");

    if(pass.type === "password"){
        pass.type = "text";
        confirmar.type = "text";
    }else{
        pass.type = "password";
        confirmar.type = "password";
    }
}
</script>

</body>
</html>