function formatarCPF(valor) {
    valor = valor.replace(/\D/g, '');
    valor = valor.slice(0, 11);

    if (valor.length > 0) {
        valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
        valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
        valor = valor.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    }

    return valor;
}

function formatarCNPJ(valor) {
    valor = valor.replace(/\D/g, '');
    valor = valor.slice(0, 14);

    if (valor.length > 0) {
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
    valor = valor.slice(0, 8);
    valor = valor.replace(/(\d{5})(\d)/, '$1-$2');
    return valor;
}

document.addEventListener('DOMContentLoaded', function() {
    const cpfInput = document.getElementById('cpf');
    const cnpjInput = document.getElementById('cnpj');
    const telefoneInput = document.getElementById('telefone');
    const cepInput = document.getElementById('cep');
    const cpfCnpjInput = document.getElementById('cpf_cnpj');

    if (cpfInput) {
        cpfInput.addEventListener('input', function() {
            this.value = formatarCPF(this.value);
        });
    }

    if (cnpjInput) {
        cnpjInput.addEventListener('input', function() {
            this.value = formatarCNPJ(this.value);
        });
    }

    if (cpfCnpjInput) {
        cpfCnpjInput.addEventListener('input', function() {
            let valor = this.value.replace(/\D/g, '');
            if (valor.length <= 11) {
                this.value = formatarCPF(this.value);
            } else {
                this.value = formatarCNPJ(this.value);
            }
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
