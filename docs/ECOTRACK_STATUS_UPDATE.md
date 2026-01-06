# Ecotrack Status Update - Code Reference

This document contains the `updateEcotrackStatus` function and all related models from ColivraisonExpress for development in another project.

---

## Main Function: updateEcotrackStatus

**File:** `app/Http/Controllers/api/PartnerApiController.php`
**Lines:** 236-321

```php
public function updateEcotrackStatus($id, $status, $raison)
{
    $getcolisdetails = Colis::find($id);

    $content = $raison;

    $getPartnerCount = ExternalApi::where("user_id", $getcolisdetails->id_partenaire)
        ->where("is_ecomanager", false)
        ->count();

    $getPartner = ExternalApi::where("user_id", $getcolisdetails->id_partenaire)
        ->where("is_ecomanager", false)
        ->first();

    //check status ::::::
    if ($status == 3) {
        if ($getcolisdetails->id_partenaire == 1889) {
            $newStatus = 3;
        } else {
            $newStatus = 1;
        }
    } elseif ($status == 4) {
        $newStatus = 2;
        $content = null;
    } elseif ($status == 86) {
        $newStatus = 4;
        $content = $raison;
    } elseif ($status == 19) {
        $getDelayReported = Reported::where("id_colis", $id)
            ->latest()
            ->first();

        $newDate = Carbon::parse($getDelayReported->updated_at)->addDays($getDelayReported["delay"]);
        $newStatus = 4;
        $content = "Reporteé le " . $newDate;
    } elseif ($status == 2) {
        $newStatus = 4;
        $getFieldStatus = Stats::find(2);
        $content = "R: " . $getFieldStatus->field_stats;
    } elseif ($status == 6) {
        $newStatus = 4;
        $getFieldStatus = Stats::find(6);
        $content = "R: " . $getFieldStatus->field_stats;
    } elseif ($status == 7) {
        $newStatus = 1;
    } elseif ($status == 999) {
        $newStatus = 4;
        $content = $raison;
    } else {
        $newStatus = 4;
        $getFieldStatus = Stats::find($status);
        $content = "R: " . $getFieldStatus->field_stats;
    }

    if ($getPartnerCount > 0) {
        $host = $getPartner["host"];

        if (strpos($host, "ecotrack") !== false) {
            if ($getPartner["external_status"] == true) {
                $checkwilyaCount = WilayaAuthToken::where("wilaya_id", $getcolisdetails->wilaya_id)
                    ->get()
                    ->count();

                if ($checkwilyaCount > 0) {
                    $checkWilayaAuth = WilayaAuthToken::select("child_tokens.*")
                        ->leftJoin("child_tokens", "child_tokens.id_token", "=", "wilaya_auth.id_token")
                        ->where("wilaya_id", $getcolisdetails->wilaya_id)
                        ->where("user_id", $getcolisdetails->id_partenaire)
                        ->first();

                    return $this->sendApiReuqest($host, $checkWilayaAuth["end_point_token"], $getcolisdetails->ref_order, $newStatus, $content);
                }
            } else {
                return $this->sendApiReuqest($host, $getPartner["end_point_token"], $getcolisdetails->ref_order, $newStatus, $content);
            }
        }
    }
}
```

---

## Helper Function: sendApiReuqest

**Lines:** 323-346

```php
public function sendApiReuqest($host, $token, $reforder, $status, $content)
{
    try {
        $response = (new Client())->post($host, [
            "headers" => [
                "Authorization" => "Bearer " . $token,
            ],
            "form_params" => [
                "tracking" => $reforder,
                "state_id" => $status,
                "content" => $content,
            ],
        ]);

        return $response->getBody()->getContents();
    } catch (ClientException $exception) {
        $errorMessage = $exception
            ->getResponse()
            ->getBody()
            ->getContents();

        return $errorMessage;
    }
}
```

---

## Status Mapping Logic

| Internal Status | External Status | Description |
|-----------------|-----------------|-------------|
| 3 | 1 or 3 | Returned (special case for partner 1889) |
| 4 | 2 | Delivered |
| 86 | 4 | Custom status with reason |
| 19 | 4 | Delayed/Postponed (includes delay date) |
| 2 | 4 | Failed - with status field reason |
| 6 | 4 | Failed - with status field reason |
| 7 | 1 | Wrong number |
| 999 | 4 | Custom status with raw reason |
| Other | 4 | Default - with status field reason |

---

## Related Models

### 1. Colis Model

