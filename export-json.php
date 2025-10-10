<?php
require_once 'includes/auth.php';
requireLogin();

$company_id = $_GET['company_id'] ?? null;

if (!$company_id || !canAccessCompany($company_id)) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
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
        echo json_encode(['error' => 'Empresa não encontrada']);
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

    // Montar estrutura JSON completa
    $export_data = [
        'export_info' => [
            'generated_at' => date('Y-m-d H:i:s'),
            'generated_by' => getCurrentUser()['email'],
            'system' => 'Sistema IA - Cadastro para Agente de IA',
            'version' => '1.0'
        ],
        'company' => [
            'id' => $company['id'],
            'name' => $company['name'],
            'cnpj' => $company['cnpj'],
            'slug' => $company['slug'],
            'main_contact' => [
                'address' => $company['main_address'],
                'phone' => $company['main_phone'],
                'whatsapp' => $company['main_whatsapp'],
                'business_hours' => $company['business_hours']
            ],
            'general_info' => $company['general_info'],
            'created_at' => $company['created_at'],
            'updated_at' => $company['updated_at']
        ],
        'addresses' => array_map(function($addr) {
            return [
                'id' => $addr['id'],
                'name' => $addr['name'],
                'address' => $addr['address'],
                'contact' => [
                    'phone' => $addr['phone'],
                    'whatsapp' => $addr['whatsapp'],
                    'business_hours' => $addr['business_hours']
                ],
                'additional_info' => $addr['additional_info'],
                'created_at' => $addr['created_at']
            ];
        }, $addresses),
        'faqs' => array_map(function($faq) {
            return [
                'id' => $faq['id'],
                'question' => $faq['question'],
                'answer' => $faq['answer'],
                'order_index' => (int)$faq['order_index'],
                'created_at' => $faq['created_at']
            ];
        }, $faqs),
        'statistics' => [
            'total_addresses' => count($addresses),
            'total_faqs' => count($faqs),
            'has_general_info' => !empty($company['general_info']),
            'has_main_contact' => !empty($company['main_phone']) || !empty($company['main_whatsapp'])
        ],
        'urls' => [
            'public_page' => 'https://' . $_SERVER['HTTP_HOST'] . '/public.php?slug=' . $company['slug'],
            'amp_page' => 'https://' . $_SERVER['HTTP_HOST'] . '/public-amp.php?slug=' . $company['slug']
        ]
    ];

    // Configurar headers para download
    $filename = 'empresa_' . $company['slug'] . '_' . date('Y-m-d_H-i-s') . '.json';
    
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

    // Gerar JSON formatado
    echo json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor: ' . $e->getMessage()]);
}
?>