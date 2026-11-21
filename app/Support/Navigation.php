<?php

namespace App\Support;

use App\Models\PrintShop;
use App\Models\User;

class Navigation
{
    /**
     * Resolve the authenticated user's deterministic home workspace link.
     *
     * @return array{url: string, label: string}
     */
    public static function workspaceHome(?User $user): array
    {
        if (! $user) {
            return [
                'url' => route('home'),
                'label' => 'Home',
            ];
        }

        if ($user->hasRole(User::ROLE_ADMIN)) {
            return [
                'url' => route('admin.shops'),
                'label' => 'Platform Admin',
            ];
        }

        if ($user->isOwner()) {
            return [
                'url' => route('owner.dashboard'),
                'label' => 'App Launcher',
            ];
        }

        if ($user->isStaff()) {
            return [
                'url' => route('staff.dashboard'),
                'label' => 'Production Queue',
            ];
        }

        return [
            'url' => route('customer.dashboard'),
            'label' => 'My Orders',
        ];
    }

    /**
     * Resolve a human-readable role badge label for headers and settings.
     */
    public static function roleLabel(?User $user): string
    {
        if (! $user) {
            return 'Guest';
        }

        if ($user->hasRole(User::ROLE_ADMIN)) {
            return 'Super Admin';
        }

        if ($user->isOwner()) {
            return 'Printing Shop Owner';
        }

        if ($user->isStaff()) {
            return 'Production Staff';
        }

        if ($user->isCustomer()) {
            return 'Customer';
        }

        return 'User';
    }

    /**
     * Resolve deterministic contextual back target and breadcrumbs for non-root pages.
     *
     * @param  array{shop?: PrintShop|null, from?: string|null, is_setup_completed?: bool}  $context
     * @return array{url: string, label: string, breadcrumbs: list<array{label: string, url: ?string}>}|null
     */
    public static function backTarget(?User $user, ?string $routeName = null, array $context = []): ?array
    {
        $routeName ??= request()->route()?->getName();
        if (! is_string($routeName) || $routeName === '') {
            return null;
        }

        if (in_array($routeName, [
            'home',
            'owner.dashboard',
            'staff.dashboard',
            'staff.production-hub',
            'customer.dashboard',
            'admin.shops',
        ], true)) {
            return null;
        }

        $from = array_key_exists('from', $context)
            ? $context['from']
            : (is_string(request()->query('from')) ? request()->query('from') : null);

        $override = self::resolveAllowListedOverride($user, $routeName, is_string($from) ? trim($from) : null);
        if ($override !== null) {
            return $override;
        }

        if (in_array($routeName, [
            'owner.platform',
            'owner.analytics-hub',
            'owner.inventory-hub',
            'owner.thesis-binding',
            'owner.document-printing',
        ], true)) {
            return [
                'url' => route('owner.dashboard'),
                'label' => 'App Launcher',
                'breadcrumbs' => [
                    ['label' => 'App Launcher', 'url' => route('owner.dashboard')],
                ],
            ];
        }

        if ($routeName === 'owner.wizard') {
            $shop = $user?->operationalShop();
            $setupCompleted = array_key_exists('is_setup_completed', $context)
                ? (bool) $context['is_setup_completed']
                : ($shop instanceof PrintShop ? (bool) $shop->is_setup_completed : true);
            if (! $setupCompleted) {
                return null;
            }

            return [
                'url' => route('owner.dashboard'),
                'label' => 'App Launcher',
                'breadcrumbs' => [
                    ['label' => 'App Launcher', 'url' => route('owner.dashboard')],
                ],
            ];
        }

        if ($routeName === 'owner.reports.show') {
            $reportsUrl = route('owner.analytics-hub', ['tab' => 'reports']);

            return [
                'url' => $reportsUrl,
                'label' => 'Standardized Reports',
                'breadcrumbs' => [
                    ['label' => 'App Launcher', 'url' => route('owner.dashboard')],
                    ['label' => 'Standardized Reports', 'url' => $reportsUrl],
                ],
            ];
        }

        if ($routeName === 'shops.index') {
            if (! $user) {
                return null;
            }

            $home = self::workspaceHome($user);

            return [
                'url' => $home['url'],
                'label' => $home['label'],
                'breadcrumbs' => [
                    ['label' => $home['label'], 'url' => $home['url']],
                ],
            ];
        }

        if ($routeName === 'shops.show') {
            return [
                'url' => route('shops.index'),
                'label' => 'Find Shops',
                'breadcrumbs' => [
                    ['label' => 'Find Shops', 'url' => route('shops.index')],
                ],
            ];
        }

        if (in_array($routeName, [
            'customer.shop.checkout',
            'customer.shop.order-document',
            'customer.shop.order-thesis',
        ], true)) {
            $shop = $context['shop'] ?? null;
            if ($shop instanceof PrintShop) {
                $shopUrl = route('shops.show', $shop->id);

                return [
                    'url' => $shopUrl,
                    'label' => $shop->name,
                    'breadcrumbs' => [
                        ['label' => 'Find Shops', 'url' => route('shops.index')],
                        ['label' => $shop->name, 'url' => $shopUrl],
                    ],
                ];
            }

            return [
                'url' => route('shops.index'),
                'label' => 'Find Shops',
                'breadcrumbs' => [
                    ['label' => 'Find Shops', 'url' => route('shops.index')],
                ],
            ];
        }

        if ($routeName === 'orders.receipt') {
            if ($user?->isOwner()) {
                $paymentsUrl = route('owner.analytics-hub', ['tab' => 'payments']);

                return [
                    'url' => $paymentsUrl,
                    'label' => 'Payments & Audit',
                    'breadcrumbs' => [
                        ['label' => 'App Launcher', 'url' => route('owner.dashboard')],
                        ['label' => 'Payments & Audit', 'url' => $paymentsUrl],
                    ],
                ];
            }

            if ($user?->isStaff()) {
                return [
                    'url' => route('staff.dashboard'),
                    'label' => 'Production Queue',
                    'breadcrumbs' => [
                        ['label' => 'Production Queue', 'url' => route('staff.dashboard')],
                    ],
                ];
            }

            if ($user?->isCustomer()) {
                return [
                    'url' => route('customer.dashboard'),
                    'label' => 'My Orders',
                    'breadcrumbs' => [
                        ['label' => 'My Orders', 'url' => route('customer.dashboard')],
                    ],
                ];
            }

            $home = self::workspaceHome($user);

            return [
                'url' => $home['url'],
                'label' => $home['label'],
                'breadcrumbs' => [
                    ['label' => $home['label'], 'url' => $home['url']],
                ],
            ];
        }

        if (in_array($routeName, [
            'profile.edit',
            'security.edit',
            'appearance.edit',
            'user-password.edit',
            'two-factor.show',
        ], true)) {
            $home = self::workspaceHome($user);

            return [
                'url' => $home['url'],
                'label' => $home['label'],
                'breadcrumbs' => [
                    ['label' => $home['label'], 'url' => $home['url']],
                ],
            ];
        }

        return null;
    }

