<?php
require_once 'config/database.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    http_response_code(404);
    echo '<h1>Empresa não encontrada</h1>';
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Buscar empresa
$query = "SELECT * FROM companies WHERE slug = :slug";
$stmt = $db->prepare($query);
$stmt->bindParam(':slug', $slug);
$stmt->execute();
$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    http_response_code(404);
    echo '<h1>Empresa não encontrada</h1>';
    exit;
}

// Buscar endereços
$query = "SELECT * FROM company_addresses WHERE company_id = :company_id ORDER BY created_at";
$stmt = $db->prepare($query);
$stmt->bindParam(':company_id', $company['id']);
$stmt->execute();
$addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar FAQs
$query = "SELECT * FROM faqs WHERE company_id = :company_id ORDER BY order_index";
$stmt = $db->prepare($query);
$stmt->bindParam(':company_id', $company['id']);
$stmt->execute();
$faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Gerar dados estruturados
$structuredData = [
    "@context" => "https://schema.org",
    "@type" => "Organization",
    "name" => $company['name'],
    "url" => "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
];

if ($company['cnpj']) {
    $structuredData['vatID'] = $company['cnpj'];
}

if ($company['main_phone']) {
    $structuredData['telephone'] = $company['main_phone'];
}

if ($company['main_address']) {
    $structuredData['address'] = [
        "@type" => "PostalAddress",
        "streetAddress" => $company['main_address']
    ];
}

if ($company['business_hours']) {
    $structuredData['openingHours'] = $company['business_hours'];
}

