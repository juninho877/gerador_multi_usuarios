<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["usuario"]) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

require_once 'classes/User.php';

try {
    $user = new User();
    $password = $_POST['password'] ?? '';
    
    if (empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Senha não fornecida']);
        exit;
    }
    
    // Buscar dados do usuário atual
    $userId = $_SESSION['user_id'];
    $currentUserData = $user->getUserById($userId);
    
    if (!$currentUserData) {
        echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
        exit;
    }
    
    // Testar autenticação
    $authResult = $user->authenticate($currentUserData['username'], $password);
    
    echo json_encode([
        'success' => $authResult['success'],
        'message' => $authResult['success'] ? 'Senha correta' : 'Senha incorreta'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
}
?>