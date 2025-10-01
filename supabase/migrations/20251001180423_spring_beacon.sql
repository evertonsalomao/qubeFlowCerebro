/*
  # Sistema de Cadastro para Agente de IA

  1. Novas Tabelas
    - `companies`
      - `id` (uuid, primary key)
      - `user_id` (uuid, foreign key to auth.users)
      - `name` (text, nome da empresa)
      - `cnpj` (text, CNPJ da empresa)
      - `main_address` (text, endereço principal)
      - `main_phone` (text, telefone principal)
      - `main_whatsapp` (text, WhatsApp principal)
      - `business_hours` (text, horário de funcionamento)
      - `general_info` (text, informações gerais)
      - `slug` (text, URL amigável única)
      - `created_at` (timestamptz)
      - `updated_at` (timestamptz)
    
    - `company_addresses`
      - `id` (uuid, primary key)
      - `company_id` (uuid, foreign key)
      - `name` (text, nome da unidade)
      - `address` (text, endereço completo)
      - `phone` (text, telefone)
      - `whatsapp` (text, WhatsApp)
      - `business_hours` (text, horário)
      - `additional_info` (text, informações adicionais)
      - `created_at` (timestamptz)
    
    - `faqs`
      - `id` (uuid, primary key)
      - `company_id` (uuid, foreign key)
      - `question` (text, pergunta)
      - `answer` (text, resposta)
      - `order_index` (integer, ordem)
      - `created_at` (timestamptz)

  2. Segurança
    - RLS habilitado em todas as tabelas
    - Políticas para usuários autenticados gerenciarem apenas seus dados
    - Acesso público às páginas das empresas via slug
*/

-- Tabela de empresas
CREATE TABLE IF NOT EXISTS companies (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid REFERENCES auth.users(id) ON DELETE CASCADE NOT NULL,
  name text NOT NULL,
  cnpj text DEFAULT '',
  main_address text DEFAULT '',
  main_phone text DEFAULT '',
  main_whatsapp text DEFAULT '',
  business_hours text DEFAULT '',
  general_info text DEFAULT '',
  slug text UNIQUE NOT NULL,
  created_at timestamptz DEFAULT now(),
  updated_at timestamptz DEFAULT now()
);

-- Tabela de endereços das empresas
CREATE TABLE IF NOT EXISTS company_addresses (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  company_id uuid REFERENCES companies(id) ON DELETE CASCADE NOT NULL,
  name text NOT NULL,
  address text DEFAULT '',
  phone text DEFAULT '',
  whatsapp text DEFAULT '',
  business_hours text DEFAULT '',
  additional_info text DEFAULT '',
  created_at timestamptz DEFAULT now()
);

-- Tabela de perguntas e respostas
CREATE TABLE IF NOT EXISTS faqs (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  company_id uuid REFERENCES companies(id) ON DELETE CASCADE NOT NULL,
  question text NOT NULL,
  answer text NOT NULL,
  order_index integer DEFAULT 0,
  created_at timestamptz DEFAULT now()
);

-- Índices para performance
CREATE INDEX IF NOT EXISTS companies_user_id_idx ON companies(user_id);
CREATE INDEX IF NOT EXISTS companies_slug_idx ON companies(slug);
CREATE INDEX IF NOT EXISTS company_addresses_company_id_idx ON company_addresses(company_id);
CREATE INDEX IF NOT EXISTS faqs_company_id_idx ON faqs(company_id);
CREATE INDEX IF NOT EXISTS faqs_order_idx ON faqs(company_id, order_index);

-- RLS para companies
ALTER TABLE companies ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Users can manage own companies"
  ON companies
  FOR ALL
  TO authenticated
  USING (auth.uid() = user_id)
  WITH CHECK (auth.uid() = user_id);

CREATE POLICY "Public can read companies"
  ON companies
  FOR SELECT
  TO anon, authenticated
  USING (true);

-- RLS para company_addresses
ALTER TABLE company_addresses ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Users can manage own company addresses"
  ON company_addresses
  FOR ALL
  TO authenticated
  USING (
    EXISTS (
      SELECT 1 FROM companies 
      WHERE companies.id = company_addresses.company_id 
      AND companies.user_id = auth.uid()
    )
  )
  WITH CHECK (
    EXISTS (
      SELECT 1 FROM companies 
      WHERE companies.id = company_addresses.company_id 
      AND companies.user_id = auth.uid()
    )
  );

CREATE POLICY "Public can read company addresses"
  ON company_addresses
  FOR SELECT
  TO anon, authenticated
  USING (true);

-- RLS para faqs
ALTER TABLE faqs ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Users can manage own company faqs"
  ON faqs
  FOR ALL
  TO authenticated
  USING (
    EXISTS (
      SELECT 1 FROM companies 
      WHERE companies.id = faqs.company_id 
      AND companies.user_id = auth.uid()
    )
  )
  WITH CHECK (
    EXISTS (
      SELECT 1 FROM companies 
      WHERE companies.id = faqs.company_id 
      AND companies.user_id = auth.uid()
    )
  );

CREATE POLICY "Public can read faqs"
  ON faqs
  FOR SELECT
  TO anon, authenticated
  USING (true);