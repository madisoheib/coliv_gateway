<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Colis extends Model
{
    use HasFactory;
    protected $table = 'colis';

    protected $primaryKey = 'id_colis';

    public $timestamps = true;

    protected $fillable = [
        'nom_client',
        'wilaya',
        'remarque',
        'id_com',
        'tel',
        'tel_two',
        'adress',
        'commune',
        'qte',
        'produit',
        'price',
        'order_sku',
        'shipping_price',
        'id_stats',
        'id_hub',
        'wilaya_id',
        'comun_id',
        'id_partenaire',
        'ref_order',
        'tracking_order',
        'order_at',
    ];

    /**
     * Get the current status of the colis
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Stats::class, 'id_stats', 'id_stats');
    }

    /**
     * Get the owner/partner of this colis
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_partenaire', 'id');
    }
}
