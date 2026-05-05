<?php
require_once '../php/config.php';
require_once '../php/check_session.php';
/** @var PDO $pdo */

$eh_mecanico = $_SESSION['usuario_role'] === 'mecanico';

$mensagem = '';
$tipo_mensagem = '';

if (isset($_GET['deletar'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
        $stmt->execute([$_GET['deletar']]);
        $mensagem = 'Cliente deletado com sucesso!';
        $tipo_mensagem = 'success';
    } catch (Exception $e) {
        $mensagem = 'Erro ao deletar: ' . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
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

    $cpf_cnpj = preg_replace('/[^0-9]/', '', $cpf_cnpj);
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    $cep = preg_replace('/[^0-9]/', '', $cep);

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
        $mensagem = 'Erro: ' . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}

$stmt = $pdo->query("SELECT COUNT(*) as total FROM clientes");
$total_clientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT * FROM clientes ORDER BY criado_em DESC");
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

function formatarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) === 11) {
        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }
    return $cpf;
}

function formatarCNPJ($cnpj) {
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    if (strlen($cnpj) === 14) {
        return substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3) . '.' . substr($cnpj, 5, 3) . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12, 2);
    }
    return $cnpj;
}

function formatarTelefone($telefone) {
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    if (strlen($telefone) === 11) {
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7, 4);
    } elseif (strlen($telefone) === 10) {
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6, 4);
    }
    return $telefone;
}

