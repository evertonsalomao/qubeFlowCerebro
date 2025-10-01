import React, { useState, useEffect } from 'react';
import { supabase } from '../lib/supabase';
import { Save } from 'lucide-react';

interface GeneralInfoManagerProps {
  companyId: string | null;
}

export function GeneralInfoManager({ companyId }: GeneralInfoManagerProps) {
  const [generalInfo, setGeneralInfo] = useState('');
  const [loading, setLoading] = useState(false);
  const [success, setSuccess] = useState(false);

  useEffect(() => {
    if (companyId) {
      loadGeneralInfo();
    }
  }, [companyId]);

  const loadGeneralInfo = async () => {
    if (!companyId) return;
    
    const { data } = await supabase
      .from('companies')
      .select('general_info')
      .eq('id', companyId)
      .single();
    
    if (data) {
      setGeneralInfo(data.general_info || '');
    }
  };

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!companyId) return;
    
    setLoading(true);
    setSuccess(false);
    
    try {
      const { error } = await supabase
        .from('companies')
        .update({ 
          general_info: generalInfo,
          updated_at: new Date().toISOString()
        })
        .eq('id', companyId);
      
      if (!error) {
        setSuccess(true);
        setTimeout(() => setSuccess(false), 3000);
      }
    } catch (error) {
      console.error('Erro ao salvar informações:', error);
    } finally {
      setLoading(false);
    }
  };

  if (!companyId) {
    return (
      <div className="p-8">
        <div className="max-w-4xl mx-auto">
          <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
            <p className="text-yellow-800">
              Configure primeiro os dados da empresa para poder gerenciar informações gerais.
            </p>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="p-8">
      <div className="max-w-4xl mx-auto">
        <div className="bg-white rounded-xl shadow-sm border p-8">
          <div className="mb-8">
            <h1 className="text-2xl font-bold text-gray-900">Informações Gerais</h1>
            <p className="text-gray-600 mt-1">
              Campo livre para adicionar qualquer informação adicional sobre sua empresa
            </p>
          </div>

          {success && (
            <div className="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
              <p className="text-green-600 font-medium">Informações salvas com sucesso!</p>
            </div>
          )}

          <form onSubmit={handleSave}>
            <div className="mb-6">
              <label className="block text-sm font-medium text-gray-700 mb-3">
                Informações Adicionais
              </label>
              <textarea
                value={generalInfo}
                onChange={(e) => setGeneralInfo(e.target.value)}
                rows={15}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm"
                placeholder="Digite aqui qualquer informação adicional sobre sua empresa...

Exemplos:
• História da empresa
• Missão, visão e valores
• Certificações
• Prêmios
• Produtos ou serviços especiais
• Políticas da empresa
• Informações técnicas
• Etc..."
              />
              <p className="text-sm text-gray-500 mt-2">
                Você pode usar texto formatado, listas, ou qualquer informação relevante para o agente de IA.
              </p>
            </div>

            <button
              type="submit"
              disabled={loading}
              className="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <Save className="w-4 h-4 mr-2" />
              {loading ? 'Salvando...' : 'Salvar Informações'}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}