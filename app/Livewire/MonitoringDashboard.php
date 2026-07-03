<?php

namespace App\Livewire;

use App\Repositories\AssignmentRepository;
use App\Services\MonitoringService;
use Livewire\Component;
use Carbon\Carbon;
use App\Models\District;
use App\Models\Village;
use App\Models\Sls;
use App\Models\SubSls;
use App\Models\Pcl;
use App\Models\Pml;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Cache;

class MonitoringDashboard extends Component
{
    // Progress Monitoring Filters (Section 1)
    public $monitoringLevel = 'kec';
    public $dateFilter = '';
    public $kecFilter = '';
    public $desaFilter = '';
    public $slsFilter = '';
    public $pclFilter = '';
    public $pmlFilter = '';
    public $keywordFilter = '';

    // Daily Trend Analysis Filters (Section 2 - 100% Independent)
    public $trendEntityType = 'kab';
    public $trendEntityId = '';
    public $trendStartDate = '';
    public $trendEndDate = '';

    // Drill down states
    public $drillKecId = '';
    public $drillDesaId = '';
    public $drillSlsId = '';
    public $drillPmlId = '';

    // Lists for dropdown filters
    public $districtsList = [];
    public $villagesList = [];
    public $slsList = [];
    public $pclList = [];
    public $pmlList = [];

    public function mount()
    {
        $this->districtsList = District::all();
        $this->pclList = Pcl::orderBy('nama')->get();
        $this->pmlList = Pml::orderBy('nama')->get();
        $this->updateVillagesList();
        $this->updateSlsList();

        // Default Daily Trend Analysis State: Kabupaten Bintan, Last 14 Days
        $this->trendStartDate = Carbon::now()->subDays(13)->format('Y-m-d');
        $this->trendEndDate = Carbon::now()->format('Y-m-d');
    }

    public function updatedKecFilter()
    {
        $this->desaFilter = '';
        $this->slsFilter = '';
        $this->updateVillagesList();
        $this->updateSlsList();
    }

    public function updatedDesaFilter()
    {
        $this->slsFilter = '';
        $this->updateSlsList();
    }

    public function updatedTrendEntityType($value)
    {
        $this->trendEntityId = '';
    }

    protected function updateVillagesList()
    {
        if ($this->kecFilter) {
            $this->villagesList = Village::where('idkec', $this->kecFilter)->orderBy('nmdesa')->get();
        } else {
            $this->villagesList = collect();
        }
    }

    protected function updateSlsList()
    {
        if ($this->desaFilter) {
            $this->slsList = Sls::where('iddesa', $this->desaFilter)->orderBy('nmsls')->get();
        } else {
            $this->slsList = collect();
        }
    }

    // Dynamic Select options for Daily Trend Analysis
    public function getTrendSelectorOptionsProperty()
    {
        if ($this->trendEntityType === 'kec') {
            return District::orderBy('nmkec')->get()->map(fn($d) => ['id' => $d->idkec, 'name' => $d->nmkec]);
        } elseif ($this->trendEntityType === 'desa') {
            return Village::orderBy('nmdesa')->get()->map(fn($v) => ['id' => $v->iddesa, 'name' => $v->nmdesa]);
        } elseif ($this->trendEntityType === 'subsls') {
            return SubSls::orderBy('idsubsls')->get()->map(fn($s) => ['id' => $s->idsubsls, 'name' => 'SubSLS ' . $s->kdsubsls . ' (' . $s->idsubsls . ')']);
        } elseif ($this->trendEntityType === 'pcl') {
            return Pcl::orderBy('nama')->get()->map(fn($p) => ['id' => $p->id, 'name' => $p->nama]);
        } elseif ($this->trendEntityType === 'pml') {
            return Pml::orderBy('nama')->get()->map(fn($p) => ['id' => $p->id, 'name' => $p->nama]);
        }
        return collect();
    }

    public function updatedMonitoringLevel($value)
    {
        if (!in_array($value, ['kec', 'desa', 'pml'])) {
            $this->monitoringLevel = 'kec';
        }
        $this->drillKecId = '';
        $this->drillDesaId = '';
        $this->drillSlsId = '';
        $this->drillPmlId = '';
    }

    // Drill Down handlers
    public function selectDrillKec($idkec)
    {
        $this->drillKecId = $idkec;
        $this->drillDesaId = '';
        $this->drillSlsId = '';
    }

    public function selectDrillDesa($iddesa)
    {
        $this->drillDesaId = $iddesa;
        $this->drillSlsId = '';
    }

