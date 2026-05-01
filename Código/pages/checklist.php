<?php
require_once '../php/config.php';
require_once '../php/check_session.php';

$os_id = $_GET['os_id'] ?? null;
if (!$os_id) {
    header("Location: ordens_servico.php");
    exit;
}

// Buscar dados da OS
$stmt = $pdo->prepare("SELECT os.*, c.nome as cliente_nome FROM ordens_servico os JOIN clientes c ON os.cliente_id = c.id WHERE os.id = ?");
$stmt->execute([$os_id]);
$os = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$os) {
    die("Ordem de Serviço não encontrada.");
}

// Buscar checklist existente
$stmt = $pdo->prepare("SELECT * FROM checklist_entrada WHERE os_id = ?");
$stmt->execute([$os_id]);
$checklist = $stmt->fetch(PDO::FETCH_ASSOC);

// Buscar mídias existentes
$stmtMidia = $pdo->prepare("SELECT * FROM checklist_midia WHERE os_id = ?");
$stmtMidia->execute([$os_id]);
$midias = $stmtMidia->fetchAll(PDO::FETCH_ASSOC);

$mensagens_erro = [];

// Processar salvamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aspecto_visual = $_POST['aspecto_visual'] ?? '';
    $farol_original = $_POST['farol_original'] ?? '';
    $farol_alto = $_POST['farol_alto'] ?? '';
    $farol_baixo = $_POST['farol_baixo'] ?? '';
    $farolete = $_POST['farolete'] ?? '';
    $setas = $_POST['setas'] ?? '';
    $observacoes = $_POST['observacoes'] ?? '';

    try {
        $pdo->beginTransaction();

        if ($checklist) {
            $stmt = $pdo->prepare("UPDATE checklist_entrada SET aspecto_visual = ?, farol_original = ?, farol_alto = ?, farol_baixo = ?, farolete = ?, setas = ?, observacoes = ? WHERE os_id = ?");
            $stmt->execute([$aspecto_visual, $farol_original, $farol_alto, $farol_baixo, $farolete, $setas, $observacoes, $os_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO checklist_entrada (os_id, aspecto_visual, farol_original, farol_alto, farol_baixo, farolete, setas, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$os_id, $aspecto_visual, $farol_original, $farol_alto, $farol_baixo, $farolete, $setas, $observacoes]);
        }

        if (isset($_FILES['midias'])) {
            $upload_dir = '../uploads/checklists/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            foreach ($_FILES['midias']['name'] as $key => $val) {
                if ($_FILES['midias']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_name = $_FILES['midias']['name'][$key];
                    $tmp_name = $_FILES['midias']['tmp_name'][$key];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $new_name = 'os_' . $os_id . '_media_' . uniqid() . '_' . $key . '.' . $file_ext;
                    $target_path = $upload_dir . $new_name;

                    if (move_uploaded_file($tmp_name, $target_path)) {
                        $tipo = in_array($file_ext, ['mp4', 'webm', 'ogg', 'mov', 'avi']) ? 'video' : 'imagem';
                        $url = 'uploads/checklists/' . $new_name;
                        $stmtM = $pdo->prepare("INSERT INTO checklist_midia (os_id, arquivo_url, tipo) VALUES (?, ?, ?)");
                        $stmtM->execute([$os_id, $url, $tipo]);
                    }
                }
            }
        }

        $stmtStatus = $pdo->prepare("UPDATE ordens_servico SET status = 'em_andamento' WHERE id = ?");
        $stmtStatus->execute([$os_id]);

        $pdo->commit();
        header("Location: ordens_servico.php?msg=checklist_ok");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensagens_erro[] = "Erro ao salvar: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checklist de Entrada - Oficina360</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body { background-color: #f4f7f6 !important; }
        .navbar { background-color: #001F3F !important; height: 56px; }
        .sidebar { width: 250px; background-color: #001F3F !important; color: white; position: fixed; left: 0; top: 56px; height: calc(100vh - 56px); z-index: 1000; overflow-y: auto; }
        .sidebar a { color: white; text-decoration: none; padding: 12px 20px; display: block; transition: 0.3s; border-left: 3px solid transparent; }
        .sidebar a:hover { background: rgba(255, 215, 0, 0.1); border-left-color: #FFD700; }
        .main-content { margin-left: 250px; margin-top: 56px; padding: 30px; min-height: calc(100vh - 56px); background: #f4f7f6; }
        .card { border-radius: 12px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .upload-item { background: #ffffff; padding: 15px; border-radius: 10px; margin-bottom: 12px; border: 2px dashed #e0e0e0; transition: 0.3s; }
        .upload-item:hover { border-color: #001F3F; background: #f8faff; }
        .upload-item.drag-over { border-color: #001F3F; background: #e3f2fd; }
        .media-preview { width: 100%; height: 150px; object-fit: cover; border-radius: 8px; margin-bottom: 8px; cursor: pointer; }
        .media-container { position: relative; }
        .media-type-icon { position: absolute; top: 5px; right: 5px; background: rgba(0,0,0,0.7); color: white; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .section-title { border-left: 4px solid #001F3F; padding-left: 15px; margin-bottom: 20px; color: #001F3F; font-weight: 700; }
        .btn-primary { background-color: #001F3F !important; border-color: #001F3F !important; }
        .btn-primary:hover { background-color: #000d1f !important; border-color: #000d1f !important; }
        .form-select:focus { border-color: #001F3F !important; box-shadow: 0 0 0 0.2rem rgba(0, 31, 63, 0.25) !important; }
        .badge { background-color: #001F3F !important; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <span class="navbar-brand"><i class="bi bi-tools"></i> Oficina360</span>
            <div class="ms-auto">
                <span class="text-white me-3">Administrador</span>
                <a href="../php/logout.php" class="btn btn-warning btn-sm">Sair</a>
            </div>
        </div>
    </nav>

    <div class="sidebar">
        <a href="../index.php" class="nav-link"><i class="bi bi-house"></i> Início</a>
        <a href="clientes.php" class="nav-link"><i class="bi bi-people"></i> Clientes</a>
        <a href="ordens_servico.php" class="nav-link"><i class="bi bi-file-text"></i> Ordens de Serviço</a>
        <a href="estoque.php" class="nav-link"><i class="bi bi-box"></i> Estoque</a>
    </div>

    <div class="main-content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header bg-white py-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-primary fw-bold"><i class="bi bi-check2-square me-2"></i> Checklist de Entrada - OS #<?php echo $os['numero']; ?></h5>
                        <span class="badge bg-primary-subtle text-primary px-3 py-2">Cliente: <?php echo htmlspecialchars($os['cliente_nome']); ?></span>
                    </div>
                </div>
                <form method="POST" enctype="multipart/form-data" id="formChecklist">
                    <div class="card-body p-4">
                        <?php if (!empty($mensagens_erro)): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <ul class="mb-0"><?php foreach ($mensagens_erro as $erro): ?><li><?php echo $erro; ?></li><?php endforeach; ?></ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <h6 class="section-title">Documentação Visual</h6>
                        <div class="bg-light p-4 rounded-3 mb-4">
                            <div id="container-uploads">
                                <div class="upload-item" ondrop="handleDrop(event)" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <label class="small text-muted mb-2"><i class="bi bi-cloud-arrow-up"></i> Arraste fotos/vídeos aqui ou clique para selecionar:</label>
                                            <input type="file" class="form-control form-control-sm" name="midias[]" accept="image/*,video/*" onchange="atualizarNomeArquivo(this)">
                                            <small class="text-muted d-block mt-1">Formatos suportados: JPG, PNG, MP4, WebM</small>
                                        </div>
                                        <div class="col-auto pt-3">
                                            <button type="button" class="btn btn-link text-danger p-0" onclick="this.closest('.upload-item').remove()"><i class="bi bi-trash fs-5"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm fw-bold mt-3" onclick="adicionarCampoUpload()">
                                <i class="bi bi-plus-circle me-1"></i> ADICIONAR MAIS UMA FOTO/VÍDEO
                            </button>

                            <?php if (!empty($midias)): ?>
                                <div class="mt-4 pt-3 border-top">
                                    <label class="form-label fw-bold text-muted small mb-3">MÍDIAS JÁ REGISTRADAS:</label>
                                    <div class="row g-3">
                                        <?php foreach ($midias as $m): ?>
                                            <div class="col-md-2 col-sm-4 col-6">
                                                <div class="media-container">
                                                    <?php if ($m['tipo'] === 'video'): ?>
                                                        <video src="../<?php echo $m['arquivo_url']; ?>" class="media-preview" controls></video>
                                                        <span class="media-type-icon">VÍDEO</span>
                                                    <?php else: ?>
                                                        <img src="../<?php echo $m['arquivo_url']; ?>" class="media-preview img-thumbnail" onclick="window.open(this.src)">
                                                        <span class="media-type-icon">FOTO</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <h6 class="section-title">Estado Geral e Originalidade</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Aspecto Visual Geral:</label>
                                <select class="form-select" name="aspecto_visual">
                                    <option value="bom" <?php echo ($checklist['aspecto_visual'] ?? '') == 'bom' ? 'selected' : ''; ?>>Bom</option>
                                    <option value="regular" <?php echo ($checklist['aspecto_visual'] ?? '') == 'regular' ? 'selected' : ''; ?>>Regular</option>
                                    <option value="ruim" <?php echo ($checklist['aspecto_visual'] ?? '') == 'ruim' ? 'selected' : ''; ?>>Ruim</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Farol é Original?</label>
                                <select class="form-select" name="farol_original">
                                    <option value="sim" <?php echo ($checklist['farol_original'] ?? '') == 'sim' ? 'selected' : ''; ?>>Sim</option>
                                    <option value="nao" <?php echo ($checklist['farol_original'] ?? '') == 'nao' ? 'selected' : ''; ?>>Não</option>
                                </select>
                            </div>
                        </div>

                        <h6 class="section-title">Verificação de Iluminação</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Farol Alto:</label>
                                <select class="form-select" name="farol_alto">
                                    <option value="funcionando" <?php echo ($checklist['farol_alto'] ?? '') == 'funcionando' ? 'selected' : ''; ?>>Funcionando</option>
                                    <option value="nao_funcionando" <?php echo ($checklist['farol_alto'] ?? '') == 'nao_funcionando' ? 'selected' : ''; ?>>Não Funcionando</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Farol Baixo:</label>
                                <select class="form-select" name="farol_baixo">
                                    <option value="funcionando" <?php echo ($checklist['farol_baixo'] ?? '') == 'funcionando' ? 'selected' : ''; ?>>Funcionando</option>
                                    <option value="nao_funcionando" <?php echo ($checklist['farol_baixo'] ?? '') == 'nao_funcionando' ? 'selected' : ''; ?>>Não Funcionando</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Farolete:</label>
                                <select class="form-select" name="farolete">
                                    <option value="funcionando" <?php echo ($checklist['farolete'] ?? '') == 'funcionando' ? 'selected' : ''; ?>>Funcionando</option>
                                    <option value="nao_funcionando" <?php echo ($checklist['farolete'] ?? '') == 'nao_funcionando' ? 'selected' : ''; ?>>Não Funcionando</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Setas:</label>
                                <select class="form-select" name="setas">
                                    <option value="funcionando" <?php echo ($checklist['setas'] ?? '') == 'funcionando' ? 'selected' : ''; ?>>Funcionando</option>
                                    <option value="nao_funcionando" <?php echo ($checklist['setas'] ?? '') == 'nao_funcionando' ? 'selected' : ''; ?>>Não Funcionando</option>
                                </select>
                            </div>
                        </div>

                        <h6 class="section-title">Observações</h6>
                        <textarea class="form-control" name="observacoes" rows="4" placeholder="Detalhes adicionais sobre o estado do veículo..."><?php echo htmlspecialchars($checklist['observacoes'] ?? ''); ?></textarea>
                    </div>
                    <div class="card-footer bg-light p-4 d-flex gap-3">
                        <a href="ordens_servico.php" class="btn btn-outline-secondary px-4 fw-bold">CANCELAR</a>
                        <button type="submit" class="btn btn-primary flex-grow-1 fw-bold" id="btnSalvar">SALVAR E INICIAR SERVIÇO</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        function adicionarCampoUpload() {
            const container = document.getElementById('container-uploads');
            const div = document.createElement('div');
            div.className = 'upload-item';
            div.innerHTML = `
                <div class="row align-items-center">
                    <div class="col">
                        <label class="small text-muted mb-2"><i class="bi bi-cloud-arrow-up"></i> Arraste fotos/vídeos aqui ou clique para selecionar:</label>
                        <input type="file" class="form-control form-control-sm" name="midias[]" accept="image/*,video/*" onchange="atualizarNomeArquivo(this)">
                        <small class="text-muted d-block mt-1">Formatos suportados: JPG, PNG, MP4, WebM</small>
                    </div>
                    <div class="col-auto pt-3">
                        <button type="button" class="btn btn-link text-danger p-0" onclick="this.closest('.upload-item').remove()"><i class="bi bi-trash fs-5"></i></button>
                    </div>
                </div>
            `;
            div.addEventListener('drop', handleDrop);
            div.addEventListener('dragover', handleDragOver);
            div.addEventListener('dragleave', handleDragLeave);
            container.appendChild(div);
        }

        function handleDragOver(e) {
            e.preventDefault();
            e.stopPropagation();
            e.currentTarget.classList.add('drag-over');
        }

        function handleDragLeave(e) {
            e.preventDefault();
            e.stopPropagation();
            e.currentTarget.classList.remove('drag-over');
        }

        function handleDrop(e) {
            e.preventDefault();
            e.stopPropagation();
            e.currentTarget.classList.remove('drag-over');

            const files = e.dataTransfer.files;
            const input = e.currentTarget.querySelector('input[type="file"]');
            if (input && files.length > 0) {
                input.files = files;
                const event = new Event('change', { bubbles: true });
                input.dispatchEvent(event);
                atualizarNomeArquivo(input);
            }
        }

        function atualizarNomeArquivo(input) {
            if (input.files && input.files[0]) {
                const fileName = input.files[0].name;
                const fileSize = (input.files[0].size / 1024 / 1024).toFixed(2);
                const label = input.closest('.upload-item').querySelector('label');
                label.innerHTML = `<i class="bi bi-check-circle-fill text-success"></i> <strong>${fileName}</strong> (${fileSize}MB)`;
            }
        }
        document.getElementById('formChecklist').addEventListener('submit', function() {
            const btn = document.getElementById('btnSalvar');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> SALVANDO...';
        });
    </script>
</body>
</html>
