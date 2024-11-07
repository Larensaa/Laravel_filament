<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkshopResource\Pages;
use App\Filament\Resources\WorkshopResource\RelationManagers;
use App\Models\Workshop;
use Filament\Forms\Components\Fieldset; // Corrected namespace
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WorkshopResource extends Resource
{
    protected static ?string $model = Workshop::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
{
    return $form
        ->schema([
            Fieldset::make('Details')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Textarea::make('address')
                        ->rows(3)
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Textarea::make('about') // Field 'about'
                        ->rows(3)
                        ->nullable()
                        ->maxLength(500)
                        ->required(), // Menandai sebagai field yang diperlukan

                    Forms\Components\TextInput::make('price') // Field 'price'
                        ->required()
                        ->numeric()
                        ->prefix('IDR '), // Menambahkan prefix 'IDR'

                    Forms\Components\Select::make('is_open') // Field 'is open'
                        ->options([
                            1 => 'Yes',
                            0 => 'No',
                        ])
                        ->required()
                        ->label('Is open'),

                    Forms\Components\Select::make('has_started') // Field 'has started'
                        ->options([
                            1 => 'Yes',
                            0 => 'No',
                        ])
                        ->required()
                        ->label('Has started'),

                    Forms\Components\Select::make('category_id') // Field 'Category'
                        ->relationship('category', 'name')
                        ->required()
                        ->label('Category'),

                    Forms\Components\Select::make('workshop_instructor_id') // Field 'Instructor'
                        ->relationship('instructor', 'name')
                        ->required()
                        ->label('Instructor'),

                    Forms\Components\DatePicker::make('started_at') // Field 'Started at'
                        ->required()
                        ->label('Started at'),

                    Forms\Components\TimePicker::make('time_at') // Field 'Time at'
                        ->required()
                        ->label('Time at'),
                    
                    Forms\Components\FileUpload::make('thumbnail')
                        ->image()
                        ->required(),

                    Forms\Components\FileUpload::make('venue_thumbnail')
                        ->image()
                        ->required(),

                    Forms\Components\FileUpload::make('bg_map')
                        ->image()
                        ->required(),

                    Forms\Components\Repeater::make('benefits')
                        ->relationship('benefits')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                        ]),
                ]),
        ]);
}


public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('name')
                ->searchable()
                ->sortable(), // Tambahkan sortable jika diinginkan

            Tables\Columns\TextColumn::make('address')
                ->searchable()
                ->limit(50), // Mengatur batasan tampilan

            Tables\Columns\TextColumn::make('about')
                ->limit(50), // Mengatur batasan tampilan

            Tables\Columns\TextColumn::make('price')
                ->label('Price')
                ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0)), // Format harga

            Tables\Columns\TextColumn::make('category.name')
                ->label('Category'),

            Tables\Columns\TextColumn::make('instructor.name')
                ->label('Instructor'),

            Tables\Columns\IconColumn::make('has_started')
                ->boolean()
                ->trueColor('success')
                ->falseColor('danger')
                ->trueIcon('heroicon-o-check-circle')
                ->falseIcon('heroicon-o-x-circle')
                ->label('Started'),

            Tables\Columns\TextColumn::make('participants_count')
                ->label('Participants')
                ->counts('participants'),
        ])
        ->filters([
            SelectFilter::make('category_id')
                ->label('Category')
                ->relationship('category', 'name'),

            SelectFilter::make('workshop_instructor_id')
                ->label('Instructor')
                ->relationship('instructor', 'name'),
        ])
        ->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
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
            // Define relations if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkshops::route('/'),
            'create' => Pages\CreateWorkshop::route('/create'),
            'edit' => Pages\EditWorkshop::route('/{record}/edit'),
        ];
    }
}
