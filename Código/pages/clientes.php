<?php
require '../php/check_session.php';
require '../php/config.php';

$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $cpf_cnpj = $_POST['cpf_cnpj'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $email = $_POST['email'] ?? '';
    $endereco = $_POST['endereco'] ?? '';
    $action = $_POST['action'] ?? '';

    if ($action === 'criar') {
        try {
            $stmt = $pdo->prepare('INSERT INTO clientes (nome, cpf_cnpj, telefone, email, endereco) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$nome, $cpf_cnpj, $telefone, $email, $endereco]);
            $mensagem = 'Cliente cadastrado com sucesso!';
            $tipo_mensagem = 'success';
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $mensagem = 'CPF/CNPJ já cadastrado!';
            } else {
                $mensagem = 'Erro ao cadastrar cliente';
            }
            $tipo_mensagem = 'danger';
        }
    } elseif ($action === 'editar') {
        $id = $_POST['id'] ?? '';
        try {
            $stmt = $pdo->prepare('UPDATE clientes SET nome = ?, cpf_cnpj = ?, telefone = ?, email = ?, endereco = ? WHERE id = ?');
            $stmt->execute([$nome, $cpf_cnpj, $telefone, $email, $endereco, $id]);
            $mensagem = 'Cliente atualizado com sucesso!';
            $tipo_mensagem = 'success';
        } catch (PDOException $e) {
            $mensagem = 'Erro ao atualizar cliente';
            $tipo_mensagem = 'danger';
        }
    }
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $pdo->prepare('DELETE FROM clientes WHERE id = ?')->execute([$id]);
        $mensagem = 'Cliente deletado com sucesso!';
        $tipo_mensagem = 'success';
    } catch (PDOException $e) {
        $mensagem = 'Erro ao deletar cliente';
        $tipo_mensagem = 'danger';
    }
}

$clientes = $pdo->query('SELECT * FROM clientes ORDER BY criado_em DESC')->fetchAll();
$cliente_edicao = null;

if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM clientes WHERE id = ?');
    $stmt->execute([$id]);
    $cliente_edicao = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - Oficina360</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">🔧 Oficina360</a>
            <button class="navbar-toggler" type="button" id="sidebarToggle">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="ms-auto">
                <span class="text-white me-3"><?php echo $_SESSION['usuario_nome']; ?></span>
                <a href="../php/logout.php" class="btn btn-sm btn-warning">Sair</a>
            </div>
        </div>
    </nav>

    <div class="d-flex">
        <nav class="sidebar">
            <div class="nav flex-column">
                <a href="../index.php" class="nav-link">
                    <i class="bi bi-house-door"></i> Início
                </a>
                <a href="clientes.php" class="nav-link active">
                    <i class="bi bi-people"></i> Clientes
                </a>
                <a href="ordens_servico.php" class="nav-link">
                    <i class="bi bi-file-text"></i> Ordens de Serviço
                </a>
                <a href="checklist.php" class="nav-link">
                    <i class="bi bi-check-square"></i> Checklist
                </a>
                <a href="estoque.php" class="nav-link">
                    <i class="bi bi-box"></i> Estoque
                </a>
            </div>
        </nav>

        <div class="main-content w-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Cadastro de Clientes</h1>
                    <p class="text-muted">Gerenciar clientes da oficina</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCliente">
                    <i class="bi bi-plus-circle"></i> Novo Cliente
                </button>
            </div>

            <?php if (!empty($mensagem)): ?>
                <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show">
                    <i class="bi bi-<?php echo $tipo_mensagem === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($mensagem); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <i class="bi bi-list"></i> Lista de Clientes (<?php echo count($clientes); ?> cadastrados)
                </div>
                <div class="card-body">
                    <?php if (count($clientes) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>CPF/CNPJ</th>
                                        <th>Telefone</th>
                                        <th>Email</th>
                                        <th>Endereço</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($clientes as $cliente): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($cliente['nome']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($cliente['cpf_cnpj']); ?></td>
                                            <td><?php echo htmlspecialchars($cliente['telefone'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($cliente['email'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($cliente['endereco'] ?? '-'); ?></td>
                                            <td>
                                                <a href="?edit=<?php echo $cliente['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalCliente" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="?delete=<?php echo $cliente['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja deletar este cliente?');" title="Deletar">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                            <p class="text-muted mt-3">Nenhum cliente cadastrado ainda.</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCliente">
                                <i class="bi bi-plus"></i> Cadastrar Primeiro Cliente
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCliente" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-<?php echo $cliente_edicao ? 'pencil' : 'plus-circle'; ?>"></i>
                        <?php echo $cliente_edicao ? 'Editar Cliente' : 'Novo Cliente'; ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="<?php echo $cliente_edicao ? 'editar' : 'criar'; ?>">
                        <?php if ($cliente_edicao): ?>
                            <input type="hidden" name="id" value="<?php echo $cliente_edicao['id']; ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?php echo $cliente_edicao ? htmlspecialchars($cliente_edicao['nome']) : ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="cpf_cnpj" class="form-label">CPF/CNPJ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="cpf_cnpj" name="cpf_cnpj" value="<?php echo $cliente_edicao ? htmlspecialchars($cliente_edicao['cpf_cnpj']) : ''; ?>" placeholder="000.000.000-00" required>
                        </div>
                        <div class="mb-3">
                            <label for="telefone" class="form-label">Telefone</label>
                            <input type="text" class="form-control" id="telefone" name="telefone" value="<?php echo $cliente_edicao ? htmlspecialchars($cliente_edicao['telefone']) : ''; ?>" placeholder="(11) 9999-9999">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo $cliente_edicao ? htmlspecialchars($cliente_edicao['email']) : ''; ?>" placeholder="cliente@email.com">
                        </div>
                        <div class="mb-3">
                            <label for="endereco" class="form-label">Endereço</label>
                            <input type="text" class="form-control" id="endereco" name="endereco" value="<?php echo $cliente_edicao ? htmlspecialchars($cliente_edicao['endereco']) : ''; ?>" placeholder="Rua, número, cidade">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check"></i> Salvar Cliente
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
</body>
</html>
