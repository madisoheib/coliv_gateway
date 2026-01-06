<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stats extends Model
{
    protected $table = 'stats';

    protected $primaryKey = 'id_stats';

    public $timestamps = false;

    protected $fillable = [
        'field_stats',
        'order_stats',
        'family_stats',
        'name_ar',
        'name_en',
        'name_fr',
        'wms_status',
        'call_status',
        'key_name',
        'class_icon',
        'icon_color',
    ];

    /**
     * Get the status name based on locale
     */
    public function getName(string $locale = 'fr'): string
    {
        return match($locale) {
            'ar' => $this->name_ar ?? $this->field_stats,
            'en' => $this->name_en ?? $this->field_stats,
            default => $this->name_fr ?? $this->field_stats,
        };
    }
}
