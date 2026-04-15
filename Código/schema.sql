CREATE TABLE IF NOT EXISTS usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS clientes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(150) NOT NULL,
    cpf_cnpj VARCHAR(20) UNIQUE NOT NULL,
    telefone VARCHAR(20),
    email VARCHAR(100),
    endereco VARCHAR(255),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS ordens_servico (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_id INT NOT NULL,
    descricao TEXT,
    status ENUM('Criada', 'Em Andamento', 'Concluida') DEFAULT 'Criada',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

CREATE TABLE IF NOT EXISTS checklist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ordem_id INT NOT NULL,
    placa VARCHAR(20),
    marca VARCHAR(50),
    modelo VARCHAR(50),
    ano INT,
    observacoes TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ordem_id) REFERENCES ordens_servico(id)
);

CREATE TABLE IF NOT EXISTS pecas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT,
    quantidade INT DEFAULT 0,
    quantidade_minima INT DEFAULT 5,
    preco_unitario DECIMAL(10, 2),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO usuarios (email, senha, nome) VALUES
('admin@oficina360.com', '$2y$10$YIjlrDfl2GltVQWQjH8H.eYDlZ8qB8Z8Z8Z8Z8Z8Z8Z8Z8Z8Z8Z8', 'Administrador');
