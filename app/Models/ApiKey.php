<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    use HasFactory;

    public $table = 'api_keys';

    public $timestamps = false;

    public $fillable = [
        'id', 'name', 'last_used_at', 'read_only', 'scopes', 'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scopes' => 'array',
        ];
    }

    /**
     * Whether the key has been granted the given scope. Unscoped keys (the
     * historical default) have no scopes and therefore grant none.
     */
    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes ?? [], true);
    }
}
