<?php
requireAdmin(); // Apenas admins podem acessar

$success = '';
$error = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_user'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];
        $is_admin = isset($_POST['is_admin']) ? 1 : 0;
        
        try {
            // Verificar se email já existe
            $query = "SELECT id FROM users WHERE email = :email";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error = 'Email já existe no sistema.';
            } else {
                // Criar usuário
                $query = "INSERT INTO users (email, password, is_admin) VALUES (:email, :password, :is_admin)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', password_hash($password, PASSWORD_DEFAULT));
                $stmt->bindParam(':is_admin', $is_admin);
                
                if ($stmt->execute()) {
                    $success = 'Usuário criado com sucesso!';
                }
            }
        } catch (Exception $e) {
            $error = 'Erro ao criar usuário: ' . $e->getMessage();
        }
    } elseif (isset($_POST['update_user'])) {
        $user_id = $_POST['user_id'];
        $email = $_POST['email'];
        $is_admin = isset($_POST['is_admin']) ? 1 : 0;
        $active = isset($_POST['active']) ? 1 : 0;
        
        try {
            $query = "UPDATE users SET email = :email, is_admin = :is_admin, active = :active WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':is_admin', $is_admin);
            $stmt->bindParam(':active', $active);
            $stmt->bindParam(':id', $user_id);
            
            if ($stmt->execute()) {
                $success = 'Usuário atualizado com sucesso!';
            }
        } catch (Exception $e) {
            $error = 'Erro ao atualizar usuário: ' . $e->getMessage();
        }
    } elseif (isset($_POST['change_password'])) {
        $user_id = $_POST['user_id'];
        $new_password = $_POST['new_password'];
        
        try {
            $query = "UPDATE users SET password = :password WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':password', password_hash($new_password, PASSWORD_DEFAULT));
            $stmt->bindParam(':id', $user_id);
            
            if ($stmt->execute()) {
                $success = 'Senha alterada com sucesso!';
            }
        } catch (Exception $e) {
            $error = 'Erro ao alterar senha: ' . $e->getMessage();
        }
    } elseif (isset($_POST['manage_access'])) {
        $user_id = $_POST['user_id'];
        $company_ids = $_POST['company_ids'] ?? [];
        
        try {
            // Remover acessos existentes
            $query = "DELETE FROM user_company_access WHERE user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            // Adicionar novos acessos
            if (!empty($company_ids)) {
                $query = "INSERT INTO user_company_access (user_id, company_id) VALUES (:user_id, :company_id)";
                $stmt = $db->prepare($query);
                
                foreach ($company_ids as $company_id) {
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->bindParam(':company_id', $company_id);
                    $stmt->execute();
                }
            }
            
            $success = 'Acessos atualizados com sucesso!';
        } catch (Exception $e) {
            $error = 'Erro ao gerenciar acessos: ' . $e->getMessage();
        }
    } elseif (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'];
        
        // Verificar se não está tentando excluir a si mesmo
        if ($user_id == $_SESSION['user_id']) {
            $error = 'Você não pode excluir sua própria conta.';
        } else {
            try {
                // Verificar se o usuário tem empresas
                $query = "SELECT COUNT(*) as total FROM companies WHERE user_id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result['total'] > 0) {
                    $error = 'Não é possível excluir usuário que possui empresas cadastradas. Transfira ou exclua as empresas primeiro.';
                } else {
                    // Excluir acessos compartilhados primeiro
                    $query = "DELETE FROM user_company_access WHERE user_id = :user_id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    // Excluir usuário
                    $query = "DELETE FROM users WHERE id = :user_id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    
                    if ($stmt->execute()) {
                        $success = 'Usuário excluído com sucesso!';
                    }
                }
            } catch (Exception $e) {
                $error = 'Erro ao excluir usuário: ' . $e->getMessage();
            }
        }
    }
}

// Buscar usuários
$query = "SELECT * FROM users ORDER BY email";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar todas as empresas
$query = "SELECT c.*, u.email as owner_email FROM companies c 
          LEFT JOIN users u ON c.user_id = u.id 
          ORDER BY c.name";
