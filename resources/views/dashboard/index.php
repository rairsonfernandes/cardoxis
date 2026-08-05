<?php
/**
 * CARDOXIS - Dashboard RF
 */

if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
    header('Location: ' . url('login'));
    exit;
}

$pageTitle = 'Dashboard | CARDOXIS';

$userName = $_SESSION['user_name'] ?? 'Utilizador';
$userEmail = $_SESSION['user_email'] ?? '';
$userRole = $_SESSION['user_role'] ?? 'user';
$userInitials = strtoupper(substr($userName, 0, 1) . (strpos($userName, ' ') ? substr($userName, strpos($userName, ' ') + 1, 1) : ''));

$stats = $stats ?? [];
$totalVehicles = $totalVehicles ?? 0;
$activeVehicles = $activeVehicles ?? 0;
$totalKm = $totalKm ?? 0;
$maintenanceCost = $maintenanceCost ?? 0;
$activeMaintenances = $activeMaintenances ?? 0;
$activeAlerts = $activeAlerts ?? 0;
$totalDrivers = $totalDrivers ?? 0;
$recentVehicles = $recentVehicles ?? [];
$upcomingMaintenances = $upcomingMaintenances ?? [];
$notifications = $notifications ?? [];
$unreadCount = $unreadCount ?? 0;
$vehicleGrowth = $vehicleGrowth ?? [];
$fuelDistribution = $fuelDistribution ?? [];
$hasMoreAlerts = $hasMoreAlerts ?? false;

$_SESSION['alert_count'] = $activeAlerts;
$_SESSION['maintenance_count'] = $activeMaintenances;
$_SESSION['total_vehicles'] = $totalVehicles;

// Funções auxiliares
function getStatusText($status) {
    $texts = ['active' => 'Ativo', 'maintenance' => 'Manutenção', 'inactive' => 'Inativo'];
    return $texts[$status] ?? 'Desconhecido';
}

function getFuelLabel($fuelType) {
    $labels = ['electric' => 'Elétrico', 'hybrid' => 'Híbrido', 'diesel' => 'Gasóleo', 'gasoline' => 'Gasolina'];
    return $labels[strtolower($fuelType)] ?? $fuelType ?? '-';
}

function getAlertIcon($type) {
    $icons = [
        'maintenance' => 'fa-tools',
        'document' => 'fa-file-alt',
        'payment' => 'fa-credit-card',
        'user' => 'fa-user',
        'license' => 'fa-id-card',
        'system' => 'fa-server',
        'insurance' => 'fa-shield-alt'
    ];
    return $icons[$type] ?? 'fa-bell';
}

