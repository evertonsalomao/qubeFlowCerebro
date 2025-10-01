<?php
if (!$company_id) {
    echo '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Configure primeiro os dados da empresa para poder cadastrar perguntas e respostas.</div>';
    return;
}

// Verificar se a empresa pertence ao usuário
$query = "SELECT * FROM companies WHERE id = :id AND user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $company_id);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    echo '<div class="alert alert-danger">Empresa não encontrada ou sem permissão.</div>';
    return;
}

$success = '';
$error = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['import_csv']) && isset($_FILES['csv_file'])) {
        try {
            $file = $_FILES['csv_file'];
            
            // Verificar se o arquivo foi enviado sem erros
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Erro no upload do arquivo.');
            }
            
            // Verificar extensão do arquivo
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($ext !== 'csv') {
                throw new Exception('Apenas arquivos CSV são permitidos.');
            }
            
            // Ler o arquivo CSV
            $handle = fopen($file['tmp_name'], 'r');
            if (!$handle) {
                throw new Exception('Não foi possível ler o arquivo.');
            }
            
            $imported = 0;
            $errors = [];
            $line = 0;
            
            // Buscar próximo order_index
            $query = "SELECT COALESCE(MAX(order_index), -1) + 1 as next_order FROM faqs WHERE company_id = :company_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':company_id', $company['id']);
            $stmt->execute();
            $next_order = $stmt->fetch(PDO::FETCH_ASSOC)['next_order'];
            
            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                $line++;
                
                // Pular linha vazia
                if (empty($data[0]) && empty($data[1])) {
                    continue;
                }
                
                // Verificar se tem pelo menos 2 colunas
                if (count($data) < 2) {
                    $errors[] = "Linha $line: Formato inválido (precisa ter pergunta e resposta)";
                    continue;
                }
                
                $question = trim($data[0]);
                $answer = trim($data[1]);
                
                // Verificar se pergunta e resposta não estão vazias
                if (empty($question) || empty($answer)) {
                    $errors[] = "Linha $line: Pergunta ou resposta vazia";
                    continue;
                }
                
                // Inserir no banco
                $query = "INSERT INTO faqs (company_id, question, answer, order_index) VALUES (:company_id, :question, :answer, :order_index)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':company_id', $company['id']);
                $stmt->bindParam(':question', $question);
                $stmt->bindParam(':answer', $answer);
                $stmt->bindParam(':order_index', $next_order);
                
                if ($stmt->execute()) {
                    $imported++;
                    $next_order++;
                } else {
                    $errors[] = "Linha $line: Erro ao salvar no banco de dados";
                }
            }
            
            fclose($handle);
            
            if ($imported > 0) {
                $success = "Importação concluída! $imported pergunta(s) importada(s).";
                if (!empty($errors)) {
                    $success .= " Alguns erros ocorreram: " . implode(', ', array_slice($errors, 0, 3));
                    if (count($errors) > 3) {
                        $success .= " e mais " . (count($errors) - 3) . " erro(s).";
                    }
                }
            } else {
                $error = "Nenhuma pergunta foi importada. Erros: " . implode(', ', $errors);
            }
            
        } catch (Exception $e) {
            $error = 'Erro na importação: ' . $e->getMessage();
        }
    } elseif (isset($_POST['save_faq'])) {
        $question = $_POST['question'];
        $answer = $_POST['answer'];
        
        try {
            if (isset($_POST['faq_id']) && $_POST['faq_id']) {
                // Atualizar FAQ
                $query = "UPDATE faqs SET question = :question, answer = :answer WHERE id = :id AND company_id = :company_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $_POST['faq_id']);
                $stmt->bindParam(':company_id', $company['id']);
            } else {
                // Criar novo FAQ
                $query = "SELECT COALESCE(MAX(order_index), -1) + 1 as next_order FROM faqs WHERE company_id = :company_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':company_id', $company['id']);
                $stmt->execute();
                $next_order = $stmt->fetch(PDO::FETCH_ASSOC)['next_order'];
                
                $query = "INSERT INTO faqs (company_id, question, answer, order_index) VALUES (:company_id, :question, :answer, :order_index)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':company_id', $company['id']);
                $stmt->bindParam(':order_index', $next_order);
            }
            
            $stmt->bindParam(':question', $question);
            $stmt->bindParam(':answer', $answer);
            
            if ($stmt->execute()) {
                $success = 'Pergunta salva com sucesso!';
            }
        } catch (Exception $e) {
            $error = 'Erro ao salvar pergunta: ' . $e->getMessage();
        }
    } elseif (isset($_POST['delete_faq'])) {
        try {
            $query = "DELETE FROM faqs WHERE id = :id AND company_id = :company_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $_POST['faq_id']);
            $stmt->bindParam(':company_id', $company['id']);
            
            if ($stmt->execute()) {
                $success = 'Pergunta excluída com sucesso!';
            }
        } catch (Exception $e) {
            $error = 'Erro ao excluir pergunta: ' . $e->getMessage();
        }
    } elseif (isset($_POST['move_faq'])) {
        try {
            $faq_id = $_POST['faq_id'];
            $direction = $_POST['direction'];
            
            // Buscar FAQ atual
            $query = "SELECT order_index FROM faqs WHERE id = :id AND company_id = :company_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $faq_id);
            $stmt->bindParam(':company_id', $company['id']);
            $stmt->execute();
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($current) {
                $new_order = $direction == 'up' ? $current['order_index'] - 1 : $current['order_index'] + 1;
                
                // Trocar posições
                $query = "UPDATE faqs SET order_index = :temp WHERE company_id = :company_id AND order_index = :new_order";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':temp', $current['order_index']);
                $stmt->bindParam(':company_id', $company['id']);
                $stmt->bindParam(':new_order', $new_order);
                $stmt->execute();
                
                $query = "UPDATE faqs SET order_index = :new_order WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':new_order', $new_order);
                $stmt->bindParam(':id', $faq_id);
                $stmt->execute();
            }
        } catch (Exception $e) {
            $error = 'Erro ao mover pergunta: ' . $e->getMessage();
        }
    }
}

