<?php

namespace OursBlanc\Xms\Filament\Resources\ApiTokenResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use OursBlanc\Xms\Filament\Resources\ApiTokenResource;
use OursBlanc\Xms\Models\ApiToken;

class ManageApiTokens extends ManageRecords
{
    protected static string $resource = ApiTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data): ApiToken {
                    ['token' => $token, 'plainTextToken' => $plainTextToken] = ApiToken::generate(
                        $data['name'],
                        $data['abilities'],
                    );

                    Notification::make()
                        ->title('API token created')
                        ->body("Copy it now, it will not be shown again:\n\n{$plainTextToken}")
                        ->persistent()
                        ->success()
                        ->send();

                    return $token;
                }),
        ];
    }
}
