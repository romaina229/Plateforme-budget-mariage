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
    <title>Inscription - Budget Mariage PJPM</title>
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

        .register-container {
            background: var(--bg-card);
            border-radius: 20px;
            box-shadow: 0 10px 40px var(--shadow);
            max-width: 500px;
            width: 100%;
            overflow: hidden;
        }

        .register-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .register-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .register-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            margin-bottom: 10px;
            position: relative;
        }

        .register-header p {
            opacity: 0.9;
            position: relative;
        }

        .register-body {
            padding: 40px 30px;
        }

        .form-group {
            margin-bottom: 20px;
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

        .login-link {
            text-align: center;
            margin-top: 20px;
            color: var(--text-secondary);
        }

        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
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

        .password-strength {
            margin-top: 5px;
            font-size: 0.85rem;
        }

        .strength-bar {
            height: 4px;
            background: var(--border);
            border-radius: 2px;
            margin-top: 5px;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
        }

        .strength-weak { background: var(--danger); width: 33%; }
        .strength-medium { background: #ff9800; width: 66%; }
        .strength-strong { background: var(--success); width: 100%; }

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

        .register-container {
            animation: fadeIn 0.5s ease;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>📝 Inscription</h1>
            <p>Créer votre compte pour gérer efficacement vos budget</p>
        </div>
        
        <div class="register-body">
            <div id="alert" class="alert"></div>
            
            <form id="register-form">
                <div class="form-group">
                    <label for="fullname">Nom complet (optionnel)</label>
                    <div class="input-group">
                        <i class="fas fa-user-circle"></i>
                        <input type="text" id="fullname" name="fullname">
                    </div>
                </div>

                <div class="form-group">
                    <label for="username">Nom d'utilisateur *</label>
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" required minlength="3">
                    </div>
                    <small style="color: var(--text-secondary)">Au moins 3 caractères</small>
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Mot de passe *</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" required minlength="6">
                    </div>
                    <div class="password-strength">
                        <small id="strength-text"></small>
                        <div class="strength-bar">
                            <div id="strength-fill" class="strength-fill"></div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmer le mot de passe *</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" id="register-btn">
                    <i class="fas fa-user-plus"></i> S'inscrire
                </button>
            </form>
            
            <div class="divider">OU</div>
            
            <div class="login-link">
                Déjà un compte ? <a href="login.php">Se connecter</a>
            </div>
        </div>
    </div>

    <script>
        // Vérifier la force du mot de passe
        document.getElementById('password').addEventListener('input', (e) => {
            const password = e.target.value;
            const strengthText = document.getElementById('strength-text');
            const strengthFill = document.getElementById('strength-fill');
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            strengthFill.className = 'strength-fill';
            if (strength <= 2) {
                strengthFill.classList.add('strength-weak');
                strengthText.textContent = 'Faible';
                strengthText.style.color = '#f44336';
            } else if (strength <= 3) {
                strengthFill.classList.add('strength-medium');
                strengthText.textContent = 'Moyen';
                strengthText.style.color = '#ff9800';
            } else {
                strengthFill.classList.add('strength-strong');
                strengthText.textContent = 'Fort';
                strengthText.style.color = '#4caf50';
            }
        });

        document.getElementById('register-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const btn = document.getElementById('register-btn');
            const alert = document.getElementById('alert');
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // Vérifier que les mots de passe correspondent
            if (password !== confirmPassword) {
                alert.className = 'alert alert-danger show';
                alert.textContent = 'Les mots de passe ne correspondent pas';
                return;
            }
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Inscription...';
            
            const formData = new FormData(e.target);
            const data = {
                username: formData.get('username'),
                email: formData.get('email'),
                password: formData.get('password'),
                fullname: formData.get('fullname')
            };
            
            try {
                const response = await fetch('../api/auth_api.php?action=register', {
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
                        window.location.href = 'login.php';
                    }, 2000);
                } else {
                    alert.className = 'alert alert-danger show';
                    alert.textContent = result.message;
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-user-plus"></i> S\'inscrire';
                }
            } catch (error) {
                alert.className = 'alert alert-danger show';
                alert.textContent = 'Erreur de connexion au serveur';
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-user-plus"></i> S\'inscrire';
            }
        });
    </script>
</body>
</html>