    /**
     * Resolve role-scoped, allow-listed ?from= overrides to avoid open redirects or spoofing.
     *
     * @return array{url: string, label: string, breadcrumbs: list<array{label: string, url: ?string}>}|null
     */
    private static function resolveAllowListedOverride(?User $user, string $routeName, ?string $from): ?array
    {
        if (! $user || $from === null || $from === '') {
            return null;
        }

        if ($user->isOwner() && (str_starts_with($routeName, 'owner.') || $routeName === 'orders.receipt')) {
            return match ($from) {
                'thesis-binding' => [
                    'url' => route('owner.thesis-binding'),
                    'label' => 'Thesis Binding',
                    'breadcrumbs' => [
                        ['label' => 'App Launcher', 'url' => route('owner.dashboard')],
                        ['label' => 'Thesis Binding', 'url' => route('owner.thesis-binding')],
                    ],
                ],
                'document-printing' => [
                    'url' => route('owner.document-printing'),
                    'label' => 'Document Printing',
                    'breadcrumbs' => [
                        ['label' => 'App Launcher', 'url' => route('owner.dashboard')],
                        ['label' => 'Document Printing', 'url' => route('owner.document-printing')],
                    ],
                ],
                'analytics-hub' => [
                    'url' => route('owner.analytics-hub'),
                    'label' => 'Analytics Hub',
                    'breadcrumbs' => [
                        ['label' => 'App Launcher', 'url' => route('owner.dashboard')],
                        ['label' => 'Analytics Hub', 'url' => route('owner.analytics-hub')],
                    ],
                ],
                'payments' => [
                    'url' => route('owner.analytics-hub', ['tab' => 'payments']),
                    'label' => 'Payments & Audit',
                    'breadcrumbs' => [
                        ['label' => 'App Launcher', 'url' => route('owner.dashboard')],
                        ['label' => 'Payments & Audit', 'url' => route('owner.analytics-hub', ['tab' => 'payments'])],
                    ],
                ],
                'reports' => [
                    'url' => route('owner.analytics-hub', ['tab' => 'reports']),
                    'label' => 'Standardized Reports',
                    'breadcrumbs' => [
                        ['label' => 'App Launcher', 'url' => route('owner.dashboard')],
                        ['label' => 'Standardized Reports', 'url' => route('owner.analytics-hub', ['tab' => 'reports'])],
                    ],
                ],
                'inventory' => [
                    'url' => route('owner.inventory-hub'),
                    'label' => 'Inventory Hub',
                    'breadcrumbs' => [
                        ['label' => 'App Launcher', 'url' => route('owner.dashboard')],
                        ['label' => 'Inventory Hub', 'url' => route('owner.inventory-hub')],
                    ],
                ],
                default => null,
            };
        }

        if ($user->isCustomer() && (str_starts_with($routeName, 'shops.') || str_starts_with($routeName, 'customer.') || $routeName === 'orders.receipt')) {
            return match ($from) {
                'shops' => [
                    'url' => route('shops.index'),
                    'label' => 'Find Shops',
                    'breadcrumbs' => [
                        ['label' => 'Find Shops', 'url' => route('shops.index')],
                    ],
                ],
                'orders' => [
                    'url' => route('customer.dashboard'),
                    'label' => 'My Orders',
                    'breadcrumbs' => [
                        ['label' => 'My Orders', 'url' => route('customer.dashboard')],
                    ],
                ],
                default => null,
            };
        }

        return null;
    }
}
