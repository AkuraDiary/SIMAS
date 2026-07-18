<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <!-- Total Pengguna Card -->
    <div style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);">
        <div>
            <span style="font-size: 0.75rem; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 0.5rem;">
                Total Pengguna
            </span>
            <span style="font-size: 2.25rem; font-weight: 800; color: #111827; line-height: 1;">
                {{ $totalPengguna }}
            </span>
        </div>
        <div style="width: 3rem; height: 3rem; background-color: #eef2ff; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;">
            <x-heroicon-o-users style="width: 1.5rem; height: 1.5rem; color: #4f46e5;" />
        </div>
    </div>

    <!-- Template Aktif Card -->
    <div style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);">
        <div style="display: flex; flex-direction: column; justify-content: space-between; height: 100%;">
            <div>
                <span style="font-size: 0.75rem; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 0.5rem;">
                    Template Aktif
                </span>
                <span style="font-size: 2.25rem; font-weight: 800; color: #111827; line-height: 1;">
                    {{ $templateAktif }}
                </span>
            </div>

        </div>
        <div style="width: 3rem; height: 3rem; background-color: #f3e8ff; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; align-self: flex-start;">
            <x-heroicon-o-document-text style="width: 1.5rem; height: 1.5rem; color: #a855f7;" />
        </div>
    </div>

    <!-- Unit Organisasi Card -->
    <div style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);">
        <div style="display: flex; flex-direction: column; justify-content: space-between; height: 100%;">
            <div>
                <span style="font-size: 0.75rem; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 0.5rem;">
                    Unit Organisasi
                </span>
                <span style="font-size: 2.25rem; font-weight: 800; color: #111827; line-height: 1;">
                    {{ $unitOrganisasi }}
                </span>
            </div>
            
        </div>
        <div style="width: 3rem; height: 3rem; background-color: #f0fdf4; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; align-self: flex-start;">
            <x-heroicon-o-squares-2x2 style="width: 1.5rem; height: 1.5rem; color: #22c55e;" />
        </div>
    </div>
</div>