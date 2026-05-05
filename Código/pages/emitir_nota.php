<?php
require_once '../php/config.php';
require_once '../php/check_session.php';
/** @var PDO $pdo */
$os_id = $_GET['os_id'] ?? null;
$mensagem = '';
$tipo_mensagem = '';

if (!$os_id) {
    header('Location: ordens_servico.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT os.*, c.nome as cliente_nome, c.cpf_cnpj, c.telefone as cliente_telefone,
           c.endereco
    FROM ordens_servico os
    LEFT JOIN clientes c ON os.cliente_id = c.id
    WHERE os.id = ?
");
$stmt->execute([$os_id]);
$ordem = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ordem) {
    header('Location: ordens_servico.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM os_itens WHERE os_id = ?");
$stmt->execute([$os_id]);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['gerar_pdf'])) {
    require_once __DIR__ . '/../vendor/autoload.php';

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 11);

    $pdf->SetLineWidth(0.5);
    $pdf->Rect(10, 10, 190, 277);

    // Adicionar logo
    $logo_path = __DIR__ . '/../assets/logo.png';
    if (file_exists($logo_path)) {
        $pdf->Image($logo_path, 15, 12, 25, 20, 'PNG');
    }

    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetXY(45, 15);
    $pdf->Cell(0, 8, '5 ESTRELAS FAROIS', 0, 1);

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetXY(45, 24);
    $pdf->Cell(0, 4, 'Av Belo Horizonte, 1331 - Niteroi', 0, 1);
    $pdf->SetXY(45, 28);
    $pdf->Cell(0, 4, 'Tel: (31) 92894005', 0, 1);
    $pdf->SetXY(45, 32);
    $pdf->Cell(0, 4, 'CNPJ: 41.273.861/0001-20', 0, 1);

    $data_formatada = date('d/m/Y', strtotime($ordem['criado_em']));
    $pdf->SetXY(45, 36);
    $pdf->Cell(0, 4, 'Data: ' . $data_formatada, 0, 1);

    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetXY(15, 43);
    $pdf->Cell(0, 8, 'Nota de Servico', 0, 1, 'C');

    $pdf->SetLineWidth(0.3);
    $pdf->Line(10, 53, 200, 53);

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(15, 56);
    $pdf->Cell(0, 5, 'DADOS DO CLIENTE', 0, 1);

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetXY(15, 62);
    $pdf->Cell(60, 5, 'Nome: ' . htmlspecialchars($ordem['cliente_nome'] ?? ''), 0, 0);
    $pdf->SetXY(120, 62);
    $pdf->Cell(0, 5, 'CPF/CNPJ: ' . htmlspecialchars($ordem['cpf_cnpj'] ?? ''), 0, 1);

    $pdf->SetXY(15, 68);
    $endereco = htmlspecialchars($ordem['endereco'] ?? '');
    if (strlen($endereco) > 40) {
        $pdf->MultiCell(100, 4, 'Endereco: ' . $endereco, 0, 'L');
    } else {
        $pdf->Cell(100, 5, 'Endereco: ' . $endereco, 0, 0);
    }
    $pdf->SetXY(120, 68);
    $pdf->Cell(0, 5, 'Tel: ' . htmlspecialchars($ordem['cliente_telefone'] ?? ''), 0, 1);

    $pdf->SetLineWidth(0.3);
    $pdf->Line(10, 75, 200, 75);

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(15, 78);
    $pdf->Cell(0, 5, 'DADOS DO VEICULO', 0, 1);

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetXY(15, 84);
    $pdf->Cell(30, 5, 'Placa: ' . htmlspecialchars($ordem['placa'] ?? ''), 0, 0);
    $pdf->SetXY(50, 84);
    $pdf->Cell(30, 5, 'Cor: ' . htmlspecialchars($ordem['cor'] ?? ''), 0, 0);
    $pdf->SetXY(85, 84);
    $pdf->Cell(0, 5, 'Ano: ' . htmlspecialchars($ordem['ano'] ?? ''), 0, 1);

    $pdf->SetXY(15, 90);
    $pdf->Cell(30, 5, 'Marca: ' . htmlspecialchars($ordem['marca'] ?? ''), 0, 0);
    $pdf->SetXY(50, 90);
    $pdf->Cell(30, 5, 'Modelo: ' . htmlspecialchars($ordem['modelo'] ?? ''), 0, 0);
    $pdf->SetXY(85, 90);
    $pdf->Cell(0, 5, 'KM: -', 0, 1);

    $pdf->SetXY(15, 96);
    $pdf->Cell(0, 5, 'Obs:', 0, 1);

    $pdf->SetLineWidth(0.3);
    $pdf->Line(10, 105, 200, 105);

    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(15, 108);
    $pdf->Cell(80, 5, 'DESCRICAO', 0, 0);
    $pdf->SetXY(100, 108);
    $pdf->Cell(20, 5, 'QTD', 0, 0);
    $pdf->SetXY(125, 108);
    $pdf->Cell(30, 5, 'R$ UNIT.', 0, 0);
    $pdf->SetXY(160, 108);
    $pdf->Cell(0, 5, 'R$ TOTAL', 0, 1);

    $pdf->SetLineWidth(0.3);
    $pdf->Line(10, 114, 200, 114);

    $pdf->SetFont('Arial', '', 9);
    $y_pos = 117;
    $total = 0;

    foreach ($itens as $item) {
        $quantidade = $item['quantidade'] ?? 1;
        $valor_unitario = $item['valor'] ?? 0;
        $valor_total = $quantidade * $valor_unitario;
        $total += $valor_total;

        $pdf->SetXY(15, $y_pos);
        $pdf->Cell(80, 5, htmlspecialchars($item['descricao'] ?? ''), 0, 0);
        $pdf->SetXY(100, $y_pos);
        $pdf->Cell(20, 5, $quantidade, 0, 0);
        $pdf->SetXY(125, $y_pos);
        $pdf->Cell(30, 5, 'R$ ' . number_format($valor_unitario, 2, ',', '.'), 0, 0);
        $pdf->SetXY(160, $y_pos);
        $pdf->Cell(0, 5, 'R$ ' . number_format($valor_total, 2, ',', '.'), 0, 1);

        $y_pos += 6;
    }

    $pdf->SetLineWidth(0.3);
    $pdf->Line(10, $y_pos + 2, 200, $y_pos + 2);

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(125, $y_pos + 5);
    $pdf->Cell(30, 5, 'VALOR TOTAL', 0, 0);
    $pdf->SetXY(160, $y_pos + 5);
    $pdf->Cell(0, 5, 'R$ ' . number_format($total, 2, ',', '.'), 0, 1);

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetXY(10, $y_pos + 15);
    $pdf->Cell(190, 5, 'Forma de Pagamento: Pix', 0, 1, 'C');

    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(10, $y_pos + 23);
    $pdf->Cell(190, 5, 'Observacoes gerais:', 0, 1, 'C');

    $pdf->SetFont('Arial', '', 8);
    $observacoes = "Garantia: Servicos de polimento externo e vedacao com duracao de 90 dias.\nNao cobre danos por mau uso ou farois paralelos.";
    $pdf->SetXY(15, $y_pos + 28);
    $pdf->MultiCell(170, 4, $observacoes, 0, 'C');

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetXY(15, 250);
    $pdf->Cell(50, 5, '_______________', 0, 0);
    $pdf->SetXY(130, 250);
    $pdf->Cell(0, 5, '_______________', 0, 1);

    $pdf->SetXY(15, 255);
    $pdf->Cell(50, 5, 'Ass. Cliente', 0, 0);
    $pdf->SetXY(130, 255);
    $pdf->Cell(0, 5, 'Ass. Vendedor', 0, 1);

    $pdf->Output('D', 'Nota_Servico_' . $os_id . '.pdf');
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emitir Nota Fiscal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #667eea;
            font-weight: bold;
        }
        .info-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .info-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
        }
        .info-item {
            padding: 10px;
            background: white;
            border-left: 4px solid #667eea;
            border-radius: 4px;
        }
        .info-item label {
            font-weight: bold;
            color: #667eea;
            display: block;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        .info-item span {
            font-size: 1.1em;
            color: #333;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        .items-table tr:hover {
            background: #f8f9fa;
        }
        .total-section {
            text-align: right;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #667eea;
        }
        .total-value {
            font-size: 1.5em;
            font-weight: bold;
            color: #667eea;
        }
        .btn-emitir {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-weight: bold;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            transition: transform 0.2s;
            width: 100%;
            margin-top: 20px;
        }
        .btn-emitir:hover {
            transform: scale(1.02);
            color: white;
        }
        .btn-voltar {
            background: #6c757d;
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="ordens_servico.php" class="btn-voltar">← Voltar</a>

        <div class="header">
            <h1>📄 Emitir Nota de Serviço</h1>
            <p>Ordem de Serviço #<?php echo $os_id; ?></p>
        </div>

        <div class="info-section">
            <h3 style="color: #667eea; margin-bottom: 15px;">Dados do Cliente</h3>
            <div class="info-row">
                <div class="info-item">
                    <label>Nome do Cliente</label>
                    <span><?php echo htmlspecialchars($ordem['cliente_nome'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-item">
                    <label>CPF/CNPJ</label>
                    <span><?php echo htmlspecialchars($ordem['cpf_cnpj'] ?? 'N/A'); ?></span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-item">
                    <label>Endereço</label>
                    <span><?php echo htmlspecialchars($ordem['endereco'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-item">
                    <label>Telefone</label>
                    <span><?php echo htmlspecialchars($ordem['cliente_telefone'] ?? 'N/A'); ?></span>
                </div>
            </div>
        </div>

        <div class="info-section">
            <h3 style="color: #667eea; margin-bottom: 15px;">Dados do Veículo</h3>
            <div class="info-row">
                <div class="info-item">
                    <label>Marca</label>
                    <span><?php echo htmlspecialchars($ordem['marca'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-item">
                    <label>Modelo</label>
                    <span><?php echo htmlspecialchars($ordem['modelo'] ?? 'N/A'); ?></span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-item">
                    <label>Placa</label>
                    <span><?php echo htmlspecialchars($ordem['placa'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-item">
                    <label>Cor</label>
                    <span><?php echo htmlspecialchars($ordem['cor'] ?? 'N/A'); ?></span>
                </div>
            </div>
        </div>

        <div class="info-section">
            <h3 style="color: #667eea; margin-bottom: 15px;">Itens de Serviço</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Descrição</th>
                        <th style="text-align: center; width: 80px;">QTD</th>
                        <th style="text-align: right; width: 120px;">R$ UNIT.</th>
                        <th style="text-align: right; width: 120px;">R$ TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total = 0;
                    foreach ($itens as $item):
                        $quantidade = $item['quantidade'] ?? 1;
                        $valor_unitario = $item['valor'] ?? 0;
                        $valor_total = $quantidade * $valor_unitario;
                        $total += $valor_total;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['descricao']); ?></td>
                        <td style="text-align: center;"><?php echo $quantidade; ?></td>
                        <td style="text-align: right;">R$ <?php echo number_format($valor_unitario, 2, ',', '.'); ?></td>
                        <td style="text-align: right;">R$ <?php echo number_format($valor_total, 2, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="total-section">
                <p style="font-size: 1.1em; margin-bottom: 10px;">VALOR TOTAL</p>
                <p class="total-value">R$ <?php echo number_format($total, 2, ',', '.'); ?></p>
            </div>
        </div>

        <a href="?os_id=<?php echo $os_id; ?>&gerar_pdf=1" class="btn-emitir">
            📥 Gerar e Baixar PDF
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
