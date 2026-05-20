import React, { useEffect } from "react";
import Checkbox from "@/Components/Checkbox";
import GuestLayout from "@/Layouts/GuestLayout";
import InputError from "@/Components/InputError";
import InputLabel from "@/Components/InputLabel";
import PrimaryButton from "@/Components/PrimaryButton";
import TextInput from "@/Components/TextInput";
import { Head, Link, useForm } from "@inertiajs/react";
import { ArrowRight } from "lucide-react";

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: "",
        password: "",
        remember: false,
    });

    useEffect(() => {
        return () => {
            reset("password");
        };
    }, []);

    const submit = (e) => {
        e.preventDefault();
        post(route("login"));
    };

    return (
        <GuestLayout>
            <Head title="Masuk" />

            <div className="mb-10">
                <h1 className="text-3xl font-black text-gray-900 mb-2">
                    Selamat Datang Kembali!
                </h1>
                <p className="text-gray-500">
                    Masuk untuk melanjutkan hunting barang impian.
                </p>
            </div>

            {status && (
                <div className="mb-4 font-medium text-sm text-green-600 bg-green-50 p-3 rounded-lg">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="space-y-6">
                <div>
                    <InputLabel
                        htmlFor="email"
                        value="Email Kampus / Pribadi"
                        className="text-gray-700 font-bold mb-2"
                    />
                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="w-full px-5 py-4 bg-gray-50 border-transparent focus:border-primary focus:bg-white focus:ring-0 rounded-2xl text-gray-900 placeholder-gray-400 transition-all font-medium"
                        autoComplete="username"
                        isFocused={true}
                        onChange={(e) => setData("email", e.target.value)}
                        placeholder="nama@mahasiswa.ipb.ac.id"
                    />
                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div>
                    <div className="flex justify-between items-center mb-2">
                        <InputLabel
                            htmlFor="password"
                            value="Kata Sandi"
                            className="text-gray-700 font-bold"
                        />
                        {canResetPassword && (
                            <Link
                                href={route("password.request")}
                                className="text-xs text-primary font-bold hover:underline"
                            >
                                Lupa sandi?
                            </Link>
                        )}
                    </div>
                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="w-full px-5 py-4 bg-gray-50 border-transparent focus:border-primary focus:bg-white focus:ring-0 rounded-2xl text-gray-900 transition-all font-medium"
                        autoComplete="current-password"
                        onChange={(e) => setData("password", e.target.value)}
                        placeholder="••••••••"
                    />
                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="block">
                    <label className="flex items-center cursor-pointer group">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) =>
                                setData("remember", e.target.checked)
                            }
                            className="w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary/20 transition-all"
                        />
                        <span className="ml-3 text-sm text-gray-500 group-hover:text-gray-700 transition-colors">
                            Ingat saya di perangkat ini
                        </span>
                    </label>
                </div>

                <PrimaryButton
                    className="w-full justify-center py-4 bg-primary hover:bg-primary-hover text-white font-bold rounded-full text-base shadow-lg shadow-primary/20 hover:shadow-xl hover:-translate-y-1 transition-all"
                    disabled={processing}
                >
                    Masuk Sekarang <ArrowRight className="w-5 h-5 ml-2" />
                </PrimaryButton>

                <div className="text-center mt-8">
                    <p className="text-sm text-gray-500">
                        Belum punya akun?{" "}
                        <Link
                            href={route("register")}
                            className="font-bold text-primary hover:text-primary-hover hover:underline transition-all"
                        >
                            Daftar di sini
                        </Link>
                    </p>
                </div>
            </form>
        </GuestLayout>
    );
}
