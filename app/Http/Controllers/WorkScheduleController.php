<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkScheduleController extends Controller
{
    /**
     * Show schedules for a given month/year (defaults to current).
     */
    public function index(Request $request)
    {
        $year  = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        // Get all drivers and guides
        $users = User::whereIn('role', ['driver','guide'])
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        // Eager load schedules for this month for those users
        $schedules = WorkSchedule::whereIn('user_id', $users->pluck('id')->toArray())
            ->where('month', $month)
            ->where('year', $year)
            ->get()
            ->keyBy('user_id');

        return view('work_schedules.index', compact('users','schedules','month','year'));
    }

    /**
     * Generate default schedules for all drivers/guides for the selected month.
     * Default total_hours is taken from user's monthly_work_limit or fallback.
     */
    public function generateForAll(Request $request)
    {
        $data = $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'month'=> 'required|integer|min:1|max:12',
        ]);

        $year  = (int) $data['year'];
        $month = (int) $data['month'];

        $users = User::whereIn('role', ['driver','guide'])->get();

        DB::transaction(function() use ($users, $year, $month) {
            foreach ($users as $user) {
                WorkSchedule::updateOrCreate(
                    ['user_id'=>$user->id,'month'=>$month,'year'=>$year],
                    [
                        'total_hours' => $user->monthly_work_limit ?? 200,
                        // keep used_hours if exists else set to current used_hours from user model or 0
                        'used_hours' => function ($ws) use ($user) {
                            // this closure isn't executed by updateOrCreate; we'll set below if needed
                            return $user->used_hours ?? 0;
                        }
                    ]
                );
                // after updateOrCreate, ensure used_hours not overwritten incorrectly:
                $ws = WorkSchedule::firstWhere(['user_id'=>$user->id,'month'=>$month,'year'=>$year]);
                if ($ws && ($ws->used_hours === null)) {
                    $ws->used_hours = $user->used_hours ?? 0;
                    $ws->save();
                }
            }
        });

        return redirect()->route('work-schedules.index', ['year'=>$year,'month'=>$month])
            ->with('success','Work schedules dibuat / diperbarui untuk semua driver & guide.');
    }

    /**
     * Bulk update schedules: expects inputs like schedules[user_id] = total_hours
     */
    public function bulkUpdate(Request $request)
    {
        $data = $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'month'=> 'required|integer|min:1|max:12',
            'schedules' => 'required|array',
            'schedules.*' => 'nullable|integer|min:0'
        ]);

        $year  = (int) $data['year'];
        $month = (int) $data['month'];
        $inputSchedules = $data['schedules'];

        DB::transaction(function() use ($inputSchedules, $month, $year) {
            foreach ($inputSchedules as $userId => $totalHours) {
                $user = User::find($userId);
                if (!$user || !in_array($user->role, ['driver','guide'])) continue;

                $ws = WorkSchedule::firstOrNew(['user_id'=>$user->id,'month'=>$month,'year'=>$year]);

                // if used_hours > new total_hours, cap used_hours to total_hours
                $ws->total_hours = $totalHours ?? ($user->monthly_work_limit ?? 200);
                $ws->used_hours = min($ws->used_hours ?? 0, $ws->total_hours);
                $ws->save();
            }
        });

        return redirect()->route('work-schedules.index', ['year'=>$year,'month'=>$month])
            ->with('success','Work schedules berhasil diperbarui.');
    }

    /**
     * Edit single schedule (form)
     */
    public function edit(WorkSchedule $workSchedule)
    {
        $workSchedule->load('user');
        return view('work_schedules.edit', compact('workSchedule'));
    }

    /**
     * Update single schedule
     */
    public function update(Request $request, WorkSchedule $workSchedule)
    {
        $data = $request->validate([
            'total_hours' => 'required|integer|min:0',
            'used_hours'  => 'nullable|integer|min:0'
        ]);

        $workSchedule->total_hours = $data['total_hours'];
        if (isset($data['used_hours'])) {
            $workSchedule->used_hours = min($data['used_hours'], $data['total_hours']);
        } else {
            $workSchedule->used_hours = min($workSchedule->used_hours ?? 0, $data['total_hours']);
        }
        $workSchedule->save();

        return redirect()->route('work-schedules.index', ['year'=>$workSchedule->year,'month'=>$workSchedule->month])
            ->with('success','Schedule diperbarui.');
    }

    /**
     * Reset used_hours to zero for selected users or for all in the month.
     */
    public function resetUsedHours(Request $request)
    {
        $data = $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'month'=> 'required|integer|min:1|max:12',
            'user_ids' => 'nullable|array'
        ]);

        $year  = (int)$data['year'];
        $month = (int)$data['month'];

        $query = WorkSchedule::where('year',$year)->where('month',$month);
        if (!empty($data['user_ids'])) {
            $query->whereIn('user_id',$data['user_ids']);
        }

        $query->update(['used_hours' => 0]);

        return redirect()->route('work-schedules.index', ['year'=>$year,'month'=>$month])
            ->with('success','Used hours di-reset menjadi 0.');
    }
}
