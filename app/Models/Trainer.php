<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'phone', 'password', 'hourly_rate', 'transfer_number', 'transfer_type'])]
#[Hidden(['password'])]
class Trainer extends Model
{
    public const TRANSFER_TYPE_WALLET = 'wallet';

    public const TRANSFER_TYPE_INSTAPAY = 'instapay';

    public function transferTypeLabel(): string
    {
        return self::transferTypeOptions()[$this->transfer_type] ?? $this->transfer_type;
    }

    public static function transferTypeOptions(): array
    {
        return [
            self::TRANSFER_TYPE_WALLET => 'محفظة',
            self::TRANSFER_TYPE_INSTAPAY => 'انستا باي',
        ];
    }

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
            'password' => 'hashed',
        ];
    }
}
