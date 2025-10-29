<?php
if (!$company || !canAccessCompany($company['id'])) {
    echo '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Selecione uma empresa para gerenciar seus dados.</div>';
    return;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $cnpj = $_POST['cnpj'] ?? '';
    $main_address = $_POST['main_address'] ?? '';
    $main_phone = $_POST['main_phone'] ?? '';
    $main_whatsapp = $_POST['main_whatsapp'] ?? '';
    $business_hours = $_POST['business_hours'] ?? '';
    $slug = generateSlug($name);
    
    try {
        if ($company) {
            // Atualizar empresa existente
            // Verificar se pode editar (apenas donos ou admins)
            if (!isAdmin()) {
                $query = "SELECT user_id FROM companies WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $company['id']);
                $stmt->execute();
                $owner = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$owner || $owner['user_id'] != $_SESSION['user_id']) {
                    $error = 'Você não tem permissão para editar esta empresa.';
                    // Não pode editar - continua para mostrar erro
                }
            }
            
            $query = "UPDATE companies SET name = :name, cnpj = :cnpj, main_address = :main_address, 
                     main_phone = :main_phone, main_whatsapp = :main_whatsapp, business_hours = :business_hours, 
                     slug = :slug, updated_at = NOW() WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $company['id']);
        } else {
            echo '<div class="alert alert-danger">Empresa não encontrada.</div>';
            return;
        }
        
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':cnpj', $cnpj);
        $stmt->bindParam(':main_address', $main_address);
        $stmt->bindParam(':main_phone', $main_phone);
        $stmt->bindParam(':main_whatsapp', $main_whatsapp);
        $stmt->bindParam(':business_hours', $business_hours);
        $stmt->bindParam(':slug', $slug);
        
        if ($stmt->execute()) {
            $success = 'Dados da empresa salvos com sucesso!';
            
            // Gerar arquivo TXT automaticamente
            require_once 'includes/auto-export.php';
            autoExportCompanyData($company['id']);
            
            // Recarregar dados da empresa
            $query = "SELECT * FROM companies WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $company['id']);
            $stmt->execute();
            $company = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        $error = 'Erro ao salvar dados: ' . $e->getMessage();
    }
}
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0"><i class="bi bi-building me-2"></i>Dados da Empresa</h4>
                        <small>Configure as informações principais da sua empresa</small>
                    </div>
                    <?php if ($company): ?>
                        <a href="public.php?slug=<?php echo $company['slug']; ?>" target="_blank" class="btn btn-light btn-sm">
                            <i class="bi bi-eye me-2"></i>Ver Página Pública
                        </a>
                        <a href="public-amp.php?slug=<?php echo $company['slug']; ?>" target="_blank" class="btn btn-warning btn-sm ms-2">
                            <i class="bi bi-lightning me-2"></i>Ver AMP
                        </a>
                        <a href="export-json.php?company_id=<?php echo $company['id']; ?>" class="btn btn-info btn-sm ms-2">
                            <i class="bi bi-download me-2"></i>Baixar JSON
                        </a>
                        <a href="export-txt.php?company_id=<?php echo $company['id']; ?>" class="btn btn-secondary btn-sm ms-2">
                            <i class="bi bi-file-text me-2"></i>Baixar TXT
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
                        <?php if ($company): ?>
                            <br><small>URL pública: <a href="public.php?slug=<?php echo $company['slug']; ?>" target="_blank">
                                <?php echo $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/public.php?slug=' . $company['slug']; ?>
                            </a></small>
                        <?php endif; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Nome da Empresa *</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($company['name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="cnpj" class="form-label">CNPJ</label>
                            <input type="text" class="form-control" id="cnpj" name="cnpj" 
                                   value="<?php echo htmlspecialchars($company['cnpj'] ?? ''); ?>" 
                                   placeholder="00.000.000/0000-00">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="main_address" class="form-label">Endereço Principal</label>
                        <textarea class="form-control" id="main_address" name="main_address" rows="3"><?php echo htmlspecialchars($company['main_address'] ?? ''); ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="main_phone" class="form-label">Telefone Principal</label>
                            <input type="tel" class="form-control" id="main_phone" name="main_phone" 
                                   value="<?php echo htmlspecialchars($company['main_phone'] ?? ''); ?>" 
                                   placeholder="(11) 9999-9999">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="main_whatsapp" class="form-label">WhatsApp Principal</label>
                            <input type="tel" class="form-control" id="main_whatsapp" name="main_whatsapp" 
                                   value="<?php echo htmlspecialchars($company['main_whatsapp'] ?? ''); ?>" 
                                   placeholder="(11) 99999-9999">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="business_hours" class="form-label">Horário de Funcionamento</label>
                            <input type="text" class="form-control" id="business_hours" name="business_hours" 
                                   value="<?php echo htmlspecialchars($company['business_hours'] ?? ''); ?>" 
                                   placeholder="Seg-Sex: 8h-18h">
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Salvar Dados
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>