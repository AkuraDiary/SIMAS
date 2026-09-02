<?php

namespace App\Livewire;

use AmidEsfahani\FilamentTinyEditor\TinyEditor;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;


#[Layout('components.layouts.app')]
class GuestPengajuan extends Component implements HasForms
{
    use InteractsWithForms;

    // We will bind all form data to this array
    public ?array $data = [];

    public bool $submitted = false;
    public ?string $trackingCode = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Wizard::make([

                    // STEP 1: TEMPLATE
                    Step::make('Template')
                        ->description('Pilih jenis surat')
                        ->schema([
                            ViewField::make('template_id')
                                ->view('components.template-selector') // We will create this file next
                                ->columnSpanFull()
                                ->required(),
                        ]),
                    // STEP 2: DATA PENGIRIM
                    Step::make('Data Pengirim')
                        ->description('Informasi pemohon')
                        ->schema([
                            TextInput::make('pengirim_nama')
                                ->label('Nama Lengkap')
                                ->required(),
                            TextInput::make('pengirim_nim')
                                ->label('NIM / NIDN')
                                ->required(),
                            TextInput::make('pengirim_fakultas')
                                ->label('Fakultas / Unit')
                                ->required(),
                            TextInput::make('pengirim_email')
                                ->label('Email Institusi')
                                ->email()
                                ->required(),
                        ])->columns(2), // Makes it a 2-column grid!
                    // STEP 3: ISI SURAT
                    // STEP 3: ISI SURAT
                    Step::make('Isi Surat')
                        ->description('Lengkapi detail surat')
                        ->schema([
                            Select::make('unit_tujuan')
                                ->label('Unit Tujuan')
                                ->options([
                                    '1' => 'Sekretariat Rektorat',
                                    '2' => 'Biro Administrasi Akademik',
                                    '3' => 'Biro Kemahasiswaan',
                                ])
                                ->required(),

                            TextInput::make('perihal')
                                ->label('Subjek / Judul')
                                ->required(),
                            // DYNAMIC FIELDS BASED ON TEMPLATE
                            // If Template 1 (Surat Keterangan Aktif Kuliah) is selected
                            Group::make()->schema([
                                TextInput::make('keperluan')
                                    ->label('Keperluan')
                                    ->placeholder('Contoh: Pengajuan Beasiswa PPA')
                                    ->required(),

                                Select::make('tahun_akademik')
                                    ->label('Tahun Akademik')
                                    ->options(['2023/2024' => '2023/2024', '2024/2025' => '2024/2025'])
                                    ->required(),

                                Select::make('semester')
                                    ->label('Semester')
                                    ->options(['Ganjil' => 'Ganjil', 'Genap' => 'Genap'])
                                    ->required(),
                            ])->columns(2)
                                ->visible(fn(Get $get) => $get('template_id') === '1'), // Only visible if Template 1 is selected!
                            // If another template is selected, show generic content editor
                            RichEditor::make('content')
                                ->label('Isi Surat / Keperluan Tambahan')
                                ->visible(fn(Get $get) => $get('template_id') !== '1')
                                ->columnSpanFull(),
                        ]),
                    // STEP 4: LAMPIRAN
                    Step::make('Lampiran')
                        ->description('Upload dokumen pendukung')
                        ->schema([
                            FileUpload::make('lampiran')
                                ->label('Unggah Lampiran')
                                ->helperText('Format yang didukung: PDF, JPG, PNG. Ukuran maksimal 5MB per file.')
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                ->maxSize(5120)
                                ->multiple() // Allows multiple files as per your design
                                ->panelLayout('grid')
                                ->downloadable()
                                ->columnSpanFull(),
                        ]),
                    // STEP 5: PRATINJAU
                    Step::make('Pratinjau')
                        ->description('Tinjau kembali pengajuan Anda')
                        ->schema([
                            // We will use a ViewField later to render the read-only summary
                            // exactly like your Image 4 design! For now, just the checkbox:
                            Checkbox::make('konfirmasi')
                                ->label('Saya menyatakan bahwa seluruh data yang diisi adalah benar dan sah sesuai dengan peraturan Universitas. Saya bertanggung jawab sepenuhnya atas kebenaran informasi dalam pengajuan ini.')
                                ->required()
                                ->accepted()
                                ->columnSpanFull(),
                        ]),
                ])
                    ->persistStepInQueryString()
                    ->contained(false)
                    

                    ->submitAction(new \Illuminate\Support\HtmlString('<button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-6 rounded-lg transition shadow-sm shadow-primary-200">Kirim Sekarang &nearr;</button>'))
            ])
            ->statePath('data');
    }

    public function submit()
    {
        $state = $this->form->getState();
        // Here we will handle saving the Pengajuan to the database!

        dd($state); // For now, just dump the data to see it working
    }

    public function render(): View
    {
        // Notice we are rendering a normal blade file for this component
        return view('livewire.guest-pengajuan');
    }
}