    public function selectDrillSls($idsls)
    {
        $this->drillSlsId = $idsls;
    }

    public function selectDrillPml($idPml)
    {
        $this->drillPmlId = $idPml;
    }

    public function resetDrillKec()
    {
        $this->drillKecId = '';
        $this->drillDesaId = '';
        $this->drillSlsId = '';
    }

    public function resetDrillDesa()
    {
        $this->drillDesaId = '';
        $this->drillSlsId = '';
    }

    public function resetDrillSls()
    {
        $this->drillSlsId = '';
    }

    public function resetDrillPml()
    {
        $this->drillPmlId = '';
    }

    /**
     * Computed property for filtered assignments.
     * Caches within the single request execution context.
     */
    #[Computed]
    public function filteredAssignments()
    {
        $assignmentRepo = app(AssignmentRepository::class);
        $assignments = $assignmentRepo->getAllWithRelations();

        // Apply Filters
        if ($this->kecFilter) {
            $assignments = $assignments->filter(fn($a) => $a->subsls->sls->village->idkec === $this->kecFilter);
        }
        if ($this->desaFilter) {
            $assignments = $assignments->filter(fn($a) => $a->subsls->sls->village->iddesa === $this->desaFilter);
        }
        if ($this->slsFilter) {
            $assignments = $assignments->filter(fn($a) => $a->subsls->idsls === $this->slsFilter);
        }
        if ($this->pclFilter) {
            $assignments = $assignments->filter(fn($a) => (int)$a->pcl_id === (int)$this->pclFilter);
        }
        if ($this->pmlFilter) {
            $assignments = $assignments->filter(fn($a) => (int)$a->pml_id === (int)$this->pmlFilter);
        }
        if ($this->keywordFilter) {
            $kw = strtolower($this->keywordFilter);
            $assignments = $assignments->filter(function($a) use ($kw) {
                return str_contains(strtolower($a->subsls->sls->village->district->nmkec ?? ''), $kw) ||
                       str_contains(strtolower($a->subsls->sls->village->nmdesa ?? ''), $kw) ||
                       str_contains(strtolower($a->subsls->sls->village->iddesa ?? ''), $kw) ||
                       str_contains(strtolower($a->pml->nama ?? ''), $kw);
            });
        }

        // Map report values based on dateFilter
        $assignments = $assignments->map(function ($a) {
            $reports = $a->dailyReports;
            if ($this->dateFilter) {
                $reports = $reports->filter(function ($r) {
                    return Carbon::parse($r->report_date)->format('Y-m-d') === $this->dateFilter;
                });
            }

            $a->usaha_realisasi = $reports->sum('usaha_today');
            $a->ruta_realisasi = $reports->sum('ruta_today');
            $a->progress_pct = $a->target_usaha > 0 ? (($a->usaha_realisasi + $a->ruta_realisasi) / $a->target_usaha) * 100 : 0;
            return $a;
        });

        return $assignments;
    }

    /**
     * Computed property for assignments used specifically in the Drill-Down panel.
     * Caches within the single request execution context.
     * Recalculates only when administrative/PML filters change, ignoring date and keyword filters.
     */
    #[Computed]
    public function drillAssignments()
    {
        $assignmentRepo = app(AssignmentRepository::class);
        $assignments = $assignmentRepo->getAllWithRelations();

        if ($this->kecFilter) {
            $assignments = $assignments->filter(fn($a) => $a->subsls->sls->village->idkec === $this->kecFilter);
        }
        if ($this->desaFilter) {
            $assignments = $assignments->filter(fn($a) => $a->subsls->sls->village->iddesa === $this->desaFilter);
        }
        if ($this->pmlFilter) {
            $assignments = $assignments->filter(fn($a) => (int)$a->pml_id === (int)$this->pmlFilter);
        }

        // Map cumulative report values (ignores dateFilter completely!)
        $assignments = $assignments->map(function ($a) {
            $reports = $a->dailyReports;
            $a->usaha_realisasi = $reports->sum('usaha_today');
            $a->ruta_realisasi = $reports->sum('ruta_today');
            $a->progress_pct = $a->target_usaha > 0 ? (($a->usaha_realisasi + $a->ruta_realisasi) / $a->target_usaha) * 100 : 0;
            return $a;
        });

        return $assignments;
    }

