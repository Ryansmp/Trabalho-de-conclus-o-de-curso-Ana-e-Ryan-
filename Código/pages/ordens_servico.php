<?php
require_once '../php/config.php';
require_once '../php/check_session.php';
/** @var PDO $pdo */
$mensagem = '';
$tipo_mensagem = '';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'checklist_ok') {
        $mensagem = 'Checklist salvo com sucesso! A OS agora está em andamento.';
        $tipo_mensagem = 'success';
    } elseif ($_GET['msg'] === 'finalizada') {
        $mensagem = 'Serviço finalizado com sucesso!';
        $tipo_mensagem = 'success';
    } elseif ($_GET['msg'] === 'deleted') {
        $mensagem = 'Ordem de serviço excluída com sucesso!';
        $tipo_mensagem = 'success';
    } elseif ($_GET['msg'] === 'created') {
        $mensagem = 'Ordem de serviço criada com sucesso!';
        $tipo_mensagem = 'success';
    } elseif ($_GET['msg'] === 'erro_finalizacao') {
        $mensagem = 'Erro ao finalizar a ordem de serviço. Tente novamente.';
        $tipo_mensagem = 'danger';
    } elseif ($_GET['msg'] === 'erro_imagem') {
        $mensagem = 'Erro: Imagem final é obrigatória para concluir o serviço.';
        $tipo_mensagem = 'danger';
    }
}

// Processar POST para Criar/Editar OS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'salvar_os') {
    $id = $_POST['id'] ?? null;
    $cliente_id = $_POST['cliente_id'] ?? '';
    $placa = $_POST['placa'] ?? '';
    $cor = $_POST['cor'] ?? '';
    $marca = $_POST['marca'] ?? '';
    $modelo = $_POST['modelo'] ?? '';
    $descricao_servico = $_POST['descricao_servico'] ?? '';

    $itens_desc = $_POST['item_descricao'] ?? [];
    $itens_valor = $_POST['item_valor'] ?? [];
    $total_orcamento = 0;
    foreach($itens_valor as $v) { $total_orcamento += (float)$v; }

    try {
        $pdo->beginTransaction();

        if ($id) {
            $stmt = $pdo->prepare("UPDATE ordens_servico SET cliente_id = ?, placa = ?, cor = ?, marca = ?, modelo = ?, descricao_servico = ?, orcamento = ?, atualizado_em = NOW() WHERE id = ?");
            $stmt->execute([$cliente_id, $placa, $cor, $marca, $modelo, $descricao_servico, $total_orcamento, $id]);

            $stmtDel = $pdo->prepare("DELETE FROM os_itens WHERE os_id = ?");
            $stmtDel->execute([$id]);
            $os_id = $id;
        } else {
            $numero = 'OS-' . date('YmdHis');
            $stmt = $pdo->prepare("INSERT INTO ordens_servico (numero, cliente_id, placa, cor, marca, modelo, descricao_servico, orcamento, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendente')");
            $stmt->execute([$numero, $cliente_id, $placa, $cor, $marca, $modelo, $descricao_servico, $total_orcamento]);
            $os_id = $pdo->lastInsertId();
        }

        $stmtItem = $pdo->prepare("INSERT INTO os_itens (os_id, descricao, valor) VALUES (?, ?, ?)");
        for ($i = 0; $i < count($itens_desc); $i++) {
            if (!empty($itens_desc[$i])) {
                $stmtItem->execute([$os_id, $itens_desc[$i], $itens_valor[$i]]);
            }
        }

        $pdo->commit();
        header("Location: ordens_servico.php?msg=created");
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $mensagem = 'Erro ao salvar a ordem de serviço. Tente novamente.';
        $tipo_mensagem = 'danger';
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensagem = 'Erro inesperado. Tente novamente.';
        $tipo_mensagem = 'danger';
    }
}

// Processar Exclusão de OS
if (isset($_GET['deletar_os'])) {
    $id_deletar = $_GET['deletar_os'];
    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM os_itens WHERE os_id = ?")->execute([$id_deletar]);
        $pdo->prepare("DELETE FROM checklist_entrada WHERE os_id = ?")->execute([$id_deletar]);
        $pdo->prepare("DELETE FROM checklist_midia WHERE os_id = ?")->execute([$id_deletar]);
        $pdo->prepare("DELETE FROM ordens_servico WHERE id = ?")->execute([$id_deletar]);
        $pdo->commit();
        header("Location: ordens_servico.php?msg=deleted");
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $mensagem = 'Erro ao excluir a ordem de serviço. Tente novamente.';
        $tipo_mensagem = 'danger';
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensagem = 'Erro inesperado. Tente novamente.';
        $tipo_mensagem = 'danger';
    }
}

