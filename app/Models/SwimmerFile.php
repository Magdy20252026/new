<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\File;

#[Fillable(['swimmer_id', 'type', 'title', 'file_path'])]
class SwimmerFile extends Model
{
    public const TYPE_PLAYER_PHOTO = 'player_photo';

    public const TYPE_BIRTH_CERTIFICATE = 'birth_certificate';

    public const TYPE_MEDICAL_REPORT = 'medical_report';

    public const TYPE_FEDERATION_CARD = 'federation_card';

    public static function typeOptions(): array
    {
        return [
            self::TYPE_PLAYER_PHOTO => 'صورة السباح',
            self::TYPE_BIRTH_CERTIFICATE => 'شهادة الميلاد',
            self::TYPE_MEDICAL_REPORT => 'التقرير الطبي',
            self::TYPE_FEDERATION_CARD => 'كارنية الاتحاد',
        ];
    }

    public function swimmer(): BelongsTo
    {
        return $this->belongsTo(Swimmer::class);
    }

    public function imageUrl(): string
    {
        return asset($this->file_path);
    }

    public function typeLabel(): string
    {
        return self::typeOptions()[$this->type] ?? $this->type;
    }

    protected static function booted(): void
    {
        static::deleting(function (self $swimmerFile): void {
            if (filled($swimmerFile->file_path) && File::exists(public_path($swimmerFile->file_path))) {
                File::delete(public_path($swimmerFile->file_path));
            }
        });
    }
}
