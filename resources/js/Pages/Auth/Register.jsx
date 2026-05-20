import React, { useEffect } from "react";
import GuestLayout from "@/Layouts/GuestLayout";
import InputError from "@/Components/InputError";
import InputLabel from "@/Components/InputLabel";
import PrimaryButton from "@/Components/PrimaryButton";
import TextInput from "@/Components/TextInput";
import { Head, Link, useForm } from "@inertiajs/react";
import { ArrowRight } from "lucide-react";

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: "",
        email: "",
        whatsapp_number: "", // Field tambahan sesuai database
        password: "",
        password_confirmation: "",
    });

    useEffect(() => {
        return () => {
            reset("password", "password_confirmation");
        };
    }, []);

    const submit = (e) => {
        e.preventDefault();
        post(route("register"));
    };

    return (
        <GuestLayout>
            <Head title="Daftar Akun" />

            <div className="mb-10">
                <h1 className="text-3xl font-black text-gray-900 mb-2">
                    Buat Akun Baru
                </h1>
                <p className="text-gray-500">
                    Satu akun untuk jual beli sepuasnya.
                </p>
            </div>

            <form onSubmit={submit} className="space-y-5">
                {/* Nama Lengkap */}
                <div>
                    <InputLabel
                        htmlFor="name"
                        value="Nama Lengkap"
                        className="text-gray-700 font-bold mb-2"
                    />
                    <TextInput
                        id="name"
                        name="name"
                        value={data.name}
                        className="w-full px-5 py-3.5 bg-gray-50 border-transparent focus:border-primary focus:bg-white focus:ring-0 rounded-2xl text-gray-900 font-medium"
                        autoComplete="name"
                        isFocused={true}
                        onChange={(e) => setData("name", e.target.value)}
                        placeholder="Contoh: Budi Santoso"
                    />
                    <InputError message={errors.name} className="mt-2" />
                </div>

                {/* Email */}
                <div>
                    <InputLabel
                        htmlFor="email"
                        value="Email"
                        className="text-gray-700 font-bold mb-2"
                    />
                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="w-full px-5 py-3.5 bg-gray-50 border-transparent focus:border-primary focus:bg-white focus:ring-0 rounded-2xl text-gray-900 font-medium"
                        autoComplete="username"
                        onChange={(e) => setData("email", e.target.value)}
                        placeholder="nama@email.com"
                    />
                    <InputError message={errors.email} className="mt-2" />
                </div>

                {/* WhatsApp (Tambahan Penting) */}
                <div>
                    <InputLabel
                        htmlFor="whatsapp_number"
                        value="Nomor WhatsApp"
                        className="text-gray-700 font-bold mb-2"
                    />
                    <TextInput
                        id="whatsapp_number"
                        type="text"
                        name="whatsapp_number"
                        value={data.whatsapp_number}
                        className="w-full px-5 py-3.5 bg-gray-50 border-transparent focus:border-primary focus:bg-white focus:ring-0 rounded-2xl text-gray-900 font-medium"
                        onChange={(e) => {
                            // Auto replace 0 di depan dengan 62 jika perlu, atau biarkan user input angka saja
                            const val = e.target.value.replace(/[^0-9]/g, "");
                            setData("whatsapp_number", val);
                        }}
                        placeholder="0812xxxxxxxx"
                    />
                    <p className="text-xs text-gray-400 mt-1 ml-1">
                        Digunakan pembeli untuk menghubungi Anda.
                    </p>
                    <InputError
                        message={errors.whatsapp_number}
                        className="mt-2"
                    />
                </div>

                {/* Password Group */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <InputLabel
                            htmlFor="password"
                            value="Kata Sandi"
                            className="text-gray-700 font-bold mb-2"
                        />
                        <TextInput
                            id="password"
                            type="password"
                            name="password"
                            value={data.password}
                            className="w-full px-5 py-3.5 bg-gray-50 border-transparent focus:border-primary focus:bg-white focus:ring-0 rounded-2xl text-gray-900 font-medium"
                            autoComplete="new-password"
                            onChange={(e) =>
                                setData("password", e.target.value)
                            }
                            placeholder="Minimal 8 karakter"
                        />
                        <InputError
                            message={errors.password}
                            className="mt-2"
                        />
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="password_confirmation"
                            value="Ulangi Sandi"
                            className="text-gray-700 font-bold mb-2"
                        />
                        <TextInput
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            value={data.password_confirmation}
                            className="w-full px-5 py-3.5 bg-gray-50 border-transparent focus:border-primary focus:bg-white focus:ring-0 rounded-2xl text-gray-900 font-medium"
                            autoComplete="new-password"
                            onChange={(e) =>
                                setData("password_confirmation", e.target.value)
                            }
                            placeholder="Konfirmasi sandi"
                        />
                        <InputError
                            message={errors.password_confirmation}
                            className="mt-2"
                        />
                    </div>
                </div>

                <PrimaryButton
                    className="w-full justify-center py-4 bg-primary hover:bg-primary-hover text-white font-bold rounded-full text-base shadow-lg shadow-primary/20 hover:shadow-xl hover:-translate-y-1 transition-all mt-6"
                    disabled={processing}
                >
                    Buat Akun <ArrowRight className="w-5 h-5 ml-2" />
                </PrimaryButton>

                <div className="text-center mt-6">
                    <p className="text-sm text-gray-500">
                        Sudah punya akun?{" "}
                        <Link
                            href={route("login")}
                            className="font-bold text-primary hover:text-primary-hover hover:underline transition-all"
                        >
                            Masuk di sini
                        </Link>
                    </p>
                </div>
            </form>
        </GuestLayout>
    );
}