$stmt = $db->prepare($query);
$stmt->execute();
$all_companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0"><i class="bi bi-people me-2"></i>Gerenciar Usuários</h4>
                        <small>Controle de usuários e permissões do sistema</small>
                    </div>
                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#userModal">
                        <i class="bi bi-person-plus me-2"></i>Novo Usuário
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

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Email</th>
                                <th>Perfil</th>
                                <th>Status</th>
                                <th>Criado em</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <?php if ($user['is_admin']): ?>
                                            <span class="badge bg-danger">Administrador</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Usuário</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['active']): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button class="btn btn-outline-primary" 
                                                    onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)"
                                                    data-bs-toggle="modal" data-bs-target="#editUserModal">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-outline-warning"
                                                    onclick="changePassword(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['email']); ?>')"
                                                    data-bs-toggle="modal" data-bs-target="#passwordModal">
                                                <i class="bi bi-key"></i>
                                            </button>
                                            <?php if (!$user['is_admin']): ?>
                                                <button class="btn btn-outline-info"
                                                        onclick="manageAccess(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['email']); ?>')"
                                                        data-bs-toggle="modal" data-bs-target="#accessModal">
                                                    <i class="bi bi-building"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Novo Usuário -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-person-plus me-2"></i>Novo Usuário
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Senha *</label>
                        <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_admin" name="is_admin">
                        <label class="form-check-label" for="is_admin">
                            Administrador (acesso total)
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="create_user" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Criar Usuário
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Usuário -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil me-2"></i>Editar Usuário
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" id="edit_user_id" name="user_id">
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="edit_is_admin" name="is_admin">
                        <label class="form-check-label" for="edit_is_admin">
                            Administrador
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="edit_active" name="active">
                        <label class="form-check-label" for="edit_active">
                            Usuário ativo
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="update_user" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Alterar Senha -->
<div class="modal fade" id="passwordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-key me-2"></i>Alterar Senha
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" id="password_user_id" name="user_id">
                    <p>Alterando senha do usuário: <strong id="password_user_email"></strong></p>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nova Senha *</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" minlength="6" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="change_password" class="btn btn-warning">
                        <i class="bi bi-key me-2"></i>Alterar Senha
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Gerenciar Acessos -->
<div class="modal fade" id="accessModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-building me-2"></i>Gerenciar Acessos
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" id="access_user_id" name="user_id">
                    <p>Definindo acessos para: <strong id="access_user_email"></strong></p>
                    
                    <div class="row">
                        <?php foreach ($all_companies as $company): ?>
                            <div class="col-md-6 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input company-access" type="checkbox" 
                                           name="company_ids[]" value="<?php echo $company['id']; ?>"
                                           id="company_<?php echo $company['id']; ?>">
                                    <label class="form-check-label" for="company_<?php echo $company['id']; ?>">
                                        <?php echo htmlspecialchars($company['name']); ?>
                                        <small class="text-muted d-block">
                                            Proprietário: <?php echo htmlspecialchars($company['owner_email']); ?>
                                        </small>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="manage_access" class="btn btn-info">
                        <i class="bi bi-save me-2"></i>Salvar Acessos
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editUser(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_is_admin').checked = user.is_admin == 1;
    document.getElementById('edit_active').checked = user.active == 1;
}

function changePassword(userId, email) {
    document.getElementById('password_user_id').value = userId;
    document.getElementById('password_user_email').textContent = email;
    document.getElementById('new_password').value = '';
}

async function manageAccess(userId, email) {
    document.getElementById('access_user_id').value = userId;
    document.getElementById('access_user_email').textContent = email;
    
    // Limpar checkboxes
    document.querySelectorAll('.company-access').forEach(cb => cb.checked = false);
    
    // Buscar acessos atuais via AJAX
    try {
        const response = await fetch('ajax/get_user_access.php?user_id=' + userId);
        const data = await response.json();
        
        data.forEach(access => {
            const checkbox = document.getElementById('company_' + access.company_id);
            if (checkbox) {
                checkbox.checked = true;
            }
        });
    } catch (error) {
        console.error('Erro ao carregar acessos:', error);
    }
}
</script>