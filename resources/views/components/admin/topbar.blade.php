@props(['judul', 'deskripsi' => null])

@php
    // Jumlah booking yang belum diproses untuk quick info
    $baru = \App\Models\Booking::where('status', 'Baru Diterima')->count();
@endphp

<header class="sj-topbar">
    <div class="min-w-0">
        <nav class="sj-crumb" aria-label="Breadcrumb">
            <a href="{{ route('dashboard') }}" wire:navigate>Admin</a>
            <flux:icon name="chevron-right" class="size-3.5" />
            <span aria-current="page">{{ $judul }}</span>
        </nav>

        <h1 class="sj-title">{{ $judul }}</h1>

        @if ($deskripsi)
            <p class="sj-subtitle">{{ $deskripsi }}</p>
        @endif
    </div>

    <div class="sj-quick">
        <span class="sj-chip">
            <flux:icon name="calendar" class="size-4" />
            {{ now()->locale('id')->translatedFormat('l, d F Y') }}
        </span>

        <a href="{{ route('admin.booking') }}" wire:navigate class="sj-chip sj-chip-accent">
            <flux:icon name="bell" class="size-4" />
            {{ $baru }} booking baru
        </a>
    </div>
</header>