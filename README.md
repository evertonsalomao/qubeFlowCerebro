# Sistema de Cadastro para Agente de IA

Sistema completo em PHP 8 + MySQL + Bootstrap 5 para cadastro e gerenciamento de informações empresariais destinadas a alimentar agentes de IA.

## Funcionalidades

### 🔐 Sistema de Autenticação

- Login e cadastro de usuários
- Sessões seguras
- Proteção de rotas

### 🏢 Gestão de Empresas
- Cadastro completo de dados empresariais
- Campos: nome, CNPJ, endereço, telefones, horários
- Geração automática de slug único
- URL pública para cada empresa

### 📍 Múltiplos Endereços
- Cadastro de filiais e unidades
- Informações específicas por endereço
- Campo livre para informações adicionais

### ❓ Sistema de FAQ
- Perguntas e respostas organizadas
- Reordenação por drag & drop
- Interface accordion responsiva

### 📝 Informações Gerais
- Campo de texto livre
- Ideal para informações complementares
- Suporte a formatação de texto

### 🌐 Página Pública Otimizada
- URL amigável: `/empresa/nome-da-empresa`
- Tags semânticas HTML (`<address>`, etc.)
- Dados estruturados Schema.org
- Design responsivo
- Otimizada para agentes de IA

## Requisitos

- PHP 8.0+
- MySQL 5.7+
- Apache com mod_rewrite
- Bootstrap 5 (CDN)

## Instalação

1. **Clone/baixe os arquivos** para seu servidor web

2. **Configure o banco de dados** em `config/database.php`:
```php
private $host = 'localhost';
private $db_name = 'ai_agent_system';
private $username = 'seu_usuario';
private $password = 'sua_senha';
```

3. **Execute o script SQL** em `database/schema.sql` para criar as tabelas

4. **Configure permissões** do Apache para os diretórios

5. **Acesse** `http://seudominio.com/login.php`

## Estrutura do Projeto

```
/
├── config/
│   └── database.php          # Configuração do banco
├── database/
│   └── schema.sql           # Script de criação das tabelas
├── includes/
│   └── auth.php             # Funções de autenticação
├── pages/
│   ├── company.php          # Gestão da empresa
│   ├── addresses.php        # Gestão de endereços
│   ├── faq.php             # Gestão de FAQ
│   └── general.php         # Informações gerais
├── login.php               # Página de login/cadastro
├── dashboard.php           # Dashboard principal
├── public.php             # Página pública da empresa
├── logout.php             # Logout
├── index.php              # Redirect para login
├── .htaccess              # Configurações Apache
└── README.md              # Este arquivo
```

## URLs do Sistema

- **Login/Cadastro**: `/login.php`
- **Dashboard**: `/dashboard.php`
- **Página Pública**: `/empresa/slug-da-empresa`
- **API Friendly**: `/public.php?slug=slug-da-empresa`

## Dados Estruturados

O sistema gera automaticamente dados estruturados Schema.org para:
- Informações da organização
- Endereços e localizações
- Horários de funcionamento
- Dados de contato

## Segurança

- Senhas criptografadas com `password_hash()`
- Proteção contra SQL Injection (PDO)
- Validação de sessões
- Sanitização de dados de entrada
- Proteção de diretórios sensíveis

## Customização

### Cores e Tema
Edite as variáveis CSS nos arquivos para personalizar:
- Cores primárias: `#1e40af`, `#3b82f6`
- Gradientes e sombras
- Tipografia e espaçamentos

### Campos Adicionais
Para adicionar novos campos:
1. Altere a estrutura da tabela no MySQL
2. Atualize os formulários PHP
3. Modifique a página pública

## Suporte

Sistema desenvolvido para hospedagens compartilhadas padrão com PHP e MySQL.

Testado em:
- PHP 8.0, 8.1, 8.2
- MySQL 5.7, 8.0
- Apache 2.4

## Licença

Código livre para uso comercial e pessoal.