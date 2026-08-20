<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Surat;

class GuestLacak extends Component
{
    public $trackingCode = '';
    public $surat = null;
    public $searched = false;
    public $errorMsg = '';

    protected $rules = [
        'trackingCode' => 'required|string'
    ];

    public function search()
    {
        $this->validate();
        $this->searched = true;
        $this->errorMsg = '';

        $surat = Surat::where('tracking_code', $this->trackingCode)
                      ->where('tipe_surat', 'PENGAJUAN')
                      ->first();

        if (!$surat) {
            $this->errorMsg = 'Pengajuan dengan kode tersebut tidak ditemukan.';
            $this->surat = null;
            return;
        }

        $this->surat = $surat;
    }

    public function render()
    {
        return view('livewire.guest-lacak')->layout('components.layouts.app');
    }
}
