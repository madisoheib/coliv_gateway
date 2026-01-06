<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class StatusMapper
{
    /**
     * Main Webhook Status IDs
     */

    // Delivery Statuses (100-104)
    public const DELIVERY_ON_WAY = 100;
    public const DELIVERY_DELIVERED = 101;
    public const DELIVERY_FAILED = 102;
    public const DELIVERY_RETURNED = 103;
    public const DELIVERY_IN_HUB = 104;

    // Warehouse Statuses (200-201)
    public const WAREHOUSE_ON_PROCESS = 200;
    public const WAREHOUSE_READY = 201;

    // Call Center Statuses (300-304)
    public const CALL_ON_PROCESS = 300;
    public const CALL_CONFIRMED = 301;
    public const CALL_REPORTED = 302;
    public const CALL_FAILED = 303;
    public const CALL_CANCELLED = 304;

    /**
     * Supported languages
     */
    public const SUPPORTED_LANGUAGES = ['fr', 'en', 'ar'];
    public const DEFAULT_LANGUAGE = 'fr';

    /**
     * Cached translations
     */
    protected array $translations = [];

    /**
     * Main status name mappings
     */
    protected array $mainStatusNames = [
        100 => 'delivery_on_way',
        101 => 'delivery_delivered',
        102 => 'delivery_failed',
        103 => 'delivery_returned',
        104 => 'delivery_in_hub',
        200 => 'warehouse_on_process',
        201 => 'warehouse_ready',
        300 => 'call_on_process',
        301 => 'call_confirmed',
        302 => 'call_reported',
        303 => 'call_failed',
        304 => 'call_cancelled',
    ];

    /**
     * Status mapping: DB id_stats => [main_status, sub_status]
     * Translations are loaded from JSON files in lang/{locale}/status.json
     */
    protected array $statusMapping = [
        // DELIVERY RETURNED (103)
        3   => ['main' => 103, 'sub' => 1031],
        71  => ['main' => 103, 'sub' => 1032],
        72  => ['main' => 103, 'sub' => 1033],
        80  => ['main' => 103, 'sub' => 1034],
        81  => ['main' => 103, 'sub' => 1035],
        82  => ['main' => 103, 'sub' => 1036],
        84  => ['main' => 103, 'sub' => 1037],
        85  => ['main' => 103, 'sub' => 1038],
        90  => ['main' => 103, 'sub' => 1039],
        91  => ['main' => 103, 'sub' => 10310],
        96  => ['main' => 103, 'sub' => 10311],
        97  => ['main' => 103, 'sub' => 10312],

        // WAREHOUSE ON PROCESS (200)
        15  => ['main' => 200, 'sub' => 2001],
        16  => ['main' => 200, 'sub' => 2002],

        // WAREHOUSE READY (201)
        17  => ['main' => 201, 'sub' => 2011],

        // DELIVERY ON WAY (100)
        9   => ['main' => 100, 'sub' => 1001],
        10  => ['main' => 100, 'sub' => 1002],
        11  => ['main' => 100, 'sub' => 1003],
        13  => ['main' => 100, 'sub' => 1004],
        18  => ['main' => 100, 'sub' => 1005],
        19  => ['main' => 100, 'sub' => 1006],
        20  => ['main' => 100, 'sub' => 1007],
        21  => ['main' => 100, 'sub' => 1008],

        // DELIVERY FAILED (102)
        2   => ['main' => 102, 'sub' => 1021],
        6   => ['main' => 102, 'sub' => 1022],
        7   => ['main' => 102, 'sub' => 1023],
        14  => ['main' => 102, 'sub' => 1024],
        78  => ['main' => 102, 'sub' => 1025],

        // DELIVERY DELIVERED (101)
        4   => ['main' => 101, 'sub' => 1011],
        12  => ['main' => 101, 'sub' => 1012],
        22  => ['main' => 101, 'sub' => 1013],
        23  => ['main' => 101, 'sub' => 1014],
        24  => ['main' => 101, 'sub' => 1015],
        25  => ['main' => 101, 'sub' => 1016],

        // CALL ON PROCESS (300)
        28  => ['main' => 300, 'sub' => 3001],
        26  => ['main' => 300, 'sub' => 3002],
        34  => ['main' => 300, 'sub' => 3003],
        35  => ['main' => 300, 'sub' => 3004],

        // CALL FAILED (303)
        29  => ['main' => 303, 'sub' => 3031],
        30  => ['main' => 303, 'sub' => 3032],
        32  => ['main' => 303, 'sub' => 3033],
        33  => ['main' => 303, 'sub' => 3034],
        37  => ['main' => 303, 'sub' => 3035],
        47  => ['main' => 303, 'sub' => 3036],
        65  => ['main' => 303, 'sub' => 3037],
        77  => ['main' => 303, 'sub' => 3038],

        // CALL CONFIRMED (301)
        27  => ['main' => 301, 'sub' => 3011],

        // CALL CANCELLED (304)
        38  => ['main' => 304, 'sub' => 3041],
        39  => ['main' => 304, 'sub' => 3042],
        40  => ['main' => 304, 'sub' => 3043],
        41  => ['main' => 304, 'sub' => 3044],
        42  => ['main' => 304, 'sub' => 3045],
        43  => ['main' => 304, 'sub' => 3046],
        44  => ['main' => 304, 'sub' => 3047],
        45  => ['main' => 304, 'sub' => 3048],
        46  => ['main' => 304, 'sub' => 3049],
        48  => ['main' => 304, 'sub' => 30410],
        51  => ['main' => 304, 'sub' => 30411],
        52  => ['main' => 304, 'sub' => 30412],
        61  => ['main' => 304, 'sub' => 30413],
        62  => ['main' => 304, 'sub' => 30414],
        63  => ['main' => 304, 'sub' => 30415],
        64  => ['main' => 304, 'sub' => 30416],
        98  => ['main' => 304, 'sub' => 30417],
    ];

    /**
     * Load translations for a specific language
     */
    protected function loadTranslations(string $lang): array
    {
        if (!in_array($lang, self::SUPPORTED_LANGUAGES)) {
            $lang = self::DEFAULT_LANGUAGE;
        }

        if (isset($this->translations[$lang])) {
            return $this->translations[$lang];
        }

        $path = lang_path("{$lang}/status.json");

        if (File::exists($path)) {
            $this->translations[$lang] = json_decode(File::get($path), true) ?? [];
        } else {
            $this->translations[$lang] = [];
        }

        return $this->translations[$lang];
    }

    /**
     * Get translation for a sub-status ID
     */
    public function getTranslation(int $subStatusId, string $lang = 'fr'): string
    {
        $translations = $this->loadTranslations($lang);
        $key = (string) $subStatusId;

        if (isset($translations[$key])) {
            return $translations[$key];
        }

        // Fallback to French if translation not found
        if ($lang !== 'fr') {
            $frTranslations = $this->loadTranslations('fr');
            if (isset($frTranslations[$key])) {
                return $frTranslations[$key];
            }
        }

        return 'Unknown status';
    }

    /**
     * Get the complete status mapping for a DB status ID
     */
    public function getStatusMapping(int $dbStatusId): ?array
    {
        return $this->statusMapping[$dbStatusId] ?? null;
    }

    /**
     * Map internal status ID to main webhook status ID
     */
    public function getMainStatus(int $dbStatusId): int
    {
        $mapping = $this->statusMapping[$dbStatusId] ?? null;
        return $mapping ? $mapping['main'] : self::WAREHOUSE_ON_PROCESS;
    }

    /**
     * Map internal status ID to sub-status ID
     */
    public function getSubStatus(int $dbStatusId): int
    {
        $mapping = $this->statusMapping[$dbStatusId] ?? null;
        return $mapping ? $mapping['sub'] : ($this->getMainStatus($dbStatusId) * 10 + 1);
    }

    /**
     * Get the reason in specified language (loads from JSON files)
     */
    public function getReason(int $dbStatusId, string $lang = 'fr'): string
    {
        $mapping = $this->statusMapping[$dbStatusId] ?? null;
        if (!$mapping) {
            return $this->getTranslation(0, $lang); // Will return 'Unknown status'
        }

        return $this->getTranslation($mapping['sub'], $lang);
    }

    /**
     * Get main status name
     */
    public function getMainStatusName(int $mainStatusId): string
    {
        return $this->mainStatusNames[$mainStatusId] ?? 'unknown';
    }

    /**
     * Determine the event type based on main status
     */
    public function determineEventType(int $mainStatusId, bool $isFirstStatus = false): string
    {
        if ($isFirstStatus) {
            return 'order_created';
        }

        return match ($mainStatusId) {
            self::DELIVERY_DELIVERED => 'order_delivered',
            self::DELIVERY_RETURNED => 'order_returned',
            default => 'order_updated',
        };
    }

    /**
     * Determine the service type based on main status
     */
    public function determineServiceType(int $mainStatusId): string
    {
        return match (true) {
            $mainStatusId >= 100 && $mainStatusId <= 104 => 'delivery',
            $mainStatusId >= 200 && $mainStatusId <= 201 => 'warehouse',
            $mainStatusId >= 300 && $mainStatusId <= 304 => 'call_center',
            default => 'delivery',
        };
    }

    /**
     * Validate language code
     */
    public function validateLanguage(string $lang): string
    {
        return in_array($lang, self::SUPPORTED_LANGUAGES) ? $lang : self::DEFAULT_LANGUAGE;
    }

    /**
     * Build the complete status payload for webhook
     *
     * Returns structure:
     * {
     *   "id": 103,
     *   "name": "delivery_returned",
     *   "sub_status": {
     *     "id": 1031,
     *     "name": "return_cancelled",
     *     "reason": "Retour (annulée)"
     *   }
     * }
     */
    public function buildStatusPayload(int $dbStatusId, string $lang = 'fr'): array
    {
        $lang = $this->validateLanguage($lang);
        $mapping = $this->getStatusMapping($dbStatusId);

        if (!$mapping) {
            // Fallback for unmapped statuses
            return [
                'id' => self::WAREHOUSE_ON_PROCESS,
                'name' => 'warehouse_on_process',
                'sub_status' => [
                    'id' => 2000,
                    'name' => 'unknown',
                    'reason' => 'Unknown status (ID: ' . $dbStatusId . ')',
                ],
            ];
        }

        $mainStatusId = $mapping['main'];
        $mainStatusName = $this->getMainStatusName($mainStatusId);
        $subStatusId = $mapping['sub'];

        // Generate sub-status name from main status name + sequence
        $subStatusName = $mainStatusName . '_' . ($subStatusId % 100);

        return [
            'id' => $mainStatusId,
            'name' => $mainStatusName,
            'sub_status' => [
                'id' => $subStatusId,
                'name' => $subStatusName,
                'reason' => $this->getTranslation($subStatusId, $lang),
            ],
        ];
    }

    /**
     * Get all status mappings for documentation
     */
    public function getAllMappings(): array
    {
        $result = [];

        foreach ($this->statusMapping as $dbId => $mapping) {
            $subStatusId = $mapping['sub'];
            $result[] = [
                'db_status_id' => $dbId,
                'main_status' => [
                    'id' => $mapping['main'],
                    'name' => $this->getMainStatusName($mapping['main']),
                ],
                'sub_status' => [
                    'id' => $subStatusId,
                    'reason_fr' => $this->getTranslation($subStatusId, 'fr'),
                    'reason_en' => $this->getTranslation($subStatusId, 'en'),
                    'reason_ar' => $this->getTranslation($subStatusId, 'ar'),
                ],
                'service' => $this->determineServiceType($mapping['main']),
            ];
        }

        return $result;
    }

    /**
     * Get all supported languages
     */
    public function getSupportedLanguages(): array
    {
        return self::SUPPORTED_LANGUAGES;
    }
}
