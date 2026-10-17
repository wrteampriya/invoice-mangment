{{-- InvoicePro brand lockup. Single source: the name comes from config('app.name'). --}}
@props(['tone' => 'dark'])
<a href="{{ route('dashboard') }}" class="brand-lockup brand-lockup-{{ $tone }}">
    <i class="mdi mdi-file-document-outline brand-lockup-icon"></i>
    <span class="brand-lockup-name">{{ config('app.name') }}</span>
</a>
