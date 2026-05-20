import React, { useState } from "react";
import { Link, usePage } from "@inertiajs/react";
import { Search, Heart, ShoppingBag, ShoppingCart, Menu, X, Store, ClipboardList, Package, ShieldCheck, LogOut, MessageCircle } from "lucide-react";

export default function Navbar() {
    const { auth } = usePage().props;
    const [isMenuOpen, setIsMenuOpen] = useState(false);
    const [isDropdownOpen, setIsDropdownOpen] = useState(false);

    return (
        <nav className="fixed top-0 w-full z-50 bg-white/90 backdrop-blur-md border-b border-gray-100">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="flex justify-between items-center h-20">
                    {/* Logo */}
                    <Link href="/" className="flex items-center gap-2 group">
                        <div className="w-10 h-10 bg-primary rounded-xl flex items-center justify-center text-white shadow-soft group-hover:bg-primary-hover transition-colors">
                            <ShoppingBag className="w-5 h-5" />
                        </div>
                        <div className="flex flex-col leading-tight">
                            <span className="text-xl font-bold text-primary tracking-tight">
                                Re
                                <span className="text-primary-light">
                                    Circle
                                </span>
                            </span>
                        </div>
                    </Link>

                    {/* Desktop Search */}
                    <div className="hidden md:flex flex-1 mx-12 max-w-xl relative group">
                        <input
                            type="text"
                            placeholder="Cari barang..."
                            className="w-full pl-12 pr-4 py-3 rounded-2xl bg-secondary border-transparent focus:bg-white focus:border-primary/30 focus:ring-4 focus:ring-primary/10 text-sm transition-all"
                        />
                        <Search className="absolute left-4 top-3 text-gray-400 w-5 h-5" />
                    </div>

                    {/* Menu */}
                    <div className="hidden md:flex items-center space-x-4">
                        {auth.user ? (
                            <>
                                <Link
                                    href="/cart"
                                    className="p-2 text-gray-500 hover:text-primary transition-colors relative"
                                    title="Keranjang"
                                >
                                    <ShoppingCart className="w-5 h-5" />
                                </Link>
                                <Link
                                    href="/chat"
                                    className="p-2 text-gray-500 hover:text-primary transition-colors"
                                    title="Pesan"
                                >
                                    <MessageCircle className="w-5 h-5" />
                                </Link>
                                <Link
                                    href="/my-offers"
                                    className="p-2 text-gray-500 hover:text-primary transition-colors"
                                    title="Penawaran Saya"
                                >
                                    <ClipboardList className="w-5 h-5" />
                                </Link>
                                <Link
                                    href="/orders"
                                    className="p-2 text-gray-500 hover:text-primary transition-colors"
                                    title="Pesanan Saya"
                                >
                                    <Package className="w-5 h-5" />
                                </Link>
                                <Link
                                    href="/wishlist"
                                    className="p-2 text-gray-500 hover:text-red-500 transition-colors"
                                    title="Wishlist Saya"
                                >
                                    <Heart className="w-5 h-5" />
                                </Link>

                                {/* User Dropdown */}
                                <div className="relative pl-3 border-l border-gray-200">
                                    <button
                                        onClick={() => setIsDropdownOpen(!isDropdownOpen)}
                                        className="flex items-center gap-3 focus:outline-none"
                                    >
                                        <div className="text-right hidden sm:block">
                                            <p className="text-sm font-bold text-gray-700">
                                                {auth.user.name}
                                            </p>
                                            <p className="text-xs text-gray-400">
                                                {auth.user.is_seller ? 'Penjual' : auth.user.role === 'admin' ? 'Admin' : 'Pembeli'}
                                            </p>
                                        </div>
                                        <div className="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-lg">
                                            {auth.user.name.charAt(0)}
                                        </div>
                                    </button>

                                    {isDropdownOpen && (
                                        <div className="absolute right-0 top-14 bg-white rounded-2xl shadow-xl border border-gray-100 py-2 w-56 z-50">
                                            {auth.user.is_seller && (
                                                <Link href="/seller/dashboard" className="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-secondary transition-colors">
                                                    <Store className="w-4 h-4 text-primary" /> Dashboard Penjual
                                                </Link>
                                            )}
                                            {!auth.user.is_seller && auth.user.role !== 'admin' && (
                                                <Link href="/seller/verification" className="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-secondary transition-colors">
                                                    <ShieldCheck className="w-4 h-4 text-primary" /> Jadi Penjual
                                                </Link>
                                            )}
                                            {auth.user.role === 'admin' && (
                                                <Link href="/admin/verifications" className="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-secondary transition-colors">
                                                    <ShieldCheck className="w-4 h-4 text-primary" /> Admin Panel
                                                </Link>
                                            )}
                                            <Link href="/profile" className="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-secondary transition-colors">
                                                <Heart className="w-4 h-4 text-gray-400" /> Profil Saya
                                            </Link>
                                            <div className="border-t border-gray-100 my-1"></div>
                                            <Link href="/logout" method="post" as="button" className="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 transition-colors">
                                                <LogOut className="w-4 h-4" /> Keluar
                                            </Link>
                                        </div>
                                    )}
                                </div>
                            </>
                        ) : (
                            <div className="flex items-center gap-4">
                                <Link
                                    href={route("login")}
                                    className="text-gray-600 font-medium hover:text-primary"
                                >
                                    Masuk
                                </Link>
                                <Link
                                    href={route("register")}
                                    className="bg-primary hover:bg-primary-hover text-white px-6 py-2.5 rounded-xl font-medium shadow-soft transition-all hover:-translate-y-0.5"
                                >
                                    Daftar
                                </Link>
                            </div>
                        )}
                    </div>

                    {/* Mobile Toggle */}
                    <button
                        onClick={() => setIsMenuOpen(!isMenuOpen)}
                        className="md:hidden text-gray-600 p-2"
                    >
                        {isMenuOpen ? (
                            <X className="w-6 h-6" />
                        ) : (
                            <Menu className="w-6 h-6" />
                        )}
                    </button>
                </div>
            </div>

            {/* Mobile Menu */}
            {isMenuOpen && (
                <div className="md:hidden px-4 pb-4 bg-white border-b border-gray-100">
                    <div className="relative mb-4 mt-2">
                        <input
                            type="text"
                            placeholder="Cari barang..."
                            className="w-full pl-10 pr-4 py-3 rounded-xl bg-secondary border-none text-sm"
                        />
                        <Search className="absolute left-3 top-3 text-gray-400 w-5 h-5" />
                    </div>
                    {auth.user && (
                        <div className="space-y-1">
                            <Link href="/cart" className="block px-4 py-2.5 text-sm text-gray-700 hover:bg-secondary rounded-xl">Keranjang</Link>
                            <Link href="/chat" className="block px-4 py-2.5 text-sm text-gray-700 hover:bg-secondary rounded-xl">Pesan</Link>
                            <Link href="/my-offers" className="block px-4 py-2.5 text-sm text-gray-700 hover:bg-secondary rounded-xl">Penawaran Saya</Link>
                            <Link href="/orders" className="block px-4 py-2.5 text-sm text-gray-700 hover:bg-secondary rounded-xl">Pesanan Saya</Link>
                            <Link href="/wishlist" className="block px-4 py-2.5 text-sm text-gray-700 hover:bg-red-50 hover:text-red-600 rounded-xl">Wishlist Saya</Link>
                            {auth.user.is_seller && (
                                <Link href="/seller/dashboard" className="block px-4 py-2.5 text-sm text-gray-700 hover:bg-secondary rounded-xl">Dashboard Penjual</Link>
                            )}
                            {!auth.user.is_seller && auth.user.role !== 'admin' && (
                                <Link href="/seller/verification" className="block px-4 py-2.5 text-sm text-primary font-medium hover:bg-secondary rounded-xl">Jadi Penjual</Link>
                            )}
                            <Link href="/logout" method="post" as="button" className="block w-full text-left px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 rounded-xl">Keluar</Link>
                        </div>
                    )}
                </div>
            )}
        </nav>
    );
}