**Table:** `colis`
**Primary Key:** `id_colis`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Colis extends Model
{
    protected $table = "colis";
    protected $primaryKey = "id_colis";
    public $timestamps = true;

    protected $fillable = [
        "nom_client",
        "wilaya",
        "remarque",
        "id_com",
        "tel",
        "tel_two",
        "adress",
        "commune",
        "qte",
        "produit",
        "price",
        "order_sku",
        "shipping_price",
        "id_stats",
        "id_hub",
        "wilaya_id",
        "comun_id",
        "wms_status",
        "id_partenaire",
        "echange",
        "refund",
        "openable",
        "stop_desk",
        "ref_order",
        "tracking_order",
        "brittle_order",
    ];

    public function stats_colis()
    {
        return $this->belongsTo(Stats::class, "id_stats");
    }

    public function reported()
    {
        return $this->hasMany(Reported::class, "id_colis", "id_colis")->latest();
    }
}
```

---

### 2. ExternalApi Model

**Table:** `external_api_token`
**Primary Key:** `id_token`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalApi extends Model
{
    protected $table = 'external_api_token';
    protected $primaryKey = 'id_token';
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'access_token',
        'host',
        'provider',
        'public_key',
        'country_db',
        'name',
        'enable',
        'external_status',
        'end_point_token',
        'is_warehouse',
        'is_ecomanager',
        'send_id_colis'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
```

---

### 3. Stats Model

**Table:** `stats`
**Primary Key:** `id_stats`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stats extends Model
{
    protected $primaryKey = 'id_stats';
    protected $table = 'stats';
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
        'icon_color'
    ];

    public function colis()
    {
        return $this->hasMany(Colis::class, 'id_stats', 'id_stats');
    }
}
```

---

### 4. Reported Model

**Table:** `reported_delivery`
**Primary Key:** `id_report`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reported extends Model
{
    protected $table = 'reported_delivery';
    protected $primaryKey = 'id_report';
    public $timestamps = true;

    protected $fillable = [
        'id_colis',
        'id_stats',
        'delay'
    ];
}
```

---

### 5. WilayaAuthToken Model

**Table:** `wilaya_auth`
**Primary Key:** `id_wilaya_token`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WilayaAuthToken extends Model
{
    protected $table = 'wilaya_auth';
    protected $primaryKey = 'id_wilaya_token';
    public $timestamps = false;

    protected $fillable = [
        'id_token',
        'wilaya_id'
    ];
}
```

---

## Database Tables Structure

### external_api_token

```sql
CREATE TABLE `external_api_token` (
    `id_token` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `access_token` VARCHAR(255),
    `host` VARCHAR(255),
    `provider` VARCHAR(100),
    `public_key` VARCHAR(255),
    `country_db` VARCHAR(50),
    `name` VARCHAR(100),
    `enable` BOOLEAN DEFAULT TRUE,
    `external_status` BOOLEAN DEFAULT FALSE,
    `end_point_token` VARCHAR(255),
    `is_warehouse` BOOLEAN DEFAULT FALSE,
    `is_ecomanager` BOOLEAN DEFAULT FALSE,
    `send_id_colis` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP,
    `updated_at` TIMESTAMP
);
```

### wilaya_auth

```sql
CREATE TABLE `wilaya_auth` (
    `id_wilaya_token` INT AUTO_INCREMENT PRIMARY KEY,
    `id_token` INT NOT NULL,
    `wilaya_id` INT NOT NULL
);
```

### child_tokens (referenced in JOIN)

```sql
CREATE TABLE `child_tokens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `id_token` INT NOT NULL,
    `user_id` INT NOT NULL,
    `end_point_token` VARCHAR(255)
);
```

### reported_delivery

```sql
CREATE TABLE `reported_delivery` (
    `id_report` INT AUTO_INCREMENT PRIMARY KEY,
    `id_colis` INT NOT NULL,
    `id_stats` INT,
    `delay` INT DEFAULT 0,
    `created_at` TIMESTAMP,
    `updated_at` TIMESTAMP
);
```

---

## Request Payload to External API

```json
{
    "tracking": "REF-ORDER-123",
    "state_id": 2,
    "content": "Delivered successfully"
}
```

### Headers

```
Authorization: Bearer {end_point_token}
Content-Type: application/x-www-form-urlencoded
```

---

## Dependencies

```php
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Carbon\Carbon;
```

---

## Usage Example

```php
// Called from other controllers when status changes
$partnerApiController = new PartnerApiController();

// When order is delivered
$partnerApiController->updateEcotrackStatus($colisId, 4, null);

// When order is returned
$partnerApiController->updateEcotrackStatus($colisId, 3, 'Customer refused');

// When order is delayed
$partnerApiController->updateEcotrackStatus($colisId, 19, null);

// Custom status with reason
$partnerApiController->updateEcotrackStatus($colisId, 999, 'Custom reason message');
```

---

## Flow Diagram

```
1. Get Colis details by ID
2. Get External API config for partner (where is_ecomanager = false)
3. Map internal status to external status (1-4)
4. Build content/reason message
5. Check if partner has external_status enabled
   - If YES: Get wilaya-specific token from child_tokens
   - If NO: Use partner's main end_point_token
6. Send POST request to partner's host with:
   - tracking: ref_order
   - state_id: mapped status
   - content: reason message
```
