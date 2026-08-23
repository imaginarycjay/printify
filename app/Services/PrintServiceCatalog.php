<?php

namespace App\Services;

class PrintServiceCatalog
{
    /**
     * Get all available MVP services in the catalog.
     *
     * @return array<string, array{key: string, name: string, description: string, icon: string, gradient: string, badge_color: string, is_preinstalled?: bool}>
     */
    public static function all(): array
    {
        return [
            'inventory_hub' => [
                'key' => 'inventory_hub',
                'name' => 'Inventory Hub',
                'description' => 'Central raw materials, ready-to-buy products, real-time burn rates, and restock orders',
                'icon' => 'archive-box',
                'gradient' => 'from-emerald-500 via-teal-600 to-cyan-700',
                'badge_color' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
                'is_preinstalled' => true,
            ],
            'web_builder' => [
                'key' => 'web_builder',
                'name' => 'Web Builder',
                'description' => 'Storefront visual editor to customize layout, themes, banners, and customer experience',
                'icon' => 'globe-alt',
                'gradient' => 'from-violet-500 via-purple-600 to-indigo-700',
                'badge_color' => 'bg-violet-500/20 text-violet-300 border-violet-500/30',
                'is_preinstalled' => true,
            ],
            'thesis_binding' => [
                'key' => 'thesis_binding',
                'name' => 'Hardbound / Softbound Thesis Binding',
                'description' => 'Custom cover foil stamping, hardbound & softbound thesis compilation',
                'icon' => 'book-open',
                'gradient' => 'from-amber-500 to-amber-700',
                'badge_color' => 'bg-amber-500/20 text-amber-300 border-amber-500/30',
            ],
            'document_printing' => [
                'key' => 'document_printing',
                'name' => 'Document Printing',
                'description' => 'High-speed colored & monochrome document printing and booklet scanning',
                'icon' => 'document-text',
                'gradient' => 'from-blue-500 to-indigo-700',
                'badge_color' => 'bg-blue-500/20 text-blue-300 border-blue-500/30',
            ],
            'tarpaulin' => [
                'key' => 'tarpaulin',
                'name' => 'Tarpaulin Printing',
                'description' => 'Large format outdoor & indoor tarpaulin banners and signage',
                'icon' => 'photo',
                'gradient' => 'from-purple-500 to-fuchsia-700',
                'badge_color' => 'bg-purple-500/20 text-purple-300 border-purple-500/30',
            ],
            'tshirt' => [
                'key' => 'tshirt',
                'name' => 'T-Shirt Printing',
                'description' => 'Sublimation, DTF, and silkscreen custom apparel printing',
                'icon' => 'sparkles',
                'gradient' => 'from-rose-500 to-pink-700',
                'badge_color' => 'bg-rose-500/20 text-rose-300 border-rose-500/30',
            ],
            'pvc_id' => [
                'key' => 'pvc_id',
                'name' => 'PVC ID Printing',
                'description' => 'High-resolution glossy PVC identification cards & lanyard printing',
                'icon' => 'identification',
                'gradient' => 'from-emerald-500 to-teal-700',
                'badge_color' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
            ],
            'trophy' => [
                'key' => 'trophy',
                'name' => 'Token & Trophy Customization',
                'description' => 'Acrylic plaque engraving, corporate tokens & sports trophy customization',
                'icon' => 'trophy',
                'gradient' => 'from-yellow-400 to-amber-600',
                'badge_color' => 'bg-yellow-500/20 text-yellow-300 border-yellow-500/30',
            ],
            'mug' => [
                'key' => 'mug',
                'name' => 'Personalized Mug Printing',
                'description' => 'Ceramic magic mugs, white mugs & tumbler heat press customization',
                'icon' => 'beaker',
                'gradient' => 'from-cyan-500 to-blue-700',
                'badge_color' => 'bg-cyan-500/20 text-cyan-300 border-cyan-500/30',
            ],
            'sticker' => [
                'key' => 'sticker',
                'name' => 'Sticker Label Printing (Machine Cut)',
                'description' => 'Die-cut vinyl, waterproof product label & decal sticker printing',
                'icon' => 'tag',
                'gradient' => 'from-lime-500 to-green-700',
                'badge_color' => 'bg-lime-500/20 text-lime-300 border-lime-500/30',
            ],
        ];
    }

    /**
     * Keys of pre-installed apps that cannot be removed by shop owners.
     *
     * @return array<int, string>
     */
    public static function preinstalledKeys(): array
    {
        return ['inventory_hub', 'web_builder'];
    }

    /**
     * Check if a service key is pre-installed.
     */
    public static function isPreinstalled(string $key): bool
    {
        return in_array($key, static::preinstalledKeys(), true);
    }

    /**
     * Get details for a specific service key.
     *
     * @return array{key: string, name: string, description: string, icon: string, gradient: string, badge_color: string, is_preinstalled?: bool}|null
     */
    public static function find(string $key): ?array
    {
        return static::all()[$key] ?? null;
    }
}
