<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') | Museum Geologi</title>

    <!-- Font & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- CSS Wajib (Layout Utama) -->
    <link rel="stylesheet" href="{{ asset('assets/css/css_admin/layout.css') }}">
    
    <!-- Area untuk menyuntikkan CSS spesifik per halaman -->
    @stack('styles')
</head>
<body>

<div class="app">
    <!-- Memanggil file sidebar yang tadi dibuat -->
    @include('layouts.sidebar_' . Auth::user()->role)

    <div class="main-content">
        <!-- Memanggil file header yang tadi dibuat -->
        @include('layouts.header')

        <!-- Area Konten Utama -->
        @yield('content')
    </div>
</div>

<!-- Area untuk menyuntikkan Javascript spesifik per halaman -->
@stack('scripts')
</body>
</html>