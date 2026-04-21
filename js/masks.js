function formatarCPFCNPJ(valor) {
    valor = valor.replace(/\D/g, '');

    if (valor.length <= 11) {
        valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
        valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
        valor = valor.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    } else {
        valor = valor.replace(/(\d{2})(\d)/, '$1.$2');
        valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
        valor = valor.replace(/(\d{3})(\d)/, '$1/$2');
        valor = valor.replace(/(\d{4})(\d)/, '$1-$2');
    }

    return valor;
}

function formatarTelefone(valor) {
    valor = valor.replace(/\D/g, '');

    if (valor.length === 11) {
        valor = valor.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    } else if (valor.length === 10) {
        valor = valor.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
    } else if (valor.length > 0) {
        valor = valor.replace(/(\d{2})(\d)/, '($1) $2');
        valor = valor.replace(/(\d{5})(\d)/, '$1-$2');
    }

    return valor;
}

function formatarCEP(valor) {
    valor = valor.replace(/\D/g, '');
    valor = valor.replace(/(\d{5})(\d)/, '$1-$2');
    return valor;
}

document.addEventListener('DOMContentLoaded', function() {
    const cpfCnpjInput = document.getElementById('cpf_cnpj');
    const telefoneInput = document.getElementById('telefone');
    const cepInput = document.getElementById('cep');

    if (cpfCnpjInput) {
        cpfCnpjInput.addEventListener('input', function() {
            this.value = formatarCPFCNPJ(this.value);
        });
    }

    if (telefoneInput) {
        telefoneInput.addEventListener('input', function() {
            this.value = formatarTelefone(this.value);
        });
    }

    if (cepInput) {
        cepInput.addEventListener('input', function() {
            this.value = formatarCEP(this.value);
        });
    }
});
