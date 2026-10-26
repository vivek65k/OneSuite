<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Users';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $modelLabel = 'platform user';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Account')
                    ->compact()
                    ->columns(3)
                    ->schema([
                        Forms\Components\FileUpload::make('avatar')
                            ->image()
                            ->avatar()
                            ->directory('avatars')
                            ->disk('public'),
                        Forms\Components\TextInput::make('name')
                            ->required(),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->minLength(8),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->helperText('Inactive users cannot sign in to the panel.')
                            ->default(true),
                        Forms\Components\Toggle::make('is_super_admin')
                            ->label('Super admin')
                            ->helperText('Super admins can manage other platform users and access every central resource.')
                            ->default(false)
                            ->columnSpanFull()
                            ->live(),
                        Forms\Components\Select::make('roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->hidden(fn (Forms\Get $get) => $get('is_super_admin'))
                            ->helperText('Controls which screens and actions this user can access.')
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Profile')
                    ->compact()
                    ->columns(4)
                    ->schema([
                        Forms\Components\TextInput::make('employee_code')
                            ->label('Employee code')
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('phone')
                            ->tel(),
                        Forms\Components\Select::make('gender')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                                'other' => 'Other',
                            ]),
                        Forms\Components\DatePicker::make('date_of_birth')
                            ->label('Date of birth')
                            ->displayFormat('d/m/Y'),
                    ]),
                Forms\Components\Section::make('Employment')
                    ->compact()
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('designation')
                            ->label('Designation'),
                        Forms\Components\TextInput::make('department')
                            ->label('Department'),
                        Forms\Components\DatePicker::make('joining_date')
                            ->label('Joining date')
                            ->displayFormat('d/m/Y'),
                    ]),
                Forms\Components\Section::make('Address')
                    ->compact()
                    ->columns(4)
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('city'),
                        Forms\Components\TextInput::make('state'),
                        Forms\Components\TextInput::make('country'),
                        Forms\Components\TextInput::make('postal_code')
                            ->label('Postal code'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->circular()
                    ->size(28)
                    ->defaultImageUrl(fn (User $record) => 'https://ui-avatars.com/api/?name='.urlencode($record->name)),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (User $record) => $record->designation),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->separator(',')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('employee_code')
                    ->label('Employee code')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('department')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_super_admin')
                    ->label('Super admin')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->striped()
            ->persistSortInSession()
            ->persistFiltersInSession()
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->deferLoading()
            ->deferFilters()
            ->paginationPageOptions([10, 25, 50, 100])
            ->modifyQueryUsing(fn (Builder $query) => $query->with('roles'))
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                Tables\Filters\TernaryFilter::make('is_super_admin')
                    ->label('Super admin'),
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),
                Tables\Filters\SelectFilter::make('department')
                    ->options(fn (): array => User::query()
                        ->whereNotNull('department')
                        ->distinct()
                        ->orderBy('department')
                        ->pluck('department', 'department')
                        ->all()),
                Tables\Filters\Filter::make('joined')
                    ->form([
                        Forms\Components\DatePicker::make('joined_from'),
                        Forms\Components\DatePicker::make('joined_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['joined_from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['joined_until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['joined_from'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Joined from '.$data['joined_from'])->removeField('joined_from');
                        }

                        if ($data['joined_until'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Joined until '.$data['joined_until'])->removeField('joined_until');
                        }

                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn (User $record): bool => $record->is(auth()->user())),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
