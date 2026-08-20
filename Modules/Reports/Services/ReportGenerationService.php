<?php

namespace Modules\Reports\Services;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\Reports\Models\EnterpriseReportTemplate;
use Modules\Reports\Models\GeneratedReport;

class ReportGenerationService
{
    protected DataExtractionService $extractor;

    public function __construct(DataExtractionService $extractor)
    {
        $this->extractor = $extractor;
    }

    public function generate(EnterpriseReportTemplate $template, string $format, array $filters = [], ?int $userId = null): GeneratedReport
    {
        /** @var User|null $user */
        $user = Auth::user();
        $school = session('current_tenant') ?? ($user ? $user->school : null);

        $report = GeneratedReport::create([
            'school_id' => $template->school_id,
            'enterprise_report_template_id' => $template->id,
            'name' => "{$template->name} - ".now()->format('Y-m-d His'),
            'format' => $format,
            'status' => 'processing',
            'generated_by_id' => $userId,
        ]);

        try {
            $data = $this->extractor->extract(
                $template->module,
                $template->report_type,
                $template->selected_fields,
                $filters
            );

            $fieldsRegistry = $this->extractor->getModuleRegistry()[$template->module]['types'][$template->report_type]['available_fields'];

            $columnHeadings = [];
            foreach ($template->selected_fields as $fKey) {
                $columnHeadings[$fKey] = $fieldsRegistry[$fKey] ?? ucfirst(str_replace('_', ' ', $fKey));
            }

            $fileName = 'reports/'.$template->school_id.'/'.uniqid().'.'.$format;

            if ($format === 'pdf') {
                $pdf = Pdf::loadView('modules.reports.pdf-template', [
                    'school' => $school,
                    'title' => $template->name,
                    'data' => $data,
                    'selected_fields' => $template->selected_fields,
                    'headings' => $columnHeadings,
                    'settings' => $template->layout_settings,
                    'orientation' => $template->orientation,
                ]);

                if ($template->orientation === 'landscape') {
                    $pdf->setPaper('a4', 'landscape');
                } else {
                    $pdf->setPaper('a4', 'portrait');
                }

                Storage::disk('public')->put($fileName, $pdf->output());

            } else {
                $handle = fopen('php://temp', 'r+');
                fputcsv($handle, array_values($columnHeadings));

                foreach ($data as $row) {
                    $rowData = [];
                    foreach ($template->selected_fields as $fKey) {
                        $rowData[] = $row->{$fKey} ?? '';
                    }
                    fputcsv($handle, $rowData);
                }

                rewind($handle);
                $csvData = stream_get_contents($handle);
                fclose($handle);

                Storage::disk('public')->put($fileName, $csvData);
            }

            $report->update([
                'status' => 'completed',
                'file_path' => $fileName,
                'record_count' => count($data),
            ]);

        } catch (\Exception $e) {
            $report->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }

        return $report;
    }
}
