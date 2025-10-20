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
    "url" => "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
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
<!doctype html>
<html ⚡ lang="pt-BR">
<head>
    <meta charset="utf-8">
    <script async src="https://cdn.ampproject.org/v0.js"></script>
    <script async custom-element="amp-accordion" src="https://cdn.ampproject.org/v0/amp-accordion-0.1.js"></script>
    <script async custom-element="amp-sidebar" src="https://cdn.ampproject.org/v0/amp-sidebar-0.1.js"></script>
    
    <title><?php echo htmlspecialchars($company['name']); ?> - Informações da Empresa (AMP)</title>
    <link rel="canonical" href="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . str_replace('public-amp.php', 'public.php', $_SERVER['REQUEST_URI']); ?>">
    <meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">
    <meta name="description" content="Informações completas sobre <?php echo htmlspecialchars($company['name']); ?> - Versão AMP otimizada para dispositivos móveis">
    
    <!-- Dados Estruturados -->
    <script type="application/ld+json">
    <?php echo json_encode($structuredData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?>
    </script>
    
    <style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style><noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>
    
    <style amp-custom>
        /* Reset e Base */
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background: #f8fafc;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 1rem;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2rem;
            margin: 0 0 0.5rem 0;
            font-weight: 700;
        }
        
        .header p {
            margin: 0;
            opacity: 0.9;
        }
        
        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem;
        }
        
        /* Cards */
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(45deg, #1e40af, #3b82f6);
            color: white;
            padding: 1rem 1.5rem;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        /* Grid */
        .grid {
            display: grid;
            gap: 1rem;
        }
        
        .grid-2 {
            grid-template-columns: 1fr 1fr;
        }
        
        .grid-3 {
            grid-template-columns: repeat(3, 1fr);
        }
        
        @media (max-width: 768px) {
            .grid-2, .grid-3 {
                grid-template-columns: 1fr;
            }
        }
        
        /* Info Items */
        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        
        .info-icon {
            width: 20px;
            height: 20px;
            color: #3b82f6;
            flex-shrink: 0;
            margin-top: 2px;
        }
        
        .info-content h3 {
            font-size: 0.9rem;
            font-weight: 600;
            margin: 0 0 0.25rem 0;
            color: #374151;
        }
        
        .info-content p {
            margin: 0;
            color: #6b7280;
        }
        
        /* Address */
        .address {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
            margin-bottom: 1.5rem;
        }
        
        /* FAQ Accordion */
        .faq-item {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            overflow: hidden;
        }
        
        .faq-header {
            background: #f9fafb;
            padding: 1rem;
            font-weight: 600;
            color: #374151;
            cursor: pointer;
            border: none;
            width: 100%;
            text-align: left;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .faq-content {
            padding: 1rem;
            background: white;
            color: #6b7280;
            line-height: 1.6;
        }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .badge-primary {
            background: #dbeafe;
            color: #1e40af;
        }
        
        /* Footer */
        .footer {
            background: #374151;
            color: #d1d5db;
            text-align: center;
            padding: 2rem 1rem;
            margin-top: 3rem;
        }
        
        .footer p {
            margin: 0;
        }
        
        /* AMP Badge */
        .amp-badge {
            position: fixed;
            bottom: 1rem;
            right: 1rem;
            background: #ff6b35;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
        }
        
        /* Responsive */
        @media (max-width: 640px) {
            .header {
                padding: 1.5rem 1rem;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
            
            .container {
                padding: 0.5rem;
            }
            
            .card-body {
                padding: 1rem;
            }
        }
        
        /* Icons usando Unicode */
        .icon-phone::before { content: "📞"; }
        .icon-whatsapp::before { content: "💬"; }
        .icon-clock::before { content: "🕒"; }
        .icon-location::before { content: "📍"; }
        .icon-building::before { content: "🏢"; }
        .icon-info::before { content: "ℹ️"; }
        .icon-question::before { content: "❓"; }
        
        .icon {
            font-style: normal;
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- AMP Badge -->
    <div class="amp-badge">⚡ AMP</div>
    
    <!-- Header -->
    <header class="header">
        <h1><?php echo htmlspecialchars($company['name']); ?></h1>
        <?php if ($company['cnpj']): ?>
            <p>CNPJ: <?php echo htmlspecialchars($company['cnpj']); ?></p>
        <?php endif; ?>
    </header>

    <div class="container">
        <!-- Informações da Empresa -->
        <div class="card">
            <div class="card-header">
                <span class="icon icon-building"></span>Informações da Empresa
            </div>
            <div class="card-body">
                <?php if ($company['main_address']): ?>
                    <address class="address">
                        <h3 style="color: #3b82f6; margin-bottom: 1rem; font-size: 1.1rem;">Endereço Principal</h3>
                        <div class="info-item">
                            <div class="info-content">
                                <h3>Endereço</h3>
                                <p><?php echo nl2br(htmlspecialchars($company['main_address'])); ?></p>
                            </div>
                        </div>
                    </address>
                <?php endif; ?>

                <div class="grid grid-2">
                    <?php if ($company['main_phone']): ?>
                        <div class="info-item">
                            <div class="info-content">
                                <h3>Telefone</h3>
                                <p><?php echo htmlspecialchars($company['main_phone']); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($company['main_whatsapp']): ?>
                        <div class="info-item">
                            <div class="info-content">
                                <h3>WhatsApp</h3>
                                <p><?php echo htmlspecialchars($company['main_whatsapp']); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($company['business_hours']): ?>
                        <div class="info-item" style="grid-column: 1 / -1;">
                            <div class="info-content">
                                <h3>Horário</h3>
                                <p><?php echo htmlspecialchars($company['business_hours']); ?></p>
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
                    <span class="icon icon-location"></span>Endereços e Filiais
                </div>
                <div class="card-body">
                    <?php foreach ($addresses as $address): ?>
                        <address class="address">
                            <h3 style="color: #3b82f6; margin-bottom: 1rem; font-size: 1.1rem;">
                                ## INICIO DOS DADOS DA UNIDADE <?php echo strtoupper(htmlspecialchars($address['name'])); ?> ##
                            </h3>
                            
                            <?php if ($address['address']): ?>
                                <div style="margin-bottom: 1rem;">
                                    <strong>Endereço:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($address['address'])); ?>
                                </div>
                            <?php endif; ?>

                            <div class="grid grid-3">
                                <?php if ($address['phone']): ?>
                                    <div class="info-item">
                                        <div class="info-content">
                                            <strong>Telefone:</strong><br>
                                            <?php echo htmlspecialchars($address['phone']); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($address['whatsapp']): ?>
                                    <div class="info-item">
                                        <div class="info-content">
                                            <strong>WhatsApp:</strong><br>
                                            <?php echo htmlspecialchars($address['whatsapp']); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($address['business_hours']): ?>
                                    <div class="info-item">
                                        <div class="info-content">
                                            <strong>Horário:</strong><br>
                                            <?php echo htmlspecialchars($address['business_hours']); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($address['additional_info']): ?>
                                <div style="background: #f3f4f6; padding: 1rem; border-radius: 6px; margin-top: 1rem;">
                                    <p style="margin: 0; font-size: 0.9rem; color: #6b7280;">
                                        <strong>Informações Adicionais:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($address['additional_info'])); ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                            
                            <h3 style="color: #6b7280; margin-top: 1rem; font-size: 0.9rem;">
                                ## FIM DOS DADOS DA UNIDADE <?php echo strtoupper(htmlspecialchars($address['name'])); ?> ##
                            </h3>
                        </address>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Informações Gerais -->
        <?php if ($company['general_info']): ?>
            <div class="card">
                <div class="card-header">
                    <span class="icon icon-info"></span>Informações Gerais
                </div>
                <div class="card-body">
                    <div style="color: #6b7280; line-height: 1.7;">
                        <?php echo nl2br(htmlspecialchars($company['general_info'])); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- FAQ -->
        <?php if (!empty($faqs)): ?>
            <div class="card">
                <div class="card-header">
                    <span class="icon icon-question"></span>Perguntas Frequentes
                </div>
                <div class="card-body">
                    <amp-accordion>
                        <?php foreach ($faqs as $faq): ?>
                            <section>
                                <header class="faq-header">
                                    P: <?php echo htmlspecialchars($faq['question']); ?>
                                </header>
                                <div class="faq-content">
                                    <strong>R:</strong> <?php echo nl2br(htmlspecialchars($faq['answer'])); ?>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    </amp-accordion>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <p>© <?php echo date('Y'); ?> <?php echo htmlspecialchars($company['name']); ?>. 
        Página AMP gerada automaticamente para agente de IA.</p>
        <p style="margin-top: 0.5rem; font-size: 0.8rem; opacity: 0.8;">
            ⚡ Versão otimizada para dispositivos móveis
        </p>
    </footer>
</body>
</html>