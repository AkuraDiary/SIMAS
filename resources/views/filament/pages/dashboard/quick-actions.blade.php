<div style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.5rem; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);">
    <span style="font-size: 0.75rem; font-weight: 700; color: #4b5563; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 1.25rem;">
        Aksi Cepat
    </span>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1rem;">
        <!-- New User Button -->
        <a href="{{ \App\Filament\Resources\UserPegawais\UserPegawaiResource::getUrl('create') }}" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1.5rem; background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 0.5rem; text-decoration: none; transition: all 0.2s;" onmouseover="this.style.borderColor='#4f46e5'; this.style.backgroundColor='#fafafa';" onmouseout="this.style.borderColor='#e5e7eb'; this.style.backgroundColor='#ffffff';">
            <x-heroicon-o-user-plus style="width: 1.75rem; height: 1.75rem; color: #4f46e5; margin-bottom: 0.75rem;" />
            <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">Buat Akun Pegawai</span>
        </a>

        <!-- Import Staff Button -->
        <a href="{{ \App\Filament\Resources\UserPegawais\UserPegawaiResource::getUrl() }}" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1.5rem; background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 0.5rem; text-decoration: none; transition: all 0.2s;" onmouseover="this.style.borderColor='#4f46e5'; this.style.backgroundColor='#fafafa';" onmouseout="this.style.borderColor='#e5e7eb'; this.style.backgroundColor='#ffffff';">
            <x-heroicon-o-arrow-up-tray style="width: 1.75rem; height: 1.75rem; color: #4f46e5; margin-bottom: 0.75rem;" />
            <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">Import Staff</span>
        </a>

        <!-- Kelola Organisasi Button -->
        <a href="{{ \App\Filament\Pages\Admin\ManageOrganisasi::getUrl() }}" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1.5rem; background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 0.5rem; text-decoration: none; transition: all 0.2s;" onmouseover="this.style.borderColor='#4f46e5'; this.style.backgroundColor='#fafafa';" onmouseout="this.style.borderColor='#e5e7eb'; this.style.backgroundColor='#ffffff';">
            <x-heroicon-o-building-office style="width: 1.75rem; height: 1.75rem; color: #4f46e5; margin-bottom: 0.75rem;" />
            <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">Kelola Organisasi</span>
        </a>

        <!-- Template Button -->
        <a href="{{ \App\Filament\Resources\TemplateResource\TemplateResource::getUrl('create') }}" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1.5rem; background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 0.5rem; text-decoration: none; transition: all 0.2s;" onmouseover="this.style.borderColor='#4f46e5'; this.style.backgroundColor='#fafafa';" onmouseout="this.style.borderColor='#e5e7eb'; this.style.backgroundColor='#ffffff';">
            <x-heroicon-o-document-plus style="width: 1.75rem; height: 1.75rem; color: #4f46e5; margin-bottom: 0.75rem;" />
            <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">Buat Template</span>
        </a>

        
    </div>
</div>
