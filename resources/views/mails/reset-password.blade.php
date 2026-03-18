<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f3f4f6; margin: 0; padding: 0; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f3f4f6; padding: 40px 0; }
        .main { background-color: #ffffff; margin: 0 auto; max-width: 600px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05); overflow: hidden; }
        
        /* Encabezado con color y degradado */
        .header { background: linear-gradient(135deg, #111827 0%, #1f2937 100%); padding: 40px 20px; text-align: center; border-bottom: 5px solid #34D399; }
        .logo { font-size: 34px; font-weight: 800; color: #ffffff; letter-spacing: 1px; margin: 0; }
        .logo span { color: #34D399; }

        /* Icono flotante */
        .icon-wrapper { text-align: center; margin-top: -30px; }
        .icon { background-color: #34D399; display: inline-block; padding: 15px; border-radius: 50%; border: 6px solid #ffffff; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }

        .content { padding: 30px 40px 40px 40px; text-align: center; }
        .title { color: #1f2937; font-size: 24px; font-weight: 800; margin-bottom: 16px; }
        .text { color: #4b5563; font-size: 16px; line-height: 1.6; margin-bottom: 32px; }
        
        /* Botón mejorado */
        .button { display: inline-block; background-color: #34D399; color: #ffffff !important; text-decoration: none; font-weight: bold; padding: 16px 40px; border-radius: 12px; font-size: 16px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(52, 211, 153, 0.3); text-transform: uppercase; letter-spacing: 0.5px; }
        
        .footer { background-color: #f8fafc; padding: 24px 40px; text-align: center; border-top: 1px solid #e2e8f0; }
        .footer-text { color: #64748b; font-size: 13px; line-height: 1.6; margin: 0; }
        
        .fallback { text-align: left; background-color: #f1f5f9; border-radius: 10px; padding: 20px; margin-top: 40px; border-left: 4px solid #94a3b8; }
        .fallback-text { font-size: 13px; color: #475569; word-break: break-all; margin: 0; line-height: 1.5; }
        .fallback-link { color: #10b981; text-decoration: none; font-weight: 500; }
    </style>
</head>
<body>
    <center class="wrapper">
        <div class="main">
            <div class="header">
                <h1 class="logo">Automa<span>Co</span></h1>
            </div>

            <div class="icon-wrapper">
                <div class="icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                </div>
            </div>

            <div class="content">
                <h2 class="title">Recuperación de contraseña</h2>
                <p class="text">
                    Hemos recibido una solicitud para restablecer el acceso a tu cuenta. 
                    Si fuiste tú, haz clic en el botón de abajo para configurar una nueva contraseña de forma segura.
                </p>

                <a href="{{ $url }}" class="button">Restablecer Contraseña</a>

                <div class="fallback">
                    <p class="fallback-text">
                        <strong>¿El botón no funciona?</strong><br>
                        Copia y pega este enlace directamente en tu navegador web:<br>
                        <a href="{{ $url }}" class="fallback-link">{{ $url }}</a>
                    </p>
                </div>
            </div>

            <div class="footer">
                <p class="footer-text">
                    Este enlace de seguridad expirará en <strong>60 minutos</strong>.<br><br>
                    Si no solicitaste este cambio, tu cuenta está a salvo y puedes ignorar este mensaje de forma segura.
                </p>
            </div>
        </div>
    </center>
</body>
</html>