import React, { useState, useEffect } from 'react';
import { supabase } from './lib/supabase';
import { AuthForm } from './components/AuthForm';
import { Layout } from './components/Layout';
import { CompanyForm } from './components/CompanyForm';
import { AddressesManager } from './components/AddressesManager';
import { FAQManager } from './components/FAQManager';
import { GeneralInfoManager } from './components/GeneralInfoManager';
import { PublicPage } from './components/PublicPage';

function App() {
  const [user, setUser] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('company');
  const [company, setCompany] = useState<any>(null);

  // Check if we're on a public page
  const isPublicPage = window.location.pathname.startsWith('/company/');

  useEffect(() => {
    if (isPublicPage) {
      setLoading(false);
      return;
    }

    // Get initial session
    supabase.auth.getSession().then(({ data: { session } }) => {
      setUser(session?.user ?? null);
      setLoading(false);
    });

    // Listen for auth changes
    const { data: { subscription } } = supabase.auth.onAuthStateChange((event, session) => {
      setUser(session?.user ?? null);
      setLoading(false);
    });

    return () => subscription.unsubscribe();
  }, [isPublicPage]);

  useEffect(() => {
    if (user) {
      loadCompany();
    }
  }, [user]);

  const loadCompany = async () => {
    if (!user) return;
    
    const { data } = await supabase
      .from('companies')
      .select('*')
      .eq('user_id', user.id)
      .single();
    
    if (data) {
      setCompany(data);
    }
  };

  if (isPublicPage) {
    return <PublicPage />;
  }

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-100 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600">Carregando...</p>
        </div>
      </div>
    );
  }

  if (!user) {
    return <AuthForm onAuth={loadCompany} />;
  }

  const renderContent = () => {
    switch (activeTab) {
      case 'company':
        return <CompanyForm userId={user.id} />;
      case 'addresses':
        return <AddressesManager companyId={company?.id || null} />;
      case 'faq':
        return <FAQManager companyId={company?.id || null} />;
      case 'general':
        return <GeneralInfoManager companyId={company?.id || null} />;
      default:
        return <CompanyForm userId={user.id} />;
    }
  };

  return (
    <Layout activeTab={activeTab} onTabChange={setActiveTab} user={user}>
      {renderContent()}
    </Layout>
  );
}

export default App;