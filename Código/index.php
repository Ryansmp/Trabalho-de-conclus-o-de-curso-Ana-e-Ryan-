<?php
require 'php/check_session.php';
require 'php/config.php';

$total_clientes = $pdo->query('SELECT COUNT(*) FROM clientes')->fetchColumn();
$ordens_abertas = $pdo->query("SELECT COUNT(*) FROM ordens_servico WHERE status != 'Concluida'")->fetchColumn();
$pecas_baixo = $pdo->query('SELECT COUNT(*) FROM pecas WHERE quantidade <= quantidade_minima')->fetchColumn();
$servicos_concluidos = $pdo->query("SELECT COUNT(*) FROM ordens_servico WHERE status = 'Concluida'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Oficina360</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">🔧 Oficina360</a>
            <button class="navbar-toggler" type="button" id="sidebarToggle">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="ms-auto">
                <span class="text-white me-3"><?php echo $_SESSION['usuario_nome']; ?></span>
                <a href="php/logout.php" class="btn btn-sm btn-warning">Sair</a>
            </div>
        </div>
    </nav>

    <div class="d-flex">
        <nav id="sidebar" class="sidebar">
            <div class="nav flex-column">
                <a href="index.php" class="nav-link active">
                    <i class="bi bi-house-door"></i> Início
                </a>
                <a href="pages/clientes.php" class="nav-link">
                    <i class="bi bi-people"></i> Clientes
                </a>
                <a href="pages/ordens_servico.php" class="nav-link">
                    <i class="bi bi-file-text"></i> Ordens de Serviço
                </a>
                <a href="pages/checklist.php" class="nav-link">
                    <i class="bi bi-check-square"></i> Checklist
                </a>
                <a href="pages/estoque.php" class="nav-link">
                    <i class="bi bi-box"></i> Estoque
                </a>
            </div>
        </nav>

        <div class="main-content w-100">
            <h1 class="mb-4">Bem-vindo ao Oficina360</h1>
            <p class="text-muted">Sistema de Gestão de Oficina Mecânica</p>

            <div class="row mb-4">
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="metric-card">
                        <h5>Ordens Abertas</h5>
                        <div class="number"><?php echo $ordens_abertas; ?></div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="metric-card">
                        <h5>Clientes Cadastrados</h5>
                        <div class="number"><?php echo $total_clientes; ?></div>
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
                        <h5>Serviços Concluídos</h5>
                        <div class="number" style="color: #51cf66;"><?php echo $servicos_concluidos; ?></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="bi bi-lightning"></i> Ações Rápidas
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-2">
                                    <a href="pages/clientes.php?action=novo" class="btn btn-primary w-100">
                                        <i class="bi bi-plus"></i> Novo Cliente
                                    </a>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <a href="pages/ordens_servico.php?action=novo" class="btn btn-secondary w-100">
                                        <i class="bi bi-plus"></i> Nova Ordem
                                    </a>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <a href="pages/checklist.php?action=novo" class="btn btn-primary w-100">
                                        <i class="bi bi-plus"></i> Novo Checklist
                                    </a>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <a href="pages/estoque.php?action=novo" class="btn btn-secondary w-100">
                                        <i class="bi bi-plus"></i> Adicionar Peça
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="bi bi-info-circle"></i> Resumo do Sistema
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li><strong>Total de Clientes:</strong> <?php echo $total_clientes; ?></li>
                                <li><strong>Ordens em Aberto:</strong> <?php echo $ordens_abertas; ?></li>
                                <li><strong>Peças com Estoque Baixo:</strong> <span style="color: #ff6b6b;"><?php echo $pecas_baixo; ?></span></li>
                                <li><strong>Serviços Concluídos:</strong> <span style="color: #51cf66;"><?php echo $servicos_concluidos; ?></span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>
