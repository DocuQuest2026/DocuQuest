<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\DocumentTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

#[Fillable([
    'key',
    'name',
    'fee',
    'processing_days',
    'is_active',
])]
class DocumentType extends Model
{
    /** @use HasFactory<DocumentTypeFactory> */
    use HasFactory;

    private const CACHE_KEY = 'document-types.all';

    private const CACHE_SECONDS = 3600;

    /**
     * Keep the cached list of documents in step with the table, so a fee or name the admin
     * changes shows everywhere straight away.
     */
    protected static function booted(): void
    {
        static::creating(function (DocumentType $documentType): void {
            $documentType->key ??= static::uniqueKeyFor($documentType->name);
        });

        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fee' => 'integer',
            'processing_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<RecordRequest, $this>
     */
    public function recordRequests(): HasMany
    {
        return $this->hasMany(RecordRequest::class);
    }

    /**
     * Every document, offered or not, keyed by id and sorted by name. Requests keep pointing
     * at a document after it is switched off, so lookups must include those too.
     *
     * The cache only stores plain attributes (it refuses to unserialize models), and this is
     * read for every request row on a page, so it must not cost a query each time.
     *
     * @return Collection<int, DocumentType>
     */
    public static function everyDocument(): Collection
    {
        $rows = Cache::remember(
            self::CACHE_KEY,
            self::CACHE_SECONDS,
            fn (): array => static::query()->orderBy('name')->orderBy('id')->get()
                ->map(fn (DocumentType $documentType): array => $documentType->getAttributes())
                ->all()
        );

        return (new Collection($rows))
            ->map(fn (array $row): DocumentType => (new DocumentType)->newFromBuilder($row))
            ->keyBy('id');
    }

    /**
     * The documents requesters can currently choose from.
     *
     * @return Collection<int, DocumentType>
     */
    public static function offered(): Collection
    {
        return static::everyDocument()->filter(fn (DocumentType $documentType): bool => $documentType->is_active)->values();
    }

    /**
     * The name shown in the interface.
     */
    public function label(): string
    {
        return $this->name;
    }

    /**
     * The fee in pesos for one copy.
     */
    public function fee(): int
    {
        return $this->fee;
    }

    /**
     * The per-copy fee formatted for display, e.g. "₱150.00".
     */
    public function formattedFee(): string
    {
        return '₱'.number_format($this->fee, 2);
    }

    /**
     * The most working days the registrar needs to process this document.
     */
    public function processingDays(): int
    {
        return $this->processing_days;
    }

    /**
     * The date the document should be ready by, counting working days (Monday to Friday)
     * from the given moment, or from now.
     */
    public function estimatedReadyDate(?CarbonInterface $from = null): Carbon
    {
        return Carbon::instance($from ?? now())->addWeekdays($this->processing_days);
    }

    /**
     * A key for a new document that no other document is using, built from its name, in a form
     * that is safe to use as a form field name (letters, numbers and underscores only).
     */
    public static function uniqueKeyFor(string $name): string
    {
        $base = Str::slug($name, '_') ?: 'document';
        $key = $base;
        $suffix = 2;

        while (static::query()->where('key', $key)->exists()) {
            $key = "{$base}_{$suffix}";
            $suffix++;
        }

        return $key;
    }
}
