<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Resources\BlogPostResource;
use App\Models\BlogPost;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Website Content — the public-site editor that used to live as a tab inside
 * Platform Settings, now a first-class sidebar page.
 *
 * Home / Features / About / Contact / Global & SEO keep their tabs, and Blog
 * management sits in the same tab strip so every public page is edited from
 * one place. Blog edits live under a "blog" state subtree that the inherited
 * marketing save() deliberately skips (compound keys without "_" are ignored),
 * so the shared Save button safely persists marketing content while the blog
 * tab keeps its own Save/Back actions.
 */
class WebsiteContentPage extends PlatformSettingsPage
{
    protected static ?string $slug = 'website-content';

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Website Content';

    protected static ?string $navigationLabel = 'Website Content';

    protected static ?int $navigationSort = 1;

    /** Blog tab mode: list of posts, create a new one, or edit one. */
    public string $blogMode = 'list';

    public ?int $blogEditingId = null;

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        return $user && $user->school_id === null;
    }

    public function getTitle(): string
    {
        return __('Website Content');
    }

    public function getSaveButtonLabel(): string
    {
        return __('Save Website Content');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                ...$this->websiteContentPrelude(),

                Tabs::make('websiteContentSections')
                    ->tabs([
                        ...$this->marketingSectionTabs(),
                        $this->blogTab(),
                    ]),
            ])
            ->statePath('data');
    }

    // ───────────────────────────────────────────────────────────────
    // Blog tab
    // ───────────────────────────────────────────────────────────────

    protected function blogTab(): Tab
    {
        return Tab::make('Blog')
            ->icon('heroicon-o-newspaper')
            ->schema($this->blogTabSchema());
    }

    /**
     * The Blog tab switches between a post list and a create/edit form.
     * Schema is re-evaluated every render, so toggling $blogMode swaps it.
     *
     * @return array<int, mixed>
     */
    protected function blogTabSchema(): array
    {
        if ($this->blogMode === 'list') {
            return [
                Section::make(__('Blog posts'))
                    ->description(__('Articles published on the public /blog page. Create, edit and publish them right here.'))
                    ->headerActions([
                        Action::make('blogNew')
                            ->label(__('New post'))
                            ->icon('heroicon-o-plus')
                            ->action(fn () => $this->startBlogCreate()),
                        Action::make('blogViewSite')
                            ->label(__('View public blog'))
                            ->icon('heroicon-o-arrow-top-right-on-square')
                            ->url(route('marketing.blog'), shouldOpenInNewTab: true),
                    ])
                    ->schema([
                        Placeholder::make('blog_list')
                            ->label(__('All posts'))
                            ->content(fn (): HtmlString => $this->blogListHtml()),
                    ]),
            ];
        }

        $editing = $this->blogEditingId ? BlogPost::find($this->blogEditingId) : null;
        $heading = $this->blogMode === 'create'
            ? __('New blog post')
            : __('Edit blog post');

        return [
            Section::make($heading)
                ->headerActions([
                    Action::make('blogSavePost')
                        ->label(__('Save post'))
                        ->icon('heroicon-o-check')
                        ->color('primary')
                        ->action(fn () => $this->saveBlogPostAndReturn()),
                    Action::make('blogBack')
                        ->label(__('Back to list'))
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('gray')
                        ->action(fn () => $this->blogBackToList()),
                ])
                ->schema([
                    TextInput::make('blog.title')
                        ->label(__('Title'))
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, \Filament\Forms\Set $set) => $set('blog.slug', Str::slug((string) $state))),
                    TextInput::make('blog.slug')
                        ->label(__('Slug'))
                        ->maxLength(255)
                        ->rules(fn () => [
                            Rule::unique(BlogPost::class, 'slug')->ignore($this->blogEditingId),
                        ])
                        ->helperText(__('Leave blank to generate one from the title.')),
                    Textarea::make('blog.excerpt')
                        ->label(__('Excerpt'))
                        ->rows(3)
                        ->maxLength(500)
                        ->helperText(__('Short summary shown on the blog index and in search results.'))
                        ->columnSpanFull(),
                    FileUpload::make('blog.cover_image')
                        ->label(__('Cover image'))
                        ->disk('public')
                        ->directory('platform/blog/covers')
                        ->image()
                        ->imageResizeMode('cover')
                        ->imageCropAspectRatio('16:9')
                        ->nullable(),
                    RichEditor::make('blog.content')
                        ->label(__('Content'))
                        ->required()
                        ->columnSpanFull()
                        ->toolbarButtons([
                            'bold', 'italic', 'underline', 'strike',
                            'h2', 'h3',
                            'link',
                            'orderedList', 'bulletList', 'blockquote',
                            'attachFiles',
                        ])
                        ->fileAttachmentsDisk('public')
                        ->fileAttachmentsDirectory('platform/blog/attachments')
                        ->fileAttachmentsVisibility('public')
                        ->helperText(__('Select any word or phrase and use the link icon to make it a hyperlink. Use the paperclip to embed images.')),
                    Select::make('blog.status')
                        ->label(__('Status'))
                        ->options([
                            BlogPost::STATUS_DRAFT => __('Draft'),
                            BlogPost::STATUS_PUBLISHED => __('Published'),
                        ])
                        ->default(BlogPost::STATUS_DRAFT)
                        ->required()
                        ->native(false),
                    DateTimePicker::make('blog.published_at')
                        ->label(__('Publish date'))
                        ->native(false)
                        ->format('Y-m-d H:i')
                        ->displayFormat('d M Y H:i')
                        ->helperText(__('Set automatically the first time a draft is published.')),
                    TextInput::make('blog.seo_description')
                        ->label(__('SEO description'))
                        ->maxLength(300)
                        ->helperText(__('Meta description for search engines.'))
                        ->columnSpanFull(),
                ]),
        ];
    }

    /** Server-rendered list of posts with type-safe wire:click buttons. */
    protected function blogListHtml(): HtmlString
    {
        $posts = BlogPost::query()
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->get();

        if ($posts->isEmpty()) {
            return new HtmlString(
                '<div class="rounded-xl border border-dashed border-gray-300 px-6 py-10 text-center dark:border-gray-700">'
                .'<p class="text-sm font-medium text-gray-500 dark:text-gray-400">'
                .e(__('No blog posts yet — create the first one.'))
                .'</p></div>'
            );
        }

        $rows = '';
        foreach ($posts as $post) {
            $published = $post->status === BlogPost::STATUS_PUBLISHED;
            $badge = $published
                ? '<span class="inline-flex items-center rounded-md bg-success-50 px-2 py-1 text-xs font-semibold text-success-700 ring-1 ring-inset ring-success-600/20 dark:bg-success-500/10 dark:text-success-400">'.e(__('Published')).'</span>'
                : '<span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-500/10 dark:text-gray-400">'.e(__('Draft')).'</span>';

            $date = $post->published_at
                ? e($post->published_at->format('d M Y'))
                : e($post->created_at->format('d M Y'));

            $rows .= '<li class="flex flex-wrap items-center gap-4 px-4 py-3">'
                .'<img src="'.e($post->coverUrl()).'" alt="" class="h-11 w-20 shrink-0 rounded-lg border border-gray-200 object-cover dark:border-gray-700" loading="lazy">'
                .'<div class="min-w-0 flex-1">'
                .'<p class="truncate text-sm font-semibold text-gray-900 dark:text-white">'.e($post->title).'</p>'
                .'<p class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">/blog/'.e($post->slug).' · '.$date.'</p>'
                .'</div>'
                .$badge
                .'<div class="flex items-center gap-2">'
                .'<button type="button" wire:click="blogEdit('.$post->id.')" class="rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-white/5 dark:text-gray-200 dark:ring-white/10 dark:hover:bg-white/10">'.e(__('Edit')).'</button>'
                .'<button type="button" wire:click="blogDelete('.$post->id.')" wire:confirm="'.e(__('Delete this post? This cannot be undone.')).'" class="rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-danger-600 ring-1 ring-inset ring-danger-300 hover:bg-danger-50 dark:bg-white/5 dark:text-danger-400 dark:ring-danger-500/30 dark:hover:bg-danger-500/10">'.e(__('Delete')).'</button>'
                .'</div>'
                .'</li>';
        }

        return new HtmlString(
            '<ul class="divide-y divide-gray-100 rounded-xl border border-gray-200 bg-white dark:divide-gray-800 dark:border-gray-700 dark:bg-gray-900">'
            .$rows
            .'</ul>'
        );
    }

    // ───────────────────────────────────────────────────────────────
    // Blog tab actions
    // ───────────────────────────────────────────────────────────────

    public function startBlogCreate(): void
    {
        $this->blogMode = 'create';
        $this->blogEditingId = null;
        $this->data['blog'] = [
            'title' => '',
            'slug' => '',
            'excerpt' => '',
            'cover_image' => null,
            'content' => '',
            'status' => BlogPost::STATUS_DRAFT,
            'published_at' => null,
            'seo_description' => '',
        ];
    }

    public function blogEdit(int $id): void
    {
        $post = BlogPost::find($id);

        if (! $post) {
            Notification::make()
                ->title(__('That post no longer exists'))
                ->warning()
                ->send();

            $this->blogBackToList();

            return;
        }

        $this->blogMode = 'edit';
        $this->blogEditingId = $post->id;
        $this->data['blog'] = [
            'title' => (string) $post->title,
            'slug' => (string) $post->slug,
            'excerpt' => (string) $post->excerpt,
            'cover_image' => $post->cover_image ? [$post->cover_image] : null,
            'content' => (string) $post->content,
            'status' => (string) $post->status,
            'published_at' => $post->published_at?->format('Y-m-d H:i'),
            'seo_description' => (string) $post->seo_description,
        ];
    }

    public function blogBackToList(): void
    {
        $this->blogMode = 'list';
        $this->blogEditingId = null;
    }

    /** Persist the open blog form (validates the whole page form). */
    public function persistBlogPost(): void
    {
        // getState() validates every visible field (marketing + blog) and
        // returns dehydrated state — blog content lives under the "blog" key,
        // which the inherited save() skips because compound keys without "_"
        // never map onto a PlatformSetting group.
        $state = $this->form->getState();
        $blog = (array) (data_get($state, 'blog') ?? []);

        $attrs = BlogPost::prepareAttributes($blog);

        if ($this->blogEditingId !== null) {
            $post = BlogPost::find($this->blogEditingId);

            if (! $post) {
                Notification::make()->title(__('That post no longer exists'))->warning()->send();
                $this->blogBackToList();

                return;
            }

            $post->update($attrs);
            Notification::make()->title(__('Blog post updated'))->success()->send();
        } else {
            $post = BlogPost::create($attrs);
            $this->blogEditingId = $post->id;
            Notification::make()->title(__('Blog post created'))->success()->send();
        }

        // Reflect the normalised slug back into the editor.
        $this->data['blog']['slug'] = (string) $post->slug;
    }

    public function saveBlogPostAndReturn(): void
    {
        $this->persistBlogPost();
        $this->blogBackToList();
    }

    public function blogDelete(int $id): void
    {
        $post = BlogPost::find($id);

        if (! $post) {
            return;
        }

        $post->delete();

        if ($this->blogEditingId === $id) {
            $this->blogBackToList();
        }

        Notification::make()
            ->title(__('Blog post deleted'))
            ->success()
            ->send();
    }

    /**
     * The shared Save button covers the whole page: any open blog edit is
     * persisted together with the marketing content, then the inherited
     * marketing save runs.
     */
    public function save(): void
    {
        if ($this->blogMode !== 'list') {
            $this->persistBlogPost();
        }

        parent::save();
    }
}
