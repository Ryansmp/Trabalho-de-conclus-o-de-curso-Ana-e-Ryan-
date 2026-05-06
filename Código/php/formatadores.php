<?php
function formatarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) != 11) return $cpf;
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

function formatarCNPJ($cnpj) {
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    if (strlen($cnpj) != 14) return $cnpj;
    return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
}

function formatarTelefone($tel) {
    $tel = preg_replace('/[^0-9]/', '', $tel);
    if (strlen($tel) == 11) {
        return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $tel);
    } elseif (strlen($tel) == 10) {
        return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $tel);
    }
    return $tel;
}

function formatarDocumento($doc) {
    $doc = preg_replace('/[^0-9]/', '', $doc);
    if (strlen($doc) == 11) {
        return formatarCPF($doc);
    } elseif (strlen($doc) == 14) {
        return formatarCNPJ($doc);
    }
    return $doc;
}

function formatarMoeda($valor) {
    return 'R$ ' . number_format((float)$valor, 2, ',', '.');
}

function formatarData($data) {
    return date('d/m/Y', strtotime($data));
}
?>
