<?php

namespace Tests\Unit;

use App\Services\StatusMapper;
use Tests\TestCase;

class StatusMapperTest extends TestCase
{
    protected StatusMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new StatusMapper();
    }

    /** @test */
    public function it_maps_delivery_delivered_status(): void
    {
        // DB status ID 4 = Delivered
        $mainStatus = $this->mapper->getMainStatus(4);
        $subStatus = $this->mapper->getSubStatus(4);

        $this->assertEquals(StatusMapper::DELIVERY_DELIVERED, $mainStatus);
        $this->assertEquals(1011, $subStatus);
    }

    /** @test */
    public function it_maps_call_failed_status(): void
    {
        // DB status ID 29 = Out of stock (Call Failed)
        $mainStatus = $this->mapper->getMainStatus(29);
        $subStatus = $this->mapper->getSubStatus(29);

        $this->assertEquals(StatusMapper::CALL_FAILED, $mainStatus);
        $this->assertEquals(3031, $subStatus);
    }

    /** @test */
    public function it_maps_call_cancelled_status(): void
    {
        // DB status ID 48 = Changed mind (Call Cancelled)
        $mainStatus = $this->mapper->getMainStatus(48);
        $subStatus = $this->mapper->getSubStatus(48);

        $this->assertEquals(StatusMapper::CALL_CANCELLED, $mainStatus);
        $this->assertEquals(30410, $subStatus);
    }

    /** @test */
    public function it_maps_delivery_returned_status(): void
    {
        // DB status ID 84 = Returned to hub
        $mainStatus = $this->mapper->getMainStatus(84);
        $subStatus = $this->mapper->getSubStatus(84);

        $this->assertEquals(StatusMapper::DELIVERY_RETURNED, $mainStatus);
        $this->assertEquals(1037, $subStatus);
    }

    /** @test */
    public function it_maps_warehouse_ready_status(): void
    {
        // DB status ID 17 = Packed (Warehouse Ready)
        $mainStatus = $this->mapper->getMainStatus(17);
        $subStatus = $this->mapper->getSubStatus(17);

        $this->assertEquals(StatusMapper::WAREHOUSE_READY, $mainStatus);
        $this->assertEquals(2011, $subStatus);
    }

    /** @test */
    public function it_returns_fallback_for_unknown_status(): void
    {
        // Unknown DB status ID
        $mainStatus = $this->mapper->getMainStatus(9999);

        $this->assertEquals(StatusMapper::WAREHOUSE_ON_PROCESS, $mainStatus);
    }

    /** @test */
    public function it_returns_correct_main_status_name(): void
    {
        $this->assertEquals('delivery_on_way', $this->mapper->getMainStatusName(100));
        $this->assertEquals('delivery_delivered', $this->mapper->getMainStatusName(101));
        $this->assertEquals('delivery_failed', $this->mapper->getMainStatusName(102));
        $this->assertEquals('delivery_returned', $this->mapper->getMainStatusName(103));
        $this->assertEquals('call_confirmed', $this->mapper->getMainStatusName(301));
        $this->assertEquals('call_failed', $this->mapper->getMainStatusName(303));
        $this->assertEquals('call_cancelled', $this->mapper->getMainStatusName(304));
    }

    /** @test */
    public function it_determines_correct_event_type(): void
    {
        $this->assertEquals('order_delivered', $this->mapper->determineEventType(StatusMapper::DELIVERY_DELIVERED));
        $this->assertEquals('order_returned', $this->mapper->determineEventType(StatusMapper::DELIVERY_RETURNED));
        $this->assertEquals('order_updated', $this->mapper->determineEventType(StatusMapper::DELIVERY_ON_WAY));
        $this->assertEquals('order_updated', $this->mapper->determineEventType(StatusMapper::CALL_CONFIRMED));
        $this->assertEquals('order_created', $this->mapper->determineEventType(StatusMapper::WAREHOUSE_ON_PROCESS, true));
    }

    /** @test */
    public function it_determines_correct_service_type(): void
    {
        $this->assertEquals('delivery', $this->mapper->determineServiceType(100));
        $this->assertEquals('delivery', $this->mapper->determineServiceType(101));
        $this->assertEquals('delivery', $this->mapper->determineServiceType(102));
        $this->assertEquals('warehouse', $this->mapper->determineServiceType(200));
        $this->assertEquals('warehouse', $this->mapper->determineServiceType(201));
        $this->assertEquals('call_center', $this->mapper->determineServiceType(300));
        $this->assertEquals('call_center', $this->mapper->determineServiceType(304));
    }

    /** @test */
    public function it_builds_complete_status_payload(): void
    {
        // DB status ID 4 = Delivered
        $payload = $this->mapper->buildStatusPayload(4, 'fr');

        $this->assertArrayHasKey('id', $payload);
        $this->assertArrayHasKey('name', $payload);
        $this->assertArrayHasKey('sub_status', $payload);

        $this->assertEquals(101, $payload['id']);
        $this->assertEquals('delivery_delivered', $payload['name']);

        $this->assertArrayHasKey('id', $payload['sub_status']);
        $this->assertArrayHasKey('name', $payload['sub_status']);
        $this->assertArrayHasKey('reason', $payload['sub_status']);

        $this->assertEquals(1011, $payload['sub_status']['id']);
    }

    /** @test */
    public function it_returns_french_translation_by_default(): void
    {
        $payload = $this->mapper->buildStatusPayload(4, 'fr');

        $this->assertEquals('Livré', $payload['sub_status']['reason']);
    }

    /** @test */
    public function it_returns_english_translation(): void
    {
        $payload = $this->mapper->buildStatusPayload(4, 'en');

        $this->assertEquals('Delivered', $payload['sub_status']['reason']);
    }

    /** @test */
    public function it_returns_arabic_translation(): void
    {
        $payload = $this->mapper->buildStatusPayload(4, 'ar');

        $this->assertEquals('تم التسليم', $payload['sub_status']['reason']);
    }

    /** @test */
    public function it_validates_language_and_falls_back_to_french(): void
    {
        $this->assertEquals('fr', $this->mapper->validateLanguage('fr'));
        $this->assertEquals('en', $this->mapper->validateLanguage('en'));
        $this->assertEquals('ar', $this->mapper->validateLanguage('ar'));
        $this->assertEquals('fr', $this->mapper->validateLanguage('invalid'));
        $this->assertEquals('fr', $this->mapper->validateLanguage(''));
    }

    /** @test */
    public function it_returns_supported_languages(): void
    {
        $languages = $this->mapper->getSupportedLanguages();

        $this->assertContains('fr', $languages);
        $this->assertContains('en', $languages);
        $this->assertContains('ar', $languages);
        $this->assertCount(3, $languages);
    }

    /** @test */
    public function it_gets_all_mappings_for_documentation(): void
    {
        $mappings = $this->mapper->getAllMappings();

        $this->assertIsArray($mappings);
        $this->assertNotEmpty($mappings);

        $firstMapping = $mappings[0];
        $this->assertArrayHasKey('db_status_id', $firstMapping);
        $this->assertArrayHasKey('main_status', $firstMapping);
        $this->assertArrayHasKey('sub_status', $firstMapping);
        $this->assertArrayHasKey('service', $firstMapping);
    }

    /** @test */
    public function it_handles_all_call_cancelled_sub_statuses(): void
    {
        $cancelledStatuses = [38, 39, 40, 41, 42, 43, 44, 45, 46, 48, 51, 52, 61, 62, 63, 64, 98];

        foreach ($cancelledStatuses as $dbStatusId) {
            $mainStatus = $this->mapper->getMainStatus($dbStatusId);
            $this->assertEquals(StatusMapper::CALL_CANCELLED, $mainStatus, "DB status {$dbStatusId} should map to CALL_CANCELLED");
        }
    }

    /** @test */
    public function it_handles_all_call_failed_sub_statuses(): void
    {
        $failedStatuses = [29, 30, 32, 33, 37, 47, 65, 77];

        foreach ($failedStatuses as $dbStatusId) {
            $mainStatus = $this->mapper->getMainStatus($dbStatusId);
            $this->assertEquals(StatusMapper::CALL_FAILED, $mainStatus, "DB status {$dbStatusId} should map to CALL_FAILED");
        }
    }

    /** @test */
    public function it_handles_all_delivery_returned_sub_statuses(): void
    {
        $returnedStatuses = [3, 71, 72, 80, 81, 82, 84, 85, 90, 91, 96, 97];

        foreach ($returnedStatuses as $dbStatusId) {
            $mainStatus = $this->mapper->getMainStatus($dbStatusId);
            $this->assertEquals(StatusMapper::DELIVERY_RETURNED, $mainStatus, "DB status {$dbStatusId} should map to DELIVERY_RETURNED");
        }
    }
}
