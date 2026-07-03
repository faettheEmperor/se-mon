<?php

namespace App\Http\Controllers;

use App\Models\Pcl;
use App\Models\Pml;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LandingApiController extends Controller
{
    /**
     * Fetch overall aggregated statistics for landing page dashboard, cached for 30s.
     */
    public function getLandingStats()
    {
        $stats = Cache::remember('landing_stats', 30, function () {
            // Retrieve targets and sum of report realisasis in a highly optimized way
            $assignments = DB::table('assignments')
                ->select('target_usaha')
                ->selectRaw('(SELECT COALESCE(SUM(usaha_today + ruta_today), 0) FROM daily_reports WHERE daily_reports.assignment_id = assignments.id) as realisasi_lapangan')
                ->get();

            $totalTarget = $assignments->sum('target_usaha');
            $totalRealisasiLapangan = $assignments->sum('realisasi_lapangan');
            $progress = $totalTarget > 0 ? round(($totalRealisasiLapangan / $totalTarget) * 100, 2) : 0.0;

            $countPcl = Pcl::count();
            $countPml = Pml::count();

            return [
                'total_usaha' => (int)$totalTarget,
                'realisasi' => (int)$totalRealisasiLapangan,
                'progress' => (float)$progress,
                'subsls' => 751, // fixed as requested
                'pcl' => (int)$countPcl,
                'pml' => (int)$countPml
            ];
        });

        return response()->json($stats);
    }

    /**
     * Read Kecamatan boundaries GeoJSON and inject live progress aggregates by idkec.
     */
    public function getMapData()
    {
        $geojsonPath = base_path('Final_Kec_202512102.geojson');
        if (!file_exists($geojsonPath)) {
            return response()->json(['error' => 'GeoJSON file not found.'], 404);
        }

        $geojson = json_decode(file_get_contents($geojsonPath), true);
        if (!$geojson) {
            return response()->json(['error' => 'Invalid GeoJSON.'], 500);
        }

        // Fetch live progress aggregates grouped by Kecamatan
        $progressData = DB::table('assignments')
            ->select('districts.idkec')
            ->selectRaw('SUM(assignments.target_usaha) as target')
            ->selectRaw('COALESCE(SUM(reports.usaha_today + reports.ruta_today), 0) as realisasi')
            ->join('subsls', 'assignments.idsubsls', '=', 'subsls.idsubsls')
            ->join('sls', 'subsls.idsls', '=', 'sls.idsls')
            ->join('villages', 'sls.iddesa', '=', 'villages.iddesa')
            ->join('districts', 'villages.idkec', '=', 'districts.idkec')
            ->leftJoin('daily_reports as reports', 'assignments.id', '=', 'reports.assignment_id')
            ->groupBy('districts.idkec')
            ->get()
            ->keyBy('idkec');

        foreach ($geojson['features'] as &$feature) {
            $idkec = $feature['properties']['idkec'] ?? null;
            $data = $progressData->get($idkec);
            
            $target = $data ? (int)$data->target : 0;
            $realisasi = $data ? (int)$data->realisasi : 0;
            $progress = $target > 0 ? round(($realisasi / $target) * 100, 2) : 0.0;

            $feature['properties']['target'] = $target;
            $feature['properties']['realisasi'] = $realisasi;
            $feature['properties']['progress'] = $progress;
        }

        return response()->json($geojson);
    }

    /**
     * Fetch desa/village breakdown data inside a clicked Kecamatan.
     */
    public function getKecamatanBreakdown($idkec)
    {
        $breakdown = DB::table('assignments')
            ->select('villages.nmdesa')
            ->selectRaw('SUM(assignments.target_usaha) as target')
            ->selectRaw('COALESCE(SUM(reports.usaha_today + reports.ruta_today), 0) as realisasi')
            ->join('subsls', 'assignments.idsubsls', '=', 'subsls.idsubsls')
            ->join('sls', 'subsls.idsls', '=', 'sls.idsls')
            ->join('villages', 'sls.iddesa', '=', 'villages.iddesa')
            ->leftJoin('daily_reports as reports', 'assignments.id', '=', 'reports.assignment_id')
            ->where('villages.idkec', $idkec)
            ->groupBy('villages.iddesa', 'villages.nmdesa')
            ->orderBy('villages.nmdesa')
            ->get()
            ->map(function ($row) {
                $row->progress = $row->target > 0 ? round(($row->realisasi / $row->target) * 100, 2) : 0.0;
                return $row;
            });

        return response()->json($breakdown);
    }

    /**
     * Fetch live progress aggregates grouped by Kecamatan, cached for 30s.
     */
    public function getMapProgress()
    {
        $progress = Cache::remember('map_progress', 30, function () {
            return DB::table('assignments')
                ->select('districts.idkec')
                ->selectRaw('SUM(assignments.target_usaha) as target')
                ->selectRaw('COALESCE(SUM(reports.usaha_today + reports.ruta_today), 0) as realisasi')
                ->join('subsls', 'assignments.idsubsls', '=', 'subsls.idsubsls')
                ->join('sls', 'subsls.idsls', '=', 'sls.idsls')
                ->join('villages', 'sls.iddesa', '=', 'villages.iddesa')
                ->join('districts', 'villages.idkec', '=', 'districts.idkec')
                ->leftJoin('daily_reports as reports', 'assignments.id', '=', 'reports.assignment_id')
                ->groupBy('districts.idkec')
                ->get()
                ->keyBy('idkec')
                ->map(function ($row) {
                    return [
                        'idkec' => $row->idkec,
                        'target' => (int)$row->target,
                        'realisasi' => (int)$row->realisasi,
                        'progress' => $row->target > 0 ? round(($row->realisasi / $row->target) * 100, 2) : 0.0
                    ];
                });
        });

        return response()->json($progress);
    }
}
