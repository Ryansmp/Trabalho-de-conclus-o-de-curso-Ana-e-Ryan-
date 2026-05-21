<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit;
}

function verificarAcesso($role_requerido) {
    $role_usuario = $_SESSION['usuario_role'] ?? 'mecanico';

    if ($role_usuario === 'admin') {
        return true;
    }

    if (is_array($role_requerido)) {
        return in_array($role_usuario, $role_requerido);
    }

    return $role_usuario === $role_requerido;
}
?>