function formatarDocumento($documento) {
    $documento = preg_replace('/[^0-9]/', '', $documento);
    if (strlen($documento) === 11) {
        return formatarCPF($documento);
    } elseif (strlen($documento) === 14) {
        return formatarCNPJ($documento);
    }
    return $documento;
}
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
                <span class="text-white me-3"><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></span>
                <a href="../php/logout.php" class="btn btn-warning btn-sm">Sair</a>
            </div>
        </div>
    </nav>

    <div class="d-flex">
        <div class="sidebar">
            <a href="../index.php" class="nav-link"><i class="bi bi-house"></i> Início</a>
            <a href="clientes.php" class="nav-link active"><i class="bi bi-people"></i> Clientes</a>
            <a href="ordens_servico.php" class="nav-link"><i class="bi bi-file-text"></i> Ordens de Serviço</a>

            <a href="estoque.php" class="nav-link"><i class="bi bi-box"></i> Estoque</a>
        </div>

        <div class="main-content">
            <div class="container-fluid">
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
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCliente" onclick="limparFormulario()">
                            <i class="bi bi-plus"></i> Novo Cliente
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <input type="text" class="form-control" id="buscaCliente" placeholder="🔍 Buscar por nome, CPF/CNPJ ou telefone...">
                        </div>
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
                                    <tbody id="tabelaClientes">
                                        <?php foreach ($clientes as $cliente): ?>
                                            <tr class="linha-cliente" data-nome="<?php echo strtolower($cliente['nome']); ?>" data-cpf="<?php echo strtolower($cliente['cpf_cnpj']); ?>" data-telefone="<?php echo strtolower($cliente['telefone']); ?>">
                                                <td><?php echo htmlspecialchars($cliente['nome']); ?></td>
                                                <td><?php echo htmlspecialchars(formatarDocumento($cliente['cpf_cnpj'])); ?></td>
                                                <td><?php echo htmlspecialchars(formatarTelefone($cliente['telefone'])); ?></td>
                                                <td><?php echo htmlspecialchars($cliente['email']); ?></td>
                                                <td>
                                                    <button class="btn btn-warning btn-sm" onclick="editarCliente(<?php echo $cliente['id']; ?>, '<?php echo htmlspecialchars($cliente['nome']); ?>', '<?php echo htmlspecialchars($cliente['cpf_cnpj']); ?>', '<?php echo htmlspecialchars($cliente['telefone']); ?>', '<?php echo htmlspecialchars($cliente['email']); ?>', '<?php echo htmlspecialchars($cliente['endereco']); ?>')"><i class="bi bi-pencil"></i></button>
                                                    <a href="?deletar=<?php echo $cliente['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza?')"><i class="bi bi-trash"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Nenhum cliente cadastrado. <a href="#" data-bs-toggle="modal" data-bs-target="#modalCliente" onclick="limparFormulario()">Cadastre um novo cliente</a>
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
                    <h5 class="modal-title" id="modalTitulo">Novo Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="id" name="id" value="">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nome" class="form-label">Nome <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nome" name="nome" placeholder="João Silva" required>
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
                            <div class="col-md-6 mb-3">
                                <label for="doc_input" class="form-label" id="doc_label">CPF <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="doc_input" name="cpf_cnpj" placeholder="000.000.000-00" maxlength="14" inputmode="numeric" required>
                                <small class="text-muted" id="doc_hint">11 dígitos</small>
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
    <script src="../js/masks.js"></script>
    <script>
        const tipoCpfRadio = document.getElementById('tipo_cpf');
        const tipoCnpjRadio = document.getElementById('tipo_cnpj');
        const docInput = document.getElementById('doc_input');
        const docLabel = document.getElementById('doc_label');
        const docHint = document.getElementById('doc_hint');

        tipoCpfRadio.addEventListener('change', function() {
            if (this.checked) {
                docLabel.innerHTML = 'CPF <span class="text-danger">*</span>';
                docHint.textContent = '11 dígitos';
                docInput.placeholder = '000.000.000-00';
                docInput.maxLength = '14';
                docInput.value = '';
            }
        });

        tipoCnpjRadio.addEventListener('change', function() {
            if (this.checked) {
                docLabel.innerHTML = 'CNPJ <span class="text-danger">*</span>';
                docHint.textContent = '14 dígitos';
                docInput.placeholder = '00.000.000/0000-00';
                docInput.maxLength = '18';
                docInput.value = '';
            }
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

        function limparFormulario() {
            document.getElementById('id').value = '';
            document.getElementById('nome').value = '';
            document.getElementById('doc_input').value = '';
            document.getElementById('telefone').value = '';
            document.getElementById('email').value = '';
            document.getElementById('cep').value = '';
            document.getElementById('rua').value = '';
            document.getElementById('numero').value = '';
            document.getElementById('bairro').value = '';
            document.getElementById('cidade').value = '';
            document.getElementById('estado').value = '';
            document.getElementById('tipo_cpf').checked = true;
            document.getElementById('doc_label').innerHTML = 'CPF <span class="text-danger">*</span>';
            document.getElementById('doc_hint').textContent = '11 dígitos';
            document.getElementById('doc_input').placeholder = '000.000.000-00';
            document.getElementById('doc_input').maxLength = '14';
            document.getElementById('modalTitulo').textContent = 'Novo Cliente';
        }

        function editarCliente(id, nome, cpf_cnpj, telefone, email, endereco) {
            document.getElementById('id').value = id;
            document.getElementById('nome').value = nome;
            document.getElementById('telefone').value = telefone;
            document.getElementById('email').value = email;
            document.getElementById('modalTitulo').textContent = 'Editar Cliente';

            if (cpf_cnpj.length <= 11) {
                document.getElementById('tipo_cpf').checked = true;
                document.getElementById('doc_input').value = cpf_cnpj;
                document.getElementById('doc_label').innerHTML = 'CPF <span class="text-danger">*</span>';
                document.getElementById('doc_hint').textContent = '11 dígitos';
                document.getElementById('doc_input').placeholder = '000.000.000-00';
                document.getElementById('doc_input').maxLength = '14';
            } else {
                document.getElementById('tipo_cnpj').checked = true;
                document.getElementById('doc_input').value = cpf_cnpj;
                document.getElementById('doc_label').innerHTML = 'CNPJ <span class="text-danger">*</span>';
                document.getElementById('doc_hint').textContent = '14 dígitos';
                document.getElementById('doc_input').placeholder = '00.000.000/0000-00';
                document.getElementById('doc_input').maxLength = '18';
            }

            const partes = endereco.split(' - ');
            if (partes.length >= 4) {
                const ruaNumero = partes[0].split(', ');
                document.getElementById('rua').value = ruaNumero[0] || '';
                document.getElementById('numero').value = ruaNumero[1] || '';

                document.getElementById('bairro').value = partes[1] || '';

                const cidadeEstado = partes[2].split(', ');
                document.getElementById('cidade').value = cidadeEstado[0] || '';

                const estadoCep = partes[3].split(': ');
                document.getElementById('estado').value = estadoCep[0] || '';
                document.getElementById('cep').value = estadoCep[1] || '';
            }

            const modal = new bootstrap.Modal(document.getElementById('modalCliente'));
            modal.show();
        }

        document.querySelector('form').addEventListener('submit', function(e) {
            const docInput = document.getElementById('doc_input');
            const telefoneInput = document.getElementById('telefone');
            const cepInput = document.getElementById('cep');

            docInput.value = docInput.value.replace(/\D/g, '');
            telefoneInput.value = telefoneInput.value.replace(/\D/g, '');
            cepInput.value = cepInput.value.replace(/\D/g, '');
        });

        document.getElementById('buscaCliente').addEventListener('keyup', function() {
            const busca = this.value.toLowerCase();
            const linhas = document.querySelectorAll('.linha-cliente');
            let visiveisCount = 0;

            linhas.forEach(linha => {
                const nome = linha.getAttribute('data-nome');
                const cpf = linha.getAttribute('data-cpf');
                const telefone = linha.getAttribute('data-telefone');

                if (nome.includes(busca) || cpf.includes(busca) || telefone.includes(busca)) {
                    linha.style.display = '';
                    visiveisCount++;
                } else {
                    linha.style.display = 'none';
                }
            });
        });
    </script>
                <?php endif; ?>
</body>
</html>
