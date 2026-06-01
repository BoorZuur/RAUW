<?php

namespace App\Models;

use Database\Factories\BlockedKeywordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['keyword', 'keyword_normalized', 'added_by_manager_id'])]
class BlockedKeyword extends Model
{
    /** @use HasFactory<BlockedKeywordFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * Canonicalize blocked keywords so uniqueness is stable across database collations.
     */
    public static function normalizeKeyword(string $keyword): string
    {
        return Str::of($keyword)
            ->squish()
            ->lower()
            ->toString();
    }

    protected static function booted(): void
    {
        static::saving(function (BlockedKeyword $blockedKeyword): void {
            $blockedKeyword->keyword = Str::of((string) $blockedKeyword->keyword)
                ->squish()
                ->toString();

            $blockedKeyword->keyword_normalized = self::normalizeKeyword($blockedKeyword->keyword);
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    protected function keyword(): Attribute
    {
        return Attribute::set(fn (mixed $value): string => Str::of((string) $value)
            ->squish()
            ->toString());
    }

    protected function keywordNormalized(): Attribute
    {
        return Attribute::set(fn (mixed $value): string => self::normalizeKeyword((string) $value));
    }

    public function addedByManager(): BelongsTo
    {
        return $this->belongsTo(Manager::class, 'added_by_manager_id');
    }
}
