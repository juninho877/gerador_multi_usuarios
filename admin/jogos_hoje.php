<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

// Incluir funções necessárias
require_once 'includes/banner_functions.php';

// Obter dados reais dos jogos
$jogos = obterJogosDeHoje();
$totalJogosHoje = count($jogos);

$pageTitle = "Jogos de Hoje";
include "includes/header.php";
?>

<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-futbol text-success-500 mr-3"></i>
        Jogos de Hoje
    </h1>
    <p class="page-subtitle">
        <?php if ($totalJogosHoje > 0): ?>
            <?php echo $totalJogosHoje; ?> jogos disponíveis para gerar banners
        <?php else: ?>
            Nenhum jogo programado para hoje
        <?php endif; ?>
    </p>
</div>

<!-- Actions Bar -->
<div class="flex justify-between items-center mb-6">
    <div class="flex gap-3">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Voltar ao Dashboard
        </a>
        <button id="refreshBtn" class="btn btn-secondary" onclick="location.reload()">
            <i class="fas fa-sync-alt"></i>
            Atualizar Jogos
        </button>
    </div>
    <?php if ($totalJogosHoje > 0): ?>
    <a href="futbanner.php" class="btn btn-primary">
        <i class="fas fa-magic"></i>
        Gerar Banners
    </a>
    <?php endif; ?>
</div>

