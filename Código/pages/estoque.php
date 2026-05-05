<?php
require '../php/check_session.php';
require '../php/config.php';
/** @var PDO $pdo */

$eh_mecanico = $_SESSION['usuario_role'] === 'mecanico';

$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $quantidade = $_POST['quantidade'] ?? 0;
    $quantidade_minima = $_POST['quantidade_minima'] ?? 5;
    $preco_unitario = $_POST['preco_unitario'] ?? 0;
    $action = $_POST['action'] ?? '';

    if ($action === 'criar') {
        try {
            $stmt = $pdo->prepare('INSERT INTO pecas (nome, descricao, quantidade, quantidade_minima, preco_unitario) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$nome, $descricao, $quantidade, $quantidade_minima, $preco_unitario]);
            header('Location: estoque.php');
            exit();
        } catch (PDOException $e) {
            $mensagem = 'Erro ao adicionar peça';
            $tipo_mensagem = 'danger';
        }
    } elseif ($action === 'editar') {
        $id = $_POST['id'] ?? '';
        try {
            $stmt = $pdo->prepare('UPDATE pecas SET nome = ?, descricao = ?, quantidade = ?, quantidade_minima = ?, preco_unitario = ? WHERE id = ?');
            $stmt->execute([$nome, $descricao, $quantidade, $quantidade_minima, $preco_unitario, $id]);
            header('Location: estoque.php');
            exit();
        } catch (PDOException $e) {
            $mensagem = 'Erro ao atualizar peça';
            $tipo_mensagem = 'danger';
        }
    }
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $pdo->prepare('DELETE FROM pecas WHERE id = ?')->execute([$id]);
        $mensagem = 'Peça deletada com sucesso!';
        $tipo_mensagem = 'success';
    } catch (PDOException $e) {
        $mensagem = 'Erro ao deletar peça';
        $tipo_mensagem = 'danger';
    }
}

$pecas = $pdo->query('SELECT * FROM pecas ORDER BY criado_em DESC')->fetchAll();
$pecas_baixo = $pdo->query('SELECT COUNT(*) FROM pecas WHERE quantidade <= quantidade_minima')->fetchColumn();
$pecas_total = count($pecas);
$valor_total = $pdo->query('SELECT SUM(quantidade * preco_unitario) FROM pecas')->fetchColumn() ?? 0;

$peca_edicao = null;

