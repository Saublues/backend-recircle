import React, { useState, useEffect } from 'react';
import { Heart } from 'lucide-react';
import { router, usePage } from '@inertiajs/react';

export default function LikeButton({ productId, className = "" }) {
    const { auth } = usePage().props;
    const isGuest = !auth?.user;
    
    // Check global state from Inertia
    const [isLiked, setIsLiked] = useState(
        auth?.wishlist_ids?.includes(productId) || false
    );

    // Sync with global props when it changes
    useEffect(() => {
        if (!isGuest) {
            setIsLiked(auth?.wishlist_ids?.includes(productId) || false);
        }
    }, [auth?.wishlist_ids, productId, isGuest]);

    const handleToggle = (e) => {
        e.preventDefault(); // Prevent Link navigation if wrapped inside Link
        e.stopPropagation();

        if (isGuest) {
            router.get('/login');
            return;
        }

        // Optimistic UI update
        setIsLiked(!isLiked);

        // API call via Inertia, preserves scroll and state
        router.post(`/wishlist/${productId}/toggle`, {}, {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                // Revert on error
                setIsLiked(isLiked);
            }
        });
    };

    return (
        <button
            onClick={handleToggle}
            className={`p-3 bg-white/80 backdrop-blur rounded-full hover:bg-white text-gray-400 hover:text-red-500 transition-all shadow-sm group ${className}`}
            aria-label={isLiked ? "Hapus dari wishlist" : "Tambah ke wishlist"}
        >
            <Heart 
                className={`w-5 h-5 transition-transform group-hover:scale-110 group-active:scale-95 ${isLiked ? 'fill-red-500 text-red-500' : ''}`} 
            />
        </button>
    );
}
