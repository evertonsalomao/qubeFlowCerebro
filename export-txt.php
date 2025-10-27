<?php
require_once 'includes/auth.php';
requireLogin();

$company_id = $_GET['company_id'] ?? null;

if (!$company_id || !canAccessCompany($company_id)) {
    http_response_code(403);
    echo 'Acesso negado';
    exit;
}

require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

try {
    // Buscar dados da empresa
    $query = "SELECT * FROM companies WHERE id = :company_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':company_id', $company_id);
    $stmt->execute();
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$company) {
        http_response_code(404);
        echo 'Empresa não encontrada';
        exit;
    }

    // Buscar endereços
    $query = "SELECT * FROM company_addresses WHERE company_id = :company_id ORDER BY created_at";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':company_id', $company_id);
    $stmt->execute();
    $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Buscar FAQs
    $query = "SELECT * FROM faqs WHERE company_id = :company_id ORDER BY order_index";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':company_id', $company_id);
    $stmt->execute();
    $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Configurar headers para download
    $filename = 'empresa_' . $company['slug'] . '_' . date('Y-m-d_H-i-s') . '.txt';
    
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

    // Gerar conteúdo TXT
    $output = '';
    
    // Cabeçalho
    $output .= "================================================================================\n";
    $output .= "INFORMAÇÕES DA EMPRESA - SISTEMA IA\n";
    $output .= "================================================================================\n";
    $output .= "Gerado em: " . date('d/m/Y H:i:s') . "\n";
    $output .= "Gerado por: " . getCurrentUser()['email'] . "\n";
    $output .= "Sistema: Sistema IA - Cadastro para Agente de IA\n";
    $output .= "================================================================================\n\n";

    // Dados da empresa
    $output .= "DADOS DA EMPRESA\n";
    $output .= "================================================================================\n";
    $output .= "Nome: " . $company['name'] . "\n";
    
    if ($company['cnpj']) {
        $output .= "CNPJ: " . $company['cnpj'] . "\n";
    }
    
    if ($company['main_address']) {
        $output .= "\nEndereço Principal:\n";
        $output .= $company['main_address'] . "\n";
    }
    
    if ($company['main_phone']) {
        $output .= "\nTelefone: " . $company['main_phone'] . "\n";
    }
    
    if ($company['main_whatsapp']) {
        $output .= "WhatsApp: " . $company['main_whatsapp'] . "\n";
    }
    
    if ($company['business_hours']) {
        $output .= "Horário: " . $company['business_hours'] . "\n";
    }
    
    $output .= "\nCriado em: " . date('d/m/Y H:i:s', strtotime($company['created_at'])) . "\n";
    $output .= "Atualizado em: " . date('d/m/Y H:i:s', strtotime($company['updated_at'])) . "\n";
    $output .= "\n";

    // Endereços e filiais
    if (!empty($addresses)) {
        $output .= "ENDEREÇOS E FILIAIS\n";
        $output .= "================================================================================\n";
        
        foreach ($addresses as $address) {
            $output .= "## INICIO DOS DADOS DA UNIDADE " . strtoupper($address['name']) . " ##\n\n";
            
            if ($address['address']) {
                $output .= "Endereço:\n";
                $output .= $address['address'] . "\n\n";
            }
            
            if ($address['phone']) {
                $output .= "Telefone: " . $address['phone'] . "\n";
            }
            
            if ($address['whatsapp']) {
                $output .= "WhatsApp: " . $address['whatsapp'] . "\n";
            }
            
            if ($address['business_hours']) {
                $output .= "Horário: " . $address['business_hours'] . "\n";
            }
            
            if ($address['additional_info']) {
                $output .= "\nInformações Adicionais:\n";
                $output .= $address['additional_info'] . "\n";
            }
            
            $output .= "\n## FIM DOS DADOS DA UNIDADE " . strtoupper($address['name']) . " ##\n\n";
            $output .= "--------------------------------------------------------------------------------\n\n";
        }
    }

    // Perguntas e respostas
    if (!empty($faqs)) {
        $output .= "PERGUNTAS E RESPOSTAS\n";
        $output .= "================================================================================\n\n";
        
        foreach ($faqs as $faq) {
            $output .= "P: " . $faq['question'] . "\n";
            $output .= "R: " . $faq['answer'] . "\n\n";
            $output .= "--------------------------------------------------------------------------------\n\n";
        }
    }

    // Informações gerais
    if ($company['general_info']) {
        $output .= "INFORMAÇÕES GERAIS\n";
        $output .= "================================================================================\n";
        $output .= $company['general_info'] . "\n\n";
    }

    // URLs públicas
    $output .= "URLS PÚBLICAS\n";
    $output .= "================================================================================\n";
    $output .= "Página Normal: https://" . $_SERVER['HTTP_HOST'] . "/public.php?slug=" . $company['slug'] . "\n";
    $output .= "Página AMP: https://" . $_SERVER['HTTP_HOST'] . "/public-amp.php?slug=" . $company['slug'] . "\n\n";

    // Estatísticas
    $output .= "ESTATÍSTICAS\n";
    $output .= "================================================================================\n";
    $output .= "Total de endereços: " . count($addresses) . "\n";
    $output .= "Total de perguntas e respostas: " . count($faqs) . "\n";
    $output .= "Possui informações gerais: " . (!empty($company['general_info']) ? 'Sim' : 'Não') . "\n";
    $output .= "Possui contato principal: " . (!empty($company['main_phone']) || !empty($company['main_whatsapp']) ? 'Sim' : 'Não') . "\n\n";

    // Rodapé
    $output .= "================================================================================\n";
    $output .= "Arquivo gerado pelo Sistema IA - Cadastro para Agente de IA\n";
    $output .= "© " . date('Y') . " " . $company['name'] . "\n";
    $output .= "================================================================================\n";

    // Enviar conteúdo
    echo $output;

} catch (Exception $e) {
    http_response_code(500);
    echo 'Erro interno do servidor: ' . $e->getMessage();
}
?>