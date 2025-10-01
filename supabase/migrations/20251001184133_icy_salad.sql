@@ .. @@
 -- Inserir usuário de teste (senha: 123456)
--- O usuário será criado pelo script test_password.php com hash correto
+-- O usuário será criado pelo script test_password.php com hash correto
+
+-- Inserir empresa de exemplo para teste
+INSERT IGNORE INTO companies (id, user_id, name, cnpj, main_address, main_phone, main_whatsapp, business_hours, general_info, slug) 
+SELECT 1, 1, 'Empresa Exemplo', '12.345.678/0001-90', 'Rua das Flores, 123 - Centro', '(11) 3333-4444', '(11) 99999-8888', 'Seg-Sex: 8h-18h', 'Empresa de exemplo para demonstração do sistema.', 'empresa-exemplo'
+WHERE EXISTS (SELECT 1 FROM users WHERE id = 1);