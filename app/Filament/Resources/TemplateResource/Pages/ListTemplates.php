<?php

namespace App\Filament\Resources\TemplateResource\Pages;

use App\Filament\Resources\TemplateResource\TemplateResource;
use App\Models\TemplateKategori;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTemplates extends ListRecords
{
    protected static string $resource = TemplateResource::class;
    public string $newKategoriNama = '';
    public ?int $editingKategoriId = null;
    public string $editingKategoriNama = '';
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->extraAttributes(['class' => 'text-white'])
                ->label("Buat Template Surat")
                ->icon('heroicon-o-document-plus'),
            Action::make('manageKategoriTemplate')
                ->label('Kelola Kategori Template')
                ->icon('heroicon-o-folder')
                ->color('gray')
                ->modalHeading('Kelola Kategori Template Surat')
                ->modalDescription('Tambah, ubah nama, atau hapus kategori template surat.')
                ->modalContent(fn() => view('filament.pages.manage-kategori-template-modal', [
                    'editingKategoriId'   => $this->editingKategoriId,
                    'editingKategoriNama' => $this->editingKategoriNama,
                    'newKategoriNama'     => $this->newKategoriNama,
                    'kategoriList'        => $this->kategoriList,
                ]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup'),
        ];
    }
    public function addKategori(): void
    {
        if (blank($this->newKategoriNama)) return;
        $nama = trim($this->newKategoriNama);
        $exists = TemplateKategori::where('nama_kategori', $nama)->exists();
        if ($exists) {
            Notification::make()
                ->title('Kategori sudah ada')
                ->body('Kategori dengan nama tersebut sudah terdaftar.')
                ->danger()
                ->send();
            return;
        }
        TemplateKategori::create([
            'nama_kategori' => $nama,
        ]);
        $this->newKategoriNama = '';
        Notification::make()
            ->title('Kategori template berhasil ditambahkan')
            ->success()
            ->send();
    }
    public function startEditKategori(int $id, string $nama): void
    {
        $this->editingKategoriId = $id;
        $this->editingKategoriNama = $nama;
    }
    public function cancelEditKategori(): void
    {
        $this->editingKategoriId = null;
        $this->editingKategoriNama = '';
    }
    public function saveEditKategori(): void
    {
        if (!$this->editingKategoriId || blank($this->editingKategoriNama)) return;
        $nama = trim($this->editingKategoriNama);
        $exists = TemplateKategori::where('nama_kategori', $nama)
            ->where('id', '!=', $this->editingKategoriId)
            ->exists();
        if ($exists) {
            Notification::make()
                ->title('Nama kategori sudah digunakan')
                ->danger()
                ->send();
            return;
        }
        TemplateKategori::where('id', $this->editingKategoriId)
            ->update(['nama_kategori' => $nama]);
        $this->editingKategoriId = null;
        $this->editingKategoriNama = '';
        Notification::make()
            ->title('Kategori template berhasil diperbarui')
            ->success()
            ->send();
    }
    public function deleteKategori(int $id): void
    {
        $kategori = TemplateKategori::withCount('templates')->find($id);
        if (!$kategori) return;
        if ($kategori->templates_count > 0) {
            Notification::make()
                ->title('Kategori tidak dapat dihapus')
                ->body("Masih terdapat {$kategori->templates_count} template surat yang menggunakan kategori ini. Silakan pindahkan template terlebih dahulu.")
                ->danger()
                ->send();
            return;
        }
        $kategori->delete();
        Notification::make()
            ->title('Kategori template berhasil dihapus')
            ->success()
            ->send();
    }
    public function getKategoriListProperty()
    {
        return TemplateKategori::withCount('templates')
            ->orderBy('nama_kategori')
            ->get();
    }
    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\TemplateResource\Widgets\TemplateStatsWidget::class,
        ];
    }
}
