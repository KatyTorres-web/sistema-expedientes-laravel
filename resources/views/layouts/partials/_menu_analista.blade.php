<a href="{{ route('dashboard.analista') }}" class="menu-item {{ request()->routeIs('dashboard.analista') ? 'active' : '' }} panel-principal">
    <i class="bi bi-grid-1x2-fill"></i>
    <span class="menu-text">Panel Principal</span>
</a>

<a href="{{ route('expedientes.index') }}" class="menu-item {{ request()->routeIs('expedientes.*') ? 'active' : '' }} expedientes">
    <i class="bi bi-file-earmark-bar-graph-fill"></i>
    <span class="menu-text">Expedientes</span>
</a>

<a href="{{ route('perfil') }}" class="menu-item {{ request()->routeIs('perfil') ? 'active' : '' }} perfil">
    <i class="bi bi-person-circle"></i>
    <span class="menu-text">Mi Perfil</span>
</a>