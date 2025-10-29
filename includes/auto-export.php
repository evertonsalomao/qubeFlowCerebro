<?php
function generateCompanyTXT($company_id) {
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
            return false;
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

        // Gerar conteúdo TXT
        $output = '';
        
        // Cabeçalho
        $output .= "================================================================================\n";
        $output .= "INFORMAÇÕES DA EMPRESA - SISTEMA IA\n";
        $output .= "================================================================================\n";
        $output .= "Gerado automaticamente em: " . date('d/m/Y H:i:s') . "\n";
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

        // Rodapé
        $output .= "================================================================================\n";
        $output .= "Arquivo gerado automaticamente pelo Sistema IA\n";
        $output .= "© " . date('Y') . " " . $company['name'] . "\n";
        $output .= "================================================================================\n";

        // Criar diretório se não existir
        $exportDir = 'exports';
        if (!file_exists($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        // Salvar arquivo
        $filename = 'empresa_' . $company['slug'] . '.txt';
        $filepath = $exportDir . '/' . $filename;
        
        if (file_put_contents($filepath, $output)) {
            return $filename;
        }

        return false;

    } catch (Exception $e) {
        error_log('Erro ao gerar TXT automático: ' . $e->getMessage());
        return false;
    }
}

function autoExportCompanyData($company_id) {
    return generateCompanyTXT($company_id);
}
?>