

<div class="nav-section">Menu Utama</div>
<a href="{{ route('staff.dashboard') }}"
   class="nav-link {{ request()->routeIs('staff.dashboard') ? 'active' : '' }}">
    <i class="bi bi-speedometer2"></i> Dashboard
</a>

<div class="nav-section">Pelayanan</div>
<a href="{{ route('staff.patients.index') }}"
   class="nav-link {{ request()->routeIs('staff.patients*') ? 'active' : '' }}">
    <i class="bi bi-person-vcard-fill"></i> Data Pasien
</a>
<a href="{{ route('staff.transactions.create') }}"
   class="nav-link {{ request()->routeIs('staff.transactions.create') ? 'active' : '' }}">
    <i class="bi bi-cart-plus-fill"></i> Transaksi & Pemeriksaan
</a>
<a href="{{ route('staff.transactions.index') }}"
   class="nav-link {{ request()->routeIs('staff.transactions.index') ? 'active' : '' }}">
    <i class="bi bi-receipt"></i> Riwayat Transaksi
</a>