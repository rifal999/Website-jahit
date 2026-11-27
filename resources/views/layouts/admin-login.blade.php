@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="sj-admin min-h-svh">
    <div class="grid min-h-svh">

            {{-- Panel form --}}
            <main class="flex flex-col items-center justify-center gap-6 p-6 sm:p-10">
                {{-- Logo ringkas untuk layar kecil, karena panel branding disembunyikan --}}

                <div class="w-full max-w-sm">
                    {{ $slot }}
                </div>

                <a href="{{ route('home') }}" class="text-sm text-zinc-500 transition hover:text-accent">
                    &larr; Kembali ke beranda
                </a>
            </main>
        </div>

        @fluxScripts
    </body>
</html>