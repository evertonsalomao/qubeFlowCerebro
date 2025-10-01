import React, { useState, useEffect } from 'react';
import { supabase } from '../lib/supabase';
import { FAQ } from '../types';
import { Plus, CreditCard as Edit, Trash2, Save, X, ChevronUp, ChevronDown } from 'lucide-react';

interface FAQManagerProps {
  companyId: string | null;
}

export function FAQManager({ companyId }: FAQManagerProps) {
  const [faqs, setFaqs] = useState<FAQ[]>([]);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [formData, setFormData] = useState({
    question: '',
    answer: ''
  });
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (companyId) {
      loadFAQs();
    }
  }, [companyId]);

  const loadFAQs = async () => {
    if (!companyId) return;
    
    const { data } = await supabase
      .from('faqs')
      .select('*')
      .eq('company_id', companyId)
      .order('order_index');
    
    if (data) {
      setFaqs(data);
    }
  };

  const resetForm = () => {
    setFormData({ question: '', answer: '' });
    setEditingId(null);
  };

  const handleEdit = (faq: FAQ) => {
    setFormData({
      question: faq.question,
      answer: faq.answer
    });
    setEditingId(faq.id);
  };

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!companyId) return;
    
    setLoading(true);
    
    try {
      if (editingId && editingId !== 'new') {
        const { error } = await supabase
          .from('faqs')
          .update(formData)
          .eq('id', editingId);
        
        if (!error) {
          loadFAQs();
          resetForm();
        }
      } else {
        const { error } = await supabase
          .from('faqs')
          .insert({
            ...formData,
            company_id: companyId,
            order_index: faqs.length
          });
        
        if (!error) {
          loadFAQs();
          resetForm();
        }
      }
    } catch (error) {
      console.error('Erro ao salvar FAQ:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (id: string) => {
    if (!confirm('Tem certeza que deseja excluir esta pergunta?')) return;
    
    const { error } = await supabase
      .from('faqs')
      .delete()
      .eq('id', id);
    
    if (!error) {
      loadFAQs();
    }
  };

  const moveItem = async (id: string, direction: 'up' | 'down') => {
    const currentIndex = faqs.findIndex(f => f.id === id);
    const newIndex = direction === 'up' ? currentIndex - 1 : currentIndex + 1;
    
    if (newIndex < 0 || newIndex >= faqs.length) return;
    
    const updates = [
      { id: faqs[currentIndex].id, order_index: newIndex },
      { id: faqs[newIndex].id, order_index: currentIndex }
    ];
    
    for (const update of updates) {
      await supabase
        .from('faqs')
        .update({ order_index: update.order_index })
        .eq('id', update.id);
    }
    
    loadFAQs();
  };

  if (!companyId) {
    return (
      <div className="p-8">
        <div className="max-w-4xl mx-auto">
          <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
            <p className="text-yellow-800">
              Configure primeiro os dados da empresa para poder cadastrar perguntas e respostas.
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
              <h1 className="text-2xl font-bold text-gray-900">Perguntas & Respostas</h1>
              <p className="text-gray-600 mt-1">Gerencie as perguntas frequentes da sua empresa</p>
            </div>
            <button
              onClick={() => setEditingId('new')}
              className="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
              <Plus className="w-4 h-4 mr-2" />
              Nova Pergunta
            </button>
          </div>

          {/* Form */}
          {editingId && (
            <div className="mb-8 p-6 bg-gray-50 rounded-lg">
              <form onSubmit={handleSave} className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Pergunta *
                  </label>
                  <input
                    type="text"
                    value={formData.question}
                    onChange={(e) => setFormData({ ...formData, question: e.target.value })}
                    required
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Digite a pergunta..."
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Resposta *
                  </label>
                  <textarea
                    value={formData.answer}
                    onChange={(e) => setFormData({ ...formData, answer: e.target.value })}
                    required
                    rows={5}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Digite a resposta..."
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

          {/* FAQs List */}
          <div className="space-y-4">
            {faqs.map((faq, index) => (
              <div key={faq.id} className="border border-gray-200 rounded-lg p-6">
                <div className="flex items-start justify-between">
                  <div className="flex-1">
                    <h3 className="font-semibold text-gray-900 text-lg mb-3">{faq.question}</h3>
                    <div className="text-gray-700 whitespace-pre-wrap">{faq.answer}</div>
                  </div>
                  <div className="flex items-center gap-2 ml-4">
                    <div className="flex flex-col gap-1">
                      <button
                        onClick={() => moveItem(faq.id, 'up')}
                        disabled={index === 0}
                        className="p-1 text-gray-600 hover:bg-gray-100 rounded disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        <ChevronUp className="w-4 h-4" />
                      </button>
                      <button
                        onClick={() => moveItem(faq.id, 'down')}
                        disabled={index === faqs.length - 1}
                        className="p-1 text-gray-600 hover:bg-gray-100 rounded disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        <ChevronDown className="w-4 h-4" />
                      </button>
                    </div>
                    <button
                      onClick={() => handleEdit(faq)}
                      className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                    >
                      <Edit className="w-4 h-4" />
                    </button>
                    <button
                      onClick={() => handleDelete(faq.id)}
                      className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                    >
                      <Trash2 className="w-4 h-4" />
                    </button>
                  </div>
                </div>
              </div>
            ))}
            
            {faqs.length === 0 && (
              <div className="text-center py-12">
                <p className="text-gray-500">Nenhuma pergunta cadastrada ainda.</p>
                <button
                  onClick={() => setEditingId('new')}
                  className="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                >
                  <Plus className="w-4 h-4 mr-2" />
                  Cadastrar Primeira Pergunta
                </button>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}