    /**
     * Computed property for Drill-down data.
     */
    #[Computed]
    public function drillData()
    {
        $filtered = $this->drillAssignments;
        $drillData = collect();

        if ($this->monitoringLevel === 'desa') {
            if (!$this->drillDesaId) {
                $villages = $this->kecFilter 
                    ? Village::where('idkec', $this->kecFilter)->orderBy('nmdesa')->get() 
                    : Village::orderBy('nmdesa')->get();

                $drillData = $villages->map(function ($v) use ($filtered) {
                    $subFiltered = $filtered->filter(fn($a) => $a->subsls->sls->iddesa === $v->iddesa);
                    $target = $subFiltered->sum('target_usaha');
                    $realisasi = $subFiltered->sum('usaha_realisasi') + $subFiltered->sum('ruta_realisasi');
                    $pct = $target > 0 ? ($realisasi / $target) * 100 : 0;
                    return [
                        'id' => $v->iddesa,
                        'name' => $v->nmdesa,
                        'target' => $target,
                        'realisasi' => $realisasi,
                        'percentage' => round($pct, 2),
                        'color' => $this->getProgressColor($pct),
                    ];
                });
            } elseif ($this->drillDesaId && !$this->drillSlsId) {
                $slss = Sls::where('iddesa', $this->drillDesaId)->orderBy('nmsls')->get();
                $drillData = $slss->map(function ($s) use ($filtered) {
                    $subFiltered = $filtered->filter(fn($a) => $a->subsls->idsls === $s->idsls);
                    $target = $subFiltered->sum('target_usaha');
                    $realisasi = $subFiltered->sum('usaha_realisasi') + $subFiltered->sum('ruta_realisasi');
                    $pct = $target > 0 ? ($realisasi / $target) * 100 : 0;
                    return [
                        'id' => $s->idsls,
                        'name' => $s->nmsls,
                        'target' => $target,
                        'realisasi' => $realisasi,
                        'percentage' => round($pct, 2),
                        'color' => $this->getProgressColor($pct),
                    ];
                });
            } else {
                $subFiltered = $filtered->filter(fn($a) => $a->subsls->idsls === $this->drillSlsId);
                $drillData = $subFiltered->map(function ($a) {
                    return [
                        'id' => $a->subsls->idsubsls,
                        'name' => 'SubSLS ' . $a->subsls->kdsubsls . ' (PCL: ' . ($a->pcl->nama ?? '-') . ')',
                        'target' => $a->target_usaha,
                        'realisasi' => $a->usaha_realisasi,
                        'percentage' => round($a->progress_pct, 2),
                        'color' => $this->getProgressColor($a->progress_pct),
                    ];
                });
            }
        } elseif ($this->monitoringLevel === 'pml') {
            if (!$this->drillPmlId) {
                $pmls = $this->pmlFilter 
                    ? Pml::where('id', $this->pmlFilter)->orderBy('nama')->get() 
                    : Pml::orderBy('nama')->get();

                $drillData = $pmls->map(function ($p) use ($filtered) {
                    $subFiltered = $filtered->filter(fn($a) => (int)$a->pml_id === (int)$p->id);
                    $target = $subFiltered->sum('target_usaha');
                    $realisasi = $subFiltered->sum('usaha_realisasi') + $subFiltered->sum('ruta_realisasi');
                    $pct = $target > 0 ? ($realisasi / $target) * 100 : 0;
                    return [
                        'id' => $p->id,
                        'name' => $p->nama,
                        'target' => $target,
                        'realisasi' => $realisasi,
                        'percentage' => round($pct, 2),
                        'color' => $this->getProgressColor($pct),
                    ];
                });
            } else {
                $subFiltered = $filtered->filter(fn($a) => (int)$a->pml_id === (int)$this->drillPmlId);
                $drillData = $subFiltered->map(function ($a) {
                    return [
                        'id' => $a->subsls->idsubsls,
                        'name' => 'SubSLS ' . $a->subsls->kdsubsls . ' (PCL: ' . ($a->pcl->nama ?? '-') . ')',
                        'target' => $a->target_usaha,
                        'realisasi' => $a->usaha_realisasi,
                        'percentage' => round($a->progress_pct, 2),
                        'color' => $this->getProgressColor($a->progress_pct),
                    ];
                });
            }
        } else {
            // monitoringLevel = kec
            if ($this->drillKecId && !$this->drillDesaId) {
                $villages = Village::where('idkec', $this->drillKecId)->orderBy('nmdesa')->get();
                $drillData = $villages->map(function ($v) use ($filtered) {
                    $subFiltered = $filtered->filter(fn($a) => $a->subsls->sls->iddesa === $v->iddesa);
                    $target = $subFiltered->sum('target_usaha');
                    $realisasi = $subFiltered->sum('usaha_realisasi') + $subFiltered->sum('ruta_realisasi');
                    $pct = $target > 0 ? ($realisasi / $target) * 100 : 0;
                    return [
                        'id' => $v->iddesa,
                        'name' => $v->nmdesa,
                        'target' => $target,
                        'realisasi' => $realisasi,
                        'percentage' => round($pct, 2),
                        'color' => $this->getProgressColor($pct),
                    ];
                });
            } elseif ($this->drillKecId && $this->drillDesaId && !$this->drillSlsId) {
                $slss = Sls::where('iddesa', $this->drillDesaId)->orderBy('nmsls')->get();
                $drillData = $slss->map(function ($s) use ($filtered) {
                    $subFiltered = $filtered->filter(fn($a) => $a->subsls->idsls === $s->idsls);
                    $target = $subFiltered->sum('target_usaha');
                    $realisasi = $subFiltered->sum('usaha_realisasi') + $subFiltered->sum('ruta_realisasi');
                    $pct = $target > 0 ? ($realisasi / $target) * 100 : 0;
                    return [
                        'id' => $s->idsls,
                        'name' => $s->nmsls,
                        'target' => $target,
                        'realisasi' => $realisasi,
                        'percentage' => round($pct, 2),
                        'color' => $this->getProgressColor($pct),
                    ];
                });
            } elseif ($this->drillKecId && $this->drillDesaId && $this->drillSlsId) {
                $subFiltered = $filtered->filter(fn($a) => $a->subsls->idsls === $this->drillSlsId);
                $drillData = $subFiltered->map(function ($a) {
                    return [
                        'id' => $a->subsls->idsubsls,
                        'name' => 'SubSLS ' . $a->subsls->kdsubsls . ' (PCL: ' . ($a->pcl->nama ?? '-') . ')',
                        'target' => $a->target_usaha,
                        'realisasi' => $a->usaha_realisasi,
                        'percentage' => round($a->progress_pct, 2),
                        'color' => $this->getProgressColor($a->progress_pct),
                    ];
                });
            } else {
                $districts = $this->kecFilter 
                    ? District::where('idkec', $this->kecFilter)->orderBy('nmkec')->get() 
                    : District::orderBy('nmkec')->get();

                $drillData = $districts->map(function ($d) use ($filtered) {
                    $subFiltered = $filtered->filter(fn($a) => $a->subsls->sls->village->idkec === $d->idkec);
                    $target = $subFiltered->sum('target_usaha');
                    $realisasi = $subFiltered->sum('usaha_realisasi') + $subFiltered->sum('ruta_realisasi');
                    $pct = $target > 0 ? ($realisasi / $target) * 100 : 0;
                    return [
                        'id' => $d->idkec,
                        'name' => $d->nmkec,
                        'target' => $target,
                        'realisasi' => $realisasi,
                        'percentage' => round($pct, 2),
                        'color' => $this->getProgressColor($pct),
                    ];
                });
            }
        }

        return $drillData;
    }

