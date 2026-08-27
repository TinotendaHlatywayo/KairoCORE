<?php

namespace App\Filament\App\Pages;

use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Modules\DigitalAssessment\Models\GamificationSettings;
use Modules\DigitalAssessment\Services\GamificationService;

class GamificationSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.app.pages.gamification-settings';

    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationGroup = 'Exams & Grading';

    protected static ?int $navigationSort = 66;

    protected static ?string $title = 'Gamification Settings';

    protected static ?string $navigationLabel = 'Gamification';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(GamificationService::class)->getSettings();

        $this->form->fill([
            'xp_enabled' => (bool) $settings->xp_enabled,
            'badges_enabled' => (bool) $settings->badges_enabled,
            'achievements_enabled' => (bool) $settings->achievements_enabled,
            'streaks_enabled' => (bool) $settings->streaks_enabled,
            'challenges_enabled' => (bool) $settings->challenges_enabled,
            'leaderboards_enabled' => (bool) $settings->leaderboards_enabled,
            'xp_per_assessment_complete' => $settings->xp_per_assessment_complete,
            'xp_per_improvement' => $settings->xp_per_improvement,
            'xp_per_streak_day' => $settings->xp_per_streak_day,
            'xp_per_topic_mastery' => $settings->xp_per_topic_mastery,
            'xp_per_challenge_complete' => $settings->xp_per_challenge_complete,
            'leaderboard_scope' => $settings->leaderboard_scope,
            'leaderboard_anonymize' => (bool) $settings->leaderboard_anonymize,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema($this->getFormSchema())
            ->statePath('data');
    }

    protected function getFormSchema(): array
    {        return [
            Forms\Components\Section::make('Feature Toggles')
                ->description('Enable or disable gamification features for this school.')
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\Toggle::make('xp_enabled')
                                ->label('XP System')
                                ->helperText('Award experience points for completing assessments'),
                            Forms\Components\Toggle::make('badges_enabled')
                                ->label('Badges')
                                ->helperText('Earn badges for achievements'),
                            Forms\Components\Toggle::make('achievements_enabled')
                                ->label('Achievements')
                                ->helperText('Track long-term achievements'),
                            Forms\Components\Toggle::make('streaks_enabled')
                                ->label('Streaks')
                                ->helperText('Daily activity streaks'),
                            Forms\Components\Toggle::make('challenges_enabled')
                                ->label('Challenges')
                                ->helperText('Teacher-created challenges'),
                            Forms\Components\Toggle::make('leaderboards_enabled')
                                ->label('Leaderboards')
                                ->helperText('Rank students by XP'),
                        ]),
                ]),

            Forms\Components\Section::make('XP Configuration')
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\TextInput::make('xp_per_assessment_complete')
                                ->label('XP per Assessment')
                                ->numeric()
                                ->default(10)
                                ->minValue(0)
                                ->maxValue(100),
                            Forms\Components\TextInput::make('xp_per_improvement')
                                ->label('XP per Improvement')
                                ->numeric()
                                ->default(15)
                                ->minValue(0)
                                ->maxValue(100),
                            Forms\Components\TextInput::make('xp_per_streak_day')
                                ->label('XP per Streak Day')
                                ->numeric()
                                ->default(5)
                                ->minValue(0)
                                ->maxValue(50),
                            Forms\Components\TextInput::make('xp_per_topic_mastery')
                                ->label('XP per Topic Mastery')
                                ->numeric()
                                ->default(25)
                                ->minValue(0)
                                ->maxValue(100),
                            Forms\Components\TextInput::make('xp_per_challenge_complete')
                                ->label('XP per Challenge')
                                ->numeric()
                                ->default(20)
                                ->minValue(0)
                                ->maxValue(100),
                        ]),
                ])
                ->visible(fn (Forms\Get $get) => (bool) $get('xp_enabled')),

            Forms\Components\Section::make('Leaderboard Settings')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Select::make('leaderboard_scope')
                                ->label('Leaderboard Scope')
                                ->options([
                                    'class' => 'Class',
                                    'subject' => 'Subject',
                                    'school' => 'School-wide',
                                ])
                                ->default('class'),
                            Forms\Components\Toggle::make('leaderboard_anonymize')
                                ->label('Anonymize Names')
                                ->helperText('Show anonymous names on leaderboards')
                                ->default(false),
                        ]),
                ])
                ->visible(fn (Forms\Get $get) => (bool) $get('leaderboards_enabled')),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        app(GamificationService::class)->updateSettings($data);

        Notification::make()
            ->title('Gamification settings saved')
            ->success()
            ->send();
    }
}
