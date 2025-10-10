<?php
require_once 'includes/auth.php';
requireLogin();

$user = getCurrentUser();
$activeTab = $_GET['tab'] ?? 'companies';
$company_id = $_GET['company_id'] ?? null;

// Buscar empresas do usuário
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

if (isAdmin()) {
    // Admin vê todas as empresas
    $query = "SELECT c.*, u.email as owner_email FROM companies c 
              LEFT JOIN users u ON c.user_id = u.id 
              ORDER BY c.name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Usuário comum vê suas empresas + compartilhadas
    $query = "SELECT c.*, u.email as owner_email,
                     CASE WHEN c.user_id = :user_id THEN 1 ELSE 0 END as is_owner
              FROM companies c 
              LEFT JOIN users u ON c.user_id = u.id
              WHERE c.user_id = :user_id
              UNION
              SELECT c.*, u.email as owner_email, 0 as is_owner
              FROM companies c 
              LEFT JOIN users u ON c.user_id = u.id
              INNER JOIN user_company_access uca ON c.id = uca.company_id
              WHERE uca.user_id = :user_id2
              ORDER BY name";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->bindParam(':user_id2', $_SESSION['user_id']);
    $stmt->execute();
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Buscar empresa específica se selecionada
$company = null;
if ($company_id) {
    foreach ($companies as $comp) {
        if ($comp['id'] == $company_id) {
            $company = $comp;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema IA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar {
            background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 100%);
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 15px 20px;
            margin: 5px 15px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }
        .main-content {
            background: #f8fafc;
            min-height: 100vh;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .btn-primary {
            background: linear-gradient(45deg, #1e40af, #3b82f6);
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
        }
        .btn-success {
            background: linear-gradient(45deg, #059669, #10b981);
            border: none;
            border-radius: 10px;
        }
        .btn-danger {
            background: linear-gradient(45deg, #dc2626, #ef4444);
            border: none;
            border-radius: 10px;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e5e7eb;
            padding: 12px 15px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar">
                    <div class="p-4 text-center">
                        <i class="bi bi-building text-white" style="font-size: 2rem;"></i>
                        <h4 class="text-white mt-2">Sistema IA</h4>
                        <small class="text-white-50"><?php echo htmlspecialchars($user['email']); ?></small>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link <?php echo $activeTab == 'companies' ? 'active' : ''; ?>" href="?tab=companies">
                            <i class="bi bi-buildings me-2"></i>Minhas Empresas
                        </a>
                        
                        <?php if (isAdmin()): ?>
                            <a class="nav-link <?php echo $activeTab == 'users' ? 'active' : ''; ?>" href="?tab=users">
                                <i class="bi bi-people me-2"></i>Gerenciar Usuários
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($company): ?>
                            <div class="px-3 py-2">
                                <small class="text-white-50 text-uppercase">Empresa Atual</small>
                                <div class="text-white fw-bold small"><?php echo htmlspecialchars($company['name']); ?></div>
                            </div>
                            
                            <a class="nav-link <?php echo $activeTab == 'company' ? 'active' : ''; ?>" href="?tab=company&company_id=<?php echo $company['id']; ?>">
                                <i class="bi bi-building me-2"></i>Dados da Empresa
                            </a>
                            <a class="nav-link <?php echo $activeTab == 'addresses' ? 'active' : ''; ?>" href="?tab=addresses&company_id=<?php echo $company['id']; ?>">
                                <i class="bi bi-geo-alt me-2"></i>Endereços
                            </a>
                            <a class="nav-link <?php echo $activeTab == 'faq' ? 'active' : ''; ?>" href="?tab=faq&company_id=<?php echo $company['id']; ?>">
                                <i class="bi bi-question-circle me-2"></i>FAQ
                            </a>
                            <a class="nav-link <?php echo $activeTab == 'general' ? 'active' : ''; ?>" href="?tab=general&company_id=<?php echo $company['id']; ?>">
                                <i class="bi bi-info-circle me-2"></i>Informações Gerais
                            </a>
                        <?php endif; ?>
                    </nav>
                    
                    <div class="mt-auto p-3">
                        <?php if ($company && $activeTab != 'companies'): ?>
                            <a href="public.php?slug=<?php echo $company['slug']; ?>" target="_blank" class="btn btn-success btn-sm w-100 mb-2">
                                <i class="bi bi-eye me-2"></i>Ver Página Pública
                            </a>
                            <a href="public-amp.php?slug=<?php echo $company['slug']; ?>" target="_blank" class="btn btn-warning btn-sm w-100 mb-2">
                                <i class="bi bi-lightning me-2"></i>Ver Página AMP
                            </a>
                            <a href="export-json.php?company_id=<?php echo $company['id']; ?>" class="btn btn-info btn-sm w-100 mb-2">
                                <i class="bi bi-download me-2"></i>Baixar JSON
                            </a>
                        <?php endif; ?>
                        <a href="?tab=companies" class="btn btn-outline-light btn-sm w-100 mb-2">
                            <i class="bi bi-buildings me-2"></i>Trocar Empresa
                        </a>
                        <a href="logout.php" class="btn btn-outline-light btn-sm w-100">
                            <i class="bi bi-box-arrow-right me-2"></i>Sair
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content p-4">
                    <?php
                    switch($activeTab) {
                        case 'companies':
                            include 'pages/companies.php';
                            break;
                        case 'users':
                            if (isAdmin()) {
                                include 'pages/users.php';
                            } else {
                                echo '<div class="alert alert-danger">Acesso negado.</div>';
                            }
                            break;
                        case 'company':
                            include 'pages/company.php';
                            break;
                        case 'addresses':
                            include 'pages/addresses.php';
                            break;
                        case 'faq':
                            include 'pages/faq.php';
                            break;
                        case 'general':
                            include 'pages/general.php';
                            break;
                        default:
                            include 'pages/companies.php';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>