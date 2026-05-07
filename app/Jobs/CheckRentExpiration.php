<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\RentExpirationNotification;
use App\Models\RentManager;
use Illuminate\Support\Str;
use App\Models\RentCycle;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\QueryException;
use Throwable;

class CheckRentExpiration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $days_limit = 90;
        $now = now()->setTimezone(config('app.timezone'));

        \Log::info('CheckRentExpiration job is executing...');

        try {
            $rentManagersWithCycles = RentManager::where('rent_managers.is_active', true)
                ->whereNull('rent_managers.termination_date')
                ->whereNull('rent_managers.deleted_at')
                ->where('rent_cycles.status', '!=', 'expired')
                ->where('rent_managers.account_type', '!=', 'one-off') // only non one-off
                ->join('users', 'rent_managers.occupant_id', '=', 'users.id')
                ->join('apartment_units', 'rent_managers.apartment_unit_id', '=', 'apartment_units.id')
                ->join('apartments', 'apartment_units.apartment_id', '=', 'apartments.id')
                ->join('estate_managers', 'apartments.estate_manager_id', '=', 'estate_managers.id')
                ->leftJoin('rent_cycles', 'rent_cycles.rent_manager_id', '=', 'rent_managers.id')
                ->where(function ($query) use ($days_limit, $now) {
                    $query->where(function ($q) use ($days_limit, $now) {
                        $q->whereRaw('DATEDIFF(rent_cycles.cycle_end_date_server_time, ?) <= ?', [$now->toDateString(), $days_limit])
                          ->where('rent_cycles.is_paid', true);
                    })
                    ->orWhere('rent_cycles.is_paid', false);
                })
                ->select(
                    'rent_managers.id as manager_id',
                    'rent_managers.occupant_id',
                    'rent_managers.termination_date',
                    'rent_managers.apartment_unit_id',
                    'users.email as occupant_email',
                    'users.first_name as occupant_first_name',
                    'users.last_name as occupant_last_name',
                    'apartment_units.apartment_unit_name',
                    'apartments.location as apartment_location',
                    'apartments.address as apartment_address',
                    'estate_managers.estate_name as estate_manager_name',
                    'rent_cycles.id as cycle_id',
                    'rent_cycles.cycle_start_date',
                    'rent_cycles.cycle_end_date',
                    'rent_cycles.is_paid',
                    'rent_cycles.status as cycle_status',
                    'rent_cycles.cycle_start_date_server_time',
                    'rent_cycles.cycle_end_date_server_time',
                )
                ->get();

            foreach ($rentManagersWithCycles as $record) {
                if ($record->cycle_id) {
                    $lockKey = "rent-cycle-lock:{$record->cycle_id}";

                    Cache::lock($lockKey, 10)->block(5, function () use ($record, $days_limit, $now) {
                        try {
                            DB::transaction(function () use ($record, $days_limit, $now) {
                                $endDate = Carbon::parse($record->cycle_end_date_server_time, config('app.timezone'));
                                $daysRemaining = floor($now->diffInDays($endDate, false));
                                $cycle_end_date = Carbon::parse($record->cycle_end_date)->toFormattedDateString();

                                $email_data = [
                                    'name' => $record->occupant_first_name . ' ' . $record->occupant_last_name,
                                    'apartment_unit_name' => $record->apartment_unit_name,
                                    'apartment_location' => $record->apartment_location,
                                    'apartment_address' => $record->apartment_address,
                                    'estate_manager_name' => $record->estate_manager_name,
                                    'cycle_end_date' => $cycle_end_date,
                                    'days_remaining' => $daysRemaining,
                                    'email' => $record->occupant_email,
                                    'message' => '',
                                ];

                                if ($daysRemaining <= $days_limit && $record->is_paid && $daysRemaining > 0) {
                                    if (in_array($now->day, [14, 28])) {
                                        $email_data['message'] = 'Your rent payment is due on ' . $cycle_end_date . '. Please make the necessary arrangements to ensure timely payment.';
                                    }
                                    Mail::to($record->occupant_email)->queue(new RentExpirationNotification($email_data));
                                    \Log::info("Expiration mail queued for cycle {$record->cycle_id}");

                                } elseif ($daysRemaining <= 0 && $record->is_paid && $record->cycle_status === 'active') {
                                    RentCycle::where('id', $record->cycle_id)
                                        ->lockForUpdate()
                                        ->update(['status' => 'expired']);

                                    $durationDays = Carbon::parse($record->cycle_start_date)
                                        ->diffInDays(Carbon::parse($record->cycle_end_date));

                                    $newStart = $endDate->copy();
                                    $newEnd = $newStart->copy()->addDays($durationDays);

                                    $email_data['message'] = 'Your rent payment is due on ' . $cycle_end_date . '. Please make payments to avoid interrupted service.';

                                    RentCycle::create([
                                        'uuid' => Str::uuid(),
                                        'rent_manager_id' => $record->manager_id,
                                        'cycle_start_date' => $newStart,
                                        'cycle_end_date' => $newEnd,
                                        'cycle_start_date_server_time' => $newStart->copy()->timezone('UTC'),
                                        'cycle_end_date_server_time' => $newEnd->copy()->timezone('UTC'),
                                        'status' => 'active',
                                        'is_paid' => false,
                                    ]);

                                    Mail::to($record->occupant_email)->queue(new RentExpirationNotification($email_data));
                                    \Log::info("New rent cycle mail queued for cycle {$record->cycle_id}");

                                } elseif (!$record->is_paid) {
                                    $email_data['message'] = 'Your rent payment was due on ' . $cycle_end_date . '. Please make the payment immediately to avoid service disruption.';
                                    Mail::to($record->occupant_email)->queue(new RentExpirationNotification($email_data));
                                    \Log::info("Unpaid rent reminder mail queued for cycle {$record->cycle_id}");
                                }
                            });
                        } catch (QueryException $qe) {
                            \Log::error("Database error in rent cycle {$record->cycle_id}: " . $qe->getMessage());
                        } catch (Throwable $e) {
                            \Log::error("Error processing rent cycle {$record->cycle_id}: " . $e->getMessage(), [
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                    });
                }
            }

        } catch (Throwable $e) {
            \Log::error('CheckRentExpiration job encountered an error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }

        \Log::info('CheckRentExpiration job completed successfully.');
    }
}


