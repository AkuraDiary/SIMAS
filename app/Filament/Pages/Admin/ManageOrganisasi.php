<?php

namespace App\Filament\Pages\Admin;

use App\Models\JenisUnit;
use App\Models\UnitKerja;
use App\Services\UnitKerjaService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class ManageOrganisasi extends Page implements HasActions
{
    use InteractsWithActions;

    // ── Navigation ────────────────────────────────────────────────────────────

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BuildingOffice2;
    protected static ?string $navigationLabel = 'Organisasi';
    protected static ?string $title           = '';//'Kelola Struktur Organisasi';
    // protected static ?string $slug            = 'organisasi';
    protected static ?int    $navigationSort  = 0;

    public function hasBreadcrumbs(): bool
    {
        return false; // Removes the breadcrumbs
    }
    protected string $view = 'filament.pages.admin.manage-organisasi';

    // ── State ─────────────────────────────────────────────────────────────────

    public array $treeData = [];

    // ── Lifecycle ─────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->refreshTree();
    }

    public function refreshTree(): void
    {
        $this->treeData = app(UnitKerjaService::class)->buildTreeData();
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->tipe_entitas === 'ADMIN';
    }

    // ── Shared sub-schemas ────────────────────────────────────────────────────

    /** Core unit fields — reused in both Create and Edit forms. */
    private function unitInfoFields(bool $parentRequired = false): array
    {
        return [
            TextInput::make('nama_unit')
                ->label('Nama Unit')
                ->required()
                ->maxLength(100),

            TextInput::make('singkatan')
                ->label('Singkatan')
                ->required()
                ->maxLength(20)
                ->helperText('Contoh: REK, WAREK1, HR-01'),

            Select::make('jenis_unit_id')
                ->label('Jenis Unit')
                ->options(fn() => JenisUnit::pluck('nama_jenis', 'id'))
                ->required()
                ->searchable()
                ->createOptionForm([
                    TextInput::make('nama_jenis')
                        ->label('Nama Jenis Unit')
                        ->required()
                        ->maxLength(100),
                    TextInput::make('deskripsi')
                        ->label('Deskripsi')
                        ->maxLength(255),
                ])
                ->createOptionUsing(function (array $data) {
                    return JenisUnit::create($data)->id;
                }),

            Select::make('parent_id')
                ->label('Unit Induk')
                ->options(fn() => app(UnitKerjaService::class)->allUnitsForSelect())
                ->searchable()
                ->nullable()
                ->required($parentRequired)
                ->helperText(
                    $parentRequired
                        ? 'Pilih unit induk untuk unit baru ini.'
                        : 'Kosongkan jika ini adalah unit tingkat teratas.'
                ),

            Toggle::make('is_active')
                ->label('Status Aktif')
                ->default(true),
        ];
    }

    /**
     * Jabatan repeater — shared between Create and Edit.
     * Each row: nama_jabatan (required) + level_jabatan (optional, numeric rank).
     * level 1 = most senior (Kepala Unit), level 2 = next, etc.
     */
    private function jabatanRepeater(): Repeater
    {
        return Repeater::make('jabatans')
            ->label('Jabatan dalam Unit Ini')
            ->helperText('Level 1 = paling senior (Kepala Unit / Dekan). Level 2 = Sekretaris, dst.')
            ->schema([
                TextInput::make('nama_jabatan')
                    ->label('Nama Jabatan')
                    ->required()
                    ->placeholder('Contoh: Kepala Biro'),

                TextInput::make('level_jabatan')
                    ->label('Level')
                    ->numeric()
                    ->minValue(1)
                    ->placeholder('1')
                    ->helperText('Angka (1 = paling senior)'),
            ])
            ->addActionLabel('+ Tambah Jabatan')
            ->defaultItems(0)
            ->reorderable(false)
            ->collapsible()
            ->columns(2);
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    /**
     * Create a new root or child unit.
     * Optional argument: { parentId: int|null }
     */
    public function createUnitAction(): Action
    {
        return Action::make('createUnit')
            ->label('Tambah Unit')
            ->modalHeading('Tambah Unit Kerja Baru')
            ->modalSubmitActionLabel('Simpan')
            ->fillForm(fn(array $arguments): array => [
                'parent_id' => $arguments['parentId'] ?? null,
                'jabatans'  => [],
            ])
            ->schema([
                Section::make('Informasi Unit')->schema($this->unitInfoFields())->columns(2),
                Section::make('Jabatan')->schema([$this->jabatanRepeater()])->columns(1),
            ])
            ->action(function (array $data): void {
                try {
                    app(UnitKerjaService::class)->createUnit($data);
                    Notification::make()->title('Unit berhasil ditambahkan')->success()->send();
                    $this->refreshTree();
                } catch (\Throwable $e) {
                    Notification::make()->title('Gagal menyimpan unit')->body($e->getMessage())->danger()->send();
                }
            });
    }

    /**
     * Tambah Tingkatan — like createUnit but parent is required and
     * restricted to leaf nodes (units with no children yet).
     */
    public function addLevelAction(): Action
    {
        return Action::make('addLevel')
            ->label('+ Tambah Tingkatan')
            ->modalHeading('Tambah Unit di Tingkatan Baru')
            ->modalSubmitActionLabel('Simpan')
            ->fillForm(fn(): array => ['jabatans' => []])
            ->schema([
                Section::make('Informasi Unit')
                    ->schema([
                        TextInput::make('nama_unit')->label('Nama Unit')->required()->maxLength(100),
                        TextInput::make('singkatan')->label('Singkatan')->required()->maxLength(20),
                        Select::make('jenis_unit_id')
                            ->label('Jenis Unit')
                            ->options(JenisUnit::pluck('nama_jenis', 'id'))
                            ->required()
                            ->searchable(),
                        Select::make('parent_id')
                            ->label('Unit Induk (Leaf)')
                            ->options(fn() => app(UnitKerjaService::class)->leafUnitsForSelect())
                            ->searchable()
                            ->required()
                            ->helperText('Hanya menampilkan unit yang belum memiliki sub-unit. Unit baru akan berada satu tingkat di bawah unit yang dipilih.'),
                        Toggle::make('is_active')->label('Status Aktif')->default(true),
                    ])
                    ->columns(2),
                Section::make('Jabatan')->schema([$this->jabatanRepeater()])->columns(1),
            ])
            ->action(function (array $data): void {
                try {
                    app(UnitKerjaService::class)->createUnit($data);
                    Notification::make()->title('Tingkatan baru berhasil ditambahkan')->success()->send();
                    $this->refreshTree();
                } catch (\Throwable $e) {
                    Notification::make()->title('Gagal menyimpan unit')->body($e->getMessage())->danger()->send();
                }
            });
    }

    /**
     * Edit an existing unit and its jabatan positions.
     * Argument: { unitId: int }
     */
    public function editUnitAction(): Action
    {
        return Action::make('editUnit')
            ->modalHeading('Edit Unit Kerja')
            ->modalSubmitActionLabel('Perbarui')
            ->fillForm(function (array $arguments): array {
                $unit = UnitKerja::with('jabatans')->findOrFail($arguments['unitId']);

                return [
                    'nama_unit'     => $unit->nama_unit,
                    'singkatan'     => $unit->singkatan,
                    'jenis_unit_id' => $unit->jenis_unit_id,
                    'parent_id'     => $unit->parent_id,
                    'is_active'     => $unit->is_active,
                    // jabatans ordered by level_jabatan (from HasMany default order)
                    'jabatans'      => $unit->jabatans
                        ->map(fn($j) => [
                            'id'            => $j->id,
                            'nama_jabatan'  => $j->nama_jabatan,
                            'level_jabatan' => $j->level_jabatan,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->schema([
                Section::make('Informasi Unit')->schema($this->unitInfoFields())->columns(2),
                Section::make('Jabatan')->schema([$this->jabatanRepeater()])->columns(1),
            ])
            ->action(function (array $data, array $arguments): void {
                try {
                    $unit = UnitKerja::findOrFail($arguments['unitId']);
                    app(UnitKerjaService::class)->updateUnit($unit, $data);
                    Notification::make()->title('Unit berhasil diperbarui')->success()->send();
                    $this->refreshTree();
                } catch (\Throwable $e) {
                    Notification::make()->title('Gagal memperbarui unit')->body($e->getMessage())->danger()->send();
                }
            });
    }

    /**
     * Soft-delete a unit (guarded against units with children).
     * Argument: { unitId: int }
     */
    public function deleteUnitAction(): Action
    {
        return Action::make('deleteUnit')
            ->requiresConfirmation()
            ->modalHeading('Hapus Unit Kerja?')
            ->modalDescription('Unit yang dihapus tidak akan muncul di diagram. Unit yang masih memiliki sub-unit tidak dapat dihapus.')
            ->modalSubmitActionLabel('Ya, Hapus')
            ->color('danger')
            ->action(function (array $arguments): void {
                try {
                    $unit = UnitKerja::findOrFail($arguments['unitId']);
                    app(UnitKerjaService::class)->deleteUnit($unit);
                    Notification::make()->title("Unit \"{$unit->nama_unit}\" berhasil dihapus")->success()->send();
                    $this->refreshTree();
                } catch (\Throwable $e) {
                    Notification::make()->title('Gagal menghapus unit')->body($e->getMessage())->danger()->send();
                }
            });
    }

    /**
     * Show active staff assigned to a unit, grouped by jabatan level.
     * Argument: { unitId: int }
     */
    public function viewStaffAction(): Action
    {
        return Action::make('viewStaff')
            ->modalHeading(
                fn(array $arguments): string =>
                UnitKerja::with('jenisUnit')->find($arguments['unitId'])?->nama_unit ?? 'Staf Unit'
            )
            ->modalWidth('lg')
            ->modalContent(fn(array $arguments): View => view(
                'filament.pages.admin._staff-list',
                [
                    'unit'      => UnitKerja::with(['jenisUnit', 'jabatans'])->findOrFail($arguments['unitId']),
                    'staffList' => app(UnitKerjaService::class)
                        ->getStaffForUnit(UnitKerja::findOrFail($arguments['unitId'])),
                ]
            ))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup');
    }
}
