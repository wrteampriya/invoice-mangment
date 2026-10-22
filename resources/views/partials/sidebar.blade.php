<nav class="sidebar sidebar-offcanvas" id="sidebar">

    <ul class="nav">
        <li class="nav-section-label">Workspace</li>
        <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('dashboard') }}">
                <i class="bi bi-grid menu-icon"></i>
                <span class="menu-title">Dashboard</span>
            </a>
        </li>
        <li class="nav-item {{ request()->routeIs('customers.*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('customers.index') }}">
                <i class="bi bi-people menu-icon"></i>
                <span class="menu-title">Customers</span>
            </a>
        </li>
        <li class="nav-item {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('invoices.index') }}">
                <i class="bi bi-receipt menu-icon"></i>
                <span class="menu-title">Invoices</span>
            </a>
        </li>
    </ul>
</nav>
