<?php

namespace Database\Seeders;

use App\Models\FormatNomorSurat;
use App\Models\Jabatan;
use App\Models\JenisUnit;
use App\Models\Template;
use App\Models\TemplateKategori;
use App\Models\UnitKerja;
use App\Models\User;
use App\Models\UserPegawai;
use App\Models\UserPegawaiJabatan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UeqMasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Idempotent seeder for Universitas Kadiri (UNIK) master data & UEQ evaluation.
     */
    public function run(): void
    {
        $this->command->info('====================================================');
        $this->command->info(' SEEDING UNIVERSITAS KADIRI (UNIK) MASTER DATA (M1) ');
        $this->command->info('====================================================');

        // 1. Seed Jenis Units
        $this->seedJenisUnits();

        // 2. Seed Unit Kerjas & Structural Jabatans
        $this->seedUnitKerjasAndJabatans();

        // 3. Seed 14 UEQ Users, Pegawai, & Jabatan Assignments
        $this->seedUeqUsers();
        User::factory()->createMahasiswa()->create();

        // 4. Seed 5 Official Letter Numbering Formats
        $this->seedFormatNomorSurats();

        // 5. Seed Official UNIK Letter Templates & Categories
        $this->seedTemplates();

        $this->command->info('====================================================');
        $this->command->info(' UNIK MASTER DATA SEEDING COMPLETED SUCCESSFULLY!  ');
        $this->command->info('====================================================');
    }

    /**
     * 1. Seed 7 Jenis Unit Kerja
     */
    protected function seedJenisUnits(): void
    {
        $this->command->line("\n[1/5] Seeding Jenis Units...");

        $jenisUnits = [
            [
                'nama_jenis' => 'Rektorat',
                'deskripsi' => 'Pucuk Pimpinan Tertinggi Universitas Kadiri',
            ],
            [
                'nama_jenis' => 'Biro',
                'deskripsi' => 'Unit Pelaksana Administrasi Utama Universitas',
            ],
            [
                'nama_jenis' => 'Lembaga',
                'deskripsi' => 'Lembaga Penelitian, Pengabdian, dan Penjaminan Mutu',
            ],
            [
                'nama_jenis' => 'UPT',
                'deskripsi' => 'Unit Pelaksana Teknis dan Layanan Penunjang Kampus',
            ],
            [
                'nama_jenis' => 'Bagian',
                'deskripsi' => 'Sub-Unit Pelaksana Teknis dan Tata Usaha di Bawah Biro/Lembaga/UPT/Fakultas',
            ],
            [
                'nama_jenis' => 'Fakultas',
                'deskripsi' => 'Unit Pengelola Akademik, Keilmuan, dan Pendidikan Tinggi',
            ],
            [
                'nama_jenis' => 'Prodi',
                'deskripsi' => 'Program Studi Pengelola Kurikulum dan Pengajaran',
            ],
        ];

        foreach ($jenisUnits as $ju) {
            JenisUnit::updateOrCreate(
                ['nama_jenis' => $ju['nama_jenis']],
                ['deskripsi' => $ju['deskripsi']]
            );
        }

        $count = JenisUnit::count();
        $this->command->info("  ✓ {$count} Jenis Units seeded.");
    }

    /**
     * 2. Seed 55 Unit Kerja in Topological Order & Their Jabatans
     */
    protected function seedUnitKerjasAndJabatans(): void
    {
        $this->command->line("\n[2/5] Seeding 55 Unit Kerjas & Structural Jabatans...");

        $unitKerjasPayload = [
            // ── TIER 0: ROOT ────────────────────────────────────────────────────────
            [
                'singkatan' => 'REK',
                'nama_unit' => 'Rektorat',
                'parent_singkatan' => null,
                'jenis_unit' => 'Rektorat',
                'pengaturan_akses' => [
                    'kebijakan_surat_masuk' => 'TERBATAS_DISPOSISI',
                    'min_level_jabatan' => 1,
                ],
                'jabatans' => [
                    ['nama_jabatan' => 'Rektor', 'level_jabatan' => 1],
                    ['nama_jabatan' => 'Wakil Rektor I', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Wakil Rektor II', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Wakil Rektor III', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Wakil Rektor IV', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Staf Pengadministrasi Rektorat', 'level_jabatan' => 3],
                ],
            ],

            // ── TIER 1: BIRO-BIRO ───────────────────────────────────────────────────
            [
                'singkatan' => 'BAKU',
                'nama_unit' => 'Biro Administrasi Keuangan dan Umum',
                'parent_singkatan' => 'REK',
                'jenis_unit' => 'Biro',
                'pengaturan_akses' => [
                    'kebijakan_surat_masuk' => 'TERBATAS_DISPOSISI',
                    'min_level_jabatan' => 1,
                ],
                'jabatans' => [
                    ['nama_jabatan' => 'Kepala Biro Administrasi Keuangan & Umum', 'level_jabatan' => 1],
                    ['nama_jabatan' => 'Staf Keuangan dan Umum', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'BAAK',
                'nama_unit' => 'Biro Administrasi Akademik & Kemahasiswaan',
                'parent_singkatan' => 'REK',
                'jenis_unit' => 'Biro',
                'pengaturan_akses' => [
                    'kebijakan_surat_masuk' => 'TERBATAS_DISPOSISI',
                    'min_level_jabatan' => 1,
                ],
                'jabatans' => [
                    ['nama_jabatan' => 'Kepala Biro Adm. Akademik & Kemahasiswaan', 'level_jabatan' => 1],
                    ['nama_jabatan' => 'Staf BAAK', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'BAPSI',
                'nama_unit' => 'Biro Administrasi Perencanaan & SI',
                'parent_singkatan' => 'REK',
                'jenis_unit' => 'Biro',
                'pengaturan_akses' => [
                    'kebijakan_surat_masuk' => 'TERBATAS_DISPOSISI',
                    'min_level_jabatan' => 1,
                ],
                'jabatans' => [
                    ['nama_jabatan' => 'Kepala Biro Adm. Perencanaan & SI', 'level_jabatan' => 1],
                    ['nama_jabatan' => 'Staf BAPSI', 'level_jabatan' => 3],
                ],
            ],

            // ── TIER 1: LEMBAGA & UPT PUSAT ─────────────────────────────────────────
            [
                'singkatan' => 'LPPM',
                'nama_unit' => 'Lembaga Penelitian dan Pengabdian (LP3M / LPPM)',
                'parent_singkatan' => 'REK',
                'jenis_unit' => 'Lembaga',
                'pengaturan_akses' => [
                    'kebijakan_surat_masuk' => 'TERBATAS_DISPOSISI',
                    'min_level_jabatan' => 1,
                ],
                'jabatans' => [
                    ['nama_jabatan' => 'Direktur Lembaga Penelitian & Pengabdian', 'level_jabatan' => 1],
                    ['nama_jabatan' => 'Sekretaris Lembaga', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Staf LPPM', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'PPM',
                'nama_unit' => 'Pusat Penjaminan Mutu',
                'parent_singkatan' => 'REK',
                'jenis_unit' => 'Lembaga',
                'pengaturan_akses' => [
                    'kebijakan_surat_masuk' => 'TERBATAS_DISPOSISI',
                    'min_level_jabatan' => 1,
                ],
                'jabatans' => [
                    ['nama_jabatan' => 'Kepala Pusat Penjaminan Mutu', 'level_jabatan' => 1],
                    ['nama_jabatan' => 'Sekretaris Pusat', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Staf Penjaminan Mutu', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'SPI',
                'nama_unit' => 'Satuan Pengawas Internal',
                'parent_singkatan' => 'REK',
                'jenis_unit' => 'Lembaga',
                'pengaturan_akses' => [
                    'kebijakan_surat_masuk' => 'TERBATAS_DISPOSISI',
                    'min_level_jabatan' => 1,
                ],
                'jabatans' => [
                    ['nama_jabatan' => 'Kepala SPI', 'level_jabatan' => 1],
                    ['nama_jabatan' => 'Auditor Internal', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Staf Administrasi SPI', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'ETIK',
                'nama_unit' => 'Dewan Etik',
                'parent_singkatan' => 'REK',
                'jenis_unit' => 'Lembaga',
                'pengaturan_akses' => [
                    'kebijakan_surat_masuk' => 'TERBATAS_DISPOSISI',
                    'min_level_jabatan' => 1,
                ],
                'jabatans' => [
                    ['nama_jabatan' => 'Ketua Dewan Etik', 'level_jabatan' => 1],
                    ['nama_jabatan' => 'Anggota Dewan Etik', 'level_jabatan' => 2],
                ],
            ],
            [
                'singkatan' => 'KERMA-HUMAS',
                'nama_unit' => 'Kerjasama & Humas',
                'parent_singkatan' => 'REK',
                'jenis_unit' => 'UPT',
                'pengaturan_akses' => [
                    'kebijakan_surat_masuk' => 'TERBATAS_DISPOSISI',
                    'min_level_jabatan' => 1,
                ],
                'jabatans' => [
                    ['nama_jabatan' => 'Kepala Unit Kerjasama & Humas', 'level_jabatan' => 1],
                    ['nama_jabatan' => 'Staf Kerjasama & Humas', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'MKT',
                'nama_unit' => 'Marketing (Unik Store)',
                'parent_singkatan' => 'REK',
                'jenis_unit' => 'UPT',
                'pengaturan_akses' => [
                    'kebijakan_surat_masuk' => 'TERBUKA',
                    'min_level_jabatan' => 1,
                ],
                'jabatans' => [
                    ['nama_jabatan' => 'Kepala Unit Marketing', 'level_jabatan' => 1],
                    ['nama_jabatan' => 'Staf Marketing', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'INBIS',
                'nama_unit' => 'Inkubator Bisnis',
                'parent_singkatan' => 'REK',
                'jenis_unit' => 'UPT',
                'pengaturan_akses' => [
                    'kebijakan_surat_masuk' => 'TERBUKA',
                    'min_level_jabatan' => 1,
                ],
                'jabatans' => [
                    ['nama_jabatan' => 'Kepala Unit Inkubator Bisnis', 'level_jabatan' => 1],
                    ['nama_jabatan' => 'Staf Pendamping Bisnis', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'LAB-PUSAT',
                'nama_unit' => 'Laboratorium Pusat',
                'parent_singkatan' => 'REK',
                'jenis_unit' => 'UPT',
                'pengaturan_akses' => [
                    'kebijakan_surat_masuk' => 'TERBUKA',
                    'min_level_jabatan' => 1,
                ],
                'jabatans' => [
                    ['nama_jabatan' => 'Kepala UPT Laboratorium Pusat', 'level_jabatan' => 1],
                    ['nama_jabatan' => 'Pranata Laboratorium', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'PERPUS-PUSAT',
                'nama_unit' => 'Perpustakaan Pusat',
                'parent_singkatan' => 'REK',
                'jenis_unit' => 'UPT',
                'pengaturan_akses' => [
                    'kebijakan_surat_masuk' => 'TERBUKA',
                    'min_level_jabatan' => 1,
                ],
                'jabatans' => [
                    ['nama_jabatan' => 'Kepala UPT Perpustakaan', 'level_jabatan' => 1],
                    ['nama_jabatan' => 'Pustakawan', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'JPC',
                'nama_unit' => 'Job Placement Center (JPC)',
                'parent_singkatan' => 'REK',
                'jenis_unit' => 'UPT',
                'pengaturan_akses' => [
                    'kebijakan_surat_masuk' => 'TERBUKA',
                    'min_level_jabatan' => 1,
                ],
                'jabatans' => [
                    ['nama_jabatan' => 'Kepala UPT JPC', 'level_jabatan' => 1],
                    ['nama_jabatan' => 'Staf JPC', 'level_jabatan' => 3],
                ],
            ],

            // ── TIER 1: FAKULTAS ────────────────────────────────────────────────────
            [
                'singkatan' => 'FH',
                'nama_unit' => 'Fakultas Hukum',
                'parent_singkatan' => 'REK',
                'jenis_unit' => 'Fakultas',
                'pengaturan_akses' => [
                    'kebijakan_surat_masuk' => 'TERBATAS_DISPOSISI',
                    'min_level_jabatan' => 1,
                ],
                'jabatans' => [
                    ['nama_jabatan' => 'Dekan Fakultas Hukum', 'level_jabatan' => 1],
                    ['nama_jabatan' => 'Wakil Dekan', 'level_jabatan' => 2],
                ],
            ],
            [
                'singkatan' => 'FP',
                'nama_unit' => 'Fakultas Pertanian',
                'parent_singkatan' => 'REK',
                'jenis_unit' => 'Fakultas',
                'pengaturan_akses' => [
                    'kebijakan_surat_masuk' => 'TERBATAS_DISPOSISI',
                    'min_level_jabatan' => 1,
                ],
                'jabatans' => [
                    ['nama_jabatan' => 'Dekan Fakultas Pertanian', 'level_jabatan' => 1],
                    ['nama_jabatan' => 'Wakil Dekan', 'level_jabatan' => 2],
                ],
            ],
            [
                'singkatan' => 'FT',
                'nama_unit' => 'Fakultas Teknik',
                'parent_singkatan' => 'REK',
                'jenis_unit' => 'Fakultas',
                'pengaturan_akses' => [
                    'kebijakan_surat_masuk' => 'TERBATAS_DISPOSISI',
                    'min_level_jabatan' => 1,
                ],
                'jabatans' => [
                    ['nama_jabatan' => 'Dekan Fakultas Teknik', 'level_jabatan' => 1],
                    ['nama_jabatan' => 'Wakil Dekan', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Staf Tata Usaha Fakultas Teknik', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'FIK',
                'nama_unit' => 'Fakultas Ilmu Kesehatan',
                'parent_singkatan' => 'REK',
                'jenis_unit' => 'Fakultas',
                'pengaturan_akses' => [
                    'kebijakan_surat_masuk' => 'TERBATAS_DISPOSISI',
                    'min_level_jabatan' => 1,
                ],
                'jabatans' => [
                    ['nama_jabatan' => 'Dekan Fakultas Ilmu Kesehatan', 'level_jabatan' => 1],
                    ['nama_jabatan' => 'Wakil Dekan', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Staf Tata Usaha FIK', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'FEB',
                'nama_unit' => 'Fakultas Ekonomi dan Bisnis',
                'parent_singkatan' => 'REK',
                'jenis_unit' => 'Fakultas',
                'pengaturan_akses' => [
                    'kebijakan_surat_masuk' => 'TERBATAS_DISPOSISI',
                    'min_level_jabatan' => 1,
                ],
                'jabatans' => [
                    ['nama_jabatan' => 'Dekan Fakultas Ekonomi dan Bisnis', 'level_jabatan' => 1],
                    ['nama_jabatan' => 'Wakil Dekan', 'level_jabatan' => 2],
                ],
            ],
            [
                'singkatan' => 'FISIP',
                'nama_unit' => 'Fakultas Ilmu Sosial & Ilmu Politik',
                'parent_singkatan' => 'REK',
                'jenis_unit' => 'Fakultas',
                'pengaturan_akses' => [
                    'kebijakan_surat_masuk' => 'TERBATAS_DISPOSISI',
                    'min_level_jabatan' => 1,
                ],
                'jabatans' => [
                    ['nama_jabatan' => 'Dekan Fakultas Ilmu Sosial & Ilmu Politik', 'level_jabatan' => 1],
                    ['nama_jabatan' => 'Wakil Dekan', 'level_jabatan' => 2],
                ],
            ],
            [
                'singkatan' => 'FKG',
                'nama_unit' => 'Fakultas Kedokteran Gigi',
                'parent_singkatan' => 'REK',
                'jenis_unit' => 'Fakultas',
                'pengaturan_akses' => [
                    'kebijakan_surat_masuk' => 'TERBATAS_DISPOSISI',
                    'min_level_jabatan' => 1,
                ],
                'jabatans' => [
                    ['nama_jabatan' => 'Dekan Fakultas Kedokteran Gigi', 'level_jabatan' => 1],
                    ['nama_jabatan' => 'Wakil Dekan', 'level_jabatan' => 2],
                ],
            ],

            // ── TIER 2: SUB-BAGIAN BIRO BAKU ─────────────────────────────────────────
            [
                'singkatan' => 'BAG-KEU',
                'nama_unit' => 'Bagian Keuangan',
                'parent_singkatan' => 'BAKU',
                'jenis_unit' => 'Bagian',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Kepala Bagian Keuangan', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Staf Keuangan', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'BAG-UMUM',
                'nama_unit' => 'Bagian Umum',
                'parent_singkatan' => 'BAKU',
                'jenis_unit' => 'Bagian',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Kepala Bagian Umum', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Staf Umum', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'BAG-SARPRAS',
                'nama_unit' => 'Bagian Sarana & Prasarana',
                'parent_singkatan' => 'BAKU',
                'jenis_unit' => 'Bagian',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Kepala Bagian Sarpras', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Staf Sarana & Prasarana', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'BAG-KEPEG',
                'nama_unit' => 'Bagian Kepegawaian',
                'parent_singkatan' => 'BAKU',
                'jenis_unit' => 'Bagian',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Kepala Bagian Kepegawaian', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Staf Kepegawaian', 'level_jabatan' => 3],
                ],
            ],

            // ── TIER 2: SUB-BAGIAN BIRO BAAK ─────────────────────────────────────────
            [
                'singkatan' => 'BAG-AKAD',
                'nama_unit' => 'Bagian Akademik',
                'parent_singkatan' => 'BAAK',
                'jenis_unit' => 'Bagian',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Kepala Bagian Akademik', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Staf Akademik', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'BAG-AJAR',
                'nama_unit' => 'Bagian Pembelajaran',
                'parent_singkatan' => 'BAAK',
                'jenis_unit' => 'Bagian',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Kepala Bagian Pembelajaran', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Staf Pembelajaran', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'BAG-MAWA',
                'nama_unit' => 'Bagian Kemahasiswaan',
                'parent_singkatan' => 'BAAK',
                'jenis_unit' => 'Bagian',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Kepala Bagian Kemahasiswaan', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Staf Kemahasiswaan', 'level_jabatan' => 3],
                ],
            ],

            // ── TIER 2: SUB-BAGIAN BIRO BAPSI ────────────────────────────────────────
            [
                'singkatan' => 'BAG-REN',
                'nama_unit' => 'Bagian Perencanaan',
                'parent_singkatan' => 'BAPSI',
                'jenis_unit' => 'Bagian',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Kepala Bagian Perencanaan', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Staf Perencanaan', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'BAG-SI',
                'nama_unit' => 'Bagian Sistem Informasi',
                'parent_singkatan' => 'BAPSI',
                'jenis_unit' => 'Bagian',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Kepala Bagian Sistem Informasi', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Staf Sistem Informasi', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'BAG-PSI',
                'nama_unit' => 'Bagian Pengembangan Sistem Informasi',
                'parent_singkatan' => 'BAPSI',
                'jenis_unit' => 'Bagian',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Kepala Bagian Pengembangan SI', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Programmer / Staf PSI', 'level_jabatan' => 3],
                ],
            ],

            // ── TIER 2: SUB-UNIT LAB PUSAT ───────────────────────────────────────────
            [
                'singkatan' => 'LAB-KOMP',
                'nama_unit' => 'Laboratorium Komputer',
                'parent_singkatan' => 'LAB-PUSAT',
                'jenis_unit' => 'Bagian',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Koordinator Lab Komputer', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Laboran Komputer', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'LAB-BHS',
                'nama_unit' => 'Laboratorium Bahasa',
                'parent_singkatan' => 'LAB-PUSAT',
                'jenis_unit' => 'Bagian',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Koordinator Lab Bahasa', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Laboran Bahasa', 'level_jabatan' => 3],
                ],
            ],

            // ── TIER 2: SUB-UNIT PERPUSTAKAAN PUSAT ──────────────────────────────────
            [
                'singkatan' => 'PERPUS-TEKNIS',
                'nama_unit' => 'Bidang Layanan Teknis Perpustakaan',
                'parent_singkatan' => 'PERPUS-PUSAT',
                'jenis_unit' => 'Bagian',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Koordinator Layanan Teknis', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Staf Teknis Perpustakaan', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'PERPUS-TU',
                'nama_unit' => 'Bidang Layanan Tata Usaha Perpustakaan',
                'parent_singkatan' => 'PERPUS-PUSAT',
                'jenis_unit' => 'Bagian',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Koordinator TU Perpustakaan', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Staf TU Perpustakaan', 'level_jabatan' => 3],
                ],
            ],

            // ── TIER 2: SUB-UNIT JPC ────────────────────────────────────────────────
            [
                'singkatan' => 'JPC-REK',
                'nama_unit' => 'Bidang Rekrutmen JPC',
                'parent_singkatan' => 'JPC',
                'jenis_unit' => 'Bagian',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Koordinator Rekrutmen', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Staf Rekrutmen', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'JPC-ALUMNI',
                'nama_unit' => 'Bidang Hubungan Alumni JPC',
                'parent_singkatan' => 'JPC',
                'jenis_unit' => 'Bagian',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Koordinator Hubungan Alumni', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Staf Alumni', 'level_jabatan' => 3],
                ],
            ],

            // ── TIER 2: SUB-UNIT FAKULTAS HUKUM ─────────────────────────────────────
            [
                'singkatan' => 'TU-FH',
                'nama_unit' => 'Tata Usaha Fakultas Hukum',
                'parent_singkatan' => 'FH',
                'jenis_unit' => 'Bagian',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Kepala TU Fakultas Hukum', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Staf Tata Usaha FH', 'level_jabatan' => 3],
                ],
            ],

            // ── TIER 2: SUB-UNIT FAKULTAS PERTANIAN ──────────────────────────────────
            [
                'singkatan' => 'TU-FP',
                'nama_unit' => 'Tata Usaha Fakultas Pertanian',
                'parent_singkatan' => 'FP',
                'jenis_unit' => 'Bagian',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Kepala TU Fakultas Pertanian', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Staf Tata Usaha FP', 'level_jabatan' => 3],
                ],
            ],

            // ── TIER 2: SUB-UNIT & PRODI FAKULTAS TEKNIK ─────────────────────────────
            [
                'singkatan' => 'TU-FT',
                'nama_unit' => 'Tata Usaha Fakultas Teknik',
                'parent_singkatan' => 'FT',
                'jenis_unit' => 'Bagian',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Kepala TU Fakultas Teknik', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Staf Administrasi Akademik FT', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'GPM-FT',
                'nama_unit' => 'Gugus Penjaminan Mutu FT',
                'parent_singkatan' => 'FT',
                'jenis_unit' => 'Bagian',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Ketua GPM FT', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Anggota GPM FT', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'UPPM-FT',
                'nama_unit' => 'UPPM Fakultas Teknik',
                'parent_singkatan' => 'FT',
                'jenis_unit' => 'Bagian',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Ketua UPPM FT', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Anggota UPPM FT', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'PRODI-TS',
                'nama_unit' => 'Program Studi Teknik Sipil',
                'parent_singkatan' => 'FT',
                'jenis_unit' => 'Prodi',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Ketua Program Studi Teknik Sipil', 'level_jabatan' => 1],
                    ['nama_jabatan' => 'Sekretaris Prodi Teknik Sipil', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Dosen Prodi Teknik Sipil', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'PRODI-TI',
                'nama_unit' => 'Program Studi Teknik Industri',
                'parent_singkatan' => 'FT',
                'jenis_unit' => 'Prodi',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Ketua Program Studi Teknik Industri', 'level_jabatan' => 1],
                    ['nama_jabatan' => 'Sekretaris Prodi Teknik Industri', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Dosen Prodi Teknik Industri', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'PRODI-TE',
                'nama_unit' => 'Program Studi Teknik Elektro',
                'parent_singkatan' => 'FT',
                'jenis_unit' => 'Prodi',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Ketua Program Studi Teknik Elektro', 'level_jabatan' => 1],
                    ['nama_jabatan' => 'Sekretaris Prodi Teknik Elektro', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Dosen Prodi Teknik Elektro', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'PRODI-TIF',
                'nama_unit' => 'Program Studi Teknologi Informasi',
                'parent_singkatan' => 'FT',
                'jenis_unit' => 'Prodi',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Ketua Program Studi Teknologi Informasi', 'level_jabatan' => 1],
                    ['nama_jabatan' => 'Sekretaris Prodi Teknologi Informasi', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Dosen Prodi Teknologi Informasi', 'level_jabatan' => 3],
                ],
            ],

            // ── TIER 2: SUB-UNIT & PRODI FAKULTAS ILMU KESEHATAN ─────────────────────
            [
                'singkatan' => 'TU-FIK',
                'nama_unit' => 'Tata Usaha Fakultas Ilmu Kesehatan',
                'parent_singkatan' => 'FIK',
                'jenis_unit' => 'Bagian',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Kepala TU Fakultas Ilmu Kesehatan', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Staf Administrasi Akademik FIK', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'GPM-FIK',
                'nama_unit' => 'Gugus Penjaminan Mutu FIK',
                'parent_singkatan' => 'FIK',
                'jenis_unit' => 'Bagian',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Ketua GPM FIK', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Anggota GPM FIK', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'UPPM-FIK',
                'nama_unit' => 'UPPM Fakultas Ilmu Kesehatan',
                'parent_singkatan' => 'FIK',
                'jenis_unit' => 'Bagian',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Ketua UPPM FIK', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Anggota UPPM FIK', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'PRODI-KEP',
                'nama_unit' => 'Program Studi Ilmu Keperawatan',
                'parent_singkatan' => 'FIK',
                'jenis_unit' => 'Prodi',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Ketua Program Studi Ilmu Keperawatan', 'level_jabatan' => 1],
                    ['nama_jabatan' => 'Sekretaris Prodi Ilmu Keperawatan', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Dosen Prodi Ilmu Keperawatan', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'PRODI-FAR',
                'nama_unit' => 'Program Studi Farmasi',
                'parent_singkatan' => 'FIK',
                'jenis_unit' => 'Prodi',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Ketua Program Studi Farmasi', 'level_jabatan' => 1],
                    ['nama_jabatan' => 'Sekretaris Prodi Farmasi', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Dosen Prodi Farmasi', 'level_jabatan' => 3],
                ],
            ],
            [
                'singkatan' => 'PRODI-KEB',
                'nama_unit' => 'Program Studi Kebidanan',
                'parent_singkatan' => 'FIK',
                'jenis_unit' => 'Prodi',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Ketua Program Studi Kebidanan', 'level_jabatan' => 1],
                    ['nama_jabatan' => 'Sekretaris Prodi Kebidanan', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Dosen Prodi Kebidanan', 'level_jabatan' => 3],
                ],
            ],

            // ── TIER 2: SUB-UNIT FAKULTAS EKONOMI & BISNIS ───────────────────────────
            [
                'singkatan' => 'TU-FEB',
                'nama_unit' => 'Tata Usaha FEB',
                'parent_singkatan' => 'FEB',
                'jenis_unit' => 'Bagian',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Kepala TU FEB', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Staf Tata Usaha FEB', 'level_jabatan' => 3],
                ],
            ],

            // ── TIER 2: SUB-UNIT FAKULTAS ILMU SOSIAL & POLITIK ──────────────────────
            [
                'singkatan' => 'TU-FISIP',
                'nama_unit' => 'Tata Usaha FISIP',
                'parent_singkatan' => 'FISIP',
                'jenis_unit' => 'Bagian',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Kepala TU FISIP', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Staf Tata Usaha FISIP', 'level_jabatan' => 3],
                ],
            ],

            // ── TIER 2: SUB-UNIT FAKULTAS KEDOKTERAN GIGI ────────────────────────────
            [
                'singkatan' => 'TU-FKG',
                'nama_unit' => 'Tata Usaha FKG',
                'parent_singkatan' => 'FKG',
                'jenis_unit' => 'Bagian',
                'pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBUKA', 'min_level_jabatan' => 1],
                'jabatans' => [
                    ['nama_jabatan' => 'Kepala TU FKG', 'level_jabatan' => 2],
                    ['nama_jabatan' => 'Staf Tata Usaha FKG', 'level_jabatan' => 3],
                ],
            ],
        ];

        $unitCount = 0;
        $jabatanCount = 0;

        foreach ($unitKerjasPayload as $item) {
            $jenisUnit = JenisUnit::where('nama_jenis', $item['jenis_unit'])->first();
            $parentId = null;
            if ($item['parent_singkatan']) {
                $parent = UnitKerja::where('singkatan', $item['parent_singkatan'])->first();
                $parentId = $parent?->id;
            }

            $unit = UnitKerja::updateOrCreate(
                ['singkatan' => $item['singkatan']],
                [
                    'nama_unit' => $item['nama_unit'],
                    'parent_id' => $parentId,
                    'jenis_unit_id' => $jenisUnit?->id,
                    'is_active' => true,
                    'pengaturan_akses' => $item['pengaturan_akses'],
                ]
            );
            $unitCount++;

            // Seed Jabatans for this Unit
            if (!empty($item['jabatans'])) {
                foreach ($item['jabatans'] as $jab) {
                    Jabatan::updateOrCreate(
                        [
                            'unit_kerja_id' => $unit->id,
                            'nama_jabatan' => $jab['nama_jabatan'],
                        ],
                        [
                            'level_jabatan' => $jab['level_jabatan'],
                        ]
                    );
                    $jabatanCount++;
                }
            }
        }

        $this->command->info("  ✓ {$unitCount} Unit Kerjas and {$jabatanCount} Jabatans seeded.");
    }

    /**
     * 3. Seed 14 UEQ Users, Pegawai Profiles, and Active Jabatan Assignments
     */
    protected function seedUeqUsers(): void
    {
        $this->command->line("\n[3/5] Seeding 14 UEQ Users & Structural Assignments...");

        $ueqUsersPayload = [
            [
                'username' => 'rektor',
                'nama_lengkap' => 'Ir. Djoko Rahardjo, MP',
                'nip' => '1985 03 002',
                'email' => 'rektor@unik-kediri.ac.id',
                'phone' => '081234567801',
                'unit_singkatan' => 'REK',
                'nama_jabatan' => 'Rektor',
                'level_jabatan' => 1,
                'can_disposisi' => true,
            ],
            [
                'username' => 'warek2',
                'nama_lengkap' => 'Drs. Ir. Sigit Winarto, S.T., M.T.',
                'nip' => '198212006',
                'email' => 'warek2@unik-kediri.ac.id',
                'phone' => '081234567802',
                'unit_singkatan' => 'REK',
                'nama_jabatan' => 'Wakil Rektor II',
                'level_jabatan' => 2,
                'can_disposisi' => true,
            ],
            [
                'username' => 'staff.rektorat',
                'nama_lengkap' => 'Maya Indriani, S.Kom.',
                'nip' => '199408012',
                'email' => 'staff.rektorat@unik-kediri.ac.id',
                'phone' => '081234567803',
                'unit_singkatan' => 'REK',
                'nama_jabatan' => 'Staf Pengadministrasi Rektorat',
                'level_jabatan' => 3,
                'can_disposisi' => false,
            ],
            [
                'username' => 'kepala.baku',
                'nama_lengkap' => 'Ahmad Fauzi, S.E., M.M.',
                'nip' => '198705003',
                'email' => 'kepala.baku@unik-kediri.ac.id',
                'phone' => '081234567804',
                'unit_singkatan' => 'BAKU',
                'nama_jabatan' => 'Kepala Biro Administrasi Keuangan & Umum',
                'level_jabatan' => 1,
                'can_disposisi' => true,
            ],
            [
                'username' => 'staff.baku',
                'nama_lengkap' => 'Rina Wahyuni, A.Md.',
                'nip' => '199511008',
                'email' => 'staff.baku@unik-kediri.ac.id',
                'phone' => '081234567805',
                'unit_singkatan' => 'BAKU',
                'nama_jabatan' => 'Staf Keuangan dan Umum',
                'level_jabatan' => 3,
                'can_disposisi' => false,
            ],
            [
                'username' => 'kepala.baak',
                'nama_lengkap' => 'Dra. Hj. Sri Lestari, M.Pd.',
                'nip' => '198302005',
                'email' => 'kepala.baak@unik-kediri.ac.id',
                'phone' => '081234567806',
                'unit_singkatan' => 'BAAK',
                'nama_jabatan' => 'Kepala Biro Adm. Akademik & Kemahasiswaan',
                'level_jabatan' => 1,
                'can_disposisi' => true,
            ],
            [
                'username' => 'kepala.bapsi',
                'nama_lengkap' => 'Hendra Wijaya, S.Kom., M.Cs.',
                'nip' => '198909007',
                'email' => 'kepala.bapsi@unik-kediri.ac.id',
                'phone' => '081234567807',
                'unit_singkatan' => 'BAPSI',
                'nama_jabatan' => 'Kepala Biro Adm. Perencanaan & SI',
                'level_jabatan' => 1,
                'can_disposisi' => true,
            ],
            [
                'username' => 'dekan.ft',
                'nama_lengkap' => 'Ir. Bambang Suryono, M.T.',
                'nip' => '198104001',
                'email' => 'dekan.ft@unik-kediri.ac.id',
                'phone' => '081234567808',
                'unit_singkatan' => 'FT',
                'nama_jabatan' => 'Dekan Fakultas Teknik',
                'level_jabatan' => 1,
                'can_disposisi' => true,
            ],
            [
                'username' => 'staff.ft',
                'nama_lengkap' => 'Dedy Pratama, S.T.',
                'nip' => '199201015',
                'email' => 'staff.ft@unik-kediri.ac.id',
                'phone' => '081234567809',
                'unit_singkatan' => 'FT',
                'nama_jabatan' => 'Staf Tata Usaha Fakultas Teknik',
                'level_jabatan' => 3,
                'can_disposisi' => false,
            ],
            [
                'username' => 'dekan.fik',
                'nama_lengkap' => 'Dr. apt. Nurhayati, M.Farm.',
                'nip' => '198607004',
                'email' => 'dekan.fik@unik-kediri.ac.id',
                'phone' => '081234567810',
                'unit_singkatan' => 'FIK',
                'nama_jabatan' => 'Dekan Fakultas Ilmu Kesehatan',
                'level_jabatan' => 1,
                'can_disposisi' => true,
            ],
            [
                'username' => 'staff.fik',
                'nama_lengkap' => 'Siti Aminah, A.Md.Keb.',
                'nip' => '199603019',
                'email' => 'staff.fik@unik-kediri.ac.id',
                'phone' => '081234567811',
                'unit_singkatan' => 'FIK',
                'nama_jabatan' => 'Staf Tata Usaha FIK',
                'level_jabatan' => 3,
                'can_disposisi' => false,
            ],
            [
                'username' => 'direktur.lppm',
                'nama_lengkap' => 'Prof. Dr. Sugeng Rahayu, M.Si.',
                'nip' => '197812002',
                'email' => 'direktur.lppm@unik-kediri.ac.id',
                'phone' => '081234567812',
                'unit_singkatan' => 'LPPM',
                'nama_jabatan' => 'Direktur Lembaga Penelitian & Pengabdian',
                'level_jabatan' => 1,
                'can_disposisi' => true,
            ],
            [
                'username' => 'kepala.ppm',
                'nama_lengkap' => 'Dr. Eko Wahyudi, S.T., M.T.',
                'nip' => '198406009',
                'email' => 'kepala.ppm@unik-kediri.ac.id',
                'phone' => '081234567813',
                'unit_singkatan' => 'PPM',
                'nama_jabatan' => 'Kepala Pusat Penjaminan Mutu',
                'level_jabatan' => 1,
                'can_disposisi' => true,
            ],
            [
                'username' => 'kepala.kerjasama',
                'nama_lengkap' => 'Linda Kusuma, S.Sos., M.Si.',
                'nip' => '199010011',
                'email' => 'kepala.kerjasama@unik-kediri.ac.id',
                'phone' => '081234567814',
                'unit_singkatan' => 'KERMA-HUMAS',
                'nama_jabatan' => 'Kepala Unit Kerjasama & Humas',
                'level_jabatan' => 1,
                'can_disposisi' => true,
            ],
        ];

        foreach ($ueqUsersPayload as $u) {
            // 1. User
            $user = User::updateOrCreate(
                ['username' => $u['username']],
                [
                    'email' => $u['email'],
                    'phone' => $u['phone'],
                    'password' => Hash::make('password123'),
                    'tipe_entitas' => 'STAF',
                    'is_active' => true,
                ]
            );

            // 2. UserPegawai
            $pegawai = UserPegawai::updateOrCreate(
                ['nip' => $u['nip']],
                [
                    'user_id' => $user->id,
                    'nama_lengkap' => $u['nama_lengkap'],
                ]
            );

            // 3. Resolve Unit & Jabatan
            $unit = UnitKerja::where('singkatan', $u['unit_singkatan'])->first();
            if (!$unit) {
                $this->command->error("Unit {$u['unit_singkatan']} not found for user {$u['username']}!");
                continue;
            }

            $jabatan = Jabatan::where('unit_kerja_id', $unit->id)
                ->where('nama_jabatan', $u['nama_jabatan'])
                ->first();

            if (!$jabatan) {
                $jabatan = Jabatan::create([
                    'unit_kerja_id' => $unit->id,
                    'nama_jabatan' => $u['nama_jabatan'],
                    'level_jabatan' => $u['level_jabatan'],
                ]);
            }

            // 4. UserPegawaiJabatan
            UserPegawaiJabatan::updateOrCreate(
                [
                    'user_pegawai_id' => $pegawai->id,
                    'unit_kerja_id' => $unit->id,
                    'jabatan_id' => $jabatan->id,
                ],
                [
                    'status_jabatan' => 'AKTIF',
                    'akses_surat_masuk' => 'DEFAULT',
                    'can_disposisi' => $u['can_disposisi'],
                ]
            );
        }

        $userCount = User::where('id', '!=', 1)->count();
        $this->command->info("  ✓ {$userCount} UEQ persona users seeded with active status and 'password123'.");
    }

    /**
     * 4. Seed 5 Official Letter Numbering Formats
     */
    protected function seedFormatNomorSurats(): void
    {
        $this->command->line("\n[4/5] Seeding 5 Official Numbering Formats...");

        $formatNomorSuratsPayload = [
            [
                'nama_format' => 'Format Surat Rektorat (BAKU)',
                'unit_singkatan' => 'BAKU',
                'tipe_surat' => 'INTERNAL',
                'format_penomoran' => '{NOMOR}/BAKU/{BULAN_ROMAWI}/{TAHUN}',
                'padding_digit' => 3,
                'nomor_urut_terakhir' => 0,
                'tahun' => 2026,
                'is_active' => true,
            ],
            [
                'nama_format' => 'Format Surat Rektorat (Sekretariat)',
                'unit_singkatan' => 'REK',
                'tipe_surat' => 'INTERNAL',
                'format_penomoran' => '{NOMOR}/SEK/{BULAN_ROMAWI}/{TAHUN}',
                'padding_digit' => 3,
                'nomor_urut_terakhir' => 0,
                'tahun' => 2026,
                'is_active' => true,
            ],
            [
                'nama_format' => 'Format Surat Fak. Ilmu Kesehatan',
                'unit_singkatan' => 'FIK',
                'tipe_surat' => 'INTERNAL',
                'format_penomoran' => '{NOMOR}/FIK UNIK/{BULAN_ROMAWI}/{TAHUN}',
                'padding_digit' => 3,
                'nomor_urut_terakhir' => 0,
                'tahun' => 2026,
                'is_active' => true,
            ],
            [
                'nama_format' => 'Format Surat Fakultas Teknik',
                'unit_singkatan' => 'FT',
                'tipe_surat' => 'INTERNAL',
                'format_penomoran' => '{NOMOR}/FT UNIK/{BULAN_ROMAWI}/{TAHUN}',
                'padding_digit' => 3,
                'nomor_urut_terakhir' => 0,
                'tahun' => 2026,
                'is_active' => true,
            ],
            [
                'nama_format' => 'Format Surat BAAK',
                'unit_singkatan' => 'BAAK',
                'tipe_surat' => 'INTERNAL',
                'format_penomoran' => '{NOMOR}/BAAK/{BULAN_ROMAWI}/{TAHUN}',
                'padding_digit' => 3,
                'nomor_urut_terakhir' => 0,
                'tahun' => 2026,
                'is_active' => true,
            ],
        ];

        foreach ($formatNomorSuratsPayload as $fmt) {
            $unit = UnitKerja::where('singkatan', $fmt['unit_singkatan'])->first();

            FormatNomorSurat::updateOrCreate(
                [
                    'nama_format' => $fmt['nama_format'],
                    'unit_kerja_id' => $unit?->id,
                ],
                [
                    'tipe_surat' => $fmt['tipe_surat'],
                    'format_penomoran' => $fmt['format_penomoran'],
                    'padding_digit' => $fmt['padding_digit'],
                    'nomor_urut_terakhir' => $fmt['nomor_urut_terakhir'],
                    'tahun' => $fmt['tahun'],
                    'is_active' => $fmt['is_active'],
                ]
            );
        }

        $fmtCount = FormatNomorSurat::count();
        $this->command->info("  ✓ {$fmtCount} Format Nomor Surat seeded.");
    }

    /**
     * 5. Seed 3 Official UNIK Letter Templates & Categories
     */
    protected function seedTemplates(): void
    {
        $this->command->line("\n[5/5] Seeding 3 Official Letter Templates...");

        $templateKategorisPayload = [
            [
                'nama_kategori' => 'Surat Edaran & Pemberitahuan',
                'deskripsi' => 'Kategori untuk surat edaran resmi rektorat, pemberitahuan operasional, libur bersama, dan sterilisasi kampus.',
            ],
            [
                'nama_kategori' => 'Surat Keterangan & Tugas Pegawai',
                'deskripsi' => 'Kategori untuk surat tugas, rekomendasi, dan surat keterangan kepegawaian.',
            ],
        ];

        foreach ($templateKategorisPayload as $kat) {
            TemplateKategori::updateOrCreate(
                ['nama_kategori' => $kat['nama_kategori']],
                ['deskripsi' => $kat['deskripsi']]
            );
        }

        $templatesPayload = [
            [
                'nama_template' => 'Surat Pemberitahuan Sterilisasi Ruang & Kegiatan Operasional',
                'kategori_nama' => 'Surat Edaran & Pemberitahuan',
                'entry_point_unit_singkatan' => 'REK',
                'deskripsi' => 'Template resmi rektorat/BAKU untuk pemberitahuan sterilisasi ruang laboratorium, sekretariat, dan fasilitas kampus saat berlangsungnya kegiatan operasional penting (OSCE, akreditasi, renovasi).',
                'tipe_surat' => 'INTERNAL',
                'aksesibilitas' => 'INTERNAL',
                'render_engine' => 'HTML',
                'is_ttd_enabled' => true,
                'is_active' => true,
                'template_file_path' => json_encode(['kop_posisi' => 'AWAL_SAJA', 'footer_posisi' => 'AKHIR_SAJA']),
                'field_variables' => [
                    [
                        'key' => 'rujukan_permohonan',
                        'label' => 'Rujukan Surat Permohonan',
                        'type' => 'long_text',
                        'helper' => 'Nomor dan tanggal surat permohonan asal (contoh: surat permohonan Dekan Fakultas Ilmu Kesehatan No. 066/FIK UNIK/I/2026 tanggal 24 Januari 2026)',
                    ],
                    [
                        'key' => 'nama_kegiatan',
                        'label' => 'Nama Kegiatan Utama',
                        'type' => 'text',
                        'helper' => 'Nama kegiatan yang membutuhkan sterilisasi (contoh: Ujian Kompetensi OSCE Apoteker dan Rekayasa Lalu Lintas)',
                    ],
                    [
                        'key' => 'ruang_terdampak',
                        'label' => 'Daftar Ruang Terdampak',
                        'type' => 'long_text',
                        'helper' => 'Daftar ruang yang perlu disterilkan (contoh: Sekretariat Fakultas Pertanian, Ruang Biro Bantuan Hukum (BBH), Ruang PPM, dan Ruang Kerjasama)',
                    ],
                    [
                        'key' => 'jadwal_sterilisasi',
                        'label' => 'Jadwal Waktu Sterilisasi',
                        'type' => 'text',
                        'helper' => 'Rentang tanggal dan waktu sterilisasi (contoh: tanggal 27 sampai dengan 29 Januari 2026)',
                    ],
                ],
                'content_html' => '<table class="surat-header-table" style="width: 100%; border-collapse: collapse; margin-bottom: 18px; font-family: \'Times New Roman\', Times, serif; font-size: 11pt; line-height: 1.4;">
  <tr>
    <td style="width: 15%; vertical-align: top;">Nomor</td>
    <td style="width: 3%; vertical-align: top;">:</td>
    <td style="width: 42%; vertical-align: top;">{{ nomor_surat }}</td>
    <td style="width: 40%; text-align: right; vertical-align: top;">Kediri, {{ tanggal_surat }}</td>
  </tr>
  <tr>
    <td style="vertical-align: top;">Lampiran</td>
    <td style="vertical-align: top;">:</td>
    <td colspan="2" style="vertical-align: top;">{{ lampiran }}</td>
  </tr>
  <tr>
    <td style="vertical-align: top;">Perihal</td>
    <td style="vertical-align: top;">:</td>
    <td colspan="2" style="vertical-align: top;"><strong>{{ perihal }}</strong></td>
  </tr>
</table>

{{ kepada }}

<!-- START:SURAT_ISI -->
<div class="surat-isi-body" style="font-family: \'Times New Roman\', Times, serif; font-size: 12pt; line-height: 1.4;">
  <p style="text-align: justify; margin-bottom: 12px;">Sesuai dengan {{ rujukan_permohonan }} perihal {{ nama_kegiatan }} di Lingkungan Universitas Kadiri,</p>
  <p style="text-align: justify; margin-bottom: 12px;">maka guna mendukung kelancaran kegiatan tersebut, kami mohon untuk beberapa ruang yang bersebelahan dengan lokasi kegiatan yang di antaranya adalah {{ ruang_terdampak }} mulai {{ jadwal_sterilisasi }} mohon untuk dapat disterilkan dari kegiatan operasional rutin.</p>
  <p style="text-align: justify; margin-bottom: 24px;">Demikian surat pemberitahuan ini. Atas perhatian dan kerjasamanya disampaikan terima kasih.</p>
</div>
<!-- END:SURAT_ISI -->

<div style="margin-top: 30px; text-align: right;">
  <p style="margin: 0;">{{ ttd_approver_1 }}</p>
</div>

{{ tembusan }}',
            ],
            [
                'nama_template' => 'Surat Pemberitahuan Penyesuaian Status Keaktifan Dosen (SISTER)',
                'kategori_nama' => 'Surat Keterangan & Tugas Pegawai',
                'entry_point_unit_singkatan' => 'REK',
                'deskripsi' => 'Template resmi rektorat untuk penyesuaian dan pemutakhiran status keaktifan dosen pada sistem SISTER Kemendikbudristek.',
                'tipe_surat' => 'INTERNAL',
                'aksesibilitas' => 'INTERNAL',
                'render_engine' => 'HTML',
                'is_ttd_enabled' => true,
                'is_active' => true,
                'template_file_path' => json_encode(['kop_posisi' => 'AWAL_SAJA', 'footer_posisi' => 'AKHIR_SAJA']),
                'field_variables' => [
                    [
                        'key' => 'nama_dosen_terkait',
                        'label' => 'Daftar Nama Dosen Terkait',
                        'type' => 'long_text',
                        'helper' => 'Nama dosen beserta gelar lengkap (contoh: 1. Eko Siswanto, S.T., M.T.<br>2. Fitry Rahmawaty, S.T., M.T.)',
                    ],
                    [
                        'key' => 'kategori_penyesuaian',
                        'label' => 'Konteks Penyesuaian Status',
                        'type' => 'text',
                        'helper' => 'Konteks aturan penyesuaian (default: pengelolaan status keaktifan dosen pada Sistem Informasi Sumber Daya Terintegrasi (SISTER))',
                    ],
                ],
                'content_html' => '<table class="surat-header-table" style="width: 100%; border-collapse: collapse; margin-bottom: 18px; font-family: \'Times New Roman\', Times, serif; font-size: 11pt; line-height: 1.4;">
  <tr>
    <td style="width: 15%; vertical-align: top;">Nomor</td>
    <td style="width: 3%; vertical-align: top;">:</td>
    <td style="width: 42%; vertical-align: top;">{{ nomor_surat }}</td>
    <td style="width: 40%; text-align: right; vertical-align: top;">Kediri, {{ tanggal_surat }}</td>
  </tr>
  <tr>
    <td style="vertical-align: top;">Lampiran</td>
    <td style="vertical-align: top;">:</td>
    <td colspan="2" style="vertical-align: top;">{{ lampiran }}</td>
  </tr>
  <tr>
    <td style="vertical-align: top;">Perihal</td>
    <td style="vertical-align: top;">:</td>
    <td colspan="2" style="vertical-align: top;"><strong>{{ perihal }}</strong></td>
  </tr>
</table>

{{ kepada }}

<!-- START:SURAT_ISI -->
<div class="surat-isi-body" style="font-family: \'Times New Roman\', Times, serif; font-size: 12pt; line-height: 1.4;">
  <p style="text-align: justify; margin-bottom: 12px;">Dengan Hormat,</p>
  <p style="text-align: justify; margin-bottom: 12px;">Sehubungan dengan adanya perubahan dan penyesuaian mekanisme {{ kategori_penyesuaian }}, khususnya terkait pilihan status yang sebelumnya menggunakan kategori Izin Belajar dan Tugas Belajar, maka dimohon untuk mengajukan kembali surat permohonan perubahan status keaktifan dosen atas nama:</p>
  <p style="text-align: justify; margin-left: 20px; font-weight: bold; margin-bottom: 12px;">{{ nama_dosen_terkait }}</p>
  <p style="text-align: justify; margin-bottom: 8px;">Berkenaan dengan hal tersebut, unit kerja dapat mengajukan permohonan perubahan status keaktifan dosen yang baru dengan memilih salah satu status sebagai berikut:</p>
  <ol style="margin-top: 0; margin-bottom: 12px; padding-left: 24px;">
    <li>Cuti;</li>
    <li>Tugas di Instansi Lain;</li>
    <li>Tugas Belajar Lepas Jabatan;</li>
    <li>Tugas Belajar Dalam Jabatan; atau</li>
    <li>Tidak Aktif.</li>
  </ol>
  <p style="text-align: justify; margin-bottom: 24px;">Demikian surat pemberitahuan ini disampaikan untuk dapat ditindaklanjuti sebagaimana mestinya. Atas perhatian dan kerja samanya, kami sampaikan terima kasih.</p>
</div>
<!-- END:SURAT_ISI -->

<div style="margin-top: 30px; text-align: right;">
  <p style="margin: 0;">{{ ttd_approver_1 }}</p>
</div>

{{ tembusan }}',
            ],
            [
                'nama_template' => 'Surat Pemberitahuan Libur & Cuti Bersama',
                'kategori_nama' => 'Surat Edaran & Pemberitahuan',
                'entry_point_unit_singkatan' => 'REK',
                'deskripsi' => 'Template resmi edaran rektorat untuk penetapan hari libur nasional, perkuliahan, dan cuti bersama sivitas akademika Universitas Kadiri.',
                'tipe_surat' => 'INTERNAL',
                'aksesibilitas' => 'INTERNAL',
                'render_engine' => 'HTML',
                'is_ttd_enabled' => true,
                'is_active' => true,
                'template_file_path' => json_encode(['kop_posisi' => 'AWAL_SAJA', 'footer_posisi' => 'AKHIR_SAJA']),
                'field_variables' => [
                    [
                        'key' => 'dasar_keputusan',
                        'label' => 'Dasar Hukum / Surat Keputusan',
                        'type' => 'long_text',
                        'helper' => 'Dasar hukum penetapan libur (contoh: Surat Keputusan Bersama Tiga Menteri, yaitu Menteri Agama, Menteri Ketenagakerjaan, dan Menteri Pendayagunaan Aparatur Negara dan Reformasi Birokrasi Nomor 1497 Tahun 2025, Nomor 2 Tahun 2025, dan Nomor 5 Tahun 2025)',
                    ],
                    [
                        'key' => 'dalam_rangka',
                        'label' => 'Nama Peringatan / Agenda Libur',
                        'type' => 'text',
                        'helper' => 'Alasan atau agenda hari besar (contoh: Cuti Bersama Hari Raya Nyepi (Tahun Baru Saka 1948) dan Hari Raya Idul Fitri 1447 Hijriah)',
                    ],
                    [
                        'key' => 'tanggal_pelaksanaan',
                        'label' => 'Rentang Tanggal Libur',
                        'type' => 'text',
                        'helper' => 'Rentang tanggal libur perkuliahan dan operasional (contoh: tanggal 18 Maret 2026 sampai dengan 26 Maret 2026)',
                    ],
                    [
                        'key' => 'keterangan_layanan',
                        'label' => 'Keterangan Pembukaan Layanan',
                        'type' => 'long_text',
                        'helper' => 'Informasi jadwal buka operasional kembali (contoh: Layanan akan dibuka kembali pada hari Jumat, 27 Maret 2026)',
                    ],
                ],
                'content_html' => '<table class="surat-header-table" style="width: 100%; border-collapse: collapse; margin-bottom: 18px; font-family: \'Times New Roman\', Times, serif; font-size: 11pt; line-height: 1.4;">
  <tr>
    <td style="width: 15%; vertical-align: top;">Nomor</td>
    <td style="width: 3%; vertical-align: top;">:</td>
    <td style="width: 42%; vertical-align: top;">{{ nomor_surat }}</td>
    <td style="width: 40%; text-align: right; vertical-align: top;">Kediri, {{ tanggal_surat }}</td>
  </tr>
  <tr>
    <td style="vertical-align: top;">Lampiran</td>
    <td style="vertical-align: top;">:</td>
    <td colspan="2" style="vertical-align: top;">{{ lampiran }}</td>
  </tr>
  <tr>
    <td style="vertical-align: top;">Perihal</td>
    <td style="vertical-align: top;">:</td>
    <td colspan="2" style="vertical-align: top;"><strong>{{ perihal }}</strong></td>
  </tr>
</table>

{{ kepada }}

<!-- START:SURAT_ISI -->
<div class="surat-isi-body" style="font-family: \'Times New Roman\', Times, serif; font-size: 12pt; line-height: 1.4;">
  <p style="text-align: justify; margin-bottom: 12px;">Sehubungan dengan {{ dasar_keputusan }} tentang Hari Libur Nasional dan Cuti Bersama Tahun 2026, dalam rangka {{ dalam_rangka }}, kami menginformasikan bahwa pada <strong>{{ tanggal_pelaksanaan }}</strong>, maka seluruh kegiatan di Universitas Kadiri akan diliburkan. {{ keterangan_layanan }}.</p>
  <p style="text-align: justify; margin-bottom: 24px;">Demikian surat pemberitahuan ini agar digunakan sebagaimana mestinya. Terima kasih atas perhatian dan kerjasamanya.</p>
</div>
<!-- END:SURAT_ISI -->

<div style="margin-top: 30px; text-align: right;">
  <p style="margin: 0;">{{ ttd_approver_1 }}</p>
</div>

{{ tembusan }}',
            ],
        ];

        foreach ($templatesPayload as $tpl) {
            $kategori = TemplateKategori::where('nama_kategori', $tpl['kategori_nama'])->first();
            $entryPointUnit = UnitKerja::where('singkatan', $tpl['entry_point_unit_singkatan'])->first();

            Template::updateOrCreate(
                ['nama_template' => $tpl['nama_template']],
                [
                    'kategori_id' => $kategori?->id,
                    'entry_point_unit_id' => $entryPointUnit?->id,
                    'deskripsi' => $tpl['deskripsi'],
                    'tipe_surat' => $tpl['tipe_surat'],
                    'aksesibilitas' => $tpl['aksesibilitas'],
                    'render_engine' => $tpl['render_engine'],
                    'is_ttd_enabled' => $tpl['is_ttd_enabled'],
                    'is_active' => $tpl['is_active'],
                    'template_file_path' => $tpl['template_file_path'],
                    'field_variables' => $tpl['field_variables'],
                    'content_html' => $tpl['content_html'],
                ]
            );
        }

        $templateCount = Template::count();
        $this->command->info("  ✓ {$templateCount} Official UNIK Templates seeded.");
    }
}
