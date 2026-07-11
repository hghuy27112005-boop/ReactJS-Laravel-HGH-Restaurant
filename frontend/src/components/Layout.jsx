import React from 'react';
import Navbar from './Navbar';
import Footer from './Footer';
import AccountDeletedModal from './AccountDeletedModal';

const Layout = ({ children }) => {
    return (
        <div className="flex flex-col min-h-screen">
            <Navbar />
            <main className="flex-1">
                {children}
            </main>
            <Footer />
            <AccountDeletedModal />
        </div>
    );
};

export default Layout;