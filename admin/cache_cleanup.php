<?php
/**
 * Script de limpeza automática do cache
 * Pode ser executado via cron job ou manualmente
 */

require_once 'classes/BannerCache.php';

// Verificar se é execução via linha de comando ou web
$isCLI = php_sapi_name() === 'cli';

if (!$isCLI) {
    // Se for via web, verificar autenticação
    session_start();
    if (!isset($_SESSION["usuario"]) || $_SESSION["role"] !== 'admin') {
        http_response_code(403);
        die("Acesso negado");
    }
    
    header('Content-Type: application/json');
}

try {
    $bannerCache = new BannerCache();
    
    // Limpar cache expirado
    $removedCount = $bannerCache->cleanExpiredCache();
    
    // Obter estatísticas
    $stats = $bannerCache->getCacheStats();
    
    $result = [
        'success' => true,
        'message' => "Limpeza concluída com sucesso",
        'removed_files' => $removedCount,
        'stats' => $stats,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($isCLI) {
        echo "Cache cleanup completed successfully\n";
        echo "Removed files: {$removedCount}\n";
        echo "Valid cached files: {$stats['valid_cached']}\n";
        echo "Total cached files: {$stats['total_cached']}\n";
    } else {
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    $error = [
        'success' => false,
        'message' => 'Erro na limpeza do cache: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($isCLI) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    } else {
        http_response_code(500);
        echo json_encode($error);
    }
}
?>