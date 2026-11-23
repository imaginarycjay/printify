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
            'analytics_hub' => [
                'key' => 'analytics_hub',
                'name' => 'Sales & Financial Analytics',
                'description' => 'Real-time cashflow, gross profit margins, product mix breakdown, and financial reporting',
                'icon' => 'chart-bar-square',
                'gradient' => 'bg-blue-600',
                'badge_color' => 'bg-blue-50 text-blue-800 border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800',
                'is_preinstalled' => true,
            ],
            'inventory_hub' => [
                'key' => 'inventory_hub',
                'name' => 'Inventory Hub',
                'description' => 'Central raw materials, ready-to-buy products, real-time burn rates, and restock orders',
                'icon' => 'archive-box',
                'gradient' => 'bg-emerald-600',
                'badge_color' => 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800',
                'is_preinstalled' => true,
            ],
            'production_hub' => [
                'key' => 'production_hub',
                'name' => 'Shop Floor & Production Queue',
                'description' => 'Digital job queue, machine scheduling, 1-click stage advancement, and BOM consumption',
                'icon' => 'queue-list',
                'gradient' => 'bg-slate-800',
                'badge_color' => 'bg-slate-100 text-slate-800 border-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700',
                'is_preinstalled' => true,
            ],
            'thesis_binding' => [
                'key' => 'thesis_binding',
                'name' => 'Hardbound / Softbound Thesis Binding',
                'description' => 'Custom cover foil stamping, hardbound & softbound thesis compilation',
                'icon' => 'book-open',
                'gradient' => 'bg-indigo-600',
                'badge_color' => 'bg-indigo-50 text-indigo-800 border-indigo-200 dark:bg-indigo-950/60 dark:text-indigo-300 dark:border-indigo-800',
            ],
            'document_printing' => [
                'key' => 'document_printing',
                'name' => 'Document Printing',
                'description' => 'High-speed colored & monochrome document printing and booklet scanning',
                'icon' => 'document-text',
                'gradient' => 'bg-sky-600',
                'badge_color' => 'bg-sky-50 text-sky-800 border-sky-200 dark:bg-sky-950/60 dark:text-sky-300 dark:border-sky-800',
            ],
            'tarpaulin' => [
                'key' => 'tarpaulin',
                'name' => 'Tarpaulin Printing',
                'description' => 'Large format outdoor & indoor tarpaulin banners and signage',
                'icon' => 'photo',
                'gradient' => 'bg-purple-600',
                'badge_color' => 'bg-purple-50 text-purple-800 border-purple-200 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-800',
            ],
            'tshirt' => [
                'key' => 'tshirt',
                'name' => 'T-Shirt Printing',
                'description' => 'Sublimation, DTF, and silkscreen custom apparel printing',
                'icon' => 'sparkles',
                'gradient' => 'bg-rose-600',
                'badge_color' => 'bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800',
            ],
            'pvc_id' => [
                'key' => 'pvc_id',
                'name' => 'PVC ID Printing',
                'description' => 'High-resolution glossy PVC identification cards & lanyard printing',
                'icon' => 'identification',
                'gradient' => 'bg-emerald-600',
                'badge_color' => 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800',
            ],
            'trophy' => [
                'key' => 'trophy',
                'name' => 'Token & Trophy Customization',
                'description' => 'Acrylic plaque engraving, corporate tokens & sports trophy customization',
                'icon' => 'trophy',
                'gradient' => 'bg-slate-700',
                'badge_color' => 'bg-slate-100 text-slate-800 border-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700',
            ],
            'mug' => [
                'key' => 'mug',
                'name' => 'Personalized Mug Printing',
                'description' => 'Ceramic magic mugs, white mugs & tumbler heat press customization',
                'icon' => 'beaker',
                'gradient' => 'bg-cyan-600',
                'badge_color' => 'bg-cyan-50 text-cyan-800 border-cyan-200 dark:bg-cyan-950/60 dark:text-cyan-300 dark:border-cyan-800',
            ],
            'sticker' => [
                'key' => 'sticker',
                'name' => 'Sticker Label Printing (Machine Cut)',
                'description' => 'Die-cut vinyl, waterproof product label & decal sticker printing',
                'icon' => 'tag',
                'gradient' => 'bg-teal-600',
                'badge_color' => 'bg-teal-50 text-teal-800 border-teal-200 dark:bg-teal-950/60 dark:text-teal-300 dark:border-teal-800',
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
        return ['analytics_hub', 'inventory_hub', 'production_hub'];
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

    /**
     * Get all customer-orderable print services (excluding administrative core hubs).
     *
     * @return array<string, array{key: string, name: string, description: string, icon: string, gradient: string, badge_color: string}>
     */
    public static function customerServices(): array
    {
        /** @var array<string, array{key: string, name: string, description: string, icon: string, gradient: string, badge_color: string}> $services */
        $services = array_filter(
            static::all(),
            fn (array $s, string $key): bool => ! static::isPreinstalled($key),
            ARRAY_FILTER_USE_BOTH
        );

        return $services;
    }
}
