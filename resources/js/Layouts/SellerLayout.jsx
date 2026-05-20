import React, { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { 
    LayoutDashboard, 
    PackageSearch, 
    MessageSquareText, 
    ShoppingCart, 
    Wallet,
    Search,
    Settings,
    LogOut,
    Menu,
    X,
    Bell,
    ChevronDown,
    Activity
} from 'lucide-react';

export default function SellerLayout({ children }) {
    const { auth } = usePage().props;
    const { url } = usePage();
    const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

    const isActive = (path) => {
        const currentUrl = url || '';
        if (path === '/seller/dashboard') {
            return currentUrl === path || currentUrl === '/seller';
        }
        return currentUrl.startsWith(path);
    };

    const navigation = [
        { name: 'Beranda', href: '/seller/dashboard', icon: LayoutDashboard },
        { name: 'Produk Saya', href: '/seller/products', icon: PackageSearch },
        { name: 'Penawaran', href: '/seller/offers', icon: MessageSquareText },
        { name: 'Pesanan', href: '/seller/orders', icon: ShoppingCart },
        { name: 'Saldo', href: '/seller/balance', icon: Wallet },
    ];

    return (
        <div className="min-h-screen bg-[#F4F5F7] flex text-slate-900 font-sans">
            {/* Mobile Sidebar Overlay */}
            {isMobileMenuOpen && (
                <div 
                    className="fixed inset-0 bg-slate-900/20 z-40 lg:hidden backdrop-blur-sm"
                    onClick={() => setIsMobileMenuOpen(false)}
                />
            )}

            {/* Sidebar */}
            <aside className={`
                fixed inset-y-0 left-0 z-50 w-[260px] bg-white border-r border-slate-200/70 transform transition-transform duration-300 ease-in-out flex flex-col
                ${isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'}
            `}>
                {/* Logo Area */}
                <div className="pt-6 px-6 pb-4">
                    <Link href="/" className="flex items-center gap-2 group mb-6">
                        <div className="w-8 h-8 rounded-lg flex items-center justify-center text-white bg-[#4A5D23] transition-colors">
                            <PackageSearch className="w-4 h-4" />
                        </div>
                        <span className="text-xl font-bold tracking-tight text-slate-900">
                            Re<span className="text-[#4A5D23]">Circle</span>
                        </span>
                    </Link>

                    {/* Store Selector */}
                    <button className="w-full flex items-center justify-between px-3 py-2 border border-slate-200/70 rounded-xl mb-4 hover:bg-slate-50 transition-colors">
                        <div className="flex items-center gap-2">
                            <ShoppingCart className="w-4 h-4 text-[#4A5D23]" />
                            <span className="text-sm font-semibold text-slate-900">Toko Anda</span>
                        </div>
                        <ChevronDown className="w-4 h-4 text-slate-400" />
                    </button>

                    {/* Search Bar */}
                    <div className="relative group">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <input 
                            type="text" 
                            placeholder="Cari menu..." 
                            className="w-full pl-9 pr-10 py-2 bg-slate-100 border-transparent rounded-lg text-sm focus:bg-white focus:border-slate-300 focus:ring-0 transition-colors placeholder:text-slate-500 font-medium"
                        />
                        <div className="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-0.5 opacity-60">
                            <kbd className="px-1.5 py-0.5 text-[10px] font-semibold bg-white border border-slate-200 rounded text-slate-500 shadow-sm">⌘</kbd>
                            <kbd className="px-1.5 py-0.5 text-[10px] font-semibold bg-white border border-slate-200 rounded text-slate-500 shadow-sm">K</kbd>
                        </div>
                    </div>
                </div>

                <div className="flex-1 overflow-y-auto px-4 pb-6">
                    <div className="mb-2 px-2">
                        <p className="text-[11px] font-semibold text-slate-400 tracking-wider uppercase">Menu</p>
                    </div>
                    <nav className="space-y-0.5">
                        {navigation.map((item) => {
                            const active = isActive(item.href);
                            const Icon = item.icon;
                            return (
                                <Link
                                    key={item.name}
                                    href={item.href}
                                    className={`
                                        flex items-center gap-3 px-3 py-2 text-sm transition-all duration-200 rounded-lg
                                        ${active 
                                            ? 'font-semibold text-slate-900 bg-slate-50/80' 
                                            : 'font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-50'
                                        }
                                    `}
                                >
                                    <Icon className={`w-[18px] h-[18px] ${active ? 'text-[#4A5D23]' : 'text-slate-500'}`} strokeWidth={active ? 2.5 : 2} />
                                    {item.name}
                                </Link>
                            );
                        })}
                    </nav>
                </div>

                {/* Profile Card Bottom */}
                <div className="p-4 border-t border-slate-200/70">
                    <div className="bg-white border border-slate-200/70 rounded-xl p-3 shadow-[0_2px_4px_rgba(0,0,0,0.02)]">
                        <button className="flex items-center gap-3 w-full text-left mb-3 group">
                            <div className="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center overflow-hidden border border-slate-200">
                                {auth?.user?.avatar ? (
                                    <img src={auth.user.avatar} alt="Avatar" className="w-full h-full object-cover" />
                                ) : (
                                    <span className="text-sm font-bold text-slate-600">{auth?.user?.name?.charAt(0)}</span>
                                )}
                            </div>
                            <div className="flex-1 min-w-0">
                                <p className="text-sm font-semibold text-slate-900 truncate">{auth?.user?.name || 'Seller'}</p>
                                <p className="text-xs text-slate-500 truncate">{auth?.user?.email}</p>
                            </div>
                            <ChevronDown className="w-4 h-4 text-slate-400 group-hover:text-slate-600" />
                        </button>
                        
                        <div className="space-y-1">
                            <Link href="/profile" className="flex items-center gap-3 px-2 py-1.5 text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-50 rounded-lg transition-colors">
                                <Settings className="w-[18px] h-[18px] text-slate-400" />
                                Pengaturan
                            </Link>
                            <Link href="/logout" method="post" as="button" className="flex w-full items-center gap-3 px-2 py-1.5 text-sm font-medium text-slate-600 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors">
                                <LogOut className="w-[18px] h-[18px] text-slate-400" />
                                Keluar
                            </Link>
                        </div>
                    </div>
                </div>
            </aside>

            {/* Main Content Area */}
            <div className="flex-1 lg:pl-[260px] flex flex-col min-w-0">
                <header className="lg:hidden bg-white border-b border-slate-200/70 h-16 flex items-center px-4 justify-between">
                    <span className="text-lg font-bold">ReCircle</span>
                    <button onClick={() => setIsMobileMenuOpen(true)} className="p-2 text-slate-600">
                        <Menu className="w-5 h-5" />
                    </button>
                </header>
                <main className="flex-1 p-6 lg:p-10">
                    {children}
                </main>
            </div>
        </div>
    );
}
