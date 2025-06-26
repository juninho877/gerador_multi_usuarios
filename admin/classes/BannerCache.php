<?php
require_once 'config/database.php';

class BannerCache {
    private $db;
    private $cacheDir;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->cacheDir = sys_get_temp_dir() . '/futbanner_cache/';
        
        // Criar diretório de cache se não existir
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
        
        $this->createCacheTable();
    }
    
    /**
     * Criar tabela de cache se não existir
     */
    private function createCacheTable() {
        $sql = "
        CREATE TABLE IF NOT EXISTS banner_cache (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            cache_key VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            banner_type ENUM('football_1', 'football_2', 'football_3') NOT NULL,
            grupo_index INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NOT NULL,
            
            UNIQUE KEY unique_user_cache (user_id, cache_key),
            FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            INDEX idx_expires (expires_at),
            INDEX idx_user_type (user_id, banner_type)
        );
        ";
        
        $this->db->exec($sql);
    }
    
    /**
     * Gerar chave de cache baseada nos jogos e configurações do usuário
     */
    public function generateCacheKey($userId, $bannerType, $grupoIndex, $jogos) {
        // Criar hash baseado nos dados dos jogos e configurações do usuário
        $dataString = serialize([
            'user_id' => $userId,
            'banner_type' => $bannerType,
            'grupo_index' => $grupoIndex,
            'jogos_hash' => md5(serialize($jogos)),
            'date' => date('Y-m-d') // Incluir data para invalidar cache diário
        ]);
        
        return md5($dataString);
    }
    
    /**
     * Verificar se existe cache válido
     */
    public function getCachedBanner($userId, $cacheKey) {
        try {
            $stmt = $this->db->prepare("
                SELECT file_path, original_name, created_at
                FROM banner_cache 
                WHERE user_id = ? AND cache_key = ? AND expires_at > NOW()
            ");
            $stmt->execute([$userId, $cacheKey]);
            $result = $stmt->fetch();
            
            if ($result && file_exists($result['file_path'])) {
                return $result;
            }
            
            // Se não existe ou arquivo foi removido, limpar do banco
            if ($result) {
                $this->removeCachedBanner($userId, $cacheKey);
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Erro ao buscar cache: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Salvar banner no cache
     */
    public function saveBannerToCache($userId, $cacheKey, $imageResource, $bannerType, $grupoIndex = null, $originalName = null) {
        try {
            // Gerar nome único para o arquivo
            $fileName = 'banner_' . $bannerType . '_' . $userId . '_' . $cacheKey . '.png';
            $filePath = $this->cacheDir . $fileName;
            
            // Salvar imagem
            if (!imagepng($imageResource, $filePath)) {
                return false;
            }
            
            // Nome original padrão se não fornecido
            if (!$originalName) {
                $originalName = 'banner_' . $bannerType . '_' . date('Y-m-d') . '.png';
                if ($grupoIndex !== null) {
                    $originalName = 'banner_' . $bannerType . '_parte_' . ($grupoIndex + 1) . '_' . date('Y-m-d') . '.png';
                }
            }
            
            // Expiração: 6 horas
            $expiresAt = date('Y-m-d H:i:s', time() + (6 * 3600));
            
            // Salvar no banco (usar REPLACE para atualizar se já existir)
            $stmt = $this->db->prepare("
                REPLACE INTO banner_cache (user_id, cache_key, file_path, original_name, banner_type, grupo_index, expires_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $cacheKey, $filePath, $originalName, $bannerType, $grupoIndex, $expiresAt]);
            
            return [
                'file_path' => $filePath,
                'original_name' => $originalName,
                'created_at' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            error_log("Erro ao salvar cache: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remover banner do cache
     */
    public function removeCachedBanner($userId, $cacheKey) {
        try {
            // Buscar arquivo para remover
            $stmt = $this->db->prepare("
                SELECT file_path FROM banner_cache 
                WHERE user_id = ? AND cache_key = ?
            ");
            $stmt->execute([$userId, $cacheKey]);
            $result = $stmt->fetch();
            
            if ($result && file_exists($result['file_path'])) {
                unlink($result['file_path']);
            }
            
            // Remover do banco
            $stmt = $this->db->prepare("
                DELETE FROM banner_cache 
                WHERE user_id = ? AND cache_key = ?
            ");
            $stmt->execute([$userId, $cacheKey]);
            
            return true;
        } catch (Exception $e) {
            error_log("Erro ao remover cache: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Limpar cache expirado
     */
    public function cleanExpiredCache() {
        try {
            // Buscar arquivos expirados
            $stmt = $this->db->prepare("
                SELECT file_path FROM banner_cache 
                WHERE expires_at <= NOW()
            ");
            $stmt->execute();
            $expiredFiles = $stmt->fetchAll();
            
            // Remover arquivos físicos
            foreach ($expiredFiles as $file) {
                if (file_exists($file['file_path'])) {
                    unlink($file['file_path']);
                }
            }
            
            // Remover registros do banco
            $stmt = $this->db->prepare("
                DELETE FROM banner_cache 
                WHERE expires_at <= NOW()
            ");
            $stmt->execute();
            
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Erro ao limpar cache: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Limpar todo cache de um usuário
     */
    public function clearUserCache($userId) {
        try {
            // Buscar arquivos do usuário
            $stmt = $this->db->prepare("
                SELECT file_path FROM banner_cache 
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $userFiles = $stmt->fetchAll();
            
            // Remover arquivos físicos
            foreach ($userFiles as $file) {
                if (file_exists($file['file_path'])) {
                    unlink($file['file_path']);
                }
            }
            
            // Remover registros do banco
            $stmt = $this->db->prepare("
                DELETE FROM banner_cache 
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Erro ao limpar cache do usuário: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obter estatísticas do cache
     */
    public function getCacheStats($userId = null) {
        try {
            if ($userId) {
                $stmt = $this->db->prepare("
                    SELECT 
                        COUNT(*) as total_cached,
                        COUNT(CASE WHEN expires_at > NOW() THEN 1 END) as valid_cached,
                        COUNT(CASE WHEN expires_at <= NOW() THEN 1 END) as expired_cached
                    FROM banner_cache 
                    WHERE user_id = ?
                ");
                $stmt->execute([$userId]);
            } else {
                $stmt = $this->db->prepare("
                    SELECT 
                        COUNT(*) as total_cached,
                        COUNT(CASE WHEN expires_at > NOW() THEN 1 END) as valid_cached,
                        COUNT(CASE WHEN expires_at <= NOW() THEN 1 END) as expired_cached,
                        COUNT(DISTINCT user_id) as users_with_cache
                    FROM banner_cache
                ");
                $stmt->execute();
            }
            
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Erro ao obter estatísticas do cache: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Servir arquivo do cache
     */
    public function serveCachedFile($filePath, $originalName, $download = false) {
        if (!file_exists($filePath)) {
            return false;
        }
        
        // Limpar qualquer output anterior
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: image/png');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: public, max-age=3600'); // Cache por 1 hora no navegador
        
        if ($download) {
            header('Content-Disposition: attachment; filename="' . $originalName . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
        } else {
            header('Content-Disposition: inline; filename="' . $originalName . '"');
        }
        
        return readfile($filePath);
    }
}
?>