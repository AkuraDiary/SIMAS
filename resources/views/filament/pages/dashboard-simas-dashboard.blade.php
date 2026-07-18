<x-filament-panels::page>
    @if(auth()->user()->tipe_entitas === 'ADMIN')
    <!-- Clean, Separable Overview Stats Component -->
    @include('filament.pages.dashboard.overview-stats', [
    'totalPengguna' => $totalPengguna,
    'templateAktif' => $templateAktif,
    'unitOrganisasi' => $unitOrganisasi
    ])

    <!-- Clean, Separable Quick Actions Component -->
    @include('filament.pages.dashboard.quick-actions')
    @else
    <!-- Fallback widgets for Staff / other users -->
    <x-filament-widgets::widgets
        :widgets="$this->getWidgets()"
        :columns="$this->getColumns()" />
    @endif
</x-filament-panels::page>