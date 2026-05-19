<?php
session_start();

if(!isset($_SESSION['codigo'])){
    header("Location: index.php");
    exit();
}

$error = "";
$ingreso_exitoso = false;
$redirigir_a = "";

if(isset($_POST['verificar'])){

    $codigo = $_POST['codigo'];

    if($codigo == $_SESSION['codigo']){

        $_SESSION['autenticado'] = true;
        $ingreso_exitoso = true;

        if(isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin'){
            $redirigir_a = "admin.php";
        } else {
            $redirigir_a = "usuario.php";
        }

    }else{
        $error = "Código incorrecto";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verificación 2FA</title>

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

<!-- BACKGROUND EFFECT -->
<div class="absolute inset-0">
    <div class="absolute w-[500px] h-[500px] bg-purple-600 rounded-full blur-[150px] opacity-20 top-[-100px] left-[-100px]"></div>
    <div class="absolute w-[500px] h-[500px] bg-fuchsia-600 rounded-full blur-[150px] opacity-20 bottom-[-100px] right-[-100px]"></div>
</div>

<!-- CENTER -->
<div class="relative z-10 min-h-screen flex items-center justify-center px-6">

<div class="card w-full max-w-md p-10 rounded-[30px] glow">

    <!-- ICON -->
    <div class="w-20 h-20 mx-auto rounded-2xl bg-gradient-to-r from-purple-600 to-fuchsia-600 flex items-center justify-center mb-6">
        <i class="fa-solid fa-shield-halved text-3xl"></i>
    </div>

    <h2 class="text-3xl font-black text-center">VERIFICACIÓN 2FA</h2>
    <p class="text-center text-purple-400 mt-2 mb-6">Ingresa el código enviado a tu correo</p>

    <?php if($error != ""): ?>
        <div class="bg-red-500/20 border border-red-500 text-red-300 p-3 rounded-2xl mb-4 text-sm">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if($ingreso_exitoso): ?>
        <div class="bg-green-500/20 border border-green-500 text-green-300 p-3 rounded-2xl mb-4 text-sm text-center">
            ✔ Código correcto. Redirigiendo...
        </div>

        <script>
            setTimeout(function(){
                window.location.href = "<?php echo $redirigir_a; ?>";
            }, 1200);
        </script>
    <?php endif; ?>

    <?php if(!$ingreso_exitoso): ?>
        <form method="POST" class="space-y-4">

            <div class="relative">
                <i class="fa-solid fa-key absolute left-4 top-4 text-gray-400"></i>
                <input type="text"
                    name="codigo"
                    class="w-full bg-[#0b1020] border border-purple-900/20 rounded-2xl py-4 pl-12 pr-4 outline-none focus:border-purple-500 text-center tracking-widest text-xl"
                    placeholder="------"
                    required>
            </div>

            <button type="submit"
                name="verificar"
                class="w-full py-4 rounded-2xl bg-gradient-to-r from-purple-600 to-fuchsia-600 font-bold hover:scale-105 transition-all">
                VERIFICAR
            </button>

        </form>
    <?php endif; ?>

</div>

</div>

</body>
</html>