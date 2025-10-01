import React, { useState, useEffect } from 'react';
import { supabase } from '../lib/supabase';
import { Company } from '../types';
import { Save, ExternalLink } from 'lucide-react';

interface CompanyFormProps {
  userId: string;
}

export function CompanyForm({ userId }: CompanyFormProps) {
  const [company, setCompany] = useState<Partial<Company>>({
    name: '',
    cnpj: '',
    main_address: '',
    main_phone: '',
    main_whatsapp: '',
    business_hours: '',
    general_info: '',
    slug: ''
  });
  const [loading, setLoading] = useState(false);
  const [success, setSuccess] = useState(false);

  useEffect(() => {
    loadCompany();
  }, [userId]);

  const loadCompany = async () => {
    const { data } = await supabase
      .from('companies')
      .select('*')
      .eq('user_id', userId)
      .single();
    
    if (data) {
      setCompany(data);
    }
  };

  const generateSlug = (name: string) => {
    return name
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-z0-9]/g, '-')
      .replace(/-+/g, '-')
      .replace(/^-|-$/g, '');
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setSuccess(false);

    const slug = generateSlug(company.name || '');
    const companyData = {
      ...company,
      user_id: userId,
      slug,
      updated_at: new Date().toISOString()
    };

    try {
      if (company.id) {
        const { error } = await supabase
          .from('companies')
          .update(companyData)
          .eq('id', company.id);
        
        if (!error) {
          setSuccess(true);
          setTimeout(() => setSuccess(false), 3000);
        }
      } else {
        const { data, error } = await supabase
          .from('companies')
          .insert(companyData)
          .select()
          .single();
        
        if (!error && data) {
          setCompany(data);
          setSuccess(true);
          setTimeout(() => setSuccess(false), 3000);
        }
      }
    } catch (error) {
      console.error('Erro ao salvar empresa:', error);
    } finally {
      setLoading(false);
    }
  };

  const publicUrl = company.slug ? `${window.location.origin}/company/${company.slug}` : '';

  return (
    <div className="p-8">
      <div className="max-w-4xl mx-auto">
        <div className="bg-white rounded-xl shadow-sm border p-8">
          <div className="flex items-center justify-between mb-8">
            <div>
              <h1 className="text-2xl font-bold text-gray-900">Dados da Empresa</h1>
              <p className="text-gray-600 mt-1">Configure as informações principais da sua empresa</p>
            </div>
            {company.slug && (
              <a
                href={publicUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
              >
                <ExternalLink className="w-4 h-4 mr-2" />
                Ver Página Pública
              </a>
            )}
          </div>

          {success && (
            <div className="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
              <p className="text-green-600 font-medium">Dados salvos com sucesso!</p>
              {publicUrl && (
                <p className="text-green-600 text-sm mt-1">
                  URL pública: <a href={publicUrl} className="underline" target="_blank" rel="noopener noreferrer">{publicUrl}</a>
                </p>
              )}
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Nome da Empresa *
                </label>
                <input
                  type="text"
                  value={company.name || ''}
                  onChange={(e) => setCompany({ ...company, name: e.target.value })}
                  required
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  CNPJ
                </label>
                <input
                  type="text"
                  value={company.cnpj || ''}
                  onChange={(e) => setCompany({ ...company, cnpj: e.target.value })}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="00.000.000/0000-00"
                />
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Endereço Principal
              </label>
              <textarea
                value={company.main_address || ''}
                onChange={(e) => setCompany({ ...company, main_address: e.target.value })}
                rows={3}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Telefone Principal
                </label>
                <input
                  type="tel"
                  value={company.main_phone || ''}
                  onChange={(e) => setCompany({ ...company, main_phone: e.target.value })}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="(11) 9999-9999"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  WhatsApp Principal
                </label>
                <input
                  type="tel"
                  value={company.main_whatsapp || ''}
                  onChange={(e) => setCompany({ ...company, main_whatsapp: e.target.value })}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="(11) 99999-9999"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Horário de Funcionamento
                </label>
                <input
                  type="text"
                  value={company.business_hours || ''}
                  onChange={(e) => setCompany({ ...company, business_hours: e.target.value })}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Seg-Sex: 8h-18h"
                />
              </div>
            </div>

            <div className="pt-6">
              <button
                type="submit"
                disabled={loading}
                className="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                <Save className="w-4 h-4 mr-2" />
                {loading ? 'Salvando...' : 'Salvar Dados'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}