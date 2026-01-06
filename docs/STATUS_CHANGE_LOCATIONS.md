# ColivraisonExpress - Status Change Locations

This document lists all locations in ColivraisonExpress where order/colis status (`id_stats`) is changed.
These are the integration points where webhook dispatch calls should be added.

---

## Gateway Webhook URL

```
GATEWAY_WEBHOOK_URL=http://coliv_gateway:8080/api/dispatch
```

## Integration Code Example

```php
use Illuminate\Support\Facades\Http;

// After any status change, call the gateway:
Http::post(env('GATEWAY_WEBHOOK_URL', 'http://coliv_gateway:8080/api/dispatch'), [
    'order_id' => $colis->id_colis,
    'status_id' => $newStatusId,
    'service' => 'delivery', // or 'warehouse', 'call_center'
    'reason' => 'Optional reason description'
]);
```

---

## CALLCENTER CONTROLLERS

### 1. DispatchCallsController
| File | Line | Method | Status | Description |
|------|------|--------|--------|-------------|
| `app/Http/Controllers/callcenter/DispatchCallsController.php` | 202 | `dispatchReport()` | 35 | Awaiting Dispatch - reported order |

### 2. CallconfirmationController
| File | Line | Method | Status | Description |
|------|------|--------|--------|-------------|
| `app/Http/Controllers/callcenter/CallconfirmationController.php` | 49-52 | `handleCallServiceUser()` | 27 | Sent to Maystro |
| `app/Http/Controllers/callcenter/CallconfirmationController.php` | 68-71 | `handleCallServiceUser()` | 401 | Maystro API Error |
| `app/Http/Controllers/callcenter/CallconfirmationController.php` | 551 | `updateColisStatus()` | dynamic | Generic status update |
| `app/Http/Controllers/callcenter/CallconfirmationController.php` | 607 | `updateDataColisStats()` | dynamic | Helper method |

### 3. CallcenterController
| File | Line | Method | Status | Description |
|------|------|--------|--------|-------------|
| `app/Http/Controllers/callcenter/CallcenterController.php` | 10814 | `add_colis_product_wms()` | 88 | WMS product unavailable |
| `app/Http/Controllers/callcenter/CallcenterController.php` | 10868 | `add_colis_product_wms()` | 16 | WMS product confirmed |
| `app/Http/Controllers/callcenter/CallcenterController.php` | 10902-10915 | `add_colis_product_wms()` | 27, 88, 16 | Multiple product scenarios |
| `app/Http/Controllers/callcenter/CallcenterController.php` | 10936 | `add_colis_product_wms()` | 54 | Assigned to Agent |
| `app/Http/Controllers/callcenter/CallcenterController.php` | 10988 | `add_colis_product_wms()` | 88 | Processing |
| `app/Http/Controllers/callcenter/CallcenterController.php` | 11008 | `add_colis_product_wms()` | 16 | WMS Confirmed |
| `app/Http/Controllers/callcenter/CallcenterController.php` | 11031 | `add_colis_product_wms()` | 54 | Assigned to Agent |
| `app/Http/Controllers/callcenter/CallcenterController.php` | 11051 | `add_colis_product_wms()` | 88 | Processing |
| `app/Http/Controllers/callcenter/CallcenterController.php` | 11086 | `add_colis_product_wms()` | 16 | WMS Confirmed |
| `app/Http/Controllers/callcenter/CallcenterController.php` | 11118 | `add_colis_product_wms()` | 54 | Assigned to Agent |
| `app/Http/Controllers/callcenter/CallcenterController.php` | 11509 | `update_stats_colis_gp()` | dynamic | Status from request |

---

## ADMIN CONTROLLERS

### 4. StatsColisController
| File | Line | Method | Status | Description |
|------|------|--------|--------|-------------|
| `app/Http/Controllers/admin/StatsColisController.php` | 79 | `relaunchColis()` | 10 | Relaunched |
| `app/Http/Controllers/admin/StatsColisController.php` | 121 | `delayColis()` | 19 | Delayed |
| `app/Http/Controllers/admin/StatsColisController.php` | 176 | status change | 3 | Awaiting Processing |
| `app/Http/Controllers/admin/StatsColisController.php` | 239 | dynamic status | dynamic | From request input |
| `app/Http/Controllers/admin/StatsColisController.php` | 318 | `validate_retour()` | 4 | Returned |
| `app/Http/Controllers/admin/StatsColisController.php` | 604 | `updateStatsColis()` | dynamic | From request raison |

### 5. ReceptionController
| File | Line | Method | Status | Description |
|------|------|--------|--------|-------------|
| `app/Http/Controllers/admin/ReceptionController.php` | 489 | `accept_reception()` | 11 | En Transit/Received (batch) |

### 6. RetourController
| File | Line | Method | Status | Description |
|------|------|--------|--------|-------------|
| `app/Http/Controllers/admin/RetourController.php` | 266 | `return_colis()` | 91/90 | WMS Return or Non-WMS Return |

### 7. ColisController
| File | Line | Method | Status | Description |
|------|------|--------|--------|-------------|
| `app/Http/Controllers/admin/ColisController.php` | 245 | `relaunchColis()` | 10 | Relaunched |
| `app/Http/Controllers/admin/ColisController.php` | 934 | duplicate handling | 36 | Duplicate Order |
| `app/Http/Controllers/admin/ColisController.php` | 4185 | `transfer_colis_groupe()` | 12 | Transferred/Grouped |
| `app/Http/Controllers/admin/ColisController.php` | 4229 | `transfer_colis_groupe()` | 12 | Transferred (filtered) |
| `app/Http/Controllers/admin/ColisController.php` | 4298 | `validate_colis_gp_wms()` | 11 | En Transit |
| `app/Http/Controllers/admin/ColisController.php` | 6357 | duplicate update | 34 | Duplicate/Cancelled |
| `app/Http/Controllers/admin/ColisController.php` | 7080 | `cleanEnPreparationDuplicateOrders()` | 101 | Cancelled - Duplicate |
| `app/Http/Controllers/admin/ColisController.php` | 7122 | `cleanEnPreparationWmsOrders()` | 102 | Cancelled - WMS Issue |