if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM pecas WHERE id = ?');
    $stmt->execute([$id]);
    $peca_edicao = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estoque - Oficina360</title>
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
                <a href="clientes.php" class="nav-link">
                    <i class="bi bi-people"></i> Clientes
                </a>
                <a href="ordens_servico.php" class="nav-link">
                    <i class="bi bi-file-text"></i> Ordens de Serviço
                </a>
                <a href="estoque.php" class="nav-link active">
                    <i class="bi bi-box"></i> Estoque
                </a>
            </div>
        </nav>

        <div class="main-content w-100">
            <?php if ($eh_mecanico): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i> <strong>Acesso Restrito!</strong><br>
                    Você está logado como <strong>Mecânico</strong>. Você pode visualizar apenas as <strong>Ordens de Serviço</strong>.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <a href="ordens_servico.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-arrow-right"></i> Ir para Ordens de Serviço
                        </a>
                    </div>
                </div>
            <?php else: ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Controle de Estoque</h1>
                    <p class="text-muted">Gerenciar peças e materiais</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPeca">
                    <i class="bi bi-plus-circle"></i> Adicionar Peça
                </button>
            </div>

            <?php if (!empty($mensagem)): ?>
                <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show">
                    <i class="bi bi-<?php echo $tipo_mensagem === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($mensagem); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row mb-4">
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="metric-card">
                        <h5>Total de Peças</h5>
                        <div class="number"><?php echo $pecas_total; ?></div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="metric-card">
                        <h5>Estoque Baixo</h5>
                        <div class="number" style="color: #ff6b6b;"><?php echo $pecas_baixo; ?></div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="metric-card">
                        <h5>Valor Total</h5>
                        <div class="number" style="font-size: 1.5rem;">R$ <?php echo number_format($valor_total, 2, ',', '.'); ?></div>
                    </div>
                </div>

            </div>

            <div class="card">
                <div class="card-header">
                    <i class="bi bi-list"></i> Peças em Estoque (<?php echo $pecas_total; ?> cadastradas)
                </div>
                <div class="card-body">
                    <?php if ($pecas_total > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Descrição</th>
                                        <th>Quantidade</th>
                                        <th>Preço Unit.</th>
                                        <th>Total</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pecas as $peca): ?>
                                        <?php
                                            $estoque_baixo = $peca['quantidade'] <= $peca['quantidade_minima'];
                                            $total_peca = $peca['quantidade'] * $peca['preco_unitario'];
                                        ?>
                                        <tr <?php echo $estoque_baixo ? 'style="background-color: #fff5f5;"' : ''; ?>>
                                            <td><strong><?php echo htmlspecialchars($peca['nome']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($peca['descricao'] ?? '-'); ?></td>
                                            <td><?php echo $peca['quantidade']; ?></td>
                                            <td>R$ <?php echo number_format($peca['preco_unitario'], 2, ',', '.'); ?></td>
                                            <td>R$ <?php echo number_format($total_peca, 2, ',', '.'); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info" onclick="editarPeca(<?php echo $peca['id']; ?>, '<?php echo htmlspecialchars($peca['nome']); ?>', '<?php echo htmlspecialchars($peca['descricao']); ?>', <?php echo $peca['quantidade']; ?>, 0, <?php echo $peca['preco_unitario']; ?>)" data-bs-toggle="modal" data-bs-target="#modalPeca" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <a href="?delete=<?php echo $peca['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja deletar esta peça?');" title="Deletar">
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
                            <p class="text-muted mt-3">Nenhuma peça cadastrada no estoque.</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPeca">
                                <i class="bi bi-plus"></i> Adicionar Primeira Peça
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalPeca" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-<?php echo $peca_edicao ? 'pencil' : 'plus-circle'; ?>"></i>
                        <?php echo $peca_edicao ? 'Editar Peça' : 'Adicionar Peça'; ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="criar">
                        <input type="hidden" name="id" value="">

                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome da Peça <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?php echo $peca_edicao ? htmlspecialchars($peca_edicao['nome']) : ''; ?>" placeholder="Ex: Correia de Distribuição" required>
                        </div>
                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <input type="text" class="form-control" id="descricao" name="descricao" value="<?php echo $peca_edicao ? htmlspecialchars($peca_edicao['descricao']) : ''; ?>" placeholder="Ex: Para motor 1.8">
                        </div>
                        <div class="mb-3">
                            <label for="quantidade" class="form-label">Quantidade <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="quantidade" name="quantidade" value="<?php echo $peca_edicao ? $peca_edicao['quantidade'] : '0'; ?>" min="0" required>
                        </div>

                        <div class="mb-3">
                            <label for="preco_unitario" class="form-label">Preço Unitário (R$) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="preco_unitario" name="preco_unitario" value="<?php echo $peca_edicao ? $peca_edicao['preco_unitario'] : '0'; ?>" min="0" step="0.01" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check"></i> Salvar Peça
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarPeca(id, nome, descricao, quantidade, quantidade_minima, preco_unitario) {
            document.querySelector('input[name="action"]').value = 'editar';
            document.querySelector('input[name="id"]').style.display = 'block';
            document.querySelector('input[name="id"]').value = id;
            document.getElementById('nome').value = nome;
            document.getElementById('descricao').value = descricao;
            document.getElementById('quantidade').value = quantidade;
            document.getElementById('preco_unitario').value = preco_unitario;
            document.querySelector('.modal-title').innerHTML = '<i class="bi bi-pencil"></i> Editar Peça';
        }

        function limparModal() {
            document.querySelector('input[name="action"]').value = 'criar';
            document.querySelector('input[name="id"]').style.display = 'none';
            document.querySelector('input[name="id"]').value = '';
            document.getElementById('nome').value = '';
            document.getElementById('descricao').value = '';
            document.getElementById('quantidade').value = '0';
            document.getElementById('quantidade_minima').value = '5';
            document.getElementById('preco_unitario').value = '0';
            document.querySelector('.modal-title').innerHTML = '<i class="bi bi-plus-circle"></i> Adicionar Peça';
        }

        document.getElementById('modalPeca').addEventListener('hidden.bs.modal', limparModal);
    </script>
            <?php endif; ?>
</body>
</html>
