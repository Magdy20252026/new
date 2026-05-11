<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\File;

#[Fillable(['trainer_id', 'title', 'file_path'])]
class TrainerFile extends Model
{
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    public function imageUrl(): string
    {
        return asset($this->file_path);
    }

    protected static function booted(): void
    {
        static::deleting(function (self $trainerFile): void {
            if (filled($trainerFile->file_path) && File::exists(public_path($trainerFile->file_path))) {
                File::delete(public_path($trainerFile->file_path));
            }
        });
    }
}
