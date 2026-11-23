<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen w-full bg-slate-100 dark:bg-[#0b1120] font-sans antialiased text-slate-900 dark:text-slate-100 selection:bg-blue-600 selection:text-white overflow-x-hidden">
        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
