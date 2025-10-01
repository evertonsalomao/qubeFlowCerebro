import React, { useState, useEffect } from 'react';
import { supabase } from '../lib/supabase';
import { CompanyAddress } from '../types';
import { Plus, CreditCard as Edit, Trash2, Save, X } from 'lucide-react';

interface AddressesManagerProps {
  companyId: string | null;
}

export function AddressesManager({ companyId }: AddressesManagerProps) {
  const [addresses, setAddresses] = useState<CompanyAddress[]>([]);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [formData, setFormData] = useState({
    name: '',
    address: '',
    phone: '',
    whatsapp: '',
    business_hours: '',
    additional_info: ''
  });
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (companyId) {
      loadAddresses();
    }
  }, [companyId]);

  const loadAddresses = async () => {
    if (!companyId) return;
    
    const { data } = await supabase
      .from('company_addresses')
      .select('*')
      .eq('company_id', companyId)
      .order('created_at');
    
    if (data) {
      setAddresses(data);
    }
  };

  const resetForm = () => {
    setFormData({
      name: '',
      address: '',
      phone: '',
      whatsapp: '',
      business_hours: '',
      additional_info: ''
    });
    setEditingId(null);
  };

  const handleEdit = (address: CompanyAddress) => {
    setFormData({
      name: address.name,
      address: address.address,
      phone: address.phone,
      whatsapp: address.whatsapp,
      business_hours: address.business_hours,
      additional_info: address.additional_info
    });
    setEditingId(address.id);
  };

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!companyId) return;
    
    setLoading(true);
    
    try {
      if (editingId) {
        const { error } = await supabase
          .from('company_addresses')
          .update(formData)
          .eq('id', editingId);
        
        if (!error) {
          loadAddresses();
          resetForm();
        }
      } else {
        const { error } = await supabase
          .from('company_addresses')
          .insert({
            ...formData,
            company_id: companyId
          });
        
        if (!error) {
          loadAddresses();
          resetForm();
        }
      }
    } catch (error) {
      console.error('Erro ao salvar endereço:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (id: string) => {
    if (!confirm('Tem certeza que deseja excluir este endereço?')) return;
    
    const { error } = await supabase
      .from('company_addresses')
      .delete()
      .eq('id', id);
    
    if (!error) {
      loadAddresses();
    }
  };

  if (!companyId) {
    return (
      <div className="p-8">
        <div className="max-w-4xl mx-auto">
          <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
            <p className="text-yellow-800">
              Configure primeiro os dados da empresa para poder cadastrar endereços.
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
          <div className="flex items-center justify-between mb-8">
            <div>
              <h1 className="text-2xl font-bold text-gray-900">Endereços e Filiais</h1>
              <p className="text-gray-600 mt-1">Gerencie os endereços da sua empresa</p>
            </div>
            <button
              onClick={() => setEditingId('new')}
              className="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
              <Plus className="w-4 h-4 mr-2" />
              Novo Endereço
            </button>
          </div>

          {/* Form */}
          {editingId && (
            <div className="mb-8 p-6 bg-gray-50 rounded-lg">
              <form onSubmit={handleSave} className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Nome da Unidade *
                    </label>
                    <input
                      type="text"
                      value={formData.name}
                      onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                      required
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                  </div>
                  
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Horário de Funcionamento
                    </label>
                    <input
                      type="text"
                      value={formData.business_hours}
                      onChange={(e) => setFormData({ ...formData, business_hours: e.target.value })}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Endereço Completo
                  </label>
                  <textarea
                    value={formData.address}
                    onChange={(e) => setFormData({ ...formData, address: e.target.value })}
                    rows={3}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Telefone
                    </label>
                    <input
                      type="tel"
                      value={formData.phone}
                      onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                  </div>
                  
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      WhatsApp
                    </label>
                    <input
                      type="tel"
                      value={formData.whatsapp}
                      onChange={(e) => setFormData({ ...formData, whatsapp: e.target.value })}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Informações Adicionais
                  </label>
                  <textarea
                    value={formData.additional_info}
                    onChange={(e) => setFormData({ ...formData, additional_info: e.target.value })}
                    rows={3}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>

                <div className="flex items-center gap-3">
                  <button
                    type="submit"
                    disabled={loading}
                    className="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 transition-colors"
                  >
                    <Save className="w-4 h-4 mr-2" />
                    {loading ? 'Salvando...' : 'Salvar'}
                  </button>
                  <button
                    type="button"
                    onClick={resetForm}
                    className="inline-flex items-center px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors"
                  >
                    <X className="w-4 h-4 mr-2" />
                    Cancelar
                  </button>
                </div>
              </form>
            </div>
          )}

          {/* Addresses List */}
          <div className="space-y-4">
            {addresses.map((address) => (
              <div key={address.id} className="border border-gray-200 rounded-lg p-6">
                <div className="flex items-start justify-between">
                  <div className="flex-1">
                    <h3 className="font-semibold text-gray-900 text-lg">{address.name}</h3>
                    {address.address && (
                      <p className="text-gray-600 mt-2">{address.address}</p>
                    )}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
                      {address.phone && (
                        <div>
                          <span className="text-sm font-medium text-gray-500">Telefone:</span>
                          <p className="text-gray-900">{address.phone}</p>
                        </div>
                      )}
                      {address.whatsapp && (
                        <div>
                          <span className="text-sm font-medium text-gray-500">WhatsApp:</span>
                          <p className="text-gray-900">{address.whatsapp}</p>
                        </div>
                      )}
                      {address.business_hours && (
                        <div>
                          <span className="text-sm font-medium text-gray-500">Horário:</span>
                          <p className="text-gray-900">{address.business_hours}</p>
                        </div>
                      )}
                    </div>
                    {address.additional_info && (
                      <div className="mt-3">
                        <span className="text-sm font-medium text-gray-500">Informações:</span>
                        <p className="text-gray-900 mt-1">{address.additional_info}</p>
                      </div>
                    )}
                  </div>
                  <div className="flex items-center gap-2 ml-4">
                    <button
                      onClick={() => handleEdit(address)}
                      className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                    >
                      <Edit className="w-4 h-4" />
                    </button>
                    <button
                      onClick={() => handleDelete(address.id)}
                      className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                    >
                      <Trash2 className="w-4 h-4" />
                    </button>
                  </div>
                </div>
              </div>
            ))}
            
            {addresses.length === 0 && (
              <div className="text-center py-12">
                <p className="text-gray-500">Nenhum endereço cadastrado ainda.</p>
                <button
                  onClick={() => setEditingId('new')}
                  className="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                >
                  <Plus className="w-4 h-4 mr-2" />
                  Cadastrar Primeiro Endereço
                </button>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}