<a href="{{ route('dashboard.admin') }}" class="menu-item {{ request()->routeIs('dashboard.admin') ? 'active' : '' }} panel-principal">
    <i class="bi bi-grid-1x2-fill"></i>
    <span class="menu-text">Panel Principal</span>
</a>

<a href="{{ route('expedientes.index') }}" class="menu-item {{ request()->routeIs('expedientes.*') ? 'active' : '' }} expedientes">
    <i class="bi bi-file-earmark-bar-graph-fill"></i>
    <span class="menu-text">Expedientes</span>
</a>

<a href="{{ route('usuarios.index') }}" class="menu-item {{ request()->routeIs('usuarios.*') ? 'active' : '' }} usuarios">
    <i class="bi bi-people-fill"></i>
    <span class="menu-text">Usuarios</span>
</a>

<a href="{{ route('historial.index') }}" class="menu-item {{ request()->routeIs('historial.*') ? 'active' : '' }} historial">
    <i class="bi bi-book-half"></i>
    <span class="menu-text">Historial</span>
</a>

<a href="{{ route('perfil') }}" class="menu-item {{ request()->routeIs('perfil') ? 'active' : '' }} perfil">
    <i class="bi bi-person-circle"></i>
    <span class="menu-text">Mi Perfil</span>
</a>