// Buscar FAQs
$query = "SELECT * FROM faqs WHERE company_id = :company_id ORDER BY order_index";
$stmt = $db->prepare($query);
$stmt->bindParam(':company_id', $company['id']);
$stmt->execute();
$faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0"><i class="bi bi-question-circle me-2"></i>Perguntas & Respostas</h4>
                        <small>Gerencie as perguntas frequentes da sua empresa</small>
                    </div>
                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#faqModal">
                        <i class="bi bi-plus me-2"></i>Nova Pergunta
                    </button>
                    <button class="btn btn-success btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="bi bi-upload me-2"></i>Importar CSV
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

                <?php if (empty($faqs)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-question-circle text-muted" style="font-size: 3rem;"></i>
                        <h5 class="text-muted mt-3">Nenhuma pergunta cadastrada</h5>
                        <p class="text-muted">Cadastre a primeira pergunta frequente</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#faqModal">
                            <i class="bi bi-plus me-2"></i>Cadastrar Primeira Pergunta
                        </button>
                    </div>
                <?php else: ?>
                    <div class="accordion" id="faqAccordion">
                        <?php foreach ($faqs as $index => $faq): ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#faq<?php echo $faq['id']; ?>">
                                        <strong><?php echo htmlspecialchars($faq['question']); ?></strong>
                                    </button>
                                </h2>
                                <div id="faq<?php echo $faq['id']; ?>" class="accordion-collapse collapse" 
                                     data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></p>
                                            </div>
                                            <div class="ms-3">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <?php if ($index > 0): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="faq_id" value="<?php echo $faq['id']; ?>">
                                                            <input type="hidden" name="direction" value="up">
                                                            <button type="submit" name="move_faq" class="btn btn-outline-secondary btn-sm">
                                                                <i class="bi bi-arrow-up"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($index < count($faqs) - 1): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="faq_id" value="<?php echo $faq['id']; ?>">
                                                            <input type="hidden" name="direction" value="down">
                                                            <button type="submit" name="move_faq" class="btn btn-outline-secondary btn-sm">
                                                                <i class="bi bi-arrow-down"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <a href="#" class="btn btn-outline-primary btn-sm edit-faq-btn"
                                                       onclick="editFaq('<?php echo $faq['id']; ?>', '<?php echo addslashes(htmlspecialchars($faq['question'])); ?>', '<?php echo addslashes(htmlspecialchars($faq['answer'])); ?>')"
                                                       data-bs-toggle="modal" data-bs-target="#faqModal">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir esta pergunta?')">
                                                        <input type="hidden" name="faq_id" value="<?php echo $faq['id']; ?>">
                                                        <button type="submit" name="delete_faq" class="btn btn-outline-danger btn-sm">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
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

<!-- Modal para FAQ -->
<div class="modal fade" id="faqModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-question-circle me-2"></i><span id="faqModalTitle">Nova Pergunta</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="faq_id" id="faqId" value="">
                    
                    <div class="mb-3">
                        <label for="question" class="form-label">Pergunta *</label>
                        <input type="text" class="form-control" id="question" name="question" 
                               value="" required>
                    </div>

                    <div class="mb-3">
                        <label for="answer" class="form-label">Resposta *</label>
                        <textarea class="form-control" id="answer" name="answer" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="save_faq" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Importar CSV -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-upload me-2"></i>Importar Perguntas via CSV
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <h6><i class="bi bi-info-circle me-2"></i>Formato do arquivo CSV:</h6>
                        <ul class="mb-0">
                            <li><strong>Coluna 1:</strong> Pergunta</li>
                            <li><strong>Coluna 2:</strong> Resposta</li>
                            <li><strong>Separador:</strong> Vírgula (,)</li>
                            <li><strong>Codificação:</strong> UTF-8</li>
                        </ul>
                    </div>
                    
                    <div class="alert alert-warning">
                        <h6><i class="bi bi-exclamation-triangle me-2"></i>Exemplo:</h6>
                        <code>
                            "Qual o horário de funcionamento?","Funcionamos de segunda a sexta, das 8h às 18h."<br>
                            "Vocês fazem entrega?","Sim, fazemos entrega em toda a cidade."
                        </code>
                    </div>

                    <div class="mb-3">
                        <label for="csv_file" class="form-label">Selecionar arquivo CSV *</label>
                        <input type="file" class="form-control" id="csv_file" name="csv_file" 
                               accept=".csv" required>
                        <div class="form-text">
                            Apenas arquivos .csv são aceitos. Máximo 2MB.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="import_csv" class="btn btn-success">
                        <i class="bi bi-upload me-2"></i>Importar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Função para limpar o formulário (nova pergunta)
function clearFaqForm() {
    document.getElementById('faqModalTitle').textContent = 'Nova Pergunta';
    document.getElementById('faqId').value = '';
    document.getElementById('question').value = '';
    document.getElementById('answer').value = '';
}

// Função para preencher formulário de edição
function editFaq(id, question, answer) {
    document.getElementById('faqModalTitle').textContent = 'Editar Pergunta';
    document.getElementById('faqId').value = id;
    document.getElementById('question').value = question;
    document.getElementById('answer').value = answer;
}

// Event listener quando a página carrega
document.addEventListener('DOMContentLoaded', function() {
    // Event listener para botão "Nova Pergunta" - limpar formulário
    const newFaqBtn = document.querySelector('button[data-bs-target="#faqModal"]');
    if (newFaqBtn) {
        newFaqBtn.addEventListener('click', clearFaqForm);
    }
});
</script>