<?php if ($totalJogosHoje > 0): ?>
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="card">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted">Total de Jogos</p>
                        <p class="text-2xl font-bold text-primary"><?php echo $totalJogosHoje; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-primary-50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-futbol text-primary-500"></i>
                    </div>
                </div>
            </div>
        </div>

        <?php
        // Calcular estatísticas dos jogos
        $ligas = [];
        $jogosComCanais = 0;
        $proximoJogo = null;
        $proximoHorario = null;

        foreach ($jogos as $jogo) {
            $liga = $jogo['competicao'] ?? 'Liga';
            $ligas[$liga] = ($ligas[$liga] ?? 0) + 1;
            
            if (!empty($jogo['canais'])) {
                $jogosComCanais++;
            }
            
            if ($jogo['horario'] && (!$proximoHorario || $jogo['horario'] < $proximoHorario)) {
                $proximoHorario = $jogo['horario'];
                $proximoJogo = $jogo;
            }
        }
        
        $ligasCount = count($ligas);
        $ligaPrincipal = $ligasCount > 0 ? array_keys($ligas, max($ligas))[0] : 'N/A';
        ?>

        <div class="card">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted">Ligas/Competições</p>
                        <p class="text-2xl font-bold text-success-500"><?php echo $ligasCount; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-success-50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-trophy text-success-500"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="text-xs text-success-600 font-medium">
                        Principal: <?php echo htmlspecialchars(substr($ligaPrincipal, 0, 15)); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted">Com Transmissão</p>
                        <p class="text-2xl font-bold text-warning-500"><?php echo $jogosComCanais; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-warning-50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-tv text-warning-500"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="text-xs text-warning-600 font-medium">
                        <?php echo round(($jogosComCanais / $totalJogosHoje) * 100); ?>% dos jogos
                    </span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted">Próximo Jogo</p>
                        <p class="text-2xl font-bold text-info-500"><?php echo $proximoHorario ?? '--:--'; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-info-50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-clock text-info-500"></i>
                    </div>
                </div>
                <?php if ($proximoJogo): ?>
                <div class="mt-2">
                    <span class="text-xs text-info-600 font-medium">
                        <?php echo htmlspecialchars(substr($proximoJogo['time1'] . ' vs ' . $proximoJogo['time2'], 0, 20)); ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Games Grid -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Todos os Jogos de Hoje</h3>
            <p class="card-subtitle">Clique em qualquer jogo para gerar banners</p>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <?php foreach ($jogos as $index => $jogo): 
                    $time1 = $jogo['time1'] ?? 'Time 1';
                    $time2 = $jogo['time2'] ?? 'Time 2';
                    $liga = $jogo['competicao'] ?? 'Liga';
                    $hora = $jogo['horario'] ?? '';
                    $canais = $jogo['canais'] ?? [];
                    $temCanais = !empty($canais);
                ?>
                    <div class="game-card-detailed" onclick="goToFutBanner()">
                        <div class="game-header-detailed">
                            <div class="league-info">
                                <span class="league-name-detailed"><?php echo htmlspecialchars($liga); ?></span>
                                <?php if ($temCanais): ?>
                                    <span class="live-indicator">
                                        <i class="fas fa-tv"></i>
                                        AO VIVO
                                    </span>
                                <?php endif; ?>
                            </div>
                            <span class="game-time-detailed"><?php echo htmlspecialchars($hora); ?></span>
                        </div>
                        
                        <div class="game-teams-detailed">
                            <div class="team-detailed">
                                <span class="team-name-detailed"><?php echo htmlspecialchars($time1); ?></span>
                            </div>
                            <div class="vs-detailed">VS</div>
                            <div class="team-detailed">
                                <span class="team-name-detailed"><?php echo htmlspecialchars($time2); ?></span>
                            </div>
                        </div>

                        <?php if ($temCanais): ?>
                        <div class="channels-info">
                            <div class="channels-list">
                                <?php 
                                $canaisLimitados = array_slice($canais, 0, 3);
                                foreach ($canaisLimitados as $canal): 
                                ?>
                                    <span class="channel-badge"><?php echo htmlspecialchars($canal['nome'] ?? 'Canal'); ?></span>
                                <?php endforeach; ?>
                                <?php if (count($canais) > 3): ?>
                                    <span class="channel-badge more">+<?php echo count($canais) - 3; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="game-actions">
                            <button class="btn-generate">
                                <i class="fas fa-magic"></i>
                                Gerar Banner
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- No Games Available -->
    <div class="card">
        <div class="card-body text-center py-12">
            <div class="mb-4">
                <i class="fas fa-futbol text-6xl text-gray-300"></i>
            </div>
            <h3 class="text-xl font-semibold mb-2">Nenhum jogo disponível</h3>
            <p class="text-muted mb-6">Não há jogos programados para hoje no momento.</p>
            <div class="flex gap-4 justify-center">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home"></i>
                    Voltar ao Dashboard
                </a>
                <button onclick="location.reload()" class="btn btn-secondary">
                    <i class="fas fa-sync-alt"></i>
                    Verificar Novamente
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
    /* Cores adicionais */
    :root {
        --info-50: #eff6ff;
        --info-500: #3b82f6;
        --info-600: #2563eb;
    }

    /* Cards de jogos detalhados */
    .game-card-detailed {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        padding: 1.25rem;
        transition: var(--transition);
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }

    .game-card-detailed:hover {
        background: var(--bg-tertiary);
        transform: translateY(-4px);
        box-shadow: var(--shadow-lg);
        border-color: var(--primary-500);
    }

    .game-card-detailed::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--primary-500), var(--success-500));
        opacity: 0;
        transition: var(--transition);
    }

    .game-card-detailed:hover::before {
        opacity: 1;
    }

    .game-header-detailed {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
        gap: 0.5rem;
    }

    .league-info {
        flex: 1;
        min-width: 0;
    }

    .league-name-detailed {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--primary-600);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        display: block;
        margin-bottom: 0.25rem;
    }

    .live-indicator {
        font-size: 0.625rem;
        font-weight: 600;
        color: var(--danger-600);
        background: var(--danger-50);
        padding: 0.125rem 0.375rem;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        animation: pulse 2s infinite;
    }

    .game-time-detailed {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text-primary);
        background: var(--bg-primary);
        padding: 0.375rem 0.75rem;
        border-radius: var(--border-radius-sm);
        white-space: nowrap;
    }

    .game-teams-detailed {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .team-detailed {
        flex: 1;
        text-align: center;
        min-width: 0;
    }

    .team-name-detailed {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text-primary);
        display: block;
        word-wrap: break-word;
        line-height: 1.2;
    }

    .vs-detailed {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--text-muted);
        background: var(--bg-primary);
        padding: 0.375rem 0.75rem;
        border-radius: 50%;
        min-width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid var(--border-color);
    }

    .channels-info {
        margin-bottom: 1rem;
        padding-top: 0.75rem;
        border-top: 1px solid var(--border-color);
    }

    .channels-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.375rem;
    }

    .channel-badge {
        font-size: 0.625rem;
        font-weight: 500;
        color: var(--success-600);
        background: var(--success-50);
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        border: 1px solid var(--success-200);
    }

    .channel-badge.more {
        color: var(--text-muted);
        background: var(--bg-tertiary);
        border-color: var(--border-color);
    }

    .game-actions {
        margin-top: auto;
    }

    .btn-generate {
        width: 100%;
        padding: 0.75rem;
        background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
        color: white;
        border: none;
        border-radius: var(--border-radius-sm);
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .btn-generate:hover {
        background: linear-gradient(135deg, var(--primary-600), var(--primary-700));
        transform: translateY(-1px);
    }

    /* Utilitários */
    .text-info-500 {
        color: var(--info-500);
    }

    .text-info-600 {
        color: var(--info-600);
    }

    .bg-info-50 {
        background-color: var(--info-50);
    }

    .text-6xl {
        font-size: 3.75rem;
        line-height: 1;
    }

    .text-xl {
        font-size: 1.25rem;
        line-height: 1.75rem;
    }

    .py-12 {
        padding-top: 3rem;
        padding-bottom: 3rem;
    }

    .mb-2 {
        margin-bottom: 0.5rem;
    }

    .mb-6 {
        margin-bottom: 1.5rem;
    }

    .gap-4 {
        gap: 1rem;
    }

    .justify-center {
        justify-content: center;
    }

    /* Dark theme adjustments */
    [data-theme="dark"] .text-gray-300 {
        color: var(--text-muted);
    }

    [data-theme="dark"] .league-name-detailed {
        color: var(--primary-400);
    }

    [data-theme="dark"] .live-indicator {
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger-400);
    }

    [data-theme="dark"] .channel-badge {
        background: rgba(34, 197, 94, 0.1);
        color: var(--success-400);
        border-color: rgba(34, 197, 94, 0.2);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .game-teams-detailed {
            flex-direction: column;
            gap: 0.5rem;
        }

        .vs-detailed {
            order: 2;
            margin: 0.5rem 0;
        }

        .team-detailed:first-child {
            order: 1;
        }

        .team-detailed:last-child {
            order: 3;
        }
    }
</style>

<script>
    function goToFutBanner() {
        window.location.href = 'futbanner.php';
    }

    // Auto-refresh a cada 5 minutos
    setTimeout(() => {
        location.reload();
    }, 300000); // 5 minutos
</script>

<?php include "includes/footer.php"; ?>