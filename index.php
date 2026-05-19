<?php
session_start();
include("conexion.php");

require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = "";

if(isset($_POST['login'])){

    $correo = trim($_POST['correo']);
    $password = trim($_POST['password']);

    // 🔥 CONSULTA SEGURA
    $sql = "SELECT * FROM usuario WHERE correo='$correo'";
    $resultado = $conn->query($sql);

    if($resultado && $resultado->num_rows > 0){

        $usuario = $resultado->fetch_assoc();

        // 🔍 DEBUG (puedes quitarlo después)
        // var_dump($usuario); exit();

        // 🔐 VALIDAR PASSWORD (REQUIERE HASH EN BD)
        if(password_verify($password, $usuario['contraseña'])){

            // 🔥 GENERAR 2FA PARA TODOS (ADMIN Y USER)
            $codigo = rand(100000,999999);

            $_SESSION['codigo'] = $codigo;
            $_SESSION['usuario'] = $usuario['nombre'];
            $_SESSION['rol'] = $usuario['rol'] ?? 'usuario';
            $_SESSION['correo'] = $correo;

            // 📩 ENVIAR CÓDIGO
            $mail = new PHPMailer(true);

            try{

                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'leongamer742@gmail.com';
                $mail->Password = 'acqv cjhe fbme demz';
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;
                $mail->CharSet = 'UTF-8';

                $mail->setFrom('leongamer742@gmail.com', 'Sistema Web');
                $mail->addAddress($correo);

                $mail->isHTML(true);
                $mail->Subject = 'Código de Verificación';

                $mail->Body = "
                <div style='font-family:Arial;padding:20px;'>
                    <h2>Verificación de Inicio de Sesión</h2>
                    <p>Tu código es:</p>
                    <h1 style='color:#a855f7'>$codigo</h1>
                </div>";

                $mail->send();

                header('Location: verificar.php');
                exit();

            }catch(Exception $e){
                $error = "Error SMTP: " . $mail->ErrorInfo;
            }

        }else{
            $error = "Contraseña incorrecta";
        }

    }else{
        $error = "Correo no encontrado";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login</title>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
body{
    background:#050816;
    font-family:Arial;
    color:white;
    overflow:hidden;
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

<!-- CONTAINER -->
<div class="relative z-10 min-h-screen flex items-center justify-center px-6">

<div class="w-full max-w-6xl grid grid-cols-1 md:grid-cols-2 gap-10 items-center">

    <!-- LEFT -->
    <div class="text-center md:text-left">

        <div class="w-24 h-24 mx-auto md:mx-0 rounded-2xl bg-gradient-to-r from-purple-600 to-fuchsia-600 flex items-center justify-center glow mb-6">
            <i class="fa-solid fa-gamepad text-4xl"></i>
        </div>

        <h1 class="text-5xl font-black leading-tight">
            BIENVENIDO A
            <span class="text-purple-400 block">GAMER UNIVERSE</span>
        </h1>

        <p class="text-gray-300 mt-6 text-lg">
            Accede a tu cuenta para explorar videojuegos, favoritos y carrito.
        </p>

    </div>

    <!-- RIGHT -->
    <div class="card p-10 rounded-[30px] glow">

        <h2 class="text-3xl font-black text-center mb-2">LOGIN</h2>
        <p class="text-center text-purple-400 mb-6">Inicia sesión en tu cuenta</p>

        <?php if($error != ""): ?>
            <div class="bg-red-500/20 border border-red-500 text-red-300 p-3 rounded-2xl mb-4 text-sm">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">

            <div class="relative">
                <i class="fa-solid fa-envelope absolute left-4 top-4 text-gray-400"></i>
                <input type="email" name="correo" required
                    class="w-full bg-[#0b1020] border border-purple-900/20 rounded-2xl py-4 pl-12 pr-4 outline-none focus:border-purple-500"
                    placeholder="Correo electrónico">
            </div>

            <div class="relative">
                <i class="fa-solid fa-lock absolute left-4 top-4 text-gray-400"></i>
                <input type="password" name="password" required
                    class="w-full bg-[#0b1020] border border-purple-900/20 rounded-2xl py-4 pl-12 pr-4 outline-none focus:border-purple-500"
                    placeholder="Contraseña">
            </div>

            <button type="submit" name="login"
                class="w-full py-4 rounded-2xl bg-gradient-to-r from-purple-600 to-fuchsia-600 font-bold hover:scale-105 transition-all">
                INICIAR SESIÓN
            </button>

        </form>

        <div class="text-center text-gray-500 my-6">o</div>

        <div class="space-y-3">

            <a href="oauth/google.php"
                class="flex items-center justify-center gap-3 w-full py-3 rounded-2xl bg-[#0b1020] border border-purple-900/20 hover:bg-[#151d34] transition">
                <i class="fa-brands fa-google text-red-400"></i>
                Continuar con Google
            </a>

            <a href="oauth/facebook.php"
                class="flex items-center justify-center gap-3 w-full py-3 rounded-2xl bg-[#0b1020] border border-purple-900/20 hover:bg-[#151d34] transition">
                <i class="fa-brands fa-facebook text-blue-500"></i>
                Continuar con Facebook
            </a>

        </div>

        <p class="text-center mt-6 text-sm text-gray-400">
            ¿No tienes cuenta?
            <a href="register.php" class="text-purple-400 hover:underline">Crear cuenta</a>
        </p>

    </div>

</div>

</div>

</body>
</html>