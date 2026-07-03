<?php
 
namespace App\Services;
 
use App\Models\District;
use App\Models\Village;
use App\Models\Sls;
use App\Models\Pcl;
use App\Models\Pml;
use Illuminate\Support\Collection;
use Carbon\Carbon;
 
class MonitoringService
{
    /**
     * Get overall statistics for dashboard cards.
     */
    public function getOverallStats(Collection $assignments): array
    {
        $totalTarget = $assignments->sum('target_usaha');
        $totalRealisasiUsaha = $assignments->sum('usaha_realisasi');
        $totalRealisasiRuta = $assignments->sum('ruta_realisasi');
 
        $percentage = $totalTarget > 0 ? (($totalRealisasiUsaha + $totalRealisasiRuta) / $totalTarget) * 100 : 0;
 
        return [
            'total_target' => $totalTarget,
            'total_realisasi_usaha' => $totalRealisasiUsaha,
            'total_realisasi_ruta' => $totalRealisasiRuta,
            'percentage' => round($percentage, 2),
            'progress_color' => $this->getProgressColor($percentage),
            'count_pcl' => Pcl::count(),
            'count_pml' => Pml::count(),
            'count_sls' => Sls::count(),
            'count_subsls' => $assignments->count(),
        ];
    }
 
    /**
     * Get progress breakdown by Districts (Kabupaten).
     */
    public function getProgressByDistrict(Collection $assignments): Collection
    {
        return $assignments->groupBy('subsls.sls.village.district.idkab')
            ->map(function ($group, $idkab) {
                $name = $group->first()->subsls->sls->village->district->nmkab ?? 'N/A';
                $target = $group->sum('target_usaha');
                $realisasi = $group->sum('usaha_realisasi') + $group->sum('ruta_realisasi');
                $percentage = $target > 0 ? ($realisasi / $target) * 100 : 0;
 
                return [
                    'id' => $idkab,
                    'name' => $name,
                    'target' => $target,
                    'realisasi' => $realisasi,
                    'percentage' => round($percentage, 2),
                    'color' => $this->getProgressColor($percentage),
                ];
            })->values();
    }
 
    /**
     * Get progress breakdown by Kecamatan.
     */
    public function getProgressByKecamatan(Collection $assignments): Collection
    {
        return $assignments->groupBy('subsls.sls.village.idkec')
            ->map(function ($group, $idkec) {
                $name = $group->first()->subsls->sls->village->district->nmkec ?? 'N/A';
                $target = $group->sum('target_usaha');
                $realisasi = $group->sum('usaha_realisasi') + $group->sum('ruta_realisasi');
                $percentage = $target > 0 ? ($realisasi / $target) * 100 : 0;
 
                return [
                    'id' => $idkec,
                    'name' => $name,
                    'target' => $target,
                    'realisasi' => $realisasi,
                    'percentage' => round($percentage, 2),
                    'color' => $this->getProgressColor($percentage),
                ];
            })->values();
    }
 
    /**
     * Get progress breakdown by Desa.
     */
    public function getProgressByDesa(Collection $assignments): Collection
    {
        return $assignments->groupBy('subsls.sls.village.iddesa')
            ->map(function ($group, $iddesa) {
                $name = $group->first()->subsls->sls->village->nmdesa ?? 'N/A';
                $target = $group->sum('target_usaha');
                $realisasi = $group->sum('usaha_realisasi') + $group->sum('ruta_realisasi');
                $percentage = $target > 0 ? ($realisasi / $target) * 100 : 0;
 
                return [
                    'id' => $iddesa,
                    'name' => $name,
                    'target' => $target,
                    'realisasi' => $realisasi,
                    'percentage' => round($percentage, 2),
                    'color' => $this->getProgressColor($percentage),
                ];
            })->values()->sortByDesc('percentage')->values();
    }
 
