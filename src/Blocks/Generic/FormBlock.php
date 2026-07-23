<?php

namespace OursBlanc\Xms\Blocks\Generic;

use Filament\Forms\Components\Select;
use OursBlanc\Xms\Blocks\Block;
use OursBlanc\Xms\Models\Form as XmsForm;
use OursBlanc\Xms\Models\Page;

class FormBlock extends Block
{
    public static function name(): string
    {
        return 'form';
    }

    public static function label(): string
    {
        return 'Form';
    }

    public static function description(): string
    {
        return 'Renders a form (built in the Forms admin section) with its fields, honeypot, and CSRF protection.';
    }

    public static function fields(): array
    {
        return [
            Select::make('form_id')
                ->label('Form')
                ->options(fn () => XmsForm::query()->orderBy('name')->pluck('name', 'id'))
                ->required(),
        ];
    }

    public static function view(): string
    {
        return 'xms::blocks.form';
    }

    public static function resolveData(array $data, Page $page): array
    {
        if (! empty($data['form_id'])) {
            $data['form'] = XmsForm::with('fields')->find($data['form_id']);
        }

        return $data;
    }
}
