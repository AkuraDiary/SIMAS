<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Surat;
use Livewire\WithFileUploads;

class GuestPengajuan extends Component
{
    use WithFileUploads;

    public $pengirim_nama;
    public $pengirim_email;
    public $perihal;
    public $content;
    public $lampiran = [];

    public $submitted = false;
    public $trackingCode = '';

    protected $rules = [
        'pengirim_nama' => 'required|string|max:255',
        'pengirim_email' => 'required|email|max:255',
        'perihal' => 'required|string|max:255',
        'content' => 'nullable|string',
        'lampiran.*' => 'nullable|file|max:10240',
    ];

    public function submit()
    {
        $this->validate();

        $surat = Surat::create([
            'tipe_surat' => 'PENGAJUAN',
            'status_surat' => 'DIPROSES',
            'pengirim_nama' => $this->pengirim_nama,
            'pengirim_email' => $this->pengirim_email,
            'perihal' => $this->perihal,
            'content' => $this->content,
            // user_pembuat_id and unit_pengirim_id can be null for Guest
        ]);

        foreach ($this->lampiran as $file) {
            $surat->addMedia($file->getRealPath())
                  ->usingName($file->getClientOriginalName())
                  ->usingFileName($file->getClientOriginalName())
                  ->toMediaCollection('lampiran');
        }

        // Generate tracking code
        $hash = substr(md5($surat->id . config('app.key') . 'PENGAJUAN'), 0, 8);
        $this->trackingCode = "REQ-{$surat->id}-{$hash}";
        
        $surat->tracking_code = $this->trackingCode;
        $surat->save();

        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.guest-pengajuan')->layout('components.layouts.app');
    }
}
