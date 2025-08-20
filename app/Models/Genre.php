<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\OwnedByUser;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class Genre extends Model
{
    use OwnedByUser;

    protected $fillable = ['name','slug','user_id'];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function keywords()
    {
        return $this->hasMany(Keyword::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Genre $genre) {
            if (Schema::hasColumn($genre->getTable(), 'slug') && empty($genre->slug)) {
                $genre->slug = self::generateUniqueSlug((string) $genre->name);
            }
        });

        static::updating(function (Genre $genre) {
            if (Schema::hasColumn($genre->getTable(), 'slug') && empty($genre->slug)) {
                $genre->slug = self::generateUniqueSlug((string) $genre->name);
            }
        });
    }

    protected static function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: (string) Str::uuid();
        $slug = $base;
        $i = 1;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
            if ($i > 50) { // 念のための保険
                $slug = $base . '-' . Str::random(6);
                break;
            }
        }

        return $slug;
    }
}

