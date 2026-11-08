<?php

namespace App\Filament\Pages\Settings;

use App\Support\Settings;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;

/**
 * One short settings form per group, stored as a JSON row in `settings`.
 * Owner only. Settings apply immediately (they are not drafted); every change
 * is recorded in the audit log.
 */
abstract class SettingsPage extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    public ?array $data = [];

    protected static string|\UnitEnum|null $navigationGroup = 'Site';

    /** Key in the settings table. */
    abstract protected static function group(): string;

    /** @return array<int, Component> */
    abstract protected function components(): array;

    public static function canAccess(): bool
    {
        return auth()->user()?->isOwner() ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(Settings::group(static::group()));
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components($this->components())->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([Action::make('save')->label('Opslaan')->submit('save')->keyBindings(['mod+s'])])
                        ->sticky()->key('form-actions'),
                ]),
        ]);
    }

    public function save(): void
    {
        Settings::save(static::group(), $this->form->getState());

        activity()->causedBy(auth()->user())->event('updated')->withProperties(['settings' => static::group()])->log('settings_updated');

        Notification::make()->success()->title('Opgeslagen')->send();
    }
}
