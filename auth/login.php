<?php
session_start();
require_once 'AuthManager.php';

// Si déjà connecté, rediriger vers index
if (AuthManager::isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Budget Mariage PJPM</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lato:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #8b4f8d;
            --primary-light: #b87bb8;
            --primary-dark: #5d2f5f;
            --secondary: #d4af37;
            --bg-main: #faf8f5;
            --bg-card: #ffffff;
            --text-primary: #2d2d2d;
            --text-secondary: #6b6b6b;
            --border: #e8e3dd;
            --success: #4caf50;
            --danger: #f44336;
            --shadow: rgba(139, 79, 141, 0.1);
        }

        body {
            font-family: 'Lato', sans-serif;
            background: linear-gradient(135deg, #faf8f5 0%, #f5f0eb 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: var(--bg-card);
            border-radius: 20px;
            box-shadow: 0 10px 40px var(--shadow);
            max-width: 450px;
            width: 100%;
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .login-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            margin-bottom: 10px;
            position: relative;
        }

        .login-header p {
            opacity: 0.9;
            position: relative;
        }

        .login-body {
            padding: 40px 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-primary);
            font-size: 0.9rem;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }

        input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: 'Lato', sans-serif;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(139, 79, 141, 0.1);
        }

        .btn {
            width: 100%;
            padding: 14px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Lato', sans-serif;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px var(--shadow);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .divider {
            text-align: center;
            margin: 25px 0;
            color: var(--text-secondary);
            position: relative;
        }

        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background: var(--border);
        }

        .divider::before {
            left: 0;
        }

        .divider::after {
            right: 0;
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
            color: var(--text-secondary);
        }

        .register-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        .alert.show {
            display: block;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .back-home {
            text-align: center;
            margin-top: 20px;
        }

        .back-home a {
            color: var(--text-secondary);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .back-home a:hover {
            color: var(--primary);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-container {
            animation: fadeIn 0.5s ease;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>💍 Connexion</h1>
            <p>Gestion de mon espace budgtaire</p>
        </div>
        
        <div class="login-body">
            <div id="alert" class="alert"></div>
            
            <form id="login-form">
                <div class="form-group">
                    <label for="username">Nom d'utilisateur ou Email</label>
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" required autofocus>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" id="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </button>
            </form>
            
            <div class="divider">OU</div>
            
            <div class="register-link">
                Pas encore de compte ? <a href="register.php">S'inscrire</a>
            </div>
            
            <div class="back-home">
                <a href="index.php">
                    <i class="fas fa-arrow-left"></i> Retour à l'accueil
                </a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('login-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const btn = document.getElementById('login-btn');
            const alert = document.getElementById('alert');
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Connexion...';
            
            const formData = new FormData(e.target);
            const data = {
                username: formData.get('username'),
                password: formData.get('password')
            };
            
            try {
                const response = await fetch('../api/auth_api.php?action=login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert.className = 'alert alert-success show';
                    alert.textContent = result.message;
                    
                    setTimeout(() => {
                        window.location.href = '../index.php';
                    }, 1000);
                } else {
                    alert.className = 'alert alert-danger show';
                    alert.textContent = result.message;
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Se connecter';
                }
            } catch (error) {
                alert.className = 'alert alert-danger show';
                alert.textContent = 'Erreur de connexion au serveur';
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Se connecter';
            }
        });
    </script>
</body>
</html>
