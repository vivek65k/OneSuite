<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Account;
use App\Models\Crm\Campaign;
use App\Models\Crm\CaseModel;
use App\Models\Crm\CustomerSegment;
use App\Models\Crm\CustomReport;
use App\Models\Crm\Lead;
use App\Models\Crm\LeadSource;
use App\Models\Crm\Opportunity;
use App\Models\Crm\SalesStage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomReportController extends Controller
{
    use ExportsCsv;

    protected const SOURCE_MODELS = [
        'leads' => Lead::class,
        'opportunities' => Opportunity::class,
        'accounts' => Account::class,
        'cases' => CaseModel::class,
        'campaigns' => Campaign::class,
    ];

    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.crm.custom-reports.index', [
            'reports' => CustomReport::where('company_id', $companyId)->with('owner')->orderByDesc('created_at')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.crm.custom-reports.form', [
            'report' => new CustomReport(['columns' => [], 'report_type' => 'table']),
            'sources' => CustomReport::SOURCES,
            'sourceColumns' => CustomReport::SOURCE_COLUMNS,
            'groupFields' => CustomReport::SOURCE_GROUP_FIELDS,
            'measureFields' => CustomReport::SOURCE_MEASURE_FIELDS,
            'reportTypes' => CustomReport::REPORT_TYPES,
            'chartTypes' => CustomReport::CHART_TYPES,
            'aggregates' => CustomReport::AGGREGATES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $reportType = $request->input('report_type', 'table');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'source' => ['required', 'in:'.implode(',', array_keys(CustomReport::SOURCES))],
            'report_type' => ['required', 'in:'.implode(',', array_keys(CustomReport::REPORT_TYPES))],
            'columns' => [$reportType === 'table' ? 'required' : 'nullable', 'array'],
        ]);

        $config = null;
        if ($reportType === 'pivot') {
            $config = $request->validate([
                'pivot_row' => ['required', 'string'],
                'pivot_col' => ['nullable', 'string'],
                'pivot_value' => ['required', 'string'],
                'pivot_agg' => ['required', 'in:'.implode(',', array_keys(CustomReport::AGGREGATES))],
            ]);
        } elseif ($reportType === 'chart') {
            $config = $request->validate([
                'chart_type' => ['required', 'in:'.implode(',', array_keys(CustomReport::CHART_TYPES))],
                'chart_x' => ['required', 'string'],
                'chart_y' => ['required', 'string'],
                'chart_agg' => ['required', 'in:'.implode(',', array_keys(CustomReport::AGGREGATES))],
            ]);
        }

        $report = CustomReport::create($data + ['config' => $config, 'company_id' => Company::current()->id]);

        return redirect()->route('admin.crm.custom-reports.show', $report)->with('status', 'Report created.');
    }

    public function show(CustomReport $customReport): View
    {
        abort_unless($customReport->company_id === Company::current()->id, 404);

        $rows = $this->runReport($customReport);

        return view('admin.crm.custom-reports.show', [
            'report' => $customReport,
            'rows' => $customReport->report_type === 'table' ? $rows : null,
            'pivot' => $customReport->report_type === 'pivot' ? $this->computePivot($customReport, $rows) : null,
            'chartData' => $customReport->report_type === 'chart' ? $this->computeChart($customReport, $rows) : null,
        ]);
    }

    public function export(CustomReport $customReport)
    {
        abort_unless($customReport->company_id === Company::current()->id, 404);
        $rows = $this->runReport($customReport);

        if ($customReport->report_type === 'pivot') {
            $pivot = $this->computePivot($customReport, $rows);
            $header = array_merge(['Row'], $pivot['columns']);
            $csvRows = collect($pivot['rows'])->map(fn ($row) => array_merge([$row['label']], array_map(fn ($c) => $row['values'][$c] ?? 0, $pivot['columns'])));

            return $this->streamCsv($customReport->name.'.csv', $header, $csvRows);
        }

        if ($customReport->report_type === 'chart') {
            $chart = $this->computeChart($customReport, $rows);

            return $this->streamCsv($customReport->name.'.csv', ['Label', 'Value'], collect($chart['series'])->map(fn ($p) => [$p['label'], $p['value']]));
        }

        return $this->streamCsv($customReport->name.'.csv', $customReport->columns, $rows->map(fn ($row) => collect($customReport->columns)->map(fn ($col) => data_get($row, $col))->all()));
    }

    public function destroy(CustomReport $customReport): RedirectResponse
    {
        abort_unless($customReport->company_id === Company::current()->id, 404);
        $customReport->delete();

        return redirect()->route('admin.crm.custom-reports.index')->with('status', 'Report deleted.');
    }

    protected function runReport(CustomReport $report)
    {
        $modelClass = self::SOURCE_MODELS[$report->source];

        return $modelClass::where('company_id', $report->company_id)->limit(500)->get();
    }

    protected function computePivot(CustomReport $report, $rows): array
    {
        $config = $report->config ?? [];
        $rowField = $config['pivot_row'];
        $colField = $config['pivot_col'] ?? null;
        $valueField = $config['pivot_value'];
        $agg = $config['pivot_agg'];

        $groups = $rows->groupBy(fn ($row) => $this->labelFor($report->source, $rowField, $row->{$rowField} ?? null));

        $columns = $colField
            ? $rows->map(fn ($row) => $this->labelFor($report->source, $colField, $row->{$colField} ?? null))->unique()->values()->all()
            : ['Total'];

        $pivotRows = $groups->map(function ($groupRows, $rowLabel) use ($colField, $columns, $valueField, $agg, $report) {
            $values = [];
            foreach ($columns as $colLabel) {
                $subset = $colField
                    ? $groupRows->filter(fn ($row) => $this->labelFor($report->source, $colField, $row->{$colField} ?? null) === $colLabel)
                    : $groupRows;

                $values[$colLabel] = $this->aggregate($subset, $valueField, $agg);
            }

            return ['label' => $rowLabel, 'values' => $values, 'total' => $this->aggregate($groupRows, $valueField, $agg)];
        })->values()->all();

        return ['columns' => $columns, 'rows' => $pivotRows];
    }

    protected function computeChart(CustomReport $report, $rows): array
    {
        $config = $report->config ?? [];
        $xField = $config['chart_x'];
        $yField = $config['chart_y'];
        $agg = $config['chart_agg'];

        $series = $rows->groupBy(fn ($row) => $this->labelFor($report->source, $xField, $row->{$xField} ?? null))
            ->map(fn ($group, $label) => ['label' => $label, 'value' => $this->aggregate($group, $yField, $agg)])
            ->sortByDesc('value')->values()->all();

        return ['type' => $config['chart_type'], 'series' => $series];
    }

    protected function aggregate($rows, string $field, string $agg): float
    {
        return match ($agg) {
            'avg' => $rows->count() > 0 ? round((float) $rows->avg($field), 2) : 0,
            'count' => $rows->count(),
            default => round((float) $rows->sum($field), 2),
        };
    }

    protected function labelFor(string $source, string $field, $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return match ($field) {
            'lead_source_id' => LeadSource::find($value)?->name ?? (string) $value,
            'sales_stage_id' => SalesStage::find($value)?->name ?? (string) $value,
            'customer_segment_id' => CustomerSegment::find($value)?->name ?? (string) $value,
            default => (string) $value,
        };
    }
}