function getAlertSeverityClass($severity) {
    $classes = ['critical' => 'critical', 'warning' => 'warning', 'info' => 'info'];
    return $classes[$severity] ?? 'info';
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0052CC">
    <meta name="description" content="Dashboard de gestão de frotas CARDOXIS">
    <meta name="author" content="CARDOXIS Team">
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
    
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    <link rel="icon" type="image/x-icon" href="<?= asset('img/logo/favicon.png') ?>">
    <link rel="apple-touch-icon" href="<?= asset('img/logo/apple-touch-icon.png') ?>">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <link rel="stylesheet" href="<?= asset('css/dashboard/dashboard.css') ?>">
    
    <script>
        window.vehicleGrowthData = <?= json_encode($vehicleGrowth) ?>;
        window.fuelDistributionData = <?= json_encode($fuelDistribution) ?>;
        window.userName = '<?= htmlspecialchars($userName) ?>';
        window.totalVehicles = <?= $totalVehicles ?>;
        window.activeVehicles = <?= $activeVehicles ?>;
        window.totalKm = <?= $totalKm ?>;
        window.maintenanceCost = <?= $maintenanceCost ?>;
        window.activeMaintenances = <?= $activeMaintenances ?>;
        window.activeAlerts = <?= $activeAlerts ?>;
        window.totalDrivers = <?= $totalDrivers ?>;
        window.hasMoreAlerts = <?= $hasMoreAlerts ? 'true' : 'false' ?>;
        window.API_URL = '<?= url('api/v1') ?>';
    </script>
</head>
<body>

<!-- MOBILE HEADER -->
<div class="mobile-header">
    <button class="menu-hamburger" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="mobile-logo">
        <img src="<?= asset('img/logo/logo.png') ?>" alt="Logo" class="mobile-logo-img">
        <span class="mobile-logo-text">CARDOXIS<span>.io</span></span>
    </div>

    <button class="btn-icon-refresh" onclick="location.reload()">
        <i class="fas fa-sync-alt"></i>
    </button>
</div>

<!-- SIDEBAR OVERLAY -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- SIDEBAR -->
<?php include_once ROOT_PATH . '/resources/views/layouts/partials/sidebar.php'; ?>

<!-- MAIN CONTENT -->
<main class="main-content">
    <div class="dashboard-container">
        
        <!-- HEADER -->
        <div class="dashboard-header">
            <div class="header-top">
                <h1 class="header-title"><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="globalSearch" placeholder="Pesquisar veículos, motoristas...">
                </div>
                <div class="header-actions">
                    <button class="btn-icon-refresh" onclick="location.reload()"><i class="fas fa-sync-alt"></i> Atualizar</button>
                    <a href="<?= url('vehicles') ?>" class="btn-primary"><i class="fas fa-plus"></i> Novo Veículo</a>
                </div>
            </div>
            <div class="header-bottom">
                <div>
                    <h2 class="welcome-title">Olá, <span class="user-highlight"><?= htmlspecialchars($userName) ?></span> <span class="welcome-emoji">👋</span></h2>
                    <p class="welcome-subtitle">Bem-vindo ao CARDOXIS. Aqui está o resumo da sua frota.</p>
                </div>
                 <div class="last-update">
                    <i class="fas fa-clock"></i>
                    <span>Última atualização: <span id="lastUpdateTime">-</span></span>
                </div>
            </div>
        </div>

        <!-- STATS GRID - Linha 1 -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Total Veículos</span>
                    <div class="stat-icon primary"><i class="fas fa-car"></i></div>
                </div>
                <div class="stat-value" id="totalVehicles"><?= number_format($totalVehicles, 0, ',', '.') ?></div>
                <div class="stat-change">Na sua frota</div>
                <div class="stat-progress">
                    <div class="progress-bar"><div class="progress-fill primary" style="width:100%"></div></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Veículos Ativos</span>
                    <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
                </div>
                <div class="stat-value" id="activeVehicles"><?= number_format($activeVehicles, 0, ',', '.') ?></div>
                <div class="stat-change">Em circulação</div>
                <div class="stat-progress">
                    <div class="progress-bar">
                        <div class="progress-fill success" id="activeVehiclesProgress" style="width: <?= $totalVehicles > 0 ? round($activeVehicles / $totalVehicles * 100) : 0 ?>%"></div>
                    </div>
                    <div class="progress-text" id="activeVehiclesPercent"><?= $totalVehicles > 0 ? round($activeVehicles / $totalVehicles * 100) : 0 ?>%</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Manutenções Pendentes</span>
                    <div class="stat-icon warning"><i class="fas fa-tools"></i></div>
                </div>
                <div class="stat-value" id="pendingMaintenance"><?= number_format($activeMaintenances, 0, ',', '.') ?></div>
                <div class="stat-change">Agendadas</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Alertas</span>
                    <div class="stat-icon danger"><i class="fas fa-bell"></i></div>
                </div>
                <div class="stat-value" id="totalAlerts"><?= number_format($activeAlerts, 0, ',', '.') ?></div>
                <div class="stat-change">Não lidos</div>
            </div>
        </div>

        <!-- STATS GRID - Linha 2 -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Quilómetros Totais</span>
                    <div class="stat-icon primary"><i class="fas fa-road"></i></div>
                </div>
                <div class="stat-value" id="totalKm"><?= number_format($totalKm / 1000, 1, ',', '.') ?>k</div>
                <div class="stat-change">Percorridos</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Custo Manutenções</span>
                    <div class="stat-icon success"><i class="fas fa-euro-sign"></i></div>
                </div>
                <div class="stat-value" id="maintenanceCost">€ <?= number_format($maintenanceCost, 2, ',', '.') ?></div>
                <div class="stat-change">Este ano</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Motoristas</span>
                    <div class="stat-icon info"><i class="fas fa-users"></i></div>
                </div>
                <div class="stat-value" id="totalDrivers"><?= number_format($totalDrivers, 0, ',', '.') ?></div>
                <div class="stat-change">Na frota</div>
            </div>
        </div>

        <!-- CHARTS -->
        <div class="two-columns">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-line" style="color:#0052CC;"></i> Crescimento da Frota</div>
                </div>
                <div class="card-body">
                    <div class="chart-container"><canvas id="vehiclesChart"></canvas></div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-pie" style="color:#36B37E;"></i> Distribuição por Combustível</div>
                </div>
                <div class="card-body">
                    <div class="chart-container"><canvas id="fuelChart"></canvas></div>
                </div>
            </div>
        </div>

        <!-- VEÍCULOS RECENTES -->
        <div class="card" style="margin-bottom:24px;">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-truck" style="color:#0052CC;"></i> Veículos Recentes</div>
                <a href="<?= url('vehicles') ?>" class="btn-secondary" style="font-size:0.75rem;padding:6px 12px;text-decoration:none;">
                    Ver todos <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="card-body">
                <div class="vehicles-grid" id="vehiclesList">
                    <?php if (count($recentVehicles) > 0): ?>
                        <?php foreach ($recentVehicles as $vehicle): ?>
                            <div class="vehicle-card" onclick="viewVehicleDetails(<?= $vehicle['id'] ?>)">
                                <div class="vehicle-image">
                                    <?php if (!empty($vehicle['image_url'])): ?>
                                        <img src="<?= htmlspecialchars($vehicle['image_url']) ?>" alt="<?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?>" onerror="this.parentElement.innerHTML='<i class=\'fas fa-truck\'></i>'">
                                    <?php else: ?>
                                        <i class="fas fa-truck"></i>
                                    <?php endif; ?>
                                    <span class="vehicle-badge <?= $vehicle['status'] ?? 'active' ?>"><?= getStatusText($vehicle['status'] ?? 'active') ?></span>
                                </div>
                                <div class="vehicle-info">
                                    <div class="vehicle-title"><?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?></div>
                                    <div class="vehicle-plate"><?= htmlspecialchars($vehicle['plate'] ?? '-') ?></div>
                                    <div class="vehicle-details">
                                        <div class="vehicle-detail"><i class="fas fa-calendar"></i> <?= $vehicle['year'] ?? '-' ?></div>
                                        <div class="vehicle-detail"><i class="fas fa-palette"></i> <?= htmlspecialchars($vehicle['color'] ?? '-') ?></div>
                                        <div class="vehicle-detail"><i class="fas fa-gas-pump"></i> <?= getFuelLabel($vehicle['fuel_type'] ?? '') ?></div>
                                    </div>
                                    <div class="vehicle-footer">
                                        <div class="vehicle-odometer"><i class="fas fa-road"></i> <?= number_format($vehicle['odometer'] ?? 0, 0, ',', '.') ?> km</div>
                                        <div class="vehicle-actions">
                                            <button class="btn-icon" onclick="event.stopPropagation();viewVehicleDetails(<?= $vehicle['id'] ?>)"><i class="fas fa-eye"></i></button>
                                            <button class="btn-icon" onclick="event.stopPropagation();editVehicleFromDashboard(<?= $vehicle['id'] ?>)"><i class="fas fa-edit"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state"><i class="fas fa-car"></i><p>Nenhum veículo registado</p><a href="<?= url('cardoxis/vehicles') ?>" class="btn-primary" style="padding:8px 20px;">Adicionar veículo</a></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- MANUTENÇÕES | ALERTAS -->
        <div class="two-columns">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-tools" style="color:#FF8B00;"></i> Manutenções Próximas</div>
                    <a href="<?= url('maintenance') ?>" class="btn-secondary" style="font-size:0.75rem;padding:6px 12px;text-decoration:none;">Ver todas <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="card-body">
                    <div id="upcomingMaintenances">
                        <?php if (count($upcomingMaintenances) > 0): ?>
                            <div class="maintenance-list">
                                <?php foreach ($upcomingMaintenances as $maintenance): ?>
                                    <?php 
                                        $daysLeft = ceil((strtotime($maintenance['scheduled_date']) - time()) / (60 * 60 * 24));
                                        $urgencyClass = $daysLeft <= 7 ? 'danger' : ($daysLeft <= 15 ? 'warning' : 'success');
                                    ?>
                                    <div class="maintenance-item">
                                        <div class="maintenance-icon <?= $urgencyClass ?>"><i class="fas fa-tools"></i></div>
                                        <div class="maintenance-content">
                                            <div class="maintenance-title"><?= htmlspecialchars($maintenance['title']) ?></div>
                                            <div class="maintenance-meta"><?= htmlspecialchars($maintenance['brand'] ?? '') ?> <?= htmlspecialchars($maintenance['model'] ?? '') ?> · <?= htmlspecialchars($maintenance['plate'] ?? '') ?></div>
                                        </div>
                                        <div class="maintenance-date <?= $daysLeft <= 7 ? 'urgent' : '' ?>">
                                            <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($maintenance['scheduled_date'])) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state"><i class="fas fa-check-circle"></i><p>Nenhuma manutenção agendada</p></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- ALERTAS - SIMPLES (SEM BOTÕES DE AÇÃO) -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-bell" style="color:#DE350B;"></i> Alertas
                        <span class="nav-badge danger" id="alertsCount"><?= $activeAlerts ?></span>
                    </div>
                    <button class="btn-icon-refresh" onclick="location.reload()" title="Atualizar" style="padding:6px 10px;">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div id="alertsList">
                        <?php if (count($notifications) > 0): ?>
                            <div class="alert-list">
                                <?php 
                                $alertCount = 0;
                                foreach ($notifications as $alert): 
                                    if ($alertCount >= 5) break;
                                    $alertCount++;
                                    $severityClass = getAlertSeverityClass($alert['severity'] ?? 'info');
                                    $icon = getAlertIcon($alert['type'] ?? '');
                                ?>
                                    <div class="alert-item <?= $severityClass ?>">
                                        <div class="alert-icon"><i class="fas <?= $icon ?>"></i></div>
                                        <div class="alert-content">
                                            <div class="alert-title"><?= htmlspecialchars($alert['title']) ?></div>
                                            <div class="alert-message"><?= htmlspecialchars($alert['message']) ?></div>
                                            <div class="alert-time"><i class="fas fa-clock"></i> <?= date('d/m/Y H:i', strtotime($alert['created_at'])) ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <?php if ($hasMoreAlerts): ?>
                                    <div class="alert-more" style="text-align:center;padding:8px 0;">
                                        <a href="<?= url('alerts') ?>" class="btn-secondary" style="width:100%;text-align:center;padding:10px;text-decoration:none;display:block;">
                                            Ver mais alertas <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state"><i class="fas fa-bell-slash"></i><p>Nenhum alerta pendente</p></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- MODAL DETALHES VEÍCULO -->

