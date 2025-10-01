import React from 'react';
import { LogOut, Building2, MapPin, MessageCircle, Info } from 'lucide-react';
import { signOut } from '../lib/supabase';

interface LayoutProps {
  children: React.ReactNode;
  activeTab: string;
  onTabChange: (tab: string) => void;
  user: any;
}

export function Layout({ children, activeTab, onTabChange, user }: LayoutProps) {
  const handleSignOut = async () => {
    await signOut();
    window.location.reload();
  };

  return (
    <div className="min-h-screen bg-gray-50 flex">
      {/* Sidebar */}
      <div className="w-64 bg-white shadow-lg">
        <div className="p-6">
          <h1 className="text-xl font-bold text-gray-800">Sistema IA</h1>
          <p className="text-sm text-gray-600 mt-1">{user?.email}</p>
        </div>
        
        <nav className="mt-6">
          <button
            onClick={() => onTabChange('company')}
            className={`w-full flex items-center px-6 py-3 text-left hover:bg-gray-50 transition-colors ${
              activeTab === 'company' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-700' : 'text-gray-700'
            }`}
          >
            <Building2 className="w-5 h-5 mr-3" />
            Empresa
          </button>
          
          <button
            onClick={() => onTabChange('addresses')}
            className={`w-full flex items-center px-6 py-3 text-left hover:bg-gray-50 transition-colors ${
              activeTab === 'addresses' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-700' : 'text-gray-700'
            }`}
          >
            <MapPin className="w-5 h-5 mr-3" />
            Endereços
          </button>
          
          <button
            onClick={() => onTabChange('faq')}
            className={`w-full flex items-center px-6 py-3 text-left hover:bg-gray-50 transition-colors ${
              activeTab === 'faq' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-700' : 'text-gray-700'
            }`}
          >
            <MessageCircle className="w-5 h-5 mr-3" />
            Perguntas & Respostas
          </button>
          
          <button
            onClick={() => onTabChange('general')}
            className={`w-full flex items-center px-6 py-3 text-left hover:bg-gray-50 transition-colors ${
              activeTab === 'general' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-700' : 'text-gray-700'
            }`}
          >
            <Info className="w-5 h-5 mr-3" />
            Informações Gerais
          </button>
        </nav>
        
        <div className="absolute bottom-0 w-64 p-6">
          <button
            onClick={handleSignOut}
            className="w-full flex items-center px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
          >
            <LogOut className="w-4 h-4 mr-2" />
            Sair
          </button>
        </div>
      </div>
      
      {/* Main Content */}
      <div className="flex-1 overflow-auto">
        {children}
      </div>
    </div>
  );
}