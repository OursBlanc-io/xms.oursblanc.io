<?php

namespace OursBlanc\Xms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    protected $table = 'xms_api_tokens';

    protected $fillable = [
        'name',
        'token',
        'abilities',
        'last_used_at',
    ];

    protected $hidden = [
        'token',
    ];

    protected $casts = [
        'abilities' => 'array',
        'last_used_at' => 'datetime',
    ];

    /**
     * Create a token, returning the model alongside the one-time plaintext value.
     *
     * @param  array<int, string>  $abilities
     * @return array{token: ApiToken, plainTextToken: string}
     */
    public static function generate(string $name, array $abilities = []): array
    {
        $plainTextToken = Str::random(64);

        $token = static::create([
            'name' => $name,
            'token' => hash('sha256', $plainTextToken),
            'abilities' => $abilities,
        ]);

        return [
            'token' => $token,
            'plainTextToken' => $plainTextToken,
        ];
    }

    public static function findByPlainTextToken(string $plainTextToken): ?self
    {
        return static::query()
            ->where('token', hash('sha256', $plainTextToken))
            ->first();
    }

    public function can(string $ability): bool
    {
        return in_array($ability, $this->abilities ?? [], true);
    }

    public function markAsUsed(): void
    {
        $this->forceFill(['last_used_at' => now()])->saveQuietly();
    }
}