<div id="vehicleDetailsModal" class="modal">
    <div class="modal-content" style="max-width:600px;">
        <div class="modal-header">
            <h3><i class="fas fa-car"></i> <span id="modalTitle">Detalhes do Veículo</span></h3>
            <button class="modal-close" onclick="closeModal('vehicleDetailsModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div id="modalContent"><div class="loading-spinner"><div class="spinner"></div></div></div>
        </div>
        <div class="modal-footer">
            <button class="btn-danger" onclick="confirmDeleteVehicle()"><i class="fas fa-trash-alt"></i> Eliminar</button>
            <button class="btn-secondary" onclick="closeModal('vehicleDetailsModal')">Fechar</button>
            <button class="btn-primary" id="editFromDetailsBtn"><i class="fas fa-edit"></i> Editar</button>
        </div>
    </div>
</div>

<!-- MODAL EDIÇÃO VEÍCULO -->

<div id="editVehicleModal" class="modal">
    <div class="modal-content" style="max-width:600px;">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Editar Veículo</h3>
            <button class="modal-close" onclick="closeModal('editVehicleModal')">&times;</button>
        </div>
        <form id="editVehicleForm" enctype="multipart/form-data">
            <input type="hidden" id="editVehicleId">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group"><label><i class="fas fa-car"></i> Marca *</label><input type="text" id="editVehicleBrand" required></div>
                    <div class="form-group"><label><i class="fas fa-car-side"></i> Modelo *</label><input type="text" id="editVehicleModel" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label><i class="fas fa-id-card"></i> Matrícula *</label><input type="text" id="editVehiclePlate" required></div>
                    <div class="form-group"><label><i class="fas fa-calendar"></i> Ano</label><input type="number" id="editVehicleYear" min="1900" max="2025"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label><i class="fas fa-palette"></i> Cor</label><input type="text" id="editVehicleColor"></div>
                    <div class="form-group"><label><i class="fas fa-gas-pump"></i> Combustível</label>
                        <select id="editVehicleFuel">
                            <option value="diesel">Diesel</option>
                            <option value="gasoline">Gasolina</option>
                            <option value="electric">Elétrico</option>
                            <option value="hybrid">Híbrido</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label><i class="fas fa-chart-line"></i> Status</label>
                        <select id="editVehicleStatus">
                            <option value="active">Em Operação</option>
                            <option value="maintenance">Manutenção</option>
                            <option value="inactive">Inativo</option>
                        </select>
                    </div>
                    <div class="form-group"><label><i class="fas fa-road"></i> KM Atual</label><input type="number" id="editVehicleOdometer" min="0"></div>
                </div>
                <div class="form-row">
                    <div class="form-group full-width"><label><i class="fas fa-qrcode"></i> Número de Chassis (VIN)</label><input type="text" id="editVehicleChassis" placeholder="Número de chassis"></div>
                </div>
                <div class="form-group full-width">
                    <label><i class="fas fa-image"></i> Imagem</label>
                    <div class="image-upload-area" onclick="document.getElementById('editVehicleImage').click()">
                        <input type="file" id="editVehicleImage" accept="image/jpeg,image/png,image/jpg,image/webp" style="display:none;">
                        <div class="upload-placeholder" id="editUploadPlaceholder"><i class="fas fa-cloud-upload-alt"></i><p>Clique para selecionar uma imagem</p><span>PNG, JPG, WEBP até 5MB</span></div>
                        <div class="image-preview-wrapper" id="editImagePreviewWrapper" style="display:none;">
                            <img id="editImagePreview" src="">
                            <button type="button" class="remove-image" onclick="removeEditImage()"><i class="fas fa-trash-alt"></i> Remover</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('editVehicleModal')">Cancelar</button>
                <button type="submit" class="btn-primary">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL CONFIRMAÇÃO DELETE -->

<div id="deleteConfirmModal" class="modal">
    <div class="modal-content" style="max-width:400px;">
        <div class="modal-header">
            <h3><i class="fas fa-trash-alt"></i> Confirmar Eliminação</h3>
            <button class="modal-close" onclick="closeModal('deleteConfirmModal')">&times;</button>
        </div>
        <div class="modal-body" style="text-align:center;">
            <i class="fas fa-exclamation-triangle" style="font-size:3rem;color:#DE350B;margin-bottom:16px;"></i>
            <p style="font-weight:600;font-size:1.1rem;">Tem certeza que deseja eliminar este veículo?</p>
            <p style="font-size:0.85rem;color:#6B778C;">Esta ação não pode ser desfeita.</p>
        </div>
        <div class="modal-footer" style="justify-content:center;">
            <button class="btn-secondary" onclick="closeModal('deleteConfirmModal')">Cancelar</button>
            <button class="btn-danger" id="confirmDeleteBtn">Eliminar</button>
        </div>
    </div>
</div>

<!-- TOAST -->
<div id="toastContainer" class="toast-container"></div>

<!-- DASHBOARD JS -->
<script src="<?= asset('js/dashboard/dashboard.js') ?>"></script>

</body>
</html>