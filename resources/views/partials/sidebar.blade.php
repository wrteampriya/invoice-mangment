<nav class="sidebar sidebar-offcanvas" id="sidebar">
   
    <ul class="nav">
        <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('dashboard') }}">
                <span class="menu-title">Dashboard</span>
                <i class="mdi mdi-home menu-icon"></i>
            </a>
        </li>
        <li class="nav-item {{ request()->routeIs('customers.*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('customers.index') }}">
                <span class="menu-title">Customers</span>
                <i class="mdi mdi-contacts menu-icon"></i>
            </a>
        </li>
        {{-- TODO: point to the invoices index route once it exists. --}}
        <li class="nav-item">
            <a class="nav-link" href="#">
                <span class="menu-title">Invoices</span>
                <i class="mdi mdi-receipt menu-icon"></i>
            </a>
        </li>
        
    </ul>
</nav>
