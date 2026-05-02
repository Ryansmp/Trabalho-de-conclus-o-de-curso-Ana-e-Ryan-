<?php
require_once '../php/config.php';
require_once '../php/check_session.php';
/** @var PDO $pdo */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['os_id'])) {
    $os_id = $_POST['os_id'];
    $imagem_final_url = '';

    // Dados de verificação de saída e pagamento
    $saida_farol_alto = $_POST['saida_farol_alto'] ?? '';
    $saida_farol_baixo = $_POST['saida_farol_baixo'] ?? '';
    $saida_farolete = $_POST['saida_farolete'] ?? '';
    $saida_setas = $_POST['saida_setas'] ?? '';
    $saida_observacoes = $_POST['saida_observacoes'] ?? '';
    $forma_pagamento = $_POST['forma_pagamento'] ?? '';

    // Processar upload da imagem final
    if (isset($_FILES['imagem_final']) && $_FILES['imagem_final']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/finalizacoes/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_ext = pathinfo($_FILES['imagem_final']['name'], PATHINFO_EXTENSION);
        $file_name = 'os_final_' . $os_id . '_' . time() . '.' . $file_ext;
        $target_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['imagem_final']['tmp_name'], $target_path)) {
            $imagem_final_url = 'uploads/finalizacoes/' . $file_name;
        }
    }

    if ($imagem_final_url) {
        try {
            $pdo->beginTransaction();

            // Atualizar status da OS para finalizada e salvar a imagem, verificações e pagamento
            $stmt = $pdo->prepare("UPDATE ordens_servico
                                   SET status = 'finalizada',
                                       data_finalizacao = NOW(),
                                       imagem_final_url = ?,
                                       saida_farol_alto = ?,
                                       saida_farol_baixo = ?,
                                       saida_farolete = ?,
                                       saida_setas = ?,
                                       saida_observacoes = ?,
                                       forma_pagamento = ?
                                   WHERE id = ?");
            $stmt->execute([
                $imagem_final_url,
                $saida_farol_alto,
                $saida_farol_baixo,
                $saida_farolete,
                $saida_setas,
                $saida_observacoes,
                $forma_pagamento,
                $os_id
            ]);

            $pdo->commit();
            header("Location: ordens_servico.php?msg=finalizada");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            die("Erro ao finalizar OS: " . $e->getMessage());
        }
    } else {
        die("Erro: Imagem final é obrigatória para concluir o serviço.");
    }
} else {
    header("Location: ordens_servico.php");
    exit;
}
?>
