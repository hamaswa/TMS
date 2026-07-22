<?php

namespace App\Http\Controllers;

use App\Models\BusinessActivityLog;
use App\Models\Business;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class BusinessActivityController extends Controller
{
    public function index(Request $request)
    {
        $validated = $this->validatedFilters($request);
        $business = $request->user()->business;
        $query = $this->filteredQuery($business, $validated);

        return view('team.activity', [
            'logs' => $query->latest('id')->paginate(30)->withQueryString(),
            'members' => $business->members()->orderBy('name')->get(['id', 'name', 'username']),
            'todayCount' => BusinessActivityLog::where('business_id', $business->id)->whereDate('created_at', today())->count(),
            'actorCount' => BusinessActivityLog::where('business_id', $business->id)->whereDate('created_at', today())->distinct('actor_user_id')->count('actor_user_id'),
        ]);
    }

    public function export(Request $request)
    {
        $validated = $this->validatedFilters($request);
        $business = $request->user()->business;
        $query = $this->filteredQuery($business, $validated)->latest('id');
        $filename = 'employee-activity-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['تاریخ', 'وقت', 'ملازم', 'یوزر نیم', 'شعبہ', 'کارروائی', 'طریقہ', 'حوالہ', 'ریکارڈ شناخت', 'IP']);

            foreach ($query->cursor() as $log) {
                fputcsv($output, array_map(fn ($value) => $this->csvCell($value), [
                    $log->created_at?->format('d-m-Y'),
                    $log->created_at?->format('h:i A'),
                    $log->actor?->name ?? 'سسٹم',
                    $log->actor?->username,
                    $log->areaLabel(),
                    $log->actionDescription(),
                    $log->method,
                    $log->route_name ?: $log->path,
                    $this->routeParameters($log->route_parameters),
                    $log->ip_address,
                ]));
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'employee' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'action' => ['nullable', 'string', 'max:100'],
        ]);
    }

    private function filteredQuery(Business $business, array $filters): Builder
    {
        $query = BusinessActivityLog::where('business_id', $business->id)
            ->with('actor:id,name,username');

        if (! empty($filters['employee'])) {
            abort_unless($business->members()->whereKey($filters['employee'])->exists(), 404);
            $query->where('actor_user_id', $filters['employee']);
        }
        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }
        if (! empty($filters['action'])) {
            $query->where('route_name', 'like', '%' . $filters['action'] . '%');
        }

        return $query;
    }

    private function routeParameters(?array $parameters): string
    {
        return collect($parameters ?? [])->map(function ($value, $key): string {
            $display = is_array($value) ? ($value['id'] ?? '') : $value;

            return $key . ':' . $display;
        })->implode(' | ');
    }

    private function csvCell(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return preg_match('/^[=+\-@]/u', $value) === 1 ? "'" . $value : $value;
    }
}