    /**
     * Computed property for Drill-down Active Level.
     */
    #[Computed]
    public function drillLevel()
    {
        if ($this->monitoringLevel === 'desa') {
            if (!$this->drillDesaId) {
                return 'desa';
            } elseif ($this->drillDesaId && !$this->drillSlsId) {
                return 'sls';
            }
            return 'subsls';
        } elseif ($this->monitoringLevel === 'pml') {
            if (!$this->drillPmlId) {
                return 'pml';
            }
            return 'subsls';
        }

        // monitoringLevel = kec
        if ($this->drillKecId && !$this->drillDesaId) {
            return 'desa';
        } elseif ($this->drillKecId && $this->drillDesaId && !$this->drillSlsId) {
            return 'sls';
        } elseif ($this->drillKecId && $this->drillDesaId && $this->drillSlsId) {
            return 'subsls';
        }
        return 'kec';
    }

    /**
     * Computed property for Histogram aggregations.
     */
    #[Computed]
    public function histogramData()
    {
        if (!in_array($this->monitoringLevel, ['kec', 'desa', 'pml'])) {
            $this->monitoringLevel = 'kec';
        }

        $filtered = $this->filteredAssignments;
        $monitoringService = app(MonitoringService::class);

        if ($this->monitoringLevel === 'kec') {
            return $monitoringService->getProgressByKecamatan($filtered);
        } elseif ($this->monitoringLevel === 'desa') {
            return $monitoringService->getProgressByDesa($filtered);
        } elseif ($this->monitoringLevel === 'pml') {
            return $monitoringService->getProgressByPml($filtered);
        }

        return collect();
    }

