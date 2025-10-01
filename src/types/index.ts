export interface User {
  id: string;
  email: string;
  created_at: string;
}

export interface Company {
  id: string;
  user_id: string;
  name: string;
  cnpj: string;
  main_address: string;
  main_phone: string;
  main_whatsapp: string;
  business_hours: string;
  general_info: string;
  slug: string;
  created_at: string;
  updated_at: string;
}

export interface CompanyAddress {
  id: string;
  company_id: string;
  name: string;
  address: string;
  phone: string;
  whatsapp: string;
  business_hours: string;
  additional_info: string;
  created_at: string;
}

export interface FAQ {
  id: string;
  company_id: string;
  question: string;
  answer: string;
  order_index: number;
  created_at: string;
}