<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\GradingScaleResource\Pages;
use App\Services\ModuleVisibilityManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Modules\Academics\Models\GradingPoint;
use Modules\Academics\Models\GradingScale;
use Modules\Academics\Services\ZimsecGradingTemplates;
use Modules\Admin\Services\PermissionRegistry;

class GradingScaleResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Exams & Grading');
    }

    protected static ?string $model = GradingScale::class;

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        if (! ModuleVisibilityManager::isVisible('academics')) {
            return false;
        }

        if (class_exists('\Modules\Admin\Services\PermissionRegistry')) {
            return PermissionRegistry::checkPermission('academic_ops.manage_assessments');
        }

        return true;
    }

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Grading Scales';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?string $navigationGroup = 'Exams & Grading';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Grading Scale Profile')
                    ->schema([
                        Forms\Components\Select::make('import_template')
                            ->label(__('Import Grading Scale'))
                            ->helperText(__('Pick a ZIMSEC template to pre-fill the scale name and grade bands below. Ranges stay fully editable before you save.'))
                            ->options(fn () => ZimsecGradingTemplates::optionsFor(current_tenant()))
                            ->placeholder(__('I\'ll define the scale manually...'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                $template = $state ? ZimsecGradingTemplates::template($state) : null;
                                if (! $template) {
                                    $set('name', null);
                                    $set('points', []);

                                    return;
                                }

                                $set('name', $template['name']);
                                $set('points', $template['points']);
                            })
                            ->dehydrated(false)
                            ->hiddenOn('edit'),

                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder(__('e.g. ZIMSEC Ordinary Level, Cambridge O-Level')),
                    ]),

                Forms\Components\Section::make('Grading Points & Letter Ranges')
                    ->description(__('Define raw percentage intervals to map output symbols.'))
                    ->schema([
                        Forms\Components\Repeater::make('points')
                            ->relationship('points') // Maps directly to Modules/Academics/Models/GradingPoint
                            ->schema([
                                Forms\Components\TextInput::make('symbol')
                                    ->label(__('Letter Grade'))
                                    ->required()
                                    ->maxLength(10)
                                    ->placeholder(__('e.g. A, B, C, U')),
                                Forms\Components\TextInput::make('min_score')
                                    ->label(__('Minimum Score (%)'))
                                    ->numeric()
                                    ->required()
                                    ->default(0.00),
                                Forms\Components\TextInput::make('max_score')
                                    ->label(__('Maximum Score (%)'))
                                    ->numeric()
                                    ->required()
                                    ->default(100.00),
                                Forms\Components\TextInput::make('remark')
                                    ->label(__('Comment / Descriptor'))
                                    ->placeholder(__('e.g. Distinction, Merit, Credit')),
                            ])
                            ->columns(4)
                            ->defaultItems(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('points_count')
                    ->counts('points')
                    ->label(__('Grade Levels Defined'))
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('created_at')
                    ->date()
                    ->sortable(),
            ])
            ->headerActions([
                // =====================================================================
                // IMPORT GRADING SCALES (EXCEL/CSV)
                // Download-template button lives INSIDE the import modal (top),
                // matching the standard "Import X from Excel or CSV" wizard used by
                // Subjects, Courses and every other CSV import.
                // =====================================================================
                Tables\Actions\Action::make('importGradingScales')
                    ->label(__('Import Grading Scales (Excel/CSV)'))
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('warning')
                    ->modalHeading(__('Import Grading Scales from Excel or CSV'))
                    ->modalDescription(__('Download the template, fill it in and upload the file with your grading scales. Repeat the scale name on every row — one row per grade band. The system matches every column automatically.'))
                    ->modalWidth(MaxWidth::ExtraLarge)
                    ->modalSubmitActionLabel(__('Import Grading Scales'))
                    ->form([
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('downloadGradingScaleTemplate')
                                ->label(__('Download Excel Template'))
                                ->icon('heroicon-o-arrow-down-tray')
                                ->color('primary')
                                ->action(function () {
                                    $filename = 'Grading_Scales_Import_Template.csv';

                                    return response()->stream(function () {
                                        $handle = fopen('php://output', 'w');

                                        $columns = ['Scale Name', 'Grade Symbol', 'Minimum Score (%)', 'Maximum Score (%)', 'Remark'];

                                        fputcsv($handle, $columns);
                                        fputcsv($handle, ['School Assessment Scale', 'A', '90', '100', 'Distinction']);
                                        fputcsv($handle, ['School Assessment Scale', 'B', '70', '89', 'Merit']);
                                        fputcsv($handle, ['School Assessment Scale', 'C', '50', '69', 'Pass']);
                                        fputcsv($handle, ['School Assessment Scale', 'U', '0', '49', 'Fail']);

                                        fclose($handle);
                                    }, 200, [
                                        'Content-Type' => 'text/csv',
                                        'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                                    ]);
                                }),
                        ]),
                        Forms\Components\FileUpload::make('csv_file')
                            ->label(__('Excel / CSV File'))
                            ->helperText(__('The template above contains the exact system columns. Replace the example rows with your grading bands.'))
                            ->acceptedFileTypes(['text/csv', 'text/plain', 'text/x-csv', 'application/csv', 'application/vnd.ms-excel'])
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $schoolId = app('current_tenant')->id;
                        $filePath = public_path('storage/'.$data['csv_file']);

                        if (! file_exists($filePath)) {
                            Notification::make()
                                ->title(__('File Error'))
                                ->body('The uploaded spreadsheet could not be loaded. Please try again.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $handle = fopen($filePath, 'r');
                        $headers = fgetcsv($handle, 1000, ',');

                        // Validate minimum header structure (tolerate a UTF-8 BOM)
                        if (! $headers || count($headers) < 5 || trim($headers[0], "\xEF\xBB\xBF") !== 'Scale Name') {
                            fclose($handle);
                            Notification::make()
                                ->title(__('Invalid Template Format'))
                                ->body('The uploaded CSV file does not match the official grading scales template structure.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $rowNum = 1;
                        $errors = [];
                        $bands = [];

                        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                            $rowNum++;

                            if (empty($row) || count($row) < 5) {
                                continue;
                            }

                            $scaleName = trim($row[0]);
                            $symbol = trim($row[1]);
                            $minScore = trim($row[2]);
                            $maxScore = trim($row[3]);
                            $remark = trim($row[4]);

                            // Skip completely empty spacer lines
                            if ($scaleName === '') {
                                continue;
                            }

                            if ($symbol === '') {
                                $errors[] = "Row {$rowNum}: Grade Symbol cannot be empty for scale '{$scaleName}'.";

                                continue;
                            }

                            if (! is_numeric($minScore) || ! is_numeric($maxScore)) {
                                $errors[] = "Row {$rowNum}: Minimum and Maximum Score must be valid numbers for symbol '{$symbol}'.";

                                continue;
                            }

                            $minValue = (float) $minScore;
                            $maxValue = (float) $maxScore;

                            if ($minValue < 0 || $maxValue > 100 || $minValue > $maxValue) {
                                $errors[] = "Row {$rowNum}: Range must be between 0 and 100 with Minimum ≤ Maximum for symbol '{$symbol}'.";

                                continue;
                            }

                            $bands[] = [
                                'scale_name' => $scaleName,
                                'symbol' => $symbol,
                                'min_score' => $minValue,
                                'max_score' => $maxValue,
                                'remark' => $remark,
                            ];
                        }

                        fclose($handle);

                        // Dispatch red-alert notification lists (bypasses hard SQL crashes)
                        if (! empty($errors)) {
                            $errorList = implode('<br>', array_slice($errors, 0, 10));
                            if (count($errors) > 10) {
                                $errorList .= '<br>...and '.(count($errors) - 10).' more mismatch errors found.';
                            }

                            Notification::make()
                                ->title(__('Grading Scale Template Mismatches Found'))
                                ->body(new HtmlString('<div style="font-size: 11px; text-align: left; max-height: 250px; overflow-y: auto; color: #b91c1c;">'.$errorList.'</div>'))
                                ->danger()
                                ->persistent()
                                ->send();

                            return;
                        }

                        // Secure batch upsert: scale by school + name, band by symbol
                        $scaleNames = [];
                        $savedBands = 0;

                        foreach ($bands as $band) {
                            $scale = GradingScale::updateOrCreate(
                                ['school_id' => $schoolId, 'name' => $band['scale_name']],
                                ['school_id' => $schoolId, 'name' => $band['scale_name']]
                            );

                            GradingPoint::updateOrCreate(
                                ['grading_scale_id' => $scale->id, 'symbol' => $band['symbol']],
                                [
                                    'min_score' => $band['min_score'],
                                    'max_score' => $band['max_score'],
                                    'remark' => $band['remark'] !== '' ? $band['remark'] : null,
                                ]
                            );

                            $scaleNames[$band['scale_name']] = true;
                            $savedBands++;
                        }

                        Notification::make()
                            ->title(__('Grading Scales Uploaded Successfully'))
                            ->body("Imported and saved {$savedBands} grade band(s) across ".count($scaleNames).' grading scale(s).')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\DeleteAction::make()->iconButton(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGradingScales::route('/'),
            'create' => Pages\CreateGradingScale::route('/create'),
            'edit' => Pages\EditGradingScale::route('/{record}/edit'),
        ];
    }
}
