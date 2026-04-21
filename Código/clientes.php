<?php
require_once '../php/config.php';
require_once '../php/check_session.php';

$cliente_edicao = null;
$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $cpf_cnpj = $_POST['cpf_cnpj'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $email = $_POST['email'] ?? '';
    $cep = $_POST['cep'] ?? '';
    $rua = $_POST['rua'] ?? '';
    $numero = $_POST['numero'] ?? '';
    $bairro = $_POST['bairro'] ?? '';
    $cidade = $_POST['cidade'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $id = $_POST['id'] ?? null;

    $endereco_completo = "$rua, $numero - $bairro - $cidade, $estado - CEP: $cep";

    try {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE clientes SET nome = ?, cpf_cnpj = ?, telefone = ?, email = ?, endereco = ? WHERE id = ?");
            $stmt->execute([$nome, $cpf_cnpj, $telefone, $email, $endereco_completo, $id]);
            $mensagem = 'Cliente atualizado com sucesso!';
            $tipo_mensagem = 'success';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM clientes WHERE cpf_cnpj = ?");
            $stmt->execute([$cpf_cnpj]);
            if ($stmt->rowCount() > 0) {
                $mensagem = 'CPF/CNPJ já cadastrado!';
                $tipo_mensagem = 'danger';
            } else {
                $stmt = $pdo->prepare("INSERT INTO clientes (nome, cpf_cnpj, telefone, email, endereco) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$nome, $cpf_cnpj, $telefone, $email, $endereco_completo]);
                $mensagem = 'Cliente cadastrado com sucesso!';
                $tipo_mensagem = 'success';
            }
        }
    } catch (Exception $e) {
        $mensagem = 'Erro ao salvar cliente: ' . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}

if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$_GET['editar']]);
    $cliente_edicao = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (isset($_GET['deletar'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
        $stmt->execute([$_GET['deletar']]);
        $mensagem = 'Cliente deletado com sucesso!';
        $tipo_mensagem = 'success';
    } catch (Exception $e) {
        $mensagem = 'Erro ao deletar cliente: ' . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}

$stmt = $pdo->query("SELECT COUNT(*) as total FROM clientes");
$total_clientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT * FROM clientes ORDER BY criado_em DESC");
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            <span class="navbar-brand"><i class="bi bi-tools"></i> Oficina360</span>
            <div class="ms-auto">
                <span class="text-white me-3">Administrador</span>
                <a href="../php/logout.php" class="btn btn-warning btn-sm">Sair</a>
            </div>
        </div>
    </nav>

    <div class="d-flex">
        <div class="sidebar">
            <a href="../index.php" class="nav-link"><i class="bi bi-house"></i> Início</a>
            <a href="clientes.php" class="nav-link active"><i class="bi bi-people"></i> Clientes</a>
            <a href="#" class="nav-link"><i class="bi bi-file-text"></i> Ordens de Serviço</a>
            <a href="#" class="nav-link"><i class="bi bi-checklist"></i> Checklist</a>
            <a href="../pages/estoque.php" class="nav-link"><i class="bi bi-box"></i> Estoque</a>
        </div>

        <div class="main-content">
            <div class="container-fluid">
                <h2 class="mb-4"><i class="bi bi-people"></i> Clientes</h2>

                <?php if ($mensagem): ?>
                    <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
                        <?php echo $mensagem; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-list"></i> Total de Clientes: <strong><?php echo $total_clientes; ?></strong></span>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCliente">
                            <i class="bi bi-plus"></i> Novo Cliente
                        </button>
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
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($clientes as $cliente): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($cliente['nome']); ?></td>
                                                <td><?php echo htmlspecialchars($cliente['cpf_cnpj']); ?></td>
                                                <td><?php echo htmlspecialchars($cliente['telefone']); ?></td>
                                                <td><?php echo htmlspecialchars($cliente['email']); ?></td>
                                                <td>
                                                    <a href="?editar=<?php echo $cliente['id']; ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i></a>
                                                    <a href="?deletar=<?php echo $cliente['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza?')"><i class="bi bi-trash"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Nenhum cliente cadastrado. <a href="#" data-bs-toggle="modal" data-bs-target="#modalCliente">Cadastre um novo cliente</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCliente" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $cliente_edicao ? 'Editar Cliente' : 'Novo Cliente'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <?php if ($cliente_edicao): ?>
                            <input type="hidden" name="id" value="<?php echo $cliente_edicao['id']; ?>">
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nome" class="form-label">Nome <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nome" name="nome" value="<?php echo $cliente_edicao ? htmlspecialchars($cliente_edicao['nome']) : ''; ?>" placeholder="João Silva" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo de Documento <span class="text-danger">*</span></label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="tipo_doc" id="tipo_cpf" value="cpf" checked>
                                    <label class="btn btn-outline-primary" for="tipo_cpf">CPF</label>
                                    <input type="radio" class="btn-check" name="tipo_doc" id="tipo_cnpj" value="cnpj">
                                    <label class="btn btn-outline-primary" for="tipo_cnpj">CNPJ</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3" id="cpf_container">
                                <label for="cpf" class="form-label">CPF <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="cpf" name="cpf_cnpj" placeholder="000.000.000-00" maxlength="14" inputmode="numeric" required>
                                <small class="text-muted">11 dígitos</small>
                            </div>
                            <div class="col-md-6 mb-3" id="cnpj_container" style="display: none;">
                                <label for="cnpj" class="form-label">CNPJ <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="cnpj" placeholder="00.000.000/0000-00" maxlength="18" inputmode="numeric">
                                <small class="text-muted">14 dígitos</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="telefone" class="form-label">Telefone</label>
                                <input type="text" class="form-control" id="telefone" name="telefone" placeholder="(11) 99999-9999" maxlength="15" inputmode="numeric">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="joao@email.com">
                            </div>
                        </div>

                        <hr>
                        <h6 class="mb-3"><i class="bi bi-geo-alt"></i> Endereço</h6>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="cep" class="form-label">CEP <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="cep" name="cep" placeholder="00000-000" maxlength="9" required>
                                <small class="text-muted">Digite o CEP e os dados serão preenchidos automaticamente</small>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label for="rua" class="form-label">Rua <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="rua" name="rua" placeholder="Rua das Flores" readonly required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="numero" class="form-label">Número <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="numero" name="numero" placeholder="123" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="bairro" class="form-label">Bairro <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="bairro" name="bairro" placeholder="Centro" readonly required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="cidade" class="form-label">Cidade <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="cidade" name="cidade" placeholder="São Paulo" readonly required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="estado" class="form-label">Estado <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="estado" name="estado" placeholder="SP" maxlength="2" readonly required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Cliente</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/masks.js"></script>
    <script>
        const tipoCpfRadio = document.getElementById('tipo_cpf');
        const tipoCnpjRadio = document.getElementById('tipo_cnpj');
        const cpfInput = document.getElementById('cpf');
        const cnpjInput = document.getElementById('cnpj');
        const cpfContainer = document.getElementById('cpf_container');
        const cnpjContainer = document.getElementById('cnpj_container');

        tipoCpfRadio.addEventListener('change', function() {
            if (this.checked) {
                cpfContainer.style.display = 'block';
                cnpjContainer.style.display = 'none';
                cpfInput.required = true;
                cnpjInput.required = false;
                cnpjInput.value = '';
            }
        });

        tipoCnpjRadio.addEventListener('change', function() {
            if (this.checked) {
                cpfContainer.style.display = 'none';
                cnpjContainer.style.display = 'block';
                cpfInput.required = false;
                cnpjInput.required = true;
                cpfInput.value = '';
            }
        });

        cpfInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);
        });

        cnpjInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 14);
        });

        document.getElementById('telefone').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 14);
        });

        document.getElementById('cep').addEventListener('blur', function() {
            let cep = this.value.replace(/\D/g, '');

            if (cep.length === 8) {
                fetch(`https://viacep.com.br/ws/${cep}/json/`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.erro) {
                            alert('CEP não encontrado!');
                        } else {
                            document.getElementById('rua').value = data.logradouro;
                            document.getElementById('bairro').value = data.bairro;
                            document.getElementById('cidade').value = data.localidade;
                            document.getElementById('estado').value = data.uf;
                        }
                    })
                    .catch(error => console.error('Erro:', error));
            }
        });
    </script>
</body>
</html>
