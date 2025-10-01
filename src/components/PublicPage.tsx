import React, { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { supabase } from '../lib/supabase';
import { Company, CompanyAddress, FAQ } from '../types';
import { Phone, MessageCircle, Clock, MapPin, Building2 } from 'lucide-react';

export function PublicPage() {
  const { slug } = useParams<{ slug: string }>();
  const [company, setCompany] = useState<Company | null>(null);
  const [addresses, setAddresses] = useState<CompanyAddress[]>([]);
  const [faqs, setFaqs] = useState<FAQ[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (slug) {
      loadCompanyData();
    }
  }, [slug]);

  const loadCompanyData = async () => {
    if (!slug) return;
    
    try {
      // Load company
      const { data: companyData } = await supabase
        .from('companies')
        .select('*')
        .eq('slug', slug)
        .single();
      
      if (companyData) {
        setCompany(companyData);
        
        // Load addresses
        const { data: addressesData } = await supabase
          .from('company_addresses')
          .select('*')
          .eq('company_id', companyData.id)
          .order('created_at');
        
        if (addressesData) {
          setAddresses(addressesData);
        }
        
        // Load FAQs
        const { data: faqsData } = await supabase
          .from('faqs')
          .select('*')
          .eq('company_id', companyData.id)
          .order('order_index');
        
        if (faqsData) {
          setFaqs(faqsData);
        }
      }
    } catch (error) {
      console.error('Erro ao carregar dados:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600">Carregando informações...</p>
        </div>
      </div>
    );
  }

  if (!company) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <Building2 className="w-16 h-16 text-gray-400 mx-auto mb-4" />
          <h1 className="text-2xl font-bold text-gray-900 mb-2">Empresa não encontrada</h1>
          <p className="text-gray-600">A empresa solicitada não existe ou foi removida.</p>
        </div>
      </div>
    );
  }

  const generateStructuredData = () => {
    const structuredData = {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": company.name,
      "url": window.location.href,
      ...(company.cnpj && { "vatID": company.cnpj }),
      ...(company.main_phone && { "telephone": company.main_phone }),
      ...(company.main_address && {
        "address": {
          "@type": "PostalAddress",
          "streetAddress": company.main_address
        }
      }),
      ...(company.business_hours && { "openingHours": company.business_hours }),
      ...(addresses.length > 0 && {
        "location": addresses.map(addr => ({
          "@type": "Place",
          "name": addr.name,
          "address": {
            "@type": "PostalAddress",
            "streetAddress": addr.address
          },
          ...(addr.phone && { "telephone": addr.phone }),
          ...(addr.business_hours && { "openingHours": addr.business_hours })
        }))
      })
    };

    return JSON.stringify(structuredData, null, 2);
  };

  return (
    <>
      {/* Structured Data */}
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: generateStructuredData() }}
      />

      <div className="min-h-screen bg-gray-50">
        {/* Header */}
        <header className="bg-white shadow-sm">
          <div className="max-w-6xl mx-auto px-6 py-8">
            <h1 className="text-4xl font-bold text-gray-900 mb-2">{company.name}</h1>
            {company.cnpj && (
              <p className="text-gray-600">CNPJ: {company.cnpj}</p>
            )}
          </div>
        </header>

        <div className="max-w-6xl mx-auto px-6 py-8">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {/* Main Content */}
            <div className="lg:col-span-2 space-y-8">
              {/* Company Info */}
              <section className="bg-white rounded-xl shadow-sm border p-6">
                <h2 className="text-2xl font-bold text-gray-900 mb-6">Informações da Empresa</h2>
                
                <div className="space-y-6">
                  {company.main_address && (
                    <address className="not-italic">
                      <div className="flex items-start gap-3">
                        <MapPin className="w-5 h-5 text-blue-600 mt-1" />
                        <div>
                          <h3 className="font-medium text-gray-900">Endereço Principal</h3>
                          <p className="text-gray-700 mt-1">{company.main_address}</p>
                        </div>
                      </div>
                    </address>
                  )}

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {company.main_phone && (
                      <div className="flex items-center gap-3">
                        <Phone className="w-5 h-5 text-blue-600" />
                        <div>
                          <h3 className="font-medium text-gray-900">Telefone</h3>
                          <p className="text-gray-700">{company.main_phone}</p>
                        </div>
                      </div>
                    )}

                    {company.main_whatsapp && (
                      <div className="flex items-center gap-3">
                        <MessageCircle className="w-5 h-5 text-green-600" />
                        <div>
                          <h3 className="font-medium text-gray-900">WhatsApp</h3>
                          <p className="text-gray-700">{company.main_whatsapp}</p>
                        </div>
                      </div>
                    )}

                    {company.business_hours && (
                      <div className="flex items-center gap-3 md:col-span-2">
                        <Clock className="w-5 h-5 text-blue-600" />
                        <div>
                          <h3 className="font-medium text-gray-900">Horário de Funcionamento</h3>
                          <p className="text-gray-700">{company.business_hours}</p>
                        </div>
                      </div>
                    )}
                  </div>
                </div>
              </section>

              {/* Addresses */}
              {addresses.length > 0 && (
                <section className="bg-white rounded-xl shadow-sm border p-6">
                  <h2 className="text-2xl font-bold text-gray-900 mb-6">Endereços e Filiais</h2>
                  
                  <div className="space-y-6">
                    {addresses.map((address) => (
                      <address key={address.id} className="not-italic border-l-4 border-blue-200 pl-6">
                        <h3 className="font-semibold text-gray-900 text-lg mb-3">{address.name}</h3>
                        
                        {address.address && (
                          <p className="text-gray-700 mb-3">{address.address}</p>
                        )}

                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                          {address.phone && (
                            <div className="flex items-center gap-2">
                              <Phone className="w-4 h-4 text-blue-600" />
                              <span className="text-gray-700">{address.phone}</span>
                            </div>
                          )}
                          
                          {address.whatsapp && (
                            <div className="flex items-center gap-2">
                              <MessageCircle className="w-4 h-4 text-green-600" />
                              <span className="text-gray-700">{address.whatsapp}</span>
                            </div>
                          )}
                          
                          {address.business_hours && (
                            <div className="flex items-center gap-2">
                              <Clock className="w-4 h-4 text-blue-600" />
                              <span className="text-gray-700">{address.business_hours}</span>
                            </div>
                          )}
                        </div>

                        {address.additional_info && (
                          <div className="bg-gray-50 rounded-lg p-4">
                            <p className="text-gray-700 whitespace-pre-wrap">{address.additional_info}</p>
                          </div>
                        )}
                      </address>
                    ))}
                  </div>
                </section>
              )}

              {/* General Info */}
              {company.general_info && (
                <section className="bg-white rounded-xl shadow-sm border p-6">
                  <h2 className="text-2xl font-bold text-gray-900 mb-6">Informações Gerais</h2>
                  <div className="prose max-w-none">
                    <div className="text-gray-700 whitespace-pre-wrap">{company.general_info}</div>
                  </div>
                </section>
              )}
            </div>

            {/* Sidebar */}
            <div className="space-y-8">
              {/* FAQ */}
              {faqs.length > 0 && (
                <section className="bg-white rounded-xl shadow-sm border p-6">
                  <h2 className="text-xl font-bold text-gray-900 mb-6">Perguntas Frequentes</h2>
                  
                  <div className="space-y-4">
                    {faqs.map((faq) => (
                      <details key={faq.id} className="group">
                        <summary className="cursor-pointer p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                          <span className="font-medium text-gray-900">{faq.question}</span>
                        </summary>
                        <div className="mt-3 p-3 text-gray-700 whitespace-pre-wrap">
                          {faq.answer}
                        </div>
                      </details>
                    ))}
                  </div>
                </section>
              )}
            </div>
          </div>
        </div>

        {/* Footer */}
        <footer className="bg-white border-t mt-16">
          <div className="max-w-6xl mx-auto px-6 py-8">
            <div className="text-center text-gray-600">
              <p>© {new Date().getFullYear()} {company.name}. Página gerada automaticamente para agente de IA.</p>
            </div>
          </div>
        </footer>
      </div>
    </>
  );
}