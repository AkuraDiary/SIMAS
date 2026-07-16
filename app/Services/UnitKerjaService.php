<?php

namespace App\Services;

use App\Models\Jabatan;
use App\Models\UnitKerja;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Business logic for UnitKerja CRUD and org-tree building.
 *
 * Hierarchy model (two dimensions, no extra columns needed):
 *   Global rank = unit depth (parent_id chain) + level_jabatan within that unit.
 *   "Sekretaris" at Rektorat (depth 0, level 2) outranks
 *   "Sekretaris" at Fakultas (depth 1, level 2) because the unit is higher.
 */
class UnitKerjaService
{
    // -------------------------------------------------------------------------
    // Tree building
    // -------------------------------------------------------------------------

    /**
     * Full nested tree as a plain PHP array for the Blade view.
     *
     * Node shape:
     * [
     *   'id', 'nama_unit', 'singkatan', 'is_active', 'staff_count',
     *   'jenis_unit' => string,
     *   'jabatans'   => [ ['id', 'nama_jabatan', 'level_jabatan'], ... ],
     *   'children'   => [ ...same shape recursively... ],
     * ]
     */
    public function buildTreeData(): array
    {
        $roots = UnitKerja::query()
            ->whereNull('parent_id')
            ->with($this->eagerLoadRelations())
            ->get();

        return $this->normalizeCollection($roots);
    }

    /** Eager-load tree relations up to 5 nesting levels. */
    private function eagerLoadRelations(): array
    {
        $leafRelations = ['jenisUnit', 'jabatans'];
        $relations     = $leafRelations;
        $prefix        = '';

        for ($depth = 1; $depth <= 5; $depth++) {
            $prefix .= ($depth === 1 ? 'children' : '.children');
            foreach ($leafRelations as $rel) {
                $relations[] = "{$prefix}.{$rel}";
            }
        }

        return $relations;
    }

    private function normalizeCollection(Collection $units): array
    {
        return $units->map(fn(UnitKerja $u) => $this->normalizeNode($u))->values()->all();
    }

    private function normalizeNode(UnitKerja $unit): array
    {
        return [
            'id'          => $unit->id,
            'nama_unit'   => $unit->nama_unit,
            'singkatan'   => $unit->singkatan,
            'is_active'   => $unit->is_active,
            'jenis_unit'  => $unit->jenisUnit?->nama_jenis ?? '—',
            'staff_count' => $unit->pegawaiJabatans()->where('status_jabatan', 'AKTIF')->count(),
            'jabatans'    => $unit->jabatans
                ->map(fn($j) => [
                    'id'            => $j->id,
                    'nama_jabatan'  => $j->nama_jabatan,
                    'level_jabatan' => $j->level_jabatan,
                ])
                ->values()
                ->all(),
            'children'    => $this->normalizeCollection($unit->children),
        ];
    }

    // -------------------------------------------------------------------------
    // CRUD
    // -------------------------------------------------------------------------

    /**
     * Create a UnitKerja with its unit-scoped Jabatan positions.
     *
     * @param  array{
     *   nama_unit: string, singkatan: string, jenis_unit_id: int,
     *   parent_id: ?int, is_active: bool,
     *   jabatans: array<array{nama_jabatan: string, level_jabatan: ?string}>
     * } $data
     */
    public function createUnit(array $data): UnitKerja
    {
        return DB::transaction(function () use ($data) {
            $jabatanRows = $data['jabatans'] ?? [];
            unset($data['jabatans']);

            $unit = UnitKerja::create($data);
            $this->syncJabatans($unit, $jabatanRows);

            return $unit;
        });
    }

    /**
     * Update a UnitKerja and sync its Jabatan positions.
     *
     * @param  array{
     *   nama_unit: string, singkatan: string, jenis_unit_id: int,
     *   parent_id: ?int, is_active: bool,
     *   jabatans: array<array{id?: int, nama_jabatan: string, level_jabatan: ?string}>
     * } $data
     */
    public function updateUnit(UnitKerja $unit, array $data): void
    {
        DB::transaction(function () use ($unit, $data) {
            $jabatanRows = $data['jabatans'] ?? [];
            unset($data['jabatans']);

            $unit->update($data);
            $this->syncJabatans($unit, $jabatanRows);
        });
    }

    /**
     * Soft-delete a unit. Guards against deleting a unit that still has children.
     *
     * @throws \RuntimeException
     */
    public function deleteUnit(UnitKerja $unit): void
    {
        if ($unit->children()->exists()) {
            throw new \RuntimeException(
                "Unit \"{$unit->nama_unit}\" tidak dapat dihapus karena masih memiliki sub-unit."
            );
        }

        $unit->delete();
    }

    // -------------------------------------------------------------------------
    // Selectors
    // -------------------------------------------------------------------------

    /** All units keyed by id → nama_unit. Used in parent_id select. */
    public function allUnitsForSelect(): array
    {
        return UnitKerja::orderBy('nama_unit')->pluck('nama_unit', 'id')->all();
    }

    /**
     * Leaf units (no children) keyed by id → nama_unit.
     * Used in "Tambah Tingkatan" — the new unit must attach to an existing leaf.
     */
    public function leafUnitsForSelect(): array
    {
        return UnitKerja::doesntHave('children')
            ->orderBy('nama_unit')
            ->pluck('nama_unit', 'id')
            ->all();
    }

    /**
     * Jabatan positions belonging to a specific unit, keyed by id → nama_jabatan.
     * Used to populate the reactive jabatan_id select in UserPegawaiForm.
     */
    public function jabatansForUnitSelect(int $unitId): array
    {
        return Jabatan::where('unit_kerja_id', $unitId)
            ->orderBy('level_jabatan')
            ->orderBy('nama_jabatan')
            ->pluck('nama_jabatan', 'id')
            ->all();
    }

    /**
     * Staff actively assigned to a unit, with their profile and position data.
     */
    public function getStaffForUnit(UnitKerja $unit): Collection
    {
        return $unit->pegawaiJabatans()
            ->with(['pegawai.user', 'jabatan'])
            ->where('status_jabatan', 'AKTIF')
            ->get();
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Sync unit-scoped jabatan positions:
     *   - Rows with an existing id → update in place
     *   - Rows without an id        → create new (scoped to this unit)
     *   - Existing ids not in list  → soft-delete
     *
     * Note: Hard deleting a Jabatan that still has UserPegawaiJabatan assignments
     * would break FK integrity. SoftDeletes on Jabatan protects historical data.
     */
    private function syncJabatans(UnitKerja $unit, array $rows): void
    {
        $existingIds = $unit->jabatans()->pluck('id')->all();
        $incomingIds = collect($rows)->pluck('id')->filter()->values()->all();
        $toDelete    = array_diff($existingIds, $incomingIds);

        // Soft-delete removed positions
        if ($toDelete) {
            Jabatan::whereIn('id', $toDelete)->delete();
        }

        foreach ($rows as $row) {
            if (!empty($row['id'])) {
                // Update existing
                Jabatan::where('id', $row['id'])->update([
                    'nama_jabatan'  => $row['nama_jabatan'],
                    'level_jabatan' => $row['level_jabatan'] ?? null,
                ]);
            } else {
                // Create new, scoped to this unit
                Jabatan::create([
                    'unit_kerja_id' => $unit->id,
                    'nama_jabatan'  => $row['nama_jabatan'],
                    'level_jabatan' => $row['level_jabatan'] ?? null,
                ]);
            }
        }
    }
}
