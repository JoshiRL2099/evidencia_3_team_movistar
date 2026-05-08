<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sistema</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        :root {
            --sidebar-width: 260px;
        }

        body {
            min-height: 100vh;
        }

        .sidebar {
            width: var(--sidebar-width);
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            padding-top: 1rem;
            background: #f8f9fa;
            border-right: 1px solid #e9ecef;
            z-index: 1030;
        }

        .content-wrapper {
            margin-left: var(--sidebar-width);
            padding: 1.5rem;
        }

        .sidebar .nav-link {
            color: #333;
        }

        .sidebar .nav-link.active,
        .sidebar .nav-link:hover {
            background: #e9f6f6;
            color: #000;
            border-radius: .5rem;
        }

        .sidebar .brand {
            padding: .75rem 1rem;
            font-weight: 700;
        }

        .sidebar .profile {
            position: absolute;
            bottom: 1rem;
            width: calc(var(--sidebar-width) - 2rem);
            padding: .5rem 1rem;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding-bottom: 1rem;
        }
    </style>

</head>

<body>

    <div class="sidebar">
        <div class="brand px-3">
            <a href="/" class="text-decoration-none text-dark">Sistema</a>
        </div>
        @php $__role = strtoupper(trim(auth()->user()->role->name ?? '')); @endphp
        @if($__role === 'ADMIN')
            <a class="nav-link d-flex align-items-center px-3 py-2 {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                href="{{ route('dashboard') }}">
                <span class="me-2">🏠</span> Dashboard
            </a>
        @endif
        {{-- Órdenes: visible para roles que trabajan con órdenes --}}
        @if(in_array($__role, ['ADMIN', 'SALES', 'WAREHOUSE', 'PURCHASING', 'ROUTE']))
            <a class="nav-link d-flex align-items-center px-3 py-2 {{ request()->routeIs('orders.*') ? 'active' : '' }}"
                href="{{ route('orders.index') }}">
                <span class="me-2">📋</span> Órdenes
            </a>
        @endif

        {{-- Clientes: Admin + Sales --}}
        @if(in_array($__role, ['ADMIN', 'SALES']))
            <a class="nav-link d-flex align-items-center px-3 py-2 {{ request()->routeIs('customers.*') ? 'active' : '' }}"
                href="{{ route('customers.index') }}">
                <span class="me-2">📇</span> Clientes
            </a>
        @endif

        {{-- Usuarios: admin only --}}
        @if($__role === 'ADMIN')
            <a class="nav-link d-flex align-items-center px-3 py-2 {{ request()->routeIs('users.*') ? 'active' : '' }}"
                href="{{ route('users.index') }}">
                <span class="me-2">👥</span> Usuarios
            </a>
        @endif

        {{-- Productos: admin only --}}
        @if($__role === 'ADMIN')
            <a class="nav-link d-flex align-items-center px-3 py-2 {{ request()->routeIs('products.*') ? 'active' : '' }}"
                href="{{ route('products.index') }}">
                <span class="me-2">📦</span> Productos
            </a>
        @endif

        {{-- Papelera: admin only --}}
        @if($__role === 'ADMIN')
            <a class="nav-link d-flex align-items-center px-3 py-2 {{ request()->routeIs('orders.trash') ? 'active' : '' }}"
                href="{{ route('orders.trash') }}">
                <span class="me-2">🗑️</span> Papelera
            </a>
        @endif

        <a class="nav-link d-flex align-items-center px-3 py-2" href="{{ route('logout') }}"
            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <span class="me-2">↩️</span> Cerrar sesión
        </a>
        </nav>

        <div class="profile px-3">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle bg-secondary" style="width:40px;height:40px;"></div>
                <div>
                    <div>{{ auth()->user()->full_name ?? 'Admin' }}</div>
                    <small class="text-muted">{{ auth()->user()->role->name ?? '' }}</small>
                </div>
            </div>
        </div>

        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
    </div>

    <div class="content-wrapper">
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light d-lg-none" type="button"
                    onclick="document.querySelector('.sidebar').classList.toggle('d-none')">☰</button>
                <h4 class="mb-0">@yield('page_title', 'Dashboard')</h4>
            </div>
            <div class="d-flex align-items-center gap-2">
                <form action="{{ url()->current() }}" method="GET" class="me-2">
                    <div class="input-group">
                        <input name="q" value="{{ request('q') }}" class="form-control form-control-sm"
                            placeholder="Search...">
                        <button class="btn btn-outline-secondary btn-sm" type="submit">🔎</button>
                    </div>
                </form>
                <div class="d-none d-md-block text-end">
                    <div>{{ auth()->user()->full_name ?? 'Admin' }}</div>
                    <small class="text-muted">{{ auth()->user()->role->name ?? '' }}</small>
                </div>
            </div>
        </header>

        <main>
            @yield('content')
        </main>
    </div>

    @yield('scripts')

</body>

</html>