<?php
session_start();
/** @var PDO $pdo */
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

require 'php/config.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        $erro = 'Email e senha são obrigatórios';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id, nome, email, senha, role FROM usuarios WHERE email = ?');
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();

            if ($usuario && $senha === $usuario['senha']) {
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_email'] = $usuario['email'];
                $_SESSION['usuario_role'] = $usuario['role'] ?? 'mecanico';

                header('Location: index.php');
                exit;
            } else {
                $erro = 'Email ou senha incorretos';
            }
        } catch (Exception $e) {
            $erro = 'Erro ao conectar ao banco de dados';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Oficina360</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #001F3F 0%, #003d82 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 400px;
            width: 100%;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            color: #001F3F;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        .form-control {
            border: 1px solid #ddd;
            padding: 10px 15px;
            margin-bottom: 15px;
        }
        .form-control:focus {
            border-color: #001F3F;
            box-shadow: 0 0 0 0.2rem rgba(0, 31, 63, 0.25);
        }
        .btn-login {
            background-color: #001F3F;
            border: none;
            padding: 10px;
            font-weight: bold;
            width: 100%;
            margin-top: 10px;
        }
        .btn-login:hover {
            background-color: #003d82;
        }
        .alert {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><i class="bi bi-tools"></i> Oficina360</h1>
            <p>Sistema de Gestão de Oficinas Mecânicas</p>
        </div>

        <?php if ($erro): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($erro); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>

            <div class="mb-3">
                <label for="senha" class="form-label">Senha</label>
                <input type="password" class="form-control" id="senha" name="senha" required>
            </div>

            <button type="submit" class="btn btn-primary btn-login">
                <i class="bi bi-box-arrow-in-right"></i> Entrar
            </button>
        </form>

        <hr class="my-4">

        <div class="alert alert-info" role="alert">
            <strong>Dados de Teste:</strong><br>
            <small>
                <strong>Admin:</strong> admin@oficina360.com / admin123<br>
                <strong>Mecânico:</strong> mecanico@oficina360.com / 123456
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
