<?php
session_start();
/** @var PDO $pdo */
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require 'php/config.php';

$total_clientes = $pdo->query('SELECT COUNT(*) FROM clientes')->fetchColumn();
$ordens_abertas = $pdo->query("SELECT COUNT(*) FROM ordens_servico WHERE LOWER(status) != 'finalizada'")->fetchColumn();
$pecas_baixo = $pdo->query('SELECT COUNT(*) FROM pecas WHERE quantidade <= quantidade_minima')->fetchColumn();
$servicos_concluidos = $pdo->query("SELECT COUNT(*) FROM ordens_servico WHERE LOWER(status) = 'finalizada'")->fetchColumn();

$eh_mecanico = $_SESSION['usuario_role'] === 'mecanico';

function link_mecanico($url) {
    global $eh_mecanico;
    return $eh_mecanico ? 'pages/ordens_servico.php' : $url;
}
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
            <span class="navbar-brand"><i class="bi bi-tools"></i> Oficina360</span>

            <div class="ms-auto">
                <span class="text-white me-3">
                    <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>
                    <?php if ($eh_mecanico): ?>
                        <span class="badge bg-warning text-dark"></span>
                    <?php else: ?>
                        <span class="badge bg-info"></span>
                    <?php endif; ?>
                </span>
                <a href="php/logout.php" class="btn btn-sm btn-warning">Sair</a>
            </div>
        </div>
    </nav>

    <div class="d-flex">
        <nav id="sidebar" class="sidebar">
            <div class="nav flex-column">
                <a href="<?php echo link_mecanico('index.php'); ?>" class="nav-link <?php echo !$eh_mecanico ? 'active' : ''; ?>">
                    <i class="bi bi-house-door"></i> Início
                </a>
                <a href="<?php echo link_mecanico('pages/clientes.php'); ?>" class="nav-link">
                    <i class="bi bi-people"></i> Clientes
                </a>
                <a href="pages/ordens_servico.php" class="nav-link <?php echo $eh_mecanico ? 'active' : ''; ?>">
                    <i class="bi bi-file-text"></i> Ordens de Serviço
                </a>
                <a href="<?php echo link_mecanico('pages/estoque.php'); ?>" class="nav-link">
                    <i class="bi bi-box"></i> Estoque
                </a>
            </div>
        </nav>

        <div class="main-content w-100">
            <?php if ($eh_mecanico): ?>
                <div class="alert alert-info" role="alert">
                    <i class="bi bi-info-circle"></i> Você está logado como <strong>Mecânico</strong>.
                    Você pode visualizar apenas as <strong>Ordens de Serviço</strong>.
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header" style="background-color: #001F3F; color: white;">
                                <h5 class="mb-0">Ordens de Serviço</h5>
                            </div>
                            <div class="card-body">
                                <p>Total de ordens abertas: <strong><?php echo $ordens_abertas; ?></strong></p>
                                <p>Total de serviços concluídos: <strong><?php echo $servicos_concluidos; ?></strong></p>
                                <a href="pages/ordens_servico.php" class="btn btn-primary">
                                    <i class="bi bi-arrow-right"></i> Ir para Ordens de Serviço
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
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
                                        <a href="<?php echo link_mecanico('pages/clientes.php'); ?>" class="btn btn-primary w-100">
                                            <i class="bi bi-plus"></i> Novo Cliente
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <a href="pages/ordens_servico.php" class="btn btn-secondary w-100">
                                            <i class="bi bi-plus"></i> Nova Ordem
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <a href="<?php echo link_mecanico('pages/estoque.php'); ?>" class="btn btn-secondary w-100">
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
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>
