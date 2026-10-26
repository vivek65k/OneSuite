<?php

namespace App\Filament\Pages;

use App\Models\Company;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class GeneralSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = '👥 Organization';

    protected static ?string $navigationLabel = 'General Settings';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.general-settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('page_GeneralSettings') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(Company::current()->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Organization')
                    ->compact()
                    ->columns(3)
                    ->schema([
                        FileUpload::make('logo')
                            ->image()
                            ->avatar()
                            ->directory('company-logos')
                            ->disk('public'),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('legal_name')
                            ->label('Legal name')
                            ->maxLength(255),
                        TextInput::make('website')
                            ->url(),
                        TextInput::make('currency')
                            ->length(3)
                            ->default('USD')
                            ->required(),
                        Select::make('timezone')
                            ->options(collect(timezone_identifiers_list())->mapWithKeys(fn (string $tz) => [$tz => $tz]))
                            ->searchable()
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
                Section::make('Contact')
                    ->compact()
                    ->columns(3)
                    ->schema([
                        TextInput::make('email')
                            ->email(),
                        TextInput::make('phone')
                            ->tel(),
                        TextInput::make('registration_number')
                            ->label('Registration number'),
                        TextInput::make('tax_id')
                            ->label('Tax ID'),
                    ]),
                Section::make('Address')
                    ->compact()
                    ->columns(4)
                    ->schema([
                        TextInput::make('address')
                            ->columnSpanFull(),
                        TextInput::make('city'),
                        TextInput::make('state'),
                        TextInput::make('country'),
                        TextInput::make('postal_code')
                            ->label('Postal code'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        DB::transaction(function () use ($data) {
            Company::current()->update($data);
        });

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }
}
