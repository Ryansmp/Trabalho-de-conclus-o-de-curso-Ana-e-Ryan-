<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'php/config.php';

    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $erro = '';

    if (empty($email) || empty($senha)) {
        $erro = 'Email e senha são obrigatórios';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();

            if ($usuario && $senha === $usuario['senha']) {

                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_email'] = $usuario['email'];
                header('Location: index.php');
                exit;
            } else {
                $erro = 'Email ou senha inválidos';
            }
        } catch (PDOException $e) {
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
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #001F3F 0%, #003d7a 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }
        .login-container h1 {
            color: #001F3F;
            text-align: center;
            margin-bottom: 30px;
            font-weight: bold;
        }
        .login-container .form-control {
            margin-bottom: 15px;
        }
        .login-container .btn {
            width: 100%;
            padding: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>🔧 Oficina360</h1>

        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
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

            <button type="submit" class="btn btn-primary">Entrar</button>
        </form>

        <hr>
        <p class="text-center text-muted small">
            Credenciais de teste:<br>
            Email: admin@oficina360.com<br>
            Senha: admin123
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