    /**
     * Computed property for Daily Trend categories and series.
     */
    #[Computed]
    public function trendData()
    {
        return $this->getTrendTimelineData();
    }

    protected function getTrendTimelineData()
    {
        $trendAssignments = \App\Models\Assignment::with(['subsls.sls.village.district', 'dailyReports', 'pcl', 'pml'])->get();

        if ($this->trendEntityType === 'kec' && $this->trendEntityId) {
            $trendAssignments = $trendAssignments->filter(fn($a) => $a->subsls->sls->village->idkec === $this->trendEntityId);
        } elseif ($this->trendEntityType === 'desa' && $this->trendEntityId) {
            $trendAssignments = $trendAssignments->filter(fn($a) => $a->subsls->sls->village->iddesa === $this->trendEntityId);
        } elseif ($this->trendEntityType === 'subsls' && $this->trendEntityId) {
            $trendAssignments = $trendAssignments->filter(fn($a) => $a->idsubsls === $this->trendEntityId);
        } elseif ($this->trendEntityType === 'pcl' && $this->trendEntityId) {
            $trendAssignments = $trendAssignments->filter(fn($a) => (int)$a->pcl_id === (int)$this->trendEntityId);
        } elseif ($this->trendEntityType === 'pml' && $this->trendEntityId) {
            $trendAssignments = $trendAssignments->filter(fn($a) => (int)$a->pml_id === (int)$this->trendEntityId);
        }

        $dailyReports = $trendAssignments->flatMap(fn($a) => $a->dailyReports);

        if ($this->trendStartDate) {
            $dailyReports = $dailyReports->filter(fn($r) => Carbon::parse($r->report_date)->format('Y-m-d') >= $this->trendStartDate);
        }
        if ($this->trendEndDate) {
            $dailyReports = $dailyReports->filter(fn($r) => Carbon::parse($r->report_date)->format('Y-m-d') <= $this->trendEndDate);
        }

        $dailyData = $dailyReports->groupBy(function ($report) {
            return Carbon::parse($report->report_date)->format('Y-m-d');
        })
        ->map(function ($reports, $date) {
            return [
                'date' => $date,
                'usaha_today' => $reports->sum('usaha_today'),
                'ruta_today' => $reports->sum('ruta_today'),
            ];
        })
        ->sortBy('date');

        $categories = [];
        $usahaSeries = [];
        $rutaSeries = [];

        $start = Carbon::parse($this->trendStartDate ?: Carbon::now()->subDays(13)->format('Y-m-d'));
        $end = Carbon::parse($this->trendEndDate ?: Carbon::now()->format('Y-m-d'));

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dateStr = $date->format('Y-m-d');
            $categories[] = $dateStr;

            $dayData = $dailyData->get($dateStr);
            $usahaSeries[] = $dayData ? $dayData['usaha_today'] : 0;
            $rutaSeries[] = $dayData ? $dayData['ruta_today'] : 0;
        }

