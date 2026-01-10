# Colivraison Gateway API Payloads

## API Endpoints

### 1. Health Check
**Endpoint:** `GET /api/health`

**Response:**
```json
{
  "status": "healthy",
  "timestamp": "2024-01-10T10:30:00Z",
  "service": "colivraison-gateway",
  "version": "1.0.0"
}
```

### 2. Status Mappings
**Endpoint:** `GET /api/status-mappings`

**Response:**
```json
{
  "mappings": {
    "pending": "En attente",
    "processing": "En cours de traitement",
    "shipped": "Expédié",
    "delivered": "Livré",
    "cancelled": "Annulé",
    "failed": "Échec"
  }
}
```

### 3. Dispatch Webhook
**Endpoint:** `POST /api/dispatch`

**Headers:**
```
Content-Type: application/json
Authorization: Bearer {token}
X-Webhook-Secret: {secret}
```

**Request Payload:**
```json
{
  "order_id": 123456,
  "status_id": 101,
  "service": "delivery",
  "reason": "Package delivered successfully",
  "lang": "fr"
}
```

**Parameters:**
- `order_id` (integer, required): The order/colis ID
- `status_id` (integer, required): The new status ID (see status mappings)
- `service` (string, required): Service type - must be one of: `delivery`, `warehouse`, `call_center`
- `reason` (string, optional): Reason for status change (max 500 chars)
- `lang` (string, optional): Language code - `fr`, `en`, or `ar` (default: `fr`)

**Response (Success):**
```json
{
  "success": true,
  "message": "Webhooks dispatched successfully",
  "data": {
    "webhooks_triggered": 3,
    "webhooks_failed": 0,
    "details": [
      {
        "webhook_id": 1,
        "endpoint": "https://partner.example.com/webhook",
        "status_code": 200,
        "response_time_ms": 145
      }
    ]
  }
}
```

**Response (Partial Success - 207 Multi-Status):**
```json
{
  "success": false,
  "message": "Some webhooks failed",
  "data": {
    "webhooks_triggered": 3,
    "webhooks_failed": 1,
    "details": [...]
  }
}
```

