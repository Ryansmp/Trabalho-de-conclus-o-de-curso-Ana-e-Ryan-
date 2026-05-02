<?php
require_once '../php/config.php';
require_once '../php/check_session.php';
/** @var PDO $pdo */
$os_id = $_GET['os_id'] ?? null;
if (!$os_id) exit("ID inválido.");

// Buscar dados da OS
$stmt = $pdo->prepare("SELECT os.*, c.nome as cliente_nome FROM ordens_servico os JOIN clientes c ON os.cliente_id = c.id WHERE os.id = ?");
$stmt->execute([$os_id]);
$os = $stmt->fetch(PDO::FETCH_ASSOC);

// Buscar Checklist de Entrada
$stmt = $pdo->prepare("SELECT * FROM checklist_entrada WHERE os_id = ?");
$stmt->execute([$os_id]);
$checklist = $stmt->fetch(PDO::FETCH_ASSOC);

// Buscar Mídias de Entrada
$stmt = $pdo->prepare("SELECT * FROM checklist_midia WHERE os_id = ?");
$stmt->execute([$os_id]);
$midias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar Itens de Orçamento
$stmt = $pdo->prepare("SELECT * FROM os_itens WHERE os_id = ?");
$stmt->execute([$os_id]);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = 0;
?>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="p-3 bg-light rounded border">
            <h6 class="fw-bold text-primary mb-1">Cliente: <?php echo htmlspecialchars($os['cliente_nome']); ?></h6>
            <p class="mb-0 text-muted small">Veículo: <?php echo $os['modelo']; ?> - <?php echo $os['placa']; ?> (<?php echo $os['cor']; ?>)</p>
        </div>
    </div>

    <!-- COLUNA ANTES (ENTRADA) -->
    <div class="col-md-6">
        <h6 class="fw-bold text-danger border-bottom pb-2 mb-3"><i class="bi bi-box-arrow-in-right me-2"></i>ESTADO DE ENTRADA</h6>

        <div class="mb-3">
            <label class="small fw-bold text-muted d-block mb-2">GALERIA DE ENTRADA:</label>
            <div class="row g-2">
                <?php if (empty($midias)): ?>
                    <div class="col-12 text-muted small italic">Nenhuma mídia registrada na entrada.</div>
                <?php else: ?>
                    <?php foreach ($midias as $m): ?>
                        <div class="col-4">
                            <?php if ($m['tipo'] === 'video'): ?>
                                <video src="../<?php echo $m['arquivo_url']; ?>" style="width:100%; height:80px; object-fit:cover; border-radius:5px;" controls></video>
                            <?php else: ?>
                                <img src="../<?php echo $m['arquivo_url']; ?>" style="width:100%; height:80px; object-fit:cover; border-radius:5px; cursor:pointer;" onclick="window.open(this.src)">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="p-3 bg-white border rounded small">
            <p class="mb-1"><strong>Aspecto Visual:</strong> <?php echo ucfirst($checklist['aspecto_visual'] ?? 'N/A'); ?></p>
            <p class="mb-1"><strong>Farol Original:</strong> <?php echo ucfirst($checklist['farol_original'] ?? 'N/A'); ?></p>
            <hr class="my-2">
            <p class="mb-1"><strong>Farol Alto:</strong> <?php echo str_replace('_', ' ', $checklist['farol_alto'] ?? 'N/A'); ?></p>
            <p class="mb-1"><strong>Farol Baixo:</strong> <?php echo str_replace('_', ' ', $checklist['farol_baixo'] ?? 'N/A'); ?></p>
            <p class="mb-1"><strong>Farolete:</strong> <?php echo str_replace('_', ' ', $checklist['farolete'] ?? 'N/A'); ?></p>
            <p class="mb-1"><strong>Setas:</strong> <?php echo str_replace('_', ' ', $checklist['setas'] ?? 'N/A'); ?></p>
        </div>
    </div>

    <!-- COLUNA DEPOIS (SAÍDA) -->
    <div class="col-md-6">
        <h6 class="fw-bold text-success border-bottom pb-2 mb-3"><i class="bi bi-check-all me-2"></i>ESTADO DE SAÍDA</h6>

        <div class="mb-3">
            <label class="small fw-bold text-muted d-block mb-2">FOTO DE CONCLUSÃO:</label>
            <?php if ($os['imagem_final_url']): ?>
                <img src="../<?php echo $os['imagem_final_url']; ?>" style="width:100%; height:180px; object-fit:cover; border-radius:8px; border: 2px solid #198754;" onclick="window.open(this.src)">
            <?php else: ?>
                <div class="p-4 bg-light text-center text-muted rounded border small">Foto final não disponível.</div>
            <?php endif; ?>
        </div>

        <div class="p-3 bg-white border rounded small">
            <p class="mb-1"><strong>Forma de Pagamento:</strong> <span class="badge bg-success"><?php echo $os['forma_pagamento'] ?? 'N/A'; ?></span></p>
            <p class="mb-1"><strong>Data Conclusão:</strong> <?php echo date('d/m/Y H:i', strtotime($os['data_finalizacao'])); ?></p>
            <hr class="my-2">
            <p class="mb-1"><strong>Farol Alto:</strong> <?php echo str_replace('_', ' ', $os['saida_farol_alto'] ?? 'N/A'); ?></p>
            <p class="mb-1"><strong>Farol Baixo:</strong> <?php echo str_replace('_', ' ', $os['saida_farol_baixo'] ?? 'N/A'); ?></p>
            <p class="mb-1"><strong>Farolete:</strong> <?php echo str_replace('_', ' ', $os['saida_farolete'] ?? 'N/A'); ?></p>
            <p class="mb-1"><strong>Setas:</strong> <?php echo str_replace('_', ' ', $os['saida_setas'] ?? 'N/A'); ?></p>
        </div>
    </div>

    <!-- ORÇAMENTO FINAL -->
    <div class="col-md-12 mt-4">
        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">DETALHAMENTO DO SERVIÇO</h6>
        <table class="table table-sm table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Descrição do Serviço / Peça</th>
                    <th class="text-end" style="width: 150px;">Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itens as $item): $total += $item['valor']; ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['descricao']); ?></td>
                        <td class="text-end">R$ <?php echo number_format($item['valor'], 2, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="table-primary fw-bold">
                    <td class="text-end">TOTAL DO SERVIÇO:</td>
                    <td class="text-end">R$ <?php echo number_format($total, 2, ',', '.'); ?></td>
                </tr>
            </tfoot>
        </table>
        <?php if ($os['saida_observacoes']): ?>
            <div class="mt-3 p-3 bg-light rounded border small">
                <strong>Observações de Saída:</strong><br>
                <?php echo nl2br(htmlspecialchars($os['saida_observacoes'])); ?>
            </div>
        <?php endif; ?>
    </div>
</div>
