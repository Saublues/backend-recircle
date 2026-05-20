import React, { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import {
    LayoutDashboard,
    Package,
    MessageSquare,
    Truck,
    Wallet,
    Bell,
    Search,
    ChevronDown,
    LogOut,
    Menu,
    X,
    User,
    PackageSearch,
    Users,
    ShoppingBag,
    Clock,
    FileText,
    BadgeCheck
} from 'lucide-react';

export default function DashboardLayout({ children }) {
    const { auth } = usePage().props;
    const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';
    const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
    const [isProfileDropdownOpen, setIsProfileDropdownOpen] = useState(false);
    const [notificationsOpen, setNotificationsOpen] = useState(false);

    const adminNav = [
        { name: "Dashboard", icon: LayoutDashboard, href: "/admin/dashboard" },
        { name: "Pengguna", icon: Users, href: "/admin/users" },
        { name: "Produk", icon: Package, href: "/admin/products" },
        { name: "Penawaran", icon: MessageSquare, href: "/admin/offers" },
        { name: "Transaksi", icon: Wallet, href: "/admin/transactions" },
        { name: "Verifikasi", icon: BadgeCheck, href: "/admin/verifications" },
        { name: "Aktivitas", icon: FileText, href: "/admin/activities" },
    ];

    const sellerNav = [
        { name: "Dashboard", icon: LayoutDashboard, href: "/seller/dashboard" },
        { name: "Produk Saya", icon: Package, href: "/seller/products" },
        { name: "Penawaran", icon: MessageSquare, href: "/seller/offers" },
        { name: "Pesanan", icon: Truck, href: "/seller/orders" },
        { name: "Keuangan", icon: Wallet, href: "/seller/balance" },
        { name: "Aktivitas", icon: FileText, href: "/seller/activities" },
    ];

    const { adminActivities, sellerActivities } = usePage().props;

    const isAdmin = auth?.user?.role === 'admin';
    const isSeller = auth?.user?.is_seller;
    const navItems = isAdmin ? adminNav : sellerNav;
    const activities = isAdmin ? adminActivities : (isSeller ? sellerActivities : []);

    const isActive = (href) => currentPath.startsWith(href);

    return (
        <div className="min-h-screen bg-[#F8F9FA] flex">
            {/* Mobile Sidebar Overlay */}
            {isMobileMenuOpen && (
                <div
                    className="fixed inset-0 bg-slate-900/50 z-40 lg:hidden backdrop-blur-sm"
                    onClick={() => setIsMobileMenuOpen(false)}
                />
            )}

            {/* Sidebar */}
            <aside className={`
                fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-slate-200/60 transform transition-transform duration-300 ease-in-out flex flex-col
                ${isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'}
            `}>
                <div className="h-20 flex items-center px-8 border-b border-slate-200/60">
                    <Link href="/" className="flex items-center gap-2 group">
                        <div className="w-8 h-8 bg-[#4A5D23] rounded-lg flex items-center justify-center text-white shadow-soft group-hover:bg-[#3d4d1d] transition-colors">
                            <PackageSearch className="w-4 h-4" />
                        </div>
                        <span className="text-xl font-bold tracking-tight text-slate-900">
                            Re<span className="text-[#4A5D23]">Circle</span>
                        </span>
                    </Link>
                    {/* Mobile Close Button */}
                    <button
                        className="ml-auto lg:hidden text-slate-500 hover:text-slate-900"
                        onClick={() => setIsMobileMenuOpen(false)}
                    >
                        <X className="w-5 h-5" />
                    </button>
                </div>

                <div className="flex-1 py-6 overflow-y-auto">
                    <div className="px-4 mb-4">
                        <p className="text-xs font-semibold text-slate-400 tracking-wider uppercase">Menu Utama</p>
                    </div>
                    <nav className="space-y-1">
                        {navItems.map((item) => {
                            const active = isActive(item.href);
                            const Icon = item.icon;
                            return (
                                <Link
                                    key={item.name}
                                    href={item.href}
                                    className={`
                                        flex items-center gap-3 px-8 py-3 text-sm font-medium transition-all duration-200
                                        ${active
                                            ? 'bg-[#4A5D23]/10 text-[#4A5D23] border-l-4 border-[#4A5D23]'
                                            : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50 border-l-4 border-transparent'
                                        }
                                    `}
                                >
                                    <Icon className={`w-5 h-5 ${active ? 'text-[#4A5D23]' : 'text-slate-400'}`} />
                                    {item.name}
                                </Link>
                            );
                        })}
                    </nav>
                </div>

                <div className="p-4 border-t border-slate-200/60">
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        className="flex items-center gap-3 px-4 py-3 text-sm font-medium text-red-600 hover:bg-red-50 rounded-xl transition-colors w-full"
                    >
                        <LogOut className="w-5 h-5" />
                        Keluar
                    </Link>
                </div>
            </aside>

            {/* Main Content Area */}
            <div className="flex-1 lg:pl-64 flex flex-col min-w-0">
                {/* Header */}
                <header className="sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-slate-200/60 h-20 flex items-center justify-between px-4 sm:px-6 lg:px-8">
                    <div className="flex items-center gap-4">
                        <button
                            className="lg:hidden p-2 text-slate-500 hover:bg-slate-50 rounded-xl"
                            onClick={() => setIsMobileMenuOpen(true)}
                        >
                            <Menu className="w-5 h-5" />
                        </button>

                        <div className="hidden sm:flex relative group">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-[#4A5D23] transition-colors" />
                            <input
                                type="text"
                                placeholder="Cari pesanan, produk..."
                                className="pl-10 pr-4 py-2 bg-[#F8F9FA] border-transparent rounded-xl text-sm w-64 focus:bg-white focus:border-[#4A5D23]/30 focus:ring-4 focus:ring-[#4A5D23]/10 transition-all placeholder:text-slate-400"
                            />
                        </div>
                    </div>

                    <div className="flex items-center gap-3 sm:gap-6">
                        <button className="relative p-2 text-slate-400 hover:text-slate-600 transition-colors rounded-full hover:bg-slate-50">
                            <Bell className="w-5 h-5" />
                            <span className="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
                        </button>

                        <div className="w-px h-8 bg-slate-200/60 hidden sm:block"></div>

                        <div className="relative">
                            <button
                                onClick={() => setNotificationsOpen(!notificationsOpen)}
                                className={`relative p-2.5 rounded-xl transition-colors ${notificationsOpen ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/10'
                                    }`}
                            >
                                <Bell className="w-[18px] h-[18px]" />
                                {(activities && activities.length > 0) && (
                                    <span className="absolute top-2 right-2 w-2 h-2 bg-[#d4a373] rounded-full ring-2 ring-[#43552c]" />
                                )}
                            </button>

                            {notificationsOpen && (
                                <>
                                    <div className="fixed inset-0 z-40" onClick={() => setNotificationsOpen(false)} />
                                    <div className="absolute right-0 top-12 bg-white rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-gray-100/60 w-80 sm:w-96 z-50 overflow-hidden transform origin-top-right transition-all">
                                        <div className="px-4 py-4 border-b border-gray-100/60 flex items-center justify-between bg-gray-50/50">
                                            <h3 className="text-sm font-bold text-gray-900">Notifikasi Aktivitas</h3>
                                            <span className="text-[10px] bg-[#43552c]/10 text-[#43552c] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">Terbaru</span>
                                        </div>

                                        <div className="max-h-[400px] overflow-y-auto">
                                            {activities && activities.length > 0 ? (
                                                <div className="divide-y divide-gray-50">
                                                    {activities.map((activity, idx) => (
                                                        <div key={idx} className="p-4 flex gap-3 hover:bg-gray-50 transition-colors group">
                                                            <div className={`w-9 h-9 rounded-xl flex-shrink-0 flex items-center justify-center shadow-sm
                                                                ${activity.type === 'user' ? 'bg-blue-50 text-blue-500' :
                                                                    activity.type === 'product' || activity.type === 'order' ? 'bg-[#43552c]/10 text-[#43552c]' :
                                                                        activity.type === 'offer' ? 'bg-blue-50 text-blue-500' :
                                                                            'bg-[#d4a373]/10 text-[#d4a373]'}`}
                                                            >
                                                                {activity.type === 'user' ? <Users className="w-4 h-4" /> :
                                                                    activity.type === 'product' ? <Package className="w-4 h-4" /> :
                                                                        activity.type === 'offer' ? <MessageSquare className="w-4 h-4" /> :
                                                                            <ShoppingBag className="w-4 h-4" />}
                                                            </div>
                                                            <div className="flex-1 min-w-0">
                                                                <p className="text-sm font-bold text-gray-900 line-clamp-1 group-hover:text-[#43552c] transition-colors">
                                                                    {activity.title}
                                                                </p>
                                                                <p className="text-xs text-gray-500 mt-0.5 line-clamp-2 leading-relaxed">
                                                                    {activity.description}
                                                                </p>
                                                                <p className="text-[10px] text-gray-400 mt-1 flex items-center gap-1">
                                                                    <Clock className="w-3 h-3" /> {activity.time}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            ) : (
                                                <div className="p-8 text-center">
                                                    <div className="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3">
                                                        <Bell className="w-6 h-6 text-gray-300" />
                                                    </div>
                                                    <p className="text-sm text-gray-400">Tidak ada aktivitas baru</p>
                                                </div>
                                            )}
                                        </div>

                                        <Link
                                            href={isAdmin ? "/admin/activities" : "/seller/activities"}
                                            onClick={() => setNotificationsOpen(false)}
                                            className="block py-3 text-center text-xs font-bold text-[#43552c] bg-gray-50 hover:bg-gray-100 transition-colors border-t border-gray-100/60"
                                        >
                                            Lihat Semua Aktivitas
                                        </Link>
                                    </div>
                                </>
                            )}
                        </div>

                        {/* Profile Dropdown */}
                        <div className="relative ml-1">
                            <button
                                onClick={() => setIsProfileDropdownOpen(!isProfileDropdownOpen)}
                                className="flex items-center gap-2.5 p-1.5 pr-3 rounded-xl hover:bg-white/10 transition-colors"
                            >
                                <div className="w-8 h-8 rounded-lg bg-white/15 text-white flex items-center justify-center font-bold text-sm">
                                    {auth?.user?.name?.charAt(0) || 'U'}
                                </div>
                                <ChevronDown className="w-4 h-4 text-slate-400 hidden sm:block" />
                            </button>

                            {isProfileDropdownOpen && (
                                <>
                                    <div
                                        className="fixed inset-0 z-40"
                                        onClick={() => setIsProfileDropdownOpen(false)}
                                    />
                                    <div className="absolute right-0 mt-2 w-48 bg-white rounded-2xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] border border-slate-100 py-2 z-50">
                                        <Link href="/profile" className="flex items-center px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
                                            Pengaturan Profil
                                        </Link>
                                        <Link href="/" className="flex items-center px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
                                            Kembali ke Beranda
                                        </Link>
                                    </div>
                                </>
                            )}
                        </div>
                    </div>
                </header>

                {/* Page Content */}
                <main className="flex-1 p-4 sm:p-6 lg:p-8">
                    {children}
                </main>
            </div>
        </div>
    );
}
