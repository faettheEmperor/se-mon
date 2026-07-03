<?php

namespace App\Livewire;

use App\Models\Assignment;
use App\Models\District;
use App\Models\Village;
use App\Models\Pcl;
use App\Models\Pml;
use App\Services\MonitoringExportService;
use Livewire\Component;
use Livewire\WithPagination;

class MonitoringDetail extends Component
{
    use WithPagination;

    // Filters & Search
    public $search = '';
    public $kecFilter = '';
    public $desaFilter = '';
    public $pmlFilter = '';
    public $pclFilter = '';
    public $statusFilter = '';

    // Sorting (Default: Progress ASC, lowest performing first)
    public $sortField = 'progress_pct';
    public $sortDirection = 'asc';

    protected $queryString = [
        'search' => ['except' => ''],
        'kecFilter' => ['except' => ''],
        'desaFilter' => ['except' => ''],
        'pmlFilter' => ['except' => ''],
        'pclFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'sortField' => ['except' => 'progress_pct'],
        'sortDirection' => ['except' => 'asc'],
    ];

    // Dropdown Lists
    public $districtsList = [];
    public $villagesList = [];
    public $pmlList = [];
    public $pclList = [];

    protected function getAssignmentRepo(): \App\Repositories\AssignmentRepository
    {
        return app(\App\Repositories\AssignmentRepository::class);
    }

    public function mount()
    {
        $user = auth()->user();
        $this->districtsList = District::orderBy('nmkec')->get();

        if ($user->role === 'pml') {
            $pml = $user->pml;
            if (!$pml) {
                abort(403, 'PML profile not found.');
            }
            $this->pmlFilter = (string) $pml->id;
            $this->pmlList = collect([$pml]);
            $this->pclList = $this->getAssignmentRepo()->getPclsByPml($pml->id);
        } else {
            $this->pmlList = Pml::orderBy('nama')->get();
            $this->pclList = Pcl::orderBy('nama')->get();
        }

        $this->updateVillagesList();
    }

    public function updatedKecFilter()
    {
        $this->desaFilter = '';
        $this->updateVillagesList();
        $this->resetPage();
    }

    public function updatedDesaFilter()
    {
        $this->resetPage();
    }

    public function updatedPmlFilter()
    {
        if (auth()->user()->role === 'pml') {
            $this->pmlFilter = (string) auth()->user()->pml->id;
        }
        $this->resetPage();
    }

    public function updatedPclFilter()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    protected function updateVillagesList()
    {
        if ($this->kecFilter) {
            $this->villagesList = Village::where('idkec', $this->kecFilter)->orderBy('nmdesa')->get();
        } else {
            $this->villagesList = collect();
        }
    }

    /**
     * Interactive column sorting handler.
     */
    public function sortBy(string $field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * Build the optimized aggregate database query.
     */
    protected function buildQuery()
    {
        $pctExpression = 'CASE WHEN target_usaha > 0 THEN ((SELECT COALESCE(SUM(usaha_today + ruta_today), 0) FROM daily_reports WHERE daily_reports.assignment_id = assignments.id) * 100.0 / target_usaha) ELSE 0 END';

        // Centered query builder from repository helper
        $query = $this->getAssignmentRepo()->getMonitoringQueryBuilder();

        $user = auth()->user();
        if ($user->role === 'pml') {
            $pml = $user->pml;
            if ($pml) {
                $this->pmlFilter = (string) $pml->id;
                $query->where('assignments.pml_id', $pml->id);

                if ($this->pclFilter) {
                    $supervisedPclIds = $this->getAssignmentRepo()->getPclsByPml($pml->id)->pluck('id')->toArray();
                    if (!in_array((int)$this->pclFilter, $supervisedPclIds)) {
                        $this->pclFilter = '';
                    }
                }
            }
        }

        // Apply filters
        if ($user->role !== 'pml' && $this->pmlFilter) {
            $query->where('assignments.pml_id', $this->pmlFilter);
        }

        if ($this->pclFilter) {
            $query->where('assignments.pcl_id', $this->pclFilter);
        }

        if ($this->kecFilter) {
            $query->where('villages.idkec', $this->kecFilter);
        }

        if ($this->desaFilter) {
            $query->where('sls.iddesa', $this->desaFilter);
        }

        if ($this->statusFilter) {
            if ($this->statusFilter === 'perlu_perhatian') {
                $query->whereRaw("{$pctExpression} < 25");
            } elseif ($this->statusFilter === 'rendah') {
                $query->whereRaw("{$pctExpression} >= 25 AND {$pctExpression} < 50");
            } elseif ($this->statusFilter === 'waspada') {
                $query->whereRaw("{$pctExpression} >= 50 AND {$pctExpression} < 80");
            } elseif ($this->statusFilter === 'baik') {
                $query->whereRaw("{$pctExpression} >= 80");
            }
        }

        // Apply search keyword
        if ($this->search) {
            $kw = '%' . $this->search . '%';
            $query->where(function ($q) use ($kw) {
                $q->where('assignments.idsubsls', 'like', $kw)
                  ->orWhere('sls.nmsls', 'like', $kw)
                  ->orWhere('villages.nmdesa', 'like', $kw)
                  ->orWhere('districts.nmkec', 'like', $kw)
                  ->orWhere('pcls.nama', 'like', $kw)
                  ->orWhere('pmls.nama', 'like', $kw);
            });
        }

        // Apply sorting
        $sortableFields = [
            'idsubsls'        => 'assignments.idsubsls',
            'kecamatan'        => 'districts.nmkec',
            'desa'             => 'villages.nmdesa',
            'sls'              => 'sls.nmsls',
            'pcl'              => 'pcls.nama',
            'pml'              => 'pmls.nama',
            'target_usaha'     => 'assignments.target_usaha',
            'usaha_realisasi'  => 'usaha_realisasi',
            'ruta_realisasi'   => 'ruta_realisasi',
            'progress_pct'     => 'progress_pct'
        ];

        $orderCol = $sortableFields[$this->sortField] ?? 'progress_pct';
        $query->orderBy($orderCol, $this->sortDirection);

        return $query;
    }

    /**
     * Download data to Excel.
     */
    public function exportExcel(MonitoringExportService $exportService)
    {
        return $exportService->exportExcel($this->buildQuery());
    }

    /**
     * Download data to CSV.
     */
    public function exportCsv(MonitoringExportService $exportService)
    {
        return $exportService->exportCsv($this->buildQuery());
    }

    public function resetTable()
    {
        $this->search = '';
        $this->kecFilter = '';
        $this->desaFilter = '';
        $this->pmlFilter = '';
        $this->pclFilter = '';
        $this->statusFilter = '';
        $this->sortField = 'progress_pct';
        $this->sortDirection = 'asc';
        $this->updateVillagesList();
        $this->resetPage();
    }

    public function render()
    {
        $paginated = $this->buildQuery()->paginate(15);

        return view('livewire.monitoring-detail', [
            'tableData' => $paginated,
            'hasOperationalData' => \App\Models\DailyReport::exists(),
        ]);
    }
}