**Response (Validation Error - 422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "order_id": ["The order_id field is required."],
    "service": ["The selected service is invalid."]
  }
}
```

### 4. Dispatch to Partners
**Endpoint:** `POST /api/webhook/dispatch`

**Headers:**
```
Content-Type: application/json
Authorization: Bearer {token}
X-Webhook-Secret: {secret}
```

**Request Payload (Received from ColivraisonExpress/BEA):**
```json
{
  "order_id": 123456,
  "id_stats": 101,
  "id_partenaire": 5
}
```

**Parameters:**
- `order_id` (integer, required): The order/colis ID from ColivraisonExpress
- `id_stats` (integer, required): The status ID from ColivraisonExpress
- `id_partenaire` (integer, required): The partner ID to send webhooks to

**Gateway Response:**
```json
{
  "success": true,
  "processed": 2,
  "results": [
    {
      "webhook_id": 1,
      "endpoint": "https://partner-api.example.com/webhook",
      "success": true,
      "status_code": 200,
      "response_time_ms": 145
    },
    {
      "webhook_id": 2,
      "endpoint": "https://partner-backup.example.com/webhook",
      "success": true,
      "status_code": 200,
      "response_time_ms": 203
    }
  ]
}
```

**Response (No Webhooks Configured):**
```json
{
  "success": true,
  "message": "No active webhooks configured for partner",
  "processed": 0
}
```

**Response (Order Not Found - 404):**
```json
{
  "success": false,
  "message": "Order not found"
}
```

---

### Mapped Payload Sent to Partners

After receiving the request from ColivraisonExpress, the gateway maps the data and sends the following structured payload to each partner's webhook URL:

**Webhook Payload (Sent by Gateway to Partner):**
```json
{
  "tracking_id": "CLV-789012",
  "ref_order": "REF-2024-001",
  "status": {
    "id": 101,
    "name": "delivery_delivered",
    "sub_status": {
      "id": 1011,
      "name": "delivery_delivered_1",
      "reason": "Colis livré avec succès"
    }
  },
  "service": "delivery"
}
```

**Status Structure Explanation:**
- `status.id`: Main status ID (e.g., 101 for delivered)
- `status.name`: Main status name (e.g., "delivery_delivered")
- `status.sub_status.id`: Detailed sub-status ID
- `status.sub_status.name`: Sub-status identifier
- `status.sub_status.reason`: Translated reason based on webhook language setting
- `service`: Service type (delivery/warehouse/call_center)

**Mapping Process:**
1. Gateway receives simple payload from ColivraisonExpress (order_id, id_stats, id_partenaire)
2. Gateway fetches colis data from database using order_id
3. Gateway uses StatusMapper service to:
   - Map id_stats to main status (e.g., 4 → 101)
   - Map to sub-status (e.g., 4 → 1011)
   - Get translated reason based on webhook language
   - Determine service type
4. Gateway creates structured payload with tracking info and mapped status
5. Gateway sends mapped payload to all active webhooks for the specified partner

## Admin Panel Webhook Management

### 5. Create Webhook Configuration
**Endpoint:** `POST /admin/webhooks`

**Request Payload:**
```json
{
  "name": "Partner Delivery Updates",
  "url": "https://partner-api.example.com/webhook/delivery",
  "method": "POST",
  "headers": {
    "X-API-Key": "partner-secret-key",
    "Content-Type": "application/json"
  },
  "events": [
    "order.created",
    "order.status.updated",
    "delivery.completed"
  ],
  "active": true,
  "retry_config": {
    "max_attempts": 5,
    "backoff_seconds": [10, 30, 60, 300, 600]
  },
  "timeout_seconds": 30
}
```

**Response:**
```json
{
  "success": true,
  "webhook": {
    "id": 1,
    "name": "Partner Delivery Updates",
    "url": "https://partner-api.example.com/webhook/delivery",
    "active": true,
    "created_at": "2024-01-10T10:30:00Z"
  }
}
```

### 6. Update Webhook Configuration
**Endpoint:** `PUT /admin/webhooks/{id}`

**Request Payload:**
```json
{
  "name": "Updated Partner Webhook",
  "url": "https://new-partner-api.example.com/webhook",
  "active": true,
  "headers": {
    "Authorization": "Bearer new-token"
  },
  "events": [
    "order.status.updated",
    "delivery.completed",
    "delivery.failed"
  ]
}
```

### 7. Toggle Webhook Status
**Endpoint:** `POST /admin/webhooks/{id}/toggle`

**Request Payload:**
```json
{
  "active": false,
  "reason": "Maintenance mode"
}
```

**Response:**
```json
{
  "success": true,
  "webhook_id": 1,
  "active": false,
  "message": "Webhook disabled successfully"
}
```

## Event Types

### Supported Event Types
- `order.created` - New order created in the system
- `order.updated` - Order information updated
- `order.status.updated` - Order status changed
- `order.cancelled` - Order cancelled
- `delivery.assigned` - Delivery assigned to driver
- `delivery.pickup.completed` - Package picked up
- `delivery.in_transit` - Package in transit
- `delivery.out_for_delivery` - Out for delivery
- `delivery.completed` - Successfully delivered
- `delivery.failed` - Delivery attempt failed
- `delivery.rescheduled` - Delivery rescheduled
- `payment.received` - Payment received
- `payment.refunded` - Payment refunded
- `notification.sent` - Customer notification sent
- `tracking.updated` - Tracking information updated

## Error Responses

### Standard Error Format
```json
{
  "success": false,
  "error": {
    "code": "WEBHOOK_DISPATCH_FAILED",
    "message": "Failed to dispatch webhook to partner",
    "details": {
      "reason": "Connection timeout",
      "attempts": 3,
      "last_attempt": "2024-01-10T10:30:00Z"
    }
  },
  "timestamp": "2024-01-10T10:30:00Z"
}
```

### Common Error Codes
- `INVALID_PAYLOAD` - Invalid request payload
- `AUTHENTICATION_FAILED` - Authentication failed
- `WEBHOOK_NOT_FOUND` - Webhook configuration not found
- `WEBHOOK_DISABLED` - Webhook is currently disabled
- `RATE_LIMIT_EXCEEDED` - Too many requests
- `WEBHOOK_DISPATCH_FAILED` - Failed to dispatch webhook
- `PARTNER_UNREACHABLE` - Partner endpoint unreachable
- `TIMEOUT_ERROR` - Request timeout
- `VALIDATION_ERROR` - Data validation failed

## Rate Limits

- **API Endpoints**: 1000 requests per minute per API key
- **Webhook Dispatches**: 100 dispatches per minute per partner
- **Admin Panel**: No rate limits for authenticated admin users

## Webhook Retry Policy

When a webhook delivery fails, the gateway will retry with exponential backoff:

1. First retry: 10 seconds
2. Second retry: 30 seconds
3. Third retry: 1 minute
4. Fourth retry: 5 minutes
5. Fifth retry: 10 minutes

After 5 failed attempts, the webhook is marked as failed and requires manual intervention.

## Authentication

### API Authentication
```bash
curl -X POST https://gateway.colivraison.com/api/dispatch \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "X-Webhook-Secret: YOUR_WEBHOOK_SECRET" \
  -H "Content-Type: application/json" \
  -d '{"event_type": "order.status.updated", ...}'
```

### Admin Authentication
Admin panel uses session-based authentication with CSRF protection.