if (!empty($addresses)) {
    $structuredData['location'] = [];
    foreach ($addresses as $addr) {
        $location = [
            "@type" => "Place",
            "name" => $addr['name']
        ];
        
        if ($addr['address']) {
            $location['address'] = [
                "@type" => "PostalAddress",
                "streetAddress" => $addr['address']
            ];
        }
        
        if ($addr['phone']) {
            $location['telephone'] = $addr['phone'];
        }
        
        if ($addr['business_hours']) {
            $location['openingHours'] = $addr['business_hours'];
        }
        
        $structuredData['location'][] = $location;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($company['name']); ?> - Informações da Empresa</title>
    <meta name="description" content="Informações completas sobre <?php echo htmlspecialchars($company['name']); ?>">
    <link rel="amphtml" href="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . '/empresa/' . $company['slug'] . '/amp'; ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Dados Estruturados -->
    <script type="application/ld+json">
    <?php echo json_encode($structuredData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?>
    </script>
    
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 2rem;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        .card-header {
            background: linear-gradient(45deg, #1e40af, #3b82f6);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 1.5rem;
        }
        .info-icon {
            color: #3b82f6;
            margin-right: 0.5rem;
        }
        .faq-item {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            margin-bottom: 1rem;
            overflow: hidden;
        }
        .faq-question {
            background: #f8fafc;
            padding: 1rem;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .faq-question:hover {
            background: #e2e8f0;
        }
        .faq-answer {
            padding: 1rem;
            background: white;
            border-top: 1px solid #e5e7eb;
        }
        address {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 10px;
            border-left: 4px solid #3b82f6;
            margin-bottom: 1.5rem;
        }
        .contact-info {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .contact-info i {
            width: 20px;
            color: #3b82f6;
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h1 class="display-4 fw-bold mb-3"><?php echo htmlspecialchars($company['name']); ?></h1>
                    <?php if ($company['cnpj']): ?>
                        <p class="lead">CNPJ: <?php echo htmlspecialchars($company['cnpj']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <div class="container my-5">
        <div class="row">
            <!-- Coluna Principal -->
            <div class="col-lg-8">
                <!-- Informações da Empresa -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="h4 mb-0"><i class="bi bi-building me-2"></i>Informações da Empresa</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($company['main_address']): ?>
                            <address>
                                <h3 class="text-primary mb-3">Endereço Principal</h3>
                                <div class="contact-info">
                                    <div>
                                        <strong>Endereço:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($company['main_address'])); ?>
                                    </div>
                                </div>
                            </address>
                        <?php endif; ?>

                        <div class="row">
                            <?php if ($company['main_phone']): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="contact-info">
                                        <div>
                                            <strong>Telefone</strong><br>
                                            <?php echo htmlspecialchars($company['main_phone']); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($company['main_whatsapp']): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="contact-info">
                                        <div>
                                            <strong>WhatsApp</strong><br>
                                            <?php echo htmlspecialchars($company['main_whatsapp']); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($company['business_hours']): ?>
                                <div class="col-12 mb-3">
                                    <div class="contact-info">
                                        <div>
                                            <strong>Horário</strong><br>
                                            <?php echo htmlspecialchars($company['business_hours']); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Endereços e Filiais -->
                <?php if (!empty($addresses)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h2 class="h4 mb-0"><i class="bi bi-geo-alt me-2"></i>Endereços e Filiais</h2>
                        </div>
                        <div class="card-body">
                            <?php foreach ($addresses as $address): ?>
                                <address>
                                    <h4 class="text-primary mb-3">## INICIO DOS DADOS DA UNIDADE <?php echo strtoupper(htmlspecialchars($address['name'])); ?> ##</h4>
                                    
                                    <?php if ($address['address']): ?>
                                        <div class="mb-3">
                                            <strong>Endereço:</strong><br>
                                            <?php echo nl2br(htmlspecialchars($address['address'])); ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="row">
                                        <?php if ($address['phone']): ?>
                                            <div class="col-md-4 mb-2">
                                                <div class="contact-info">
                                                    <strong>Telefone:</strong><br>
                                                    <?php echo htmlspecialchars($address['phone']); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($address['whatsapp']): ?>
                                            <div class="col-md-4 mb-2">
                                                <div class="contact-info">
                                                    <strong>WhatsApp:</strong><br>
                                                    <?php echo htmlspecialchars($address['whatsapp']); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($address['business_hours']): ?>
                                            <div class="col-md-4 mb-2">
                                                <div class="contact-info">
                                                    <strong>Horário:</strong><br>
                                                    <?php echo htmlspecialchars($address['business_hours']); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($address['additional_info']): ?>
                                        <div class="bg-light p-3 rounded mt-3">
                                            <small class="text-muted d-block mb-1">Informações Adicionais:</small>
                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($address['additional_info'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <h4 class="text-muted mt-3">## FIM DOS DADOS DA UNIDADE <?php echo strtoupper(htmlspecialchars($address['name'])); ?> ##</h4>
                                </address>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Informações Gerais -->
                <?php if ($company['general_info']): ?>
                    <div class="card">
                        <div class="card-header">
                            <h2 class="h4 mb-0"><i class="bi bi-info-circle me-2"></i>Informações Gerais</h2>
                        </div>
                        <div class="card-body">
                            <div class="text-muted">
                                <?php echo nl2br(htmlspecialchars($company['general_info'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- FAQ -->
                <?php if (!empty($faqs)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="h5 mb-0"><i class="bi bi-question-circle me-2"></i>Perguntas Frequentes</h3>
                        </div>
                        <div class="card-body">
                            <?php foreach ($faqs as $faq): ?>
                                <div class="faq-item">
                                    <div class="faq-question" data-bs-toggle="collapse" data-bs-target="#faq<?php echo $faq['id']; ?>">
                                        <strong>P: <?php echo htmlspecialchars($faq['question']); ?></strong>
                                        <i class="bi bi-chevron-down float-end"></i>
                                    </div>
                                    <div id="faq<?php echo $faq['id']; ?>" class="collapse">
                                        <div class="faq-answer">
                                            <strong>R:</strong> <?php echo nl2br(htmlspecialchars($faq['answer'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="mb-0">© <?php echo date('Y'); ?> <?php echo htmlspecialchars($company['name']); ?>. 
                    Página gerada automaticamente para agente de IA.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>