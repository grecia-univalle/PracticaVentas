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
if (isset($_POST['btn_google_login'])) {
    
    // Tomamos los datos directamente de lo que el usuario escribió en la ventana simulada
    $correo_google = trim($_POST['google_correo']);
    $nombre_google = trim($_POST['google_nombre']);

    if(empty($correo_google) || empty($nombre_google)){
        die("Por favor ingresa un nombre y correo para simular el inicio de sesión.");
    }

    if (!isset($conn) || $conn->connect_error) {
        die("Error de conexión a la base de datos.");
    }

    // 1. Verificar si el usuario ya existe por el correo ingresado
    $stmt = $conn->prepare("SELECT * FROM usuario WHERE correo = ?");
    $stmt->bind_param("s", $correo_google);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $usuario_data = $resultado->fetch_assoc();
    } else {
        // Si el correo es nuevo en el sistema, lo registramos automáticamente
        $pass_dummy = password_hash("google123", PASSWORD_BCRYPT);
        $rol = "cliente";

        $insert = $conn->prepare("INSERT INTO usuario (nombre, correo, contraseña, rol) VALUES (?, ?, ?, ?)");
        $insert->bind_param("ssss", $nombre_google, $correo_google, $pass_dummy, $rol);
        
        if($insert->execute()) {
            $stmt->execute();
            $resultado = $stmt->get_result();
            $usuario_data = $resultado->fetch_assoc();
        } else {
            die("Error al registrar el usuario dinámico de Google.");
        }
    }

    // 2. Despachar el código 2FA Obligatorio al Correo ingresado
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
            $mail->Username   = 'leongamer742@gmail.com'; // Cuenta desde donde salen tus correos
            $mail->Password   = 'acqv cjhe fbme demz';    
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom('leongamer742@gmail.com', 'Google Accounts');
            $mail->addAddress($usuario_data['correo']); // Le llegará al correo ingresado dinámicamente

            $mail->isHTML(true);
            $mail->Subject = 'Código de Verificación 2FA - Login Google';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f4f4f4;'>
                    <div style='background-color: #fff; padding: 20px; border-radius: 10px; max-width: 500px; margin: auto;'>
                        <h2>Autenticación de Google Exitosa</h2>
                        <p>Hola <strong>".$usuario_data['nombre']."</strong>,</p>
                        <p>Introduce este código de seguridad para confirmar tu identidad:</p>
                        <h1 style='color: #ea4335; font-size: 40px; text-align: center; letter-spacing: 5px;'>$codigo</h1>
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
    <title>Iniciar sesión con Google</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; min-height: 100vh; display: flex; justify-content: center; align-items: center; font-family: Arial, sans-serif; }
        .google-box { background: white; padding: 40px; border-radius: 8px; border: 1px solid #dadce0; width: 100%; max-width: 450px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .google-logo { font-size: 40px; margin-bottom: 15px; font-weight: bold; text-align: center; letter-spacing: -2px; }
        .google-logo .g1 { color: #4285F4; } .google-logo .g2 { color: #EA4335; } .google-logo .g3 { color: #FBBC05; } .google-logo .g4 { color: #34A853; }
        .title { font-size: 22px; color: #202124; margin-bottom: 5px; text-align: center; }
        .subtitle { color: #5f6368; font-size: 14px; margin-bottom: 25px; text-align: center; }
        .form-control { border-radius: 6px; padding: 10px; margin-bottom: 15px; background: #fafafa; }
        .btn-google-submit { background-color: #1a73e8; color: white; border: none; padding: 12px; border-radius: 4px; font-weight: bold; width: 100%; transition: background 0.2s; }
        .btn-google-submit:hover { background-color: #1557b0; }
    </style>
</head>
<body>
<div class="google-box">
    <div class="google-logo">
        <span class="g1">G</span><span class="g2">o</span><span class="g3">o</span><span class="g1">g</span><span class="g4">l</span><span class="g2">e</span>
    </div>
    <div class="title">Iniciar sesión con Google</div>
    <div class="subtitle">Entorno de desarrollo: Elige la cuenta que deseas simular</div>
    
    <form method="POST">
        <label class="text-secondary small fw-bold mb-1">Nombre Completo de Google</label>
        <input type="text" name="google_nombre" class="form-control" value="Kevin" required placeholder="Ej: Kevin">

        <label class="text-secondary small fw-bold mb-1">Correo de la cuenta Google</label>
        <input type="email" name="google_correo" class="form-control" value="leongamer742@gmail.com" required placeholder="Ej: tu_correo@gmail.com">

        <button type="submit" name="btn_google_login" class="btn-google-submit">
            Continuar y Vincular Cuenta
        </button>
    </form>
</div>
</body>
</html>