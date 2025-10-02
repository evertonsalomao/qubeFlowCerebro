<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, email FROM users WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function login($email, $password) {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, email, password, is_admin FROM users WHERE email = :email AND active = 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['is_admin'] = $user['is_admin'];
        return true;
    }
    
    return false;
}

function register($email, $password) {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar se email já existe
    $query = "SELECT id FROM users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        return false; // Email já existe
    }
    
    // Criar novo usuário
    $query = "INSERT INTO users (email, password) VALUES (:email, :password)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', password_hash($password, PASSWORD_DEFAULT));
    
    return $stmt->execute();
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit();
}

function generateSlug($name) {
    $slug = strtolower($name);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit();
    }
}

function canAccessCompany($company_id) {
    if (isAdmin()) {
        return true; // Admin tem acesso a tudo
    }
    
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar se é dono da empresa
    $query = "SELECT id FROM companies WHERE id = :company_id AND user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':company_id', $company_id);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        return true;
    }
    
    // Verificar se tem acesso compartilhado
    $query = "SELECT id FROM user_company_access WHERE user_id = :user_id AND company_id = :company_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->bindParam(':company_id', $company_id);
    $stmt->execute();
    
    return $stmt->rowCount() > 0;
}
?>