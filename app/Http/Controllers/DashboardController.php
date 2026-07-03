<?php

namespace App\Http\Controllers;

use App\Repositories\AssignmentRepository;
use App\Repositories\DailyReportRepository;
use App\Services\MonitoringService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected AssignmentRepository $assignmentRepo;
    protected DailyReportRepository $reportRepo;
    protected MonitoringService $monitoringService;

    public function __construct(
        AssignmentRepository $assignmentRepo,
        DailyReportRepository $reportRepo,
        MonitoringService $monitoringService
    ) {
        $this->assignmentRepo = $assignmentRepo;
        $this->reportRepo = $reportRepo;
        $this->monitoringService = $monitoringService;
    }

    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'pcl') {
            $pcl = $user->pcl;
            if (!$pcl) {
                abort(403, 'PCL profile not found for this account.');
            }

            // Get assignments for this PCL only, map computed progress fields
            $assignments = $this->assignmentRepo->getByPcl($pcl->id)->map(function ($a) {
                $a->usaha_realisasi = $a->dailyReports->sum('usaha_today');
                $a->ruta_realisasi  = $a->dailyReports->sum('ruta_today');
                $a->progress_pct    = $a->target_usaha > 0
                    ? (($a->usaha_realisasi + $a->ruta_realisasi) / $a->target_usaha) * 100
                    : 0;
                return $a;
            });

            $stats = $this->monitoringService->getOverallStats($assignments);

            // Get recent history
            $recentReports = $this->reportRepo->getHistoryByPcl($pcl->id, 5);

            $isPcl = true;
            $isPml = false;
            $isAdmin = false;

            return view('pcl.dashboard', compact('stats', 'recentReports', 'assignments', 'isPcl', 'isPml', 'isAdmin'));
        }

        if ($user->role === 'pml') {
            $pml = $user->pml;
            if (!$pml) {
                abort(403, 'PML profile not found for this account.');
            }

            // Retrieve assignments using getByPml (uses withSum optimization)
            $assignments = $this->assignmentRepo->getByPml($pml->id)->map(function ($a) {
                $a->usaha_realisasi = (int) ($a->total_realisasi_usaha ?? 0);
                $a->ruta_realisasi  = (int) ($a->total_realisasi_ruta ?? 0);
                $a->progress_pct    = $a->target_usaha > 0
                    ? (($a->usaha_realisasi + $a->ruta_realisasi) / $a->target_usaha) * 100
                    : 0;
                return $a;
            });

            // Group assignments by pcl_id
            $assignmentsGrouped = $assignments->groupBy('pcl_id');

            // PML aggregate stats
            $totalTarget = $assignments->sum('target_usaha');
            $totalRealisasiUsaha = $assignments->sum('usaha_realisasi');
            $totalRealisasiRuta = $assignments->sum('ruta_realisasi');
            $percentage = $totalTarget > 0 ? (($totalRealisasiUsaha + $totalRealisasiRuta) / $totalTarget) * 100 : 0;

            $stats = [
                'total_target' => $totalTarget,
                'total_realisasi_usaha' => $totalRealisasiUsaha,
                'total_realisasi_ruta' => $totalRealisasiRuta,
                'percentage' => round($percentage, 2),
                'progress_color' => $percentage < 50 ? 'red' : ($percentage < 80 ? 'yellow' : 'green'),
            ];

            // Get recent reports only from supervised PCL
            $recentReports = $this->reportRepo->getRecentHistoryByPml($pml->id, 5);

            $isPcl = false;
            $isPml = true;
            $isAdmin = false;

            return view('pcl.dashboard', compact('stats', 'recentReports', 'assignments', 'assignmentsGrouped', 'isPcl', 'isPml', 'isAdmin'));
        }

        // Admin Flow
        // Fetch all assignments with full eager loading to avoid N+1 queries
        $assignments = $this->assignmentRepo->getAllWithRelations();
        $stats = $this->monitoringService->getOverallStats($assignments);

        $isPcl = false;
        $isPml = false;
        $isAdmin = true;

        return view('admin.dashboard', compact('stats', 'isPcl', 'isPml', 'isAdmin'));
    }

    public function monitoring(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'pcl') {
            abort(403, 'Unauthorized. PCL tidak memiliki akses ke halaman detail monitoring.');
        }

        return view('admin.monitoring');
    }
}