// Buscar TODAS as OS
$stmt = $pdo->query("SELECT os.*, c.nome as cliente_nome,
                     (SELECT COUNT(*) FROM checklist_entrada WHERE os_id = os.id) as tem_checklist
                     FROM ordens_servico os
                     JOIN clientes c ON os.cliente_id = c.id
                     ORDER BY os.data_criacao DESC");
$todas_os = $stmt->fetchAll(PDO::FETCH_ASSOC);

$os_pendentes = [];
$os_em_andamento = [];
$os_finalizadas = [];

foreach ($todas_os as $os) {
    $status = strtolower(trim($os['status'] ?? ''));
    if ($status === 'em_andamento') { $os_em_andamento[] = $os; }
    elseif ($status === 'finalizada') { $os_finalizadas[] = $os; }
    else { $os_pendentes[] = $os; }
}

$stmt = $pdo->query("SELECT id, nome FROM clientes ORDER BY nome");
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordens de Serviço - Oficina360</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <style>
        /* Forçando a cor absoluta em todo o documento */
        :root {
            --azul-marinho: #1a237e !important;
            --amarelo: #FFD700 !important;
        }

        /* Reset de opacidade e filtros que podem clarear a cor */
        * { opacity: 1 !important; filter: none !important; }

        body { background-color: #f4f7f6 !important; }

        /* Navbar e Sidebar com cor sólida absoluta */
        .navbar { background-color: #001F3F !important; height: 56px; }
        .sidebar {
            top: 56px !important;
            background-color: #001F3F !important;
            width: 250px;
            min-height: calc(100vh - 56px);
            position: fixed;
        }
        .sidebar .nav-link { color: #ffffff !important; border-left: 3px solid transparent; }
        .sidebar .nav-link.active {
            background-color: rgba(255, 215, 0, 0.15) !important;
            color: #FFD700 !important;
            border-left-color: #FFD700 !important;
        }

        .main-content { margin-left: 250px; margin-top: 56px; padding: 30px; }

        /* Abas de Status */
        .nav-tabs .nav-link.active { background-color: #001F3F !important; color: #ffffff !important; }

        /* Botões e Modais com cor sólida */
        .btn-primary, .modal-header.bg-primary {
            background-color: #001F3F !important;
            border-color: #001F3F !important;
            color: #ffffff !important;
        }

        /* Modal com fundo transparente */
        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.3) !important;
        }

        .modal-content {
            background-color: #ffffff !important;
        }
        .btn-primary:hover { background-color: #000d1f !important; }

        .os-card { border-top: 4px solid #001F3F !important; }

        /* Ajuste para o modal de Nova OS */
        #modalOS .modal-header { background-color: #001F3F !important; }
        #modalOS .btn-primary { background-color: #001F3F !important; }

        /* Remover fundo preto do modal */
        .modal.show .modal-dialog {
            background: transparent !important;
        }

        /* Responsividade Mobile */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: fixed;
                left: -100%;
                transition: left 0.3s;
                z-index: 999;
                min-height: 100vh;
            }

            .sidebar.active {
                left: 0;
            }

            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .col-md-4 {
                flex: 0 0 100% !important;
                max-width: 100% !important;
            }

            .nav-tabs {
                flex-wrap: nowrap;
                overflow-x: auto;
            }

            .nav-tabs .nav-link {
                white-space: nowrap;
                font-size: 0.85rem;
            }

            .os-card {
                padding: 15px !important;
            }

            .d-grid {
                gap: 10px !important;
            }

            .btn-sm {
                padding: 0.4rem 0.6rem;
                font-size: 0.8rem;
            }

            .modal-dialog {
                margin: 0.5rem;
            }
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }

        .status-pendente {
            background-color: #ffc107;
            color: #000;
        }

        .status-andamento {
            background-color: #0d6efd;
            color: #fff;
        }

        .status-finalizada {
            background-color: #198754;
            color: #fff;
        }
    </style>
</head>
<body>
    <!-- Navbar Superior -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <span class="navbar-brand"><i class="bi bi-tools"></i> Oficina360</span>
            <button class="navbar-toggler d-md-none" type="button" id="sidebarToggle">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="ms-auto">
                <span class="text-white me-3"><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></span>
                <a href="../php/logout.php" class="btn btn-warning btn-sm">Sair</a>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <nav id="sidebar" class="sidebar">
        <div class="nav flex-column">
            <a href="../index.php" class="nav-link"><i class="bi bi-house"></i> Início</a>
            <a href="clientes.php" class="nav-link"><i class="bi bi-people"></i> Clientes</a>
            <a href="ordens_servico.php" class="nav-link active"><i class="bi bi-file-text"></i> Ordens de Serviço</a>
            <a href="estoque.php" class="nav-link"><i class="bi bi-box"></i> Estoque</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <h3 class="fw-bold text-dark"><i class="bi bi-tools me-2"></i> Gestão de Ordens de Serviço</h3>
            <button class="btn btn-primary px-4 fw-bold" style="background-color: #1a237e !important;" data-bs-toggle="modal" data-bs-target="#modalOS" onclick="limparFormulario()">
                <i class="bi bi-plus-lg me-1"></i> NOVA OS
            </button>
        </div>

        <?php if ($mensagem): ?>
            <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show border-0 shadow-sm mb-4">
                <i class="bi bi-check-circle-fill me-2"></i> <?php echo $mensagem; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <ul class="nav nav-tabs" id="osTabs" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#pendente">Aguardando Checklist <span class="badge bg-warning text-dark ms-2"><?php echo count($os_pendentes); ?></span></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#andamento">Em Andamento <span class="badge bg-primary ms-2"><?php echo count($os_em_andamento); ?></span></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#finalizada">Finalizadas <span class="badge bg-success ms-2"><?php echo count($os_finalizadas); ?></span></button></li>
        </ul>

        <div class="tab-content">
            <!-- ABA PENDENTE -->
            <div class="tab-pane fade show active" id="pendente">
                <div class="row">
                    <?php if (empty($os_pendentes)): ?>
                        <div class="col-12 text-center p-5 text-muted bg-white rounded-3 shadow-sm">Nenhuma ordem de serviço aguardando checklist.</div>
                    <?php endif; ?>
                    <?php foreach ($os_pendentes as $os): ?>
                        <div class="col-md-4">
                            <div class="os-card p-4 bg-white rounded shadow-sm mb-3">
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="fw-bold" style="color: #1a237e !important;">#<?php echo $os['numero']; ?></span>
                                    <span class="status-badge status-pendente">Pendente</span>
                                </div>
                                <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($os['cliente_nome']); ?></h6>
                                <p class="text-muted small mb-3"><?php echo $os['modelo']; ?> - <?php echo $os['placa']; ?></p>
                                <div class="d-grid gap-2">
                                    <a href="checklist.php?os_id=<?php echo $os['id']; ?>" class="btn btn-primary btn-sm fw-bold">FAZER CHECKLIST</a>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-outline-secondary btn-sm flex-grow-1" onclick="editarOS(<?php echo htmlspecialchars(json_encode($os)); ?>)" data-bs-toggle="modal" data-bs-target="#modalOS"><i class="bi bi-pencil me-1"></i> Editar</button>
                                        <a href="?deletar_os=<?php echo $os['id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Excluir esta OS?')"><i class="bi bi-trash"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ABA EM ANDAMENTO -->
            <div class="tab-pane fade" id="andamento">
                <div class="row">
                    <?php if (empty($os_em_andamento)): ?>
                        <div class="col-12 text-center p-5 text-muted bg-white rounded-3 shadow-sm">Nenhuma ordem de serviço em andamento.</div>
                    <?php endif; ?>
                    <?php foreach ($os_em_andamento as $os): ?>
                        <div class="col-md-4">
                            <div class="os-card p-4 bg-white rounded shadow-sm mb-3">
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="fw-bold" style="color: #1a237e !important;">#<?php echo $os['numero']; ?></span>
                                    <span class="status-badge status-andamento">Em Andamento</span>
                                </div>
                                <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($os['cliente_nome']); ?></h6>
                                <p class="text-muted small mb-3"><?php echo $os['modelo']; ?> - <?php echo $os['placa']; ?></p>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-success btn-sm fw-bold" onclick="abrirModalFinalizar(<?php echo $os['id']; ?>)">CONCLUIR SERVIÇO</button>
                                    <a href="checklist.php?os_id=<?php echo $os['id']; ?>" class="btn btn-outline-primary btn-sm" style="color: #1a237e !important; border-color: #1a237e !important;">VER CHECKLIST</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ABA FINALIZADA -->
            <div class="tab-pane fade" id="finalizada">
                <div class="row">
                    <?php if (empty($os_finalizadas)): ?>
                        <div class="col-12 text-center p-5 text-muted bg-white rounded-3 shadow-sm">Nenhuma ordem de serviço finalizada.</div>
                    <?php endif; ?>
                    <?php foreach ($os_finalizadas as $os): ?>
                        <div class="col-md-4">
                            <div class="os-card p-4 bg-white rounded shadow-sm mb-3">
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="fw-bold" style="color: #1a237e !important;">#<?php echo $os['numero']; ?></span>
                                    <span class="status-badge status-finalizada">Finalizada</span>
                                </div>
                                <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($os['cliente_nome']); ?></h6>
                                <p class="text-muted small mb-3"><?php echo $os['modelo']; ?> - <?php echo $os['placa']; ?></p>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-outline-dark btn-sm fw-bold" onclick="verDetalhes(<?php echo $os['id']; ?>)">VER ANTES / DEPOIS</button>
                                    <a href="emitir_nota.php?os_id=<?php echo $os['id']; ?>" class="btn btn-success btn-sm fw-bold"><i class="bi bi-file-pdf me-1"></i> EMITIR NOTA</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL NOVA/EDITAR OS -->
    <div class="modal fade" id="modalOS" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header text-white" style="background-color: #1a237e !important;">
                    <h5 class="modal-title fw-bold" id="modalTitle">Nova Ordem de Serviço</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="acao" value="salvar_os">
                    <input type="hidden" name="id" id="os_id">
                    <div class="modal-body p-4">
                        <div class="row g-3 mb-4">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Cliente:</label>
                                <select class="form-select" name="cliente_id" id="os_cliente_id" required>
                                    <option value="">Selecione o Cliente...</option>
                                    <?php foreach ($clientes as $c): ?>
                                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nome']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Marca:</label>
                                <input type="text" class="form-control" name="marca" id="os_marca" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Modelo:</label>
                                <input type="text" class="form-control" name="modelo" id="os_modelo" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Placa:</label>
                                <input type="text" class="form-control" name="placa" id="os_placa" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Cor:</label>
                                <input type="text" class="form-control" name="cor" id="os_cor" required>
                            </div>
                        </div>

                        <h6 class="fw-bold border-bottom pb-2 mb-3" style="color: #1a237e !important;">Itens do Orçamento</h6>
                        <div id="orcamento-itens">
                            <div class="row g-2 mb-2">
                                <div class="col-md-8"><input type="text" class="form-control" name="item_descricao[]" placeholder="Descrição do serviço ou peça"></div>
                                <div class="col-md-3"><input type="number" step="0.01" class="form-control" name="item_valor[]" placeholder="Valor R$"></div>
                                <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100" onclick="this.parentElement.parentElement.remove()"><i class="bi bi-trash"></i></button></div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm mt-2 fw-bold" style="color: #1a237e !important; border-color: #1a237e !important;" onclick="adicionarItemOrcamento()">+ ADICIONAR ITEM</button>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold" style="background-color: #1a237e !important;">SALVAR ORDEM DE SERVIÇO</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL FINALIZAR -->
    <div class="modal fade" id="modalFinalizar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">Concluir Serviço</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="finalizar_os.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="os_id" id="finalizar_os_id">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Foto do Serviço Finalizado:</label>
                            <input type="file" class="form-control" name="imagem_final" accept="image/*" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Forma de Pagamento:</label>
                            <select class="form-select" name="forma_pagamento" required>
                                <option value="">Selecione...</option>
                                <option value="PIX">PIX</option>
                                <option value="Dinheiro">Dinheiro</option>
                                <option value="Cartão de Crédito">Cartão de Crédito</option>
                                <option value="Cartão de Débito">Cartão de Débito</option>
                            </select>
                        </div>
                        <h6 class="fw-bold text-success border-bottom pb-2 mb-3 mt-4">Checklist de Saída</h6>
                        <div class="row g-2">
                            <div class="col-6"><label class="small fw-bold">Farol Alto:</label><select class="form-select form-select-sm" name="saida_farol_alto"><option value="funcionando">Funcionando</option><option value="nao_funcionando">Não Funcionando</option></select></div>
                            <div class="col-6"><label class="small fw-bold">Farol Baixo:</label><select class="form-select form-select-sm" name="saida_farol_baixo"><option value="funcionando">Funcionando</option><option value="nao_funcionando">Não Funcionando</option></select></div>
                            <div class="col-6"><label class="small fw-bold">Farolete:</label><select class="form-select form-select-sm" name="saida_farolete"><option value="funcionando">Funcionando</option><option value="nao_funcionando">Não Funcionando</option></select></div>
                            <div class="col-6"><label class="small fw-bold">Setas:</label><select class="form-select form-select-sm" name="saida_setas"><option value="funcionando">Funcionando</option><option value="nao_funcionando">Não Funcionando</option></select></div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="submit" class="btn btn-success w-100 fw-bold">FINALIZAR E SALVAR</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL DETALHES -->
    <div class="modal fade" id="modalDetalhes" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold">Comparativo Antes e Depois</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" id="detalhes-body"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script>
        function adicionarItemOrcamento() {
            const container = document.getElementById('orcamento-itens');
            const div = document.createElement('div');
            div.className = 'row g-2 mb-2';
            div.innerHTML = `
                <div class="col-md-8"><input type="text" class="form-control" name="item_descricao[]" placeholder="Descrição"></div>
                <div class="col-md-3"><input type="number" step="0.01" class="form-control" name="item_valor[]" placeholder="Valor"></div>
                <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100" onclick="this.parentElement.parentElement.remove()"><i class="bi bi-trash"></i></button></div>
            `;
            container.appendChild(div);
        }

        function limparFormulario() {
            document.getElementById('os_id').value = '';
            document.getElementById('os_cliente_id').value = '';
            document.getElementById('os_modelo').value = '';
            document.getElementById('os_placa').value = '';
            document.getElementById('os_cor').value = '';
            document.getElementById('orcamento-itens').innerHTML = '<div class="row g-2 mb-2"><div class="col-md-8"><input type="text" class="form-control" name="item_descricao[]" placeholder="Descrição"></div><div class="col-md-3"><input type="number" step="0.01" class="form-control" name="item_valor[]" placeholder="Valor"></div><div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100" onclick="this.parentElement.parentElement.remove()"><i class="bi bi-trash"></i></button></div></div>';
            document.getElementById('modalTitle').innerText = 'Nova Ordem de Serviço';
        }

        function editarOS(os) {
            document.getElementById('os_id').value = os.id;
            document.getElementById('os_cliente_id').value = os.cliente_id;
            document.getElementById('os_marca').value = os.marca || '';
            document.getElementById('os_modelo').value = os.modelo;
            document.getElementById('os_placa').value = os.placa;
            document.getElementById('os_cor').value = os.cor;
            document.getElementById('modalTitle').innerText = 'Editar Ordem de Serviço';

            // Carregar itens do orçamento
            console.log('Buscando itens para OS ID:', os.id);
            fetch(`get_os_itens.php?os_id=${os.id}`)
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Dados recebidos:', data);
                    const container = document.getElementById('orcamento-itens');
                    container.innerHTML = '';

                    if (data.success && data.itens.length > 0) {
                        data.itens.forEach(item => {
                            const div = document.createElement('div');
                            div.className = 'row g-2 mb-2';
                            div.innerHTML = `
                                <div class="col-md-8"><input type="text" class="form-control" name="item_descricao[]" value="${item.descricao}" placeholder="Descrição"></div>
                                <div class="col-md-3"><input type="number" step="0.01" class="form-control" name="item_valor[]" value="${item.valor}" placeholder="Valor"></div>
                                <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100" onclick="this.parentElement.parentElement.remove()"><i class="bi bi-trash"></i></button></div>
                            `;
                            container.appendChild(div);
                        });
                    } else {
                        const div = document.createElement('div');
                        div.className = 'row g-2 mb-2';
                        div.innerHTML = `
                            <div class="col-md-8"><input type="text" class="form-control" name="item_descricao[]" placeholder="Descrição"></div>
                            <div class="col-md-3"><input type="number" step="0.01" class="form-control" name="item_valor[]" placeholder="Valor"></div>
                            <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100" onclick="this.parentElement.parentElement.remove()"><i class="bi bi-trash"></i></button></div>
                        `;
                        container.appendChild(div);
                    }
                })
                .catch(error => console.error('Erro ao carregar itens:', error));
        }

        function abrirModalFinalizar(id) {
            document.getElementById('finalizar_os_id').value = id;
            new bootstrap.Modal(document.getElementById('modalFinalizar')).show();
        }

        function verDetalhes(id) {
            const body = document.getElementById('detalhes-body');
            body.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary"></div><p class="mt-2">Carregando comparativo...</p></div>';
            new bootstrap.Modal(document.getElementById('modalDetalhes')).show();
            fetch(`get_os_detalhes.php?os_id=${id}`).then(r => r.text()).then(html => { body.innerHTML = html; });
        }
    </script>
</body>
</html>
