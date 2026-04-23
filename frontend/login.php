<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Gestión de Procesos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center; padding: 20px;
        }
        .login-container {
            background: white; border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,.2);
            width: 100%; max-width: 420px; padding: 40px;
            animation: slideUp .5s ease;
        }
        @keyframes slideUp {
            from { opacity:0; transform:translateY(20px); }
            to   { opacity:1; transform:translateY(0); }
        }
        .login-header { text-align:center; margin-bottom:30px; }
        .login-header h1 { color:#2c3e50; font-size:24px; margin-bottom:8px; }
        .login-header p  { color:#7f8c8d; font-size:14px; }
        .logo-icon {
            width:70px; height:70px; background:#3498db; border-radius:50%;
            display:flex; align-items:center; justify-content:center; margin:0 auto 20px;
        }
        .logo-icon i { font-size:35px; color:white; }
        .form-group { margin-bottom:20px; }
        .form-group label { display:block; margin-bottom:8px; color:#2c3e50; font-weight:bold; font-size:14px; }
        .input-wrapper { position:relative; display:flex; align-items:center; }
        .input-wrapper i.field-icon { position:absolute; left:15px; color:#95a5a6; font-size:18px; }
        .input-wrapper input {
            width:100%; padding:14px 20px 14px 45px;
            border:2px solid #e0e0e0; border-radius:8px; font-size:15px;
            transition:all .3s; background:#f8f9fa;
        }
        .input-wrapper input:focus {
            outline:none; border-color:#3498db; background:white;
            box-shadow:0 0 0 3px rgba(52,152,219,.1);
        }
        .toggle-password { position:absolute; right:15px; cursor:pointer; color:#95a5a6; font-size:18px; }
        .toggle-password:hover { color:#3498db; }
        .btn-login {
            width:100%; padding:14px; background:#3498db; color:white;
            border:none; border-radius:8px; font-size:16px; font-weight:bold;
            cursor:pointer; transition:all .3s;
            display:flex; align-items:center; justify-content:center; gap:10px;
            margin-top:10px;
        }
        .btn-login:hover:not(:disabled) { background:#2980b9; transform:translateY(-2px); box-shadow:0 5px 15px rgba(52,152,219,.3); }
        .btn-login:disabled { opacity:.7; cursor:not-allowed; transform:none; }
        .error-message {
            background:#fee; color:#e74c3c; padding:12px; border-radius:8px;
            font-size:14px; margin-bottom:20px; display:none;
            align-items:center; gap:8px; border-left:4px solid #e74c3c;
        }
        .error-message.show { display:flex; }
        .login-footer { text-align:center; margin-top:25px; color:#7f8c8d; font-size:13px; }
        .loader {
            display:inline-block; width:20px; height:20px;
            border:3px solid rgba(255,255,255,.3); border-radius:50%;
            border-top-color:white; animation:spin 1s ease-in-out infinite;
        }
        @keyframes spin { to { transform:rotate(360deg); } }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo-icon"><i class="fas fa-gavel"></i></div>
            <h1>Sistema de Gestión de Procesos</h1>
            <p>Ingresa con tus credenciales</p>
        </div>

        <div class="error-message" id="errorMessage">
            <i class="fas fa-exclamation-circle"></i>
            <span id="errorText"></span>
        </div>

        <form id="loginForm">
            <div class="form-group">
                <label>Usuario o Email</label>
                <div class="input-wrapper">
                    <i class="fas fa-user field-icon"></i>
                    <input type="text" id="usuario" placeholder="usuario@email.com" required autofocus>
                </div>
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock field-icon"></i>
                    <input type="password" id="password" placeholder="••••••••" required>
                    <i class="fas fa-eye toggle-password" id="togglePwd" onclick="togglePassword()"></i>
                </div>
            </div>
            <button type="submit" class="btn-login" id="btnLogin">
                <i class="fas fa-sign-in-alt"></i>
                <span>Ingresar</span>
            </button>
        </form>

        <div class="login-footer">
            <p>&copy; <?= date('Y') ?> Sistema de Gestión de Procesos Judiciales</p>
        </div>
    </div>

    <script>
    // Si ya hay token válido en localStorage, ir directo al dashboard
    (function() {
        const token = localStorage.getItem('token');
        if (token) {
            window.location.replace('/procesos_juridicos/frontend/index.php?view=dashboard');
        }
    })();

    function togglePassword() {
        const pwd  = document.getElementById('password');
        const icon = document.getElementById('togglePwd');
        if (pwd.type === 'password') {
            pwd.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            pwd.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }

    function showError(msg) {
        const div = document.getElementById('errorMessage');
        document.getElementById('errorText').textContent = msg;
        div.classList.add('show');
        setTimeout(() => div.classList.remove('show'), 5000);
    }

    function resetButton() {
        const btn = document.getElementById('btnLogin');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-sign-in-alt"></i><span>Ingresar</span>';
    }

    document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const usuario  = document.getElementById('usuario').value.trim();
        const password = document.getElementById('password').value;
        const btn      = document.getElementById('btnLogin');

        if (!usuario || !password) {
            showError('Por favor complete todos los campos');
            return;
        }

        // Mostrar loader
        btn.disabled = true;
        btn.innerHTML = '<span class="loader"></span> Ingresando...';

        const fd = new FormData();
        fd.append('action',   'login');
        fd.append('usuario',  usuario);
        fd.append('password', password);

        fetch('/procesos_juridicos/backend/controllers/AuthController.php', {
            method: 'POST',
            body:   fd
        })
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(data => {
            if (data.success) {
                localStorage.setItem('token', data.token);
                localStorage.setItem('user',  JSON.stringify(data.user));
                // Redirigir — NO resetear el botón para evitar doble submit
                window.location.replace('/procesos_juridicos/frontend/index.php?view=dashboard');
            } else {
                showError(data.message || 'Credenciales inválidas');
                resetButton();
            }
        })
        .catch(() => {
            showError('Error de conexión con el servidor');
            resetButton();
        });
    });
    </script>
</body>
</html>