    /**
     * Get progress breakdown by SLS.
     */
    public function getProgressBySls(Collection $assignments): Collection
    {
        return $assignments->groupBy('subsls.idsls')
            ->map(function ($group, $idsls) {
                $name = $group->first()->subsls->sls->nmsls ?? 'N/A';
                $target = $group->sum('target_usaha');
                $realisasi = $group->sum('usaha_realisasi') + $group->sum('ruta_realisasi');
                $percentage = $target > 0 ? ($realisasi / $target) * 100 : 0;
 
                return [
                    'id' => $idsls,
                    'name' => $name,
                    'target' => $target,
                    'realisasi' => $realisasi,
                    'percentage' => round($percentage, 2),
                    'color' => $this->getProgressColor($percentage),
                ];
            })->values()->sortByDesc('percentage')->values();
    }
 
    /**
     * Get progress breakdown by PCL.
     */
    public function getProgressByPcl(Collection $assignments): Collection
    {
        return $assignments->groupBy('pcl_id')
            ->map(function ($group, $pclId) {
                $name = $group->first()->pcl->nama ?? 'N/A';
                $target = $group->sum('target_usaha');
                $realisasi = $group->sum('usaha_realisasi') + $group->sum('ruta_realisasi');
                $percentage = $target > 0 ? ($realisasi / $target) * 100 : 0;
 
                return [
                    'id' => $pclId,
                    'name' => $name,
                    'target' => $target,
                    'realisasi' => $realisasi,
                    'percentage' => round($percentage, 2),
                    'color' => $this->getProgressColor($percentage),
                ];
            })->values()->sortByDesc('percentage')->values();
    }
 
    /**
     * Get progress breakdown by PML.
     */
    public function getProgressByPml(Collection $assignments): Collection
    {
        return $assignments->groupBy('pml_id')
            ->map(function ($group, $pmlId) {
                $name = $group->first()->pml->nama ?? 'N/A';
                $target = $group->sum('target_usaha');
                $realisasi = $group->sum('usaha_realisasi') + $group->sum('ruta_realisasi');
                $percentage = $target > 0 ? ($realisasi / $target) * 100 : 0;
 
                return [
                    'id' => $pmlId,
                    'name' => $name,
                    'target' => $target,
                    'realisasi' => $realisasi,
                    'percentage' => round($percentage, 2),
                    'color' => $this->getProgressColor($percentage),
                ];
            })->values()->sortByDesc('percentage')->values();
    }
 
    /**
     * Get progress breakdown by SubSLS.
     */
    public function getProgressBySubsls(Collection $assignments): Collection
    {
        return $assignments->groupBy('idsubsls')
            ->map(function ($group, $idsubsls) {
                $name = 'SubSLS ' . ($group->first()->subsls->kdsubsls ?? $idsubsls);
                $target = $group->sum('target_usaha');
                $realisasi = $group->sum('usaha_realisasi') + $group->sum('ruta_realisasi');
                $percentage = $target > 0 ? ($realisasi / $target) * 100 : 0;
 
                return [
                    'id' => $idsubsls,
                    'name' => $name,
                    'target' => $target,
                    'realisasi' => $realisasi,
                    'percentage' => round($percentage, 2),
                    'color' => $this->getProgressColor($percentage),
                ];
            })->values()->sortByDesc('percentage')->values();
    }
 
    /**
     * Get daily progress timeline for ApexCharts.
     */
    public function getDailyProgressTimeline(Collection $assignments, string $dateFilter = ''): array
    {
        $dailyReports = $assignments->flatMap(function ($a) use ($dateFilter) {
            $reports = $a->dailyReports;
            if ($dateFilter) {
                $reports = $reports->filter(function ($r) use ($dateFilter) {
                    return Carbon::parse($r->report_date)->format('Y-m-d') === $dateFilter;
                });
            }
            return $reports;
        });
 
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
 
        return [
            'categories' => $dailyData->keys()->toArray(),
            'usaha_series' => $dailyData->pluck('usaha_today')->toArray(),
            'ruta_series' => $dailyData->pluck('ruta_today')->toArray(),
        ];
    }
 
    /**
     * Utility method to get progress color based on requirements:
     * - 0 - 49%: Merah
     * - 50 - 79%: Kuning
     * - 80 - 100%: Hijau
     */
    public function getProgressColor(float $percentage): string
    {
        if ($percentage < 50) {
            return 'red';
        } elseif ($percentage < 80) {
            return 'yellow';
        } else {
            return 'green';
        }
    }
}
