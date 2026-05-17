<?php
session_start();

if (!file_exists("../conexion.php")) { 
    die("Error crítico: No se encontró el archivo 'conexion.php'."); 
}
include("../conexion.php");

if (file_exists('../vendor/autoload.php')) {
    require '../vendor/autoload.php';
} elseif (file_exists('../../vendor/autoload.php')) {
    require '../../vendor/autoload.php';
} else {
    die("Error crítico: No se pudo encontrar 'vendor/autoload.php'.");
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// PROCESAR EL INGRESO DINÁMICO
if (isset($_POST['btn_fb_login'])) {
    
    $correo_fb = trim($_POST['fb_correo']);
    $nombre_fb = trim($_POST['fb_nombre']);

    if(empty($correo_fb) || empty($nombre_fb)){
        die("Por favor completa los campos del perfil de Facebook.");
    }

    if (!isset($conn) || $conn->connect_error) {
        die("Error de conexión a la base de datos.");
    }

    // 1. Verificar si el usuario ya existe por el correo ingresado
    $stmt = $conn->prepare("SELECT * FROM usuario WHERE correo = ?");
    $stmt->bind_param("s", $correo_fb);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $usuario_data = $resultado->fetch_assoc();
    } else {
        // Registro automático si el perfil no existía
        $pass_dummy = password_hash("facebook123", PASSWORD_BCRYPT);
        $rol = "cliente";

        $insert = $conn->prepare("INSERT INTO usuario (nombre, correo, contraseña, rol) VALUES (?, ?, ?, ?)");
        $insert->bind_param("ssss", $nombre_fb, $correo_fb, $pass_dummy, $rol);
        
        if($insert->execute()) {
            $stmt->execute();
            $resultado = $stmt->get_result();
            $usuario_data = $resultado->fetch_assoc();
        } else {
            die("Error al registrar con Facebook.");
        }
    }

    // 2. Despachar el código 2FA Obligatorio al Correo
    if ($usuario_data) {
        $codigo = rand(100000, 999999);
        
        $_SESSION['codigo']  = $codigo;
        $_SESSION['usuario'] = $usuario_data['nombre'];
        $_SESSION['rol']     = $usuario_data['rol'];
        $_SESSION['correo']  = $usuario_data['correo'];

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'leongamer742@gmail.com'; 
            $mail->Password   = 'acqv cjhe fbme demz';    
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom('leongamer742@gmail.com', 'Facebook Login Security');
            $mail->addAddress($usuario_data['correo']);

            $mail->isHTML(true);
            $mail->Subject = 'Código de Verificación 2FA - Login Facebook';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f4f4f4;'>
                    <div style='background-color: #fff; padding: 20px; border-radius: 10px; max-width: 500px; margin: auto;'>
                        <h2>Autenticación de Facebook Exitosa</h2>
                        <p>Hola <strong>".$usuario_data['nombre']."</strong>,</p>
                        <p>Usa el siguiente token de 6 dígitos para completar el acceso al sistema:</p>
                        <h1 style='color: #1877f2; font-size: 40px; text-align: center; letter-spacing: 5px;'>$codigo</h1>
                    </div>
                </div>";

            $mail->send();
            header('Location: ../verificar.php');
            exit();
        } catch (Exception $e) {
            die("Error al enviar el correo 2FA: " . $mail->ErrorInfo);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión con Facebook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f0f2f5; min-height: 100vh; display: flex; justify-content: center; align-items: center; font-family: Arial, sans-serif; }
        .fb-box { background: white; padding: 35px; border-radius: 8px; width: 100%; max-width: 480px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .fb-icon { color: #1877f2; font-size: 55px; margin-bottom: 15px; text-align: center; display: block; }
        .fb-title { font-size: 20px; font-weight: bold; color: #1c1e21; margin-bottom: 5px; text-align: center; }
        .fb-text { color: #606770; font-size: 14px; margin-bottom: 25px; text-align: center; }
        .form-control { border-radius: 6px; padding: 10px; margin-bottom: 15px; }
        .btn-fb-submit { background-color: #1877f2; color: white; border: none; padding: 12px; border-radius: 6px; font-weight: bold; width: 100%; font-size: 16px; transition: background 0.2s; }
        .btn-fb-submit:hover { background-color: #166fe5; }
    </style>
</head>
<body>
<div class="fb-box">
    <i class="fa-brands fa-facebook fb-icon"></i>
    <div class="fb-title">Iniciar sesión con Facebook</div>
    <div class="fb-text">Simulador de Perfil Social de Meta</div>
    
    <form method="POST">
        <label class="text-secondary small fw-bold mb-1">Nombre de Usuario de Facebook</label>
        <input type="text" name="fb_nombre" class="form-control" value="Kevin" required placeholder="Ej: Kevin">

        <label class="text-secondary small fw-bold mb-1">Correo de la cuenta de Facebook</label>
        <input type="email" name="fb_correo" class="form-control" value="leongamer742@gmail.com" required placeholder="Ej: tu_correo@hotmail.com">

        <button type="submit" name="btn_fb_login" class="btn-fb-submit">
            Continuar con el inicio seguro
        </button>
    </form>
</div>
</body>
</html>