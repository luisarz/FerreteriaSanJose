<?php

namespace App\Providers;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Livewire\Notifications;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Alignment;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\Columns\ImageColumn;
use Filament\View\PanelsRenderHook;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ServiceProvider;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;
use App\Policies\ActivityPolice;
use Spatie\Activitylog\Models\Activity;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        FilamentView::registerRenderHook(
            PanelsRenderHook::SCRIPTS_AFTER,
            fn (): string => new HtmlString('
        <script>document.addEventListener("scroll-to-top", () => window.scrollTo(0, 0))</script>
            '),
        );

        Gate::policy(Activity::class, ActivityPolicy::class);


        TextInput::configureUsing(function (TextInput $textInput) {
            $textInput->inlineLabel();
        });
        Select::configureUsing(function (Select $select) {
            $select->inlineLabel();
        });
        Textarea::configureUsing(function (Textarea $textarea) {
            $textarea->inlineLabel();
        });
        Section::configureUsing(function (Section $section) {
            $section->columnSpanFull(true);
        });
        FileUpload::configureUsing(fn (FileUpload $fileUpload) => $fileUpload
            ->visibility('public'));

        ImageColumn::configureUsing(fn (ImageColumn $imageColumn) => $imageColumn
            ->visibility('public'));

        ImageEntry::configureUsing(fn (ImageEntry $imageEntry) => $imageEntry
            ->visibility('public'));

        Table::configureUsing(function (Table $table) {
            $table
                ->paginationPageOptions([5, 10, 25, 50, 100])
                ->striped()
                ->deferLoading()
//                ->recordClasses(fn(Model $record) => $record->deleted_at ? 'border-red-500	text-danger bg-red-500 text-red opacity-50' : '');
                ->recordClasses(fn(Model $record) => match (true) {
                    $record->deleted_at !== null => 'border-s-2 border-orange-600 dark:border-orange-300 opacity-50', // Tachado y con menor opacidad
                    $record->sale_status == "Anulado" => 'danger border-s-2 border-orange-600 dark:border-orange-300 opacity-50', // Tachado y con menor opacidad

                    default => null,
                });

        });

        Notifications::alignment(Alignment::Center);
        Page::$reportValidationErrorUsing = function (ValidationException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        };


    }
}