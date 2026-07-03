<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\DailyReport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class TestProgressSeeder extends Seeder
{
    /**
     * Helper to distribute a total value randomly into parts.
     */
    private function distribute(int $total, int $partsCount): array
    {
        if ($partsCount <= 0) return [];
        if ($total <= 0) return array_fill(0, $partsCount, 0);
        
        $parts = [];
        $remaining = $total;
        for ($i = 0; $i < $partsCount - 1; $i++) {
            $val = rand(0, (int) round($remaining / ($partsCount - $i) * 2));
            $parts[] = $val;
            $remaining -= $val;
        }
        $parts[] = $remaining;
        return $parts;
    }

    public function run(): void
    {
        if (!app()->environment('local')) {
            $this->command->error('Seeder hanya boleh dijalankan di local.');
            return;
        }

        $this->command->info('Generating smart distributed dummy progress...');

        DailyReport::truncate();

        $assignments = Assignment::all();

        if ($assignments->isEmpty()) {
            $this->command->error('Assignment kosong.');
            return;
        }

        // ==========================
        // KONFIGURASI
        // ==========================

        $days = 14;
        $targetUsaha = 4957;
        $targetRuta = 12350;
        
        $totalUsaha = 0;
        $totalRuta = 0;

        $remainingGlobalUsaha = $targetUsaha;
        $remainingGlobalRuta = $targetRuta;
        $remainingAssignmentsCount = $assignments->count();

        foreach ($assignments as $assignment) {
            // Calculate dynamic average based on remaining targets to ensure global target is met
            $avgUsaha = $remainingGlobalUsaha / max(1, $remainingAssignmentsCount);
            $avgRuta = $remainingGlobalRuta / max(1, $remainingAssignmentsCount);

            // Determine target values for this assignment
            $targetUsahaForThis = (int) min(
                $assignment->target_usaha,
                round($avgUsaha * rand(60, 140) / 100)
            );
            $targetRutaForThis = (int) round($avgRuta * rand(60, 140) / 100);

            // Generate random number of reporting days for this assignment
            $numReportDays = rand(3, 7);
            
            // Distribute targets across these days
            $usahaDistribution = $this->distribute($targetUsahaForThis, $numReportDays);
            $rutaDistribution = $this->distribute($targetRutaForThis, $numReportDays);

            // Pick random distinct days from the 14-day window
            $dayOffsets = range(0, $days - 1);
            shuffle($dayOffsets);
            $selectedDays = array_slice($dayOffsets, 0, $numReportDays);
            sort($selectedDays); // Keep chronological order

            for ($i = 0; $i < $numReportDays; $i++) {
                $uToday = $usahaDistribution[$i];
                $rToday = $rutaDistribution[$i];
                $dOffset = $selectedDays[$i];

                DailyReport::create([
                    'assignment_id' => $assignment->id,
                    'report_date' => Carbon::today()->subDays($dOffset),
                    'usaha_today' => $uToday,
                    'ruta_today' => $rToday,
                    'notes' => 'Dummy progress otomatis.',
                ]);

                $totalUsaha += $uToday;
                $totalRuta += $rToday;
            }

            $remainingGlobalUsaha -= $targetUsahaForThis;
            $remainingGlobalRuta -= $targetRutaForThis;
            $remainingAssignmentsCount--;
        }

        Cache::forget('kabupaten_stats');
        Cache::forget('landing_stats');
        Cache::forget('map_progress');

        $this->command->info('===============================');
        $this->command->info('Dummy selesai dibuat.');
        $this->command->info('Usaha : ' . number_format($totalUsaha));
        $this->command->info('Ruta   : ' . number_format($totalRuta));
        $this->command->info('===============================');
    }
}