### 8. LivraisonController
| File | Line | Method | Status | Description |
|------|------|--------|--------|-------------|
| `app/Http/Controllers/admin/LivraisonController.php` | 172 | `update_retour_cash()` | 13 | Payment Returned (batch) |
| `app/Http/Controllers/admin/LivraisonController.php` | 597 | `validate_retour()` | 4 | Returned |
| `app/Http/Controllers/admin/LivraisonController.php` | 760 | status update | 3 | Awaiting Processing |

### 9. FicheColisController
| File | Line | Method | Status | Description |
|------|------|--------|--------|-------------|
| `app/Http/Controllers/admin/FicheColisController.php` | 446 | `store_fiche()` | 9 | Sent to Partner (with tracking) |
| `app/Http/Controllers/admin/FicheColisController.php` | 449 | `store_fiche()` | 9 | Sent to Partner |
| `app/Http/Controllers/admin/FicheColisController.php` | 497 | `store_fiche()` | 9 | Sent to Partner (batch) |

### 10. ClientController
| File | Line | Method | Status | Description |
|------|------|--------|--------|-------------|
| `app/Http/Controllers/admin/ClientController.php` | 2054 | `updateClientWmsProducts()` | 89 | WMS Approved/Ready |

---

## API CONTROLLERS

### 11. WebhooksController
| File | Line | Method | Status | Description |
|------|------|--------|--------|-------------|
| `app/Http/Controllers/api/WebhooksController.php` | 72 | `updateColisStatusFromWebhook()` | dynamic | External webhook mapping |

### 12. GoogleSheetApiController
| File | Line | Method | Status | Description |
|------|------|--------|--------|-------------|
| `app/Http/Controllers/api/GoogleSheetApiController.php` | 309 | `importOrdersFromGoogleSheet()` | 66 | Spam/Duplicate detection |

---

## SERVICES & JOBS

### 13. GoogleSheet Service
| File | Method | Description |
|------|--------|-------------|
| `app/Services/GoogleSheet.php` | various | StatsColis audit trail creation |

### 14. ProcessStockDispatch Job
| File | Line | Description |
|------|------|-------------|
| `app/Jobs/ProcessStockDispatch.php` | 87 | Dispatch processing status |

### 15. UpdateOrdersAboveLimitDaily Command
| File | Line | Description |
|------|------|-------------|
| `app/Console/Commands/UpdateOrdersAboveLimitDaily.php` | 51 | Daily status updates |

### 16. ColisImportCallCenter Importer
| File | Line | Description |
|------|------|-------------|
| `app/Imports/ColisImportCallCenter.php` | 149 | Import status tracking |

---

## STATUS CODE REFERENCE

| Code | Name | Service | Webhook Status |
|------|------|---------|----------------|
| 3 | Awaiting Processing | warehouse | 200 |
| 4 | Returned/Retour | delivery | 103 |
| 9 | Sent to Partner | delivery | 100 |
| 10 | En Livraison | delivery | 100 |
| 11 | En Transit/Received | delivery | 104 |
| 12 | Transferred/Grouped | warehouse | 200 |
| 13 | Payment Returned | delivery | 103 |
| 16 | WMS Confirmed | warehouse | 201 |
| 19 | Delayed/Reporté | call_center | 302 |
| 27 | Sent to Maystro | delivery | 100 |
| 34 | Duplicate/Cancelled | call_center | 304 |
| 35 | Awaiting Dispatch | warehouse | 200 |
| 36 | Duplicate Order | call_center | 304 |
| 47 | Reported | call_center | 302 |
| 54 | Assigned to Agent | delivery | 100 |
| 56 | Relaunched (Action) | call_center | 302 |
| 66 | Spam/Duplicate | call_center | 304 |
| 88 | Processing/In Progress | warehouse | 200 |
| 89 | WMS Approved/Ready | warehouse | 201 |
| 90 | Non-WMS Return | delivery | 103 |
| 91 | WMS Return | delivery | 103 |
| 101 | Cancelled - Duplicate | call_center | 304 |
| 102 | Cancelled - WMS Issue | call_center | 304 |
| 401 | Maystro API Error | delivery | 102 |

---

## WEBHOOK PAYLOAD SENT TO CUSTOMER

```json
{
    "tracking_id": "TRK-ABC123",
    "ref_order": "CMD-2025-001",
    "status": {
        "id": 101,
        "name": "delivery_delivered",
        "reason": "Le colis a été livré avec succès"
    },
    "service": "delivery"
}
```

---

## IMPLEMENTATION CHECKLIST

- [ ] Add `GATEWAY_WEBHOOK_URL` to ColivraisonExpress `.env`
- [ ] Create helper function/trait for webhook dispatch
- [ ] Add webhook call to DispatchCallsController
- [ ] Add webhook call to CallconfirmationController
- [ ] Add webhook call to CallcenterController
- [ ] Add webhook call to StatsColisController
- [ ] Add webhook call to ReceptionController
- [ ] Add webhook call to RetourController
- [ ] Add webhook call to ColisController
- [ ] Add webhook call to LivraisonController
- [ ] Add webhook call to FicheColisController
- [ ] Add webhook call to ClientController
- [ ] Add webhook call to WebhooksController
- [ ] Add webhook call to GoogleSheetApiController
- [ ] Test all webhook integrations