        return [
            'categories' => $categories,
            'usaha_series' => $usahaSeries,
            'ruta_series' => $rutaSeries,
        ];
    }

    protected function getProgressColor(float $percentage): string
    {
        if ($percentage < 50) {
            return 'red';
        } elseif ($percentage < 80) {
            return 'yellow';
        } else {
            return 'green';
        }
    }

    public function resetFilters()
    {
        $this->monitoringLevel = 'kec';
        $this->dateFilter = '';
        $this->kecFilter = '';
        $this->desaFilter = '';
        $this->slsFilter = '';
        $this->pclFilter = '';
        $this->pmlFilter = '';
        $this->keywordFilter = '';
        $this->updateVillagesList();
        $this->updateSlsList();
    }

    public function resetTrend()
    {
        $this->trendEntityType = 'kab';
        $this->trendEntityId = '';
        $this->trendStartDate = Carbon::now()->subDays(13)->format('Y-m-d');
        $this->trendEndDate = Carbon::now()->format('Y-m-d');
    }

    /**
     * Computed property for overall statistics.
     */
    #[Computed]
    public function stats()
    {
        $assignmentRepo = app(AssignmentRepository::class);
        $monitoringService = app(MonitoringService::class);
        
        $all = $assignmentRepo->getAllWithRelations();
        $mapped = $all->map(function ($a) {
            $reports = $a->dailyReports;
            $a->usaha_realisasi = $reports->sum('usaha_today');
            $a->ruta_realisasi = $reports->sum('ruta_today');
            $a->progress_pct = $a->target_usaha > 0 ? (($a->usaha_realisasi + $a->ruta_realisasi) / $a->target_usaha) * 100 : 0;
            return $a;
        });
        return $monitoringService->getOverallStats($mapped);
    }

    public function render(AssignmentRepository $assignmentRepo, MonitoringService $monitoringService)
    {
        // 1. GLOBAL Summary Cards
        $stats = $this->stats;

        // Load instance-level cached Computed Properties
        $chartProgress = $this->histogramData;
        $drillData = $this->drillData;
        $drillLevel = $this->drillLevel;
        $trendTimeline = $this->trendData;

        // Rankings (Top 10 Tertinggi & Terendah)
        $topProgress = $chartProgress->sortByDesc('percentage')->take(10)->values();
        $lowestProgress = $chartProgress->sortBy('percentage')->take(10)->values();

        $levelLabel = $this->monitoringLevel === 'kec' ? 'Kecamatan' : ($this->monitoringLevel === 'desa' ? 'Desa' : 'PML');

        // Dispatch updated chart options to frontend Alpine chart handler
        $this->dispatch('chart-data-updated', [
            'kecCategories' => $chartProgress->pluck('name')->toArray(),
            'kecSeries' => $chartProgress->pluck('percentage')->toArray(),
            'kecTargets' => $chartProgress->pluck('target')->toArray(),
            'kecRealisasis' => $chartProgress->pluck('realisasi')->toArray(),
            'levelLabel' => $levelLabel,
            'timelineCategories' => $trendTimeline['categories'],
            'timelineUsaha' => $trendTimeline['usaha_series'],
            'timelineRuta' => $trendTimeline['ruta_series'],
        ]);

        $isOperational = Carbon::now()->greaterThanOrEqualTo(Carbon::parse(config('semon.target_date', '2026-06-15T00:00:00+07:00')));

        return view('livewire.monitoring-dashboard', [
            'stats' => $stats,
            'chartProgress' => $chartProgress,
            'topProgress' => $topProgress,
            'lowestProgress' => $lowestProgress,
            'drillData' => $drillData,
            'drillLevel' => $drillLevel,
            'drillBreadcrumbs' => $this->getDrillBreadcrumbs(),
            'trendTimeline' => $trendTimeline,
            'hasOperationalData' => \App\Models\DailyReport::exists(),
            'isOperational' => $isOperational,
        ]);
    }

    protected function getDrillBreadcrumbs(): array
    {
        if ($this->monitoringLevel === 'desa') {
            $crumbs = [['label' => 'Desa', 'action' => 'resetDrillDesa']];
            if ($this->drillDesaId) {
                $desaName = Village::where('iddesa', $this->drillDesaId)->first()->nmdesa ?? 'N/A';
                $crumbs[] = ['label' => $desaName, 'action' => 'resetDrillSls'];
            }
            if ($this->drillSlsId) {
                $slsName = Sls::where('idsls', $this->drillSlsId)->first()->nmsls ?? 'N/A';
                $crumbs[] = ['label' => $slsName, 'action' => ''];
            }
            return $crumbs;
        } elseif ($this->monitoringLevel === 'pml') {
            $crumbs = [['label' => 'PML', 'action' => 'resetDrillPml']];
            if ($this->drillPmlId) {
                $pmlName = Pml::where('id', $this->drillPmlId)->first()->nama ?? 'N/A';
                $crumbs[] = ['label' => $pmlName, 'action' => ''];
            }
            return $crumbs;
        }

        // monitoringLevel = kec
        $crumbs = [['label' => 'Kecamatan', 'action' => 'resetDrillKec']];
        if ($this->drillKecId) {
            $kecName = District::where('idkec', $this->drillKecId)->first()->nmkec ?? 'N/A';
            $crumbs[] = ['label' => $kecName, 'action' => 'resetDrillDesa'];
        }
        if ($this->drillDesaId) {
            $desaName = Village::where('iddesa', $this->drillDesaId)->first()->nmdesa ?? 'N/A';
            $crumbs[] = ['label' => $desaName, 'action' => 'resetDrillSls'];
        }
        if ($this->drillSlsId) {
            $slsName = Sls::where('idsls', $this->drillSlsId)->first()->nmsls ?? 'N/A';
            $crumbs[] = ['label' => $slsName, 'action' => ''];
        }
        return $crumbs;
    }
}
