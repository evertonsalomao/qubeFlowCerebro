<?php
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_company'])) {
        $name = $_POST['name'];
        $cnpj = $_POST['cnpj'] ?? '';
        $main_address = $_POST['main_address'] ?? '';
        $main_phone = $_POST['main_phone'] ?? '';
        $main_whatsapp = $_POST['main_whatsapp'] ?? '';
        $business_hours = $_POST['business_hours'] ?? '';
        $slug = generateSlug($name);
        
        // Verificar se slug já existe
        $query = "SELECT id FROM companies WHERE slug = :slug";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':slug', $slug);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $slug = $slug . '-' . time(); // Adicionar timestamp para tornar único
        }
        
        try {
            $query = "INSERT INTO companies (user_id, name, cnpj, main_address, main_phone, main_whatsapp, 
                     business_hours, slug) VALUES (:user_id, :name, :cnpj, :main_address, :main_phone, 
                     :main_whatsapp, :business_hours, :slug)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':cnpj', $cnpj);
            $stmt->bindParam(':main_address', $main_address);
            $stmt->bindParam(':main_phone', $main_phone);
            $stmt->bindParam(':main_whatsapp', $main_whatsapp);
            $stmt->bindParam(':business_hours', $business_hours);
            $stmt->bindParam(':slug', $slug);
            
            if ($stmt->execute()) {
                $success = 'Empresa criada com sucesso!';
                // Recarregar lista de empresas
                $query = "SELECT * FROM companies WHERE user_id = :user_id ORDER BY name";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $_SESSION['user_id']);
                $stmt->execute();
                $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            $error = 'Erro ao criar empresa: ' . $e->getMessage();
        }
    } elseif (isset($_POST['delete_company'])) {
        try {
            // Verificar se pode excluir (apenas donos ou admins)
            if (!isAdmin()) {
                $query = "SELECT user_id FROM companies WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $_POST['company_id']);
                $stmt->execute();
                $company_check = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$company_check || $company_check['user_id'] != $_SESSION['user_id']) {
                    $error = 'Você não tem permissão para excluir esta empresa.';
                    // Não pode excluir
                }
            }
            
            $query = "DELETE FROM companies WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $_POST['company_id']);
            
            if ($stmt->execute()) {
                $success = 'Empresa excluída com sucesso!';
                header('Location: ?tab=companies');
                exit();
            }
        } catch (Exception $e) {
            $error = 'Erro ao excluir empresa: ' . $e->getMessage();
        }
    }
}
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0"><i class="bi bi-buildings me-2"></i>Minhas Empresas</h4>
                        <small>Gerencie todas as suas empresas cadastradas</small>
                    </div>
                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#companyModal">
                        <i class="bi bi-plus me-2"></i>Nova Empresa
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (empty($companies)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-buildings text-muted" style="font-size: 4rem;"></i>
                        <h5 class="text-muted mt-3">Nenhuma empresa cadastrada</h5>
                        <p class="text-muted">Cadastre sua primeira empresa para começar</p>
                        <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#companyModal">
                            <i class="bi bi-plus me-2"></i>Cadastrar Primeira Empresa
                        </button>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($companies as $comp): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100 border-0 shadow-sm position-relative">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <h5 class="card-title text-primary mb-0"><?php echo htmlspecialchars($comp['name']); ?></h5>
                                            <?php if (isset($comp['owner_email']) && $comp['owner_email'] != $user['email']): ?>
                                                <small class="text-muted">Compartilhada por: <?php echo htmlspecialchars($comp['owner_email']); ?></small>
                                            <?php endif; ?>
                                            <div class="dropdown" style="z-index: 1050;">
                                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="public.php?slug=<?php echo $comp['slug']; ?>" target="_blank">
                                                        <i class="bi bi-eye me-2"></i>Ver Página Pública
                                                    </a></li>
                                                    <li><a class="dropdown-item" href="public-amp.php?slug=<?php echo $comp['slug']; ?>" target="_blank">
                                                        <i class="bi bi-lightning me-2"></i>Ver Página AMP
                                                    </a></li>
                                                    <li><a class="dropdown-item" href="export-json.php?company_id=<?php echo $comp['id']; ?>">
                                                        <i class="bi bi-download me-2"></i>Baixar JSON
                                                    </a></li>
                                                    <li><a class="dropdown-item" href="export-txt.php?company_id=<?php echo $comp['id']; ?>">
                                                        <i class="bi bi-file-text me-2"></i>Baixar TXT
                                                    </a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <?php if (isAdmin() || !isset($comp['is_owner']) || $comp['is_owner']): ?>
                                                            <form method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir esta empresa? Todos os dados serão perdidos!')">
                                                                <input type="hidden" name="company_id" value="<?php echo $comp['id']; ?>">
                                                                <button type="submit" name="delete_company" class="dropdown-item text-danger">
                                                                    <i class="bi bi-trash me-2"></i>Excluir
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <span class="dropdown-item text-muted">
                                                                <i class="bi bi-lock me-2"></i>Sem permissão
                                                            </span>
                                                        <?php endif; ?>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        
                                        <?php if ($comp['cnpj']): ?>
                                            <p class="text-muted small mb-2">CNPJ: <?php echo htmlspecialchars($comp['cnpj']); ?></p>
                                        <?php endif; ?>
                                        
                                        <?php if ($comp['main_address']): ?>
                                            <p class="card-text small text-muted mb-3">
                                                <i class="bi bi-geo-alt me-1"></i>
                                                <?php echo htmlspecialchars(substr($comp['main_address'], 0, 60)) . (strlen($comp['main_address']) > 60 ? '...' : ''); ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <div class="row g-2 mb-3">
                                            <?php if ($comp['main_phone']): ?>
                                                <div class="col-6">
                                                    <small class="text-muted d-block">Telefone</small>
                                                    <small><?php echo htmlspecialchars($comp['main_phone']); ?></small>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($comp['business_hours']): ?>
                                                <div class="col-6">
                                                    <small class="text-muted d-block">Horário</small>
                                                    <small><?php echo htmlspecialchars($comp['business_hours']); ?></small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="d-grid gap-2">
                                            <a href="?tab=company&company_id=<?php echo $comp['id']; ?>" class="btn btn-primary btn-sm">
                                                <i class="bi bi-gear me-2"></i>Gerenciar Empresa
                                            </a>
                                            <div class="btn-group" role="group">
                                                <a href="?tab=addresses&company_id=<?php echo $comp['id']; ?>" class="btn btn-outline-secondary btn-sm">
                                                    <i class="bi bi-geo-alt me-1"></i>Endereços
                                                </a>
                                                <a href="?tab=faq&company_id=<?php echo $comp['id']; ?>" class="btn btn-outline-secondary btn-sm">
                                                    <i class="bi bi-question-circle me-1"></i>FAQ
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-light">
                                        <small class="text-muted">
                                            <i class="bi bi-calendar me-1"></i>
                                            Criada em <?php echo date('d/m/Y', strtotime($comp['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Nova Empresa -->
<div class="modal fade" id="companyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-building me-2"></i>Nova Empresa
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="name" class="form-label">Nome da Empresa *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="cnpj" class="form-label">CNPJ</label>
                            <input type="text" class="form-control" id="cnpj" name="cnpj" placeholder="00.000.000/0000-00">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="main_address" class="form-label">Endereço Principal</label>
                        <textarea class="form-control" id="main_address" name="main_address" rows="2"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="main_phone" class="form-label">Telefone Principal</label>
                            <input type="tel" class="form-control" id="main_phone" name="main_phone" placeholder="(11) 3333-4444">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="main_whatsapp" class="form-label">WhatsApp Principal</label>
                            <input type="tel" class="form-control" id="main_whatsapp" name="main_whatsapp" placeholder="(11) 99999-8888">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="business_hours" class="form-label">Horário de Funcionamento</label>
                            <input type="text" class="form-control" id="business_hours" name="business_hours" placeholder="Seg-Sex: 8h-18h">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="create_company" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Criar Empresa
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>