<?php
if (!$company) {
    echo '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Configure primeiro os dados da empresa para poder cadastrar endereços.</div>';
    return;
}

$success = '';
$error = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['save_address'])) {
        $name = $_POST['name'];
        $address = $_POST['address'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $whatsapp = $_POST['whatsapp'] ?? '';
        $business_hours = $_POST['business_hours'] ?? '';
        $additional_info = $_POST['additional_info'] ?? '';
        
        try {
            if (isset($_POST['address_id']) && $_POST['address_id']) {
                // Atualizar endereço
                $query = "UPDATE company_addresses SET name = :name, address = :address, phone = :phone, 
                         whatsapp = :whatsapp, business_hours = :business_hours, additional_info = :additional_info 
                         WHERE id = :id AND company_id = :company_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $_POST['address_id']);
                $stmt->bindParam(':company_id', $company['id']);
            } else {
                // Criar novo endereço
                $query = "INSERT INTO company_addresses (company_id, name, address, phone, whatsapp, 
                         business_hours, additional_info) VALUES (:company_id, :name, :address, :phone, 
                         :whatsapp, :business_hours, :additional_info)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':company_id', $company['id']);
            }
            
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':whatsapp', $whatsapp);
            $stmt->bindParam(':business_hours', $business_hours);
            $stmt->bindParam(':additional_info', $additional_info);
            
            if ($stmt->execute()) {
                $success = 'Endereço salvo com sucesso!';
            }
        } catch (Exception $e) {
            $error = 'Erro ao salvar endereço: ' . $e->getMessage();
        }
    } elseif (isset($_POST['delete_address'])) {
        try {
            $query = "DELETE FROM company_addresses WHERE id = :id AND company_id = :company_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $_POST['address_id']);
            $stmt->bindParam(':company_id', $company['id']);
            
            if ($stmt->execute()) {
                $success = 'Endereço excluído com sucesso!';
            }
        } catch (Exception $e) {
            $error = 'Erro ao excluir endereço: ' . $e->getMessage();
        }
    }
}

// Buscar endereços
$query = "SELECT * FROM company_addresses WHERE company_id = :company_id ORDER BY created_at";
$stmt = $db->prepare($query);
$stmt->bindParam(':company_id', $company['id']);
$stmt->execute();
$addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Endereço para edição
$editAddress = null;
if (isset($_GET['edit'])) {
    foreach ($addresses as $addr) {
        if ($addr['id'] == $_GET['edit']) {
            $editAddress = $addr;
            break;
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
                        <h4 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Endereços e Filiais</h4>
                        <small>Gerencie os endereços da sua empresa</small>
                    </div>
                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addressModal">
                        <i class="bi bi-plus me-2"></i>Novo Endereço
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

                <?php if (empty($addresses)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-geo-alt text-muted" style="font-size: 3rem;"></i>
                        <h5 class="text-muted mt-3">Nenhum endereço cadastrado</h5>
                        <p class="text-muted">Cadastre o primeiro endereço da sua empresa</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addressModal">
                            <i class="bi bi-plus me-2"></i>Cadastrar Primeiro Endereço
                        </button>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($addresses as $address): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <h5 class="card-title text-primary"><?php echo htmlspecialchars($address['name']); ?></h5>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="?tab=addresses&edit=<?php echo $address['id']; ?>" data-bs-toggle="modal" data-bs-target="#addressModal">
                                                        <i class="bi bi-pencil me-2"></i>Editar
                                                    </a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este endereço?')">
                                                            <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                                                            <button type="submit" name="delete_address" class="dropdown-item text-danger">
                                                                <i class="bi bi-trash me-2"></i>Excluir
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        
                                        <?php if ($address['address']): ?>
                                            <p class="card-text"><i class="bi bi-geo-alt me-2 text-muted"></i><?php echo nl2br(htmlspecialchars($address['address'])); ?></p>
                                        <?php endif; ?>
                                        
                                        <div class="row g-2 mb-3">
                                            <?php if ($address['phone']): ?>
                                                <div class="col-6">
                                                    <small class="text-muted d-block">Telefone</small>
                                                    <span><?php echo htmlspecialchars($address['phone']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($address['whatsapp']): ?>
                                                <div class="col-6">
                                                    <small class="text-muted d-block">WhatsApp</small>
                                                    <span><?php echo htmlspecialchars($address['whatsapp']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($address['business_hours']): ?>
                                                <div class="col-12">
                                                    <small class="text-muted d-block">Horário</small>
                                                    <span><?php echo htmlspecialchars($address['business_hours']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if ($address['additional_info']): ?>
                                            <div class="bg-light p-3 rounded">
                                                <small class="text-muted d-block mb-1">Informações Adicionais</small>
                                                <small><?php echo nl2br(htmlspecialchars($address['additional_info'])); ?></small>
                                            </div>
                                        <?php endif; ?>
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

<!-- Modal para Endereço -->
<div class="modal fade" id="addressModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-geo-alt me-2"></i><?php echo $editAddress ? 'Editar Endereço' : 'Novo Endereço'; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <?php if ($editAddress): ?>
                        <input type="hidden" name="address_id" value="<?php echo $editAddress['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Nome da Unidade *</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($editAddress['name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="business_hours" class="form-label">Horário de Funcionamento</label>
                            <input type="text" class="form-control" id="business_hours" name="business_hours" 
                                   value="<?php echo htmlspecialchars($editAddress['business_hours'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Endereço Completo</label>
                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($editAddress['address'] ?? ''); ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Telefone</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($editAddress['phone'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="whatsapp" class="form-label">WhatsApp</label>
                            <input type="tel" class="form-control" id="whatsapp" name="whatsapp" 
                                   value="<?php echo htmlspecialchars($editAddress['whatsapp'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="additional_info" class="form-label">Informações Adicionais</label>
                        <textarea class="form-control" id="additional_info" name="additional_info" rows="3"><?php echo htmlspecialchars($editAddress['additional_info'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="save_address" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($editAddress): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var modal = new bootstrap.Modal(document.getElementById('addressModal'));
    modal.show();
});
</script>
<?php endif; ?>