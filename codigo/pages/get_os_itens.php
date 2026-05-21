<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../php/config.php';
    require_once '../php/check_session.php';
    /** @var PDO $pdo */

    $os_id = $_GET['os_id'] ?? null;

    if (!$os_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID inválido.']);
        exit;
    }

    // Buscar Itens de Orçamento
    $stmt = $pdo->prepare("SELECT descricao, valor FROM os_itens WHERE os_id = ? ORDER BY id");
    $stmt->execute([$os_id]);
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'itens' => $itens, 'count' => count($itens)]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
