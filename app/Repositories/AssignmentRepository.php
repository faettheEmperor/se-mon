<?php

namespace App\Repositories;

use App\Models\Assignment;
use Illuminate\Database\Eloquent\Collection;

class AssignmentRepository
{
    /**
     * Get all assignments with fully eager-loaded relations to avoid N+1 query issues.
     */
    public function getAllWithRelations(): Collection
    {
        return Assignment::with([
            'pcl',
            'pml',
            'subsls.sls.village.district',
            'dailyReports'
        ])->get();
    }

    /**
     * Get assignments assigned to a specific PCL with eager-loaded relations.
     */
    public function getByPcl(int $pclId): Collection
    {
        return Assignment::where('pcl_id', $pclId)
            ->with([
                'pcl',
                'pml',
                'subsls.sls.village.district',
                'dailyReports'
            ])
            ->get();
    }

    /**
     * Get assignments assigned to a specific PML with eager-loaded relations.
     */
    public function getByPml(int $pmlId): Collection
    {
        return Assignment::where('pml_id', $pmlId)
            ->with([
                'pcl',
                'pml',
                'subsls.sls.village.district'
            ])
            ->withSum('dailyReports as total_realisasi_usaha', 'usaha_today')
            ->withSum('dailyReports as total_realisasi_ruta', 'ruta_today')
            ->get();
    }

    /**
     * Get unique PCLs associated with a PML's assignments.
     */
    public function getPclsByPml(int $pmlId): Collection
    {
        return \App\Models\Pcl::whereIn('id', function ($query) use ($pmlId) {
            $query->select('pcl_id')
                ->from('assignments')
                ->where('pml_id', $pmlId);
        })
        ->orderBy('nama')
        ->get();
    }

    /**
     * Get the base query builder for monitoring details with calculated aggregates.
     */
    public function getMonitoringQueryBuilder(): \Illuminate\Database\Eloquent\Builder
    {
        $pctExpression = 'CASE WHEN target_usaha > 0 THEN ((SELECT COALESCE(SUM(usaha_today + ruta_today), 0) FROM daily_reports WHERE daily_reports.assignment_id = assignments.id) * 100.0 / target_usaha) ELSE 0 END';

        return Assignment::query()
            ->select('assignments.*')
            ->selectRaw('(SELECT COALESCE(SUM(usaha_today), 0) FROM daily_reports WHERE daily_reports.assignment_id = assignments.id) as usaha_realisasi')
            ->selectRaw('(SELECT COALESCE(SUM(ruta_today), 0) FROM daily_reports WHERE daily_reports.assignment_id = assignments.id) as ruta_realisasi')
            ->selectRaw('(SELECT MAX(report_date) FROM daily_reports WHERE daily_reports.assignment_id = assignments.id) as last_report_date')
            ->selectRaw($pctExpression . ' as progress_pct')
            ->join('subsls', 'assignments.idsubsls', '=', 'subsls.idsubsls')
            ->join('sls', 'subsls.idsls', '=', 'sls.idsls')
            ->join('villages', 'sls.iddesa', '=', 'villages.iddesa')
            ->join('districts', 'villages.idkec', '=', 'districts.idkec')
            ->join('pcls', 'assignments.pcl_id', '=', 'pcls.id')
            ->join('pmls', 'assignments.pml_id', '=', 'pmls.id')
            ->with(['pcl', 'pml', 'subsls.sls.village.district']);
    }

    /**
     * Find assignment by ID.
     */
    public function find(int $id): ?Assignment
    {
        return Assignment::with([
            'pcl',
            'pml',
            'subsls.sls.village.district',
            'dailyReports'
        ])->find($id);
    }
}
