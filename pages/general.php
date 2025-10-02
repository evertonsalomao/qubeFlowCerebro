<?php
if (!$company_id || !canAccessCompany($company_id)) {
    echo '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Configure primeiro os dados da empresa para poder gerenciar informações gerais.</div>';
    return;
}


$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $general_info = $_POST['general_info'] ?? '';
    
    try {
        $query = "UPDATE companies SET general_info = :general_info, updated_at = NOW() WHERE id = :id AND user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':general_info', $general_info);
        $stmt->bindParam(':id', $company['id']);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $success = 'Informações gerais salvas com sucesso!';
            $company['general_info'] = $general_info; // Atualizar variável local
        }
    } catch (Exception $e) {
        $error = 'Erro ao salvar informações: ' . $e->getMessage();
    }
}
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informações Gerais</h4>
                <small>Campo livre para adicionar qualquer informação adicional sobre sua empresa</small>
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

                <form method="POST">
                    <div class="mb-3">
                        <label for="general_info" class="form-label">Informações Adicionais</label>
                        <textarea class="form-control" id="general_info" name="general_info" rows="15" 
                                  style="font-family: 'Courier New', monospace;"><?php echo htmlspecialchars($company['general_info'] ?? ''); ?></textarea>
                        <div class="form-text">
                            Você pode usar texto formatado, listas, ou qualquer informação relevante para o agente de IA.<br>
                            <strong>Exemplos:</strong> História da empresa, missão/visão/valores, certificações, prêmios, 
                            produtos especiais, políticas, informações técnicas, etc.
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Salvar Informações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>