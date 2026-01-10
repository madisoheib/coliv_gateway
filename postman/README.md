# Postman Collection for Colivraison Gateway API

## Overview
This folder contains Postman collections and environments for testing the Colivraison Gateway API endpoints.

## Files

- **Colivraison_Gateway_API.postman_collection.json** - Main collection with all API endpoints
- **environment.json** - Environment variables for different deployment stages

## How to Use

### Import into Postman

1. Open Postman
2. Click on "Import" button
3. Select the `Colivraison_Gateway_API.postman_collection.json` file
4. Import the `environment.json` file
5. Select the "Colivraison Gateway Environment" from the environment dropdown

### Configure Environment Variables

Before running the requests, configure these variables in the environment:

- `api_token` - Your API authentication token
- `webhook_secret` - Your webhook secret key
- `admin_password` - Admin panel password

### Collection Structure

```
├── Public Endpoints
│   ├── Health Check
│   └── Status Mappings
├── Webhook Dispatch
│   ├── Dispatch Webhook
│   └── Dispatch to Partners
├── Admin Authentication
│   ├── Login
│   └── Logout
├── Admin Webhook Management
│   ├── List Webhooks
│   ├── Get Webhook Details
│   ├── Create Webhook
│   ├── Update Webhook
│   ├── Toggle Webhook Status
│   └── Delete Webhook
└── Test Scenarios
    ├── Order Created Event
    ├── Delivery Failed Event
    └── Tracking Update Event
```

## Testing Workflow

### 1. Basic Health Check
Start by testing the public endpoints:
- Run "Health Check" to verify the gateway is running
- Run "Status Mappings" to get available status codes

### 2. Admin Authentication
If testing admin endpoints:
1. Run "Login" request with credentials
2. Save the session cookie/token
3. Use it for subsequent admin requests

### 3. Webhook Testing
For webhook dispatch testing:
1. Ensure you have the correct `api_token` and `webhook_secret`
2. Run "Dispatch Webhook" with sample payload
3. Check response for dispatch confirmation

### 4. Test Scenarios
Use the pre-configured test scenarios to simulate:
- Order creation events
- Delivery failure events
- Tracking update events

## Environment Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `base_url` | Local development URL | `http://localhost:8000` |
| `staging_url` | Staging environment URL | `https://staging-gateway.colivraison.com` |
| `production_url` | Production URL | `https://gateway.colivraison.com` |
| `api_token` | API authentication token | `sk_live_xxxxx` |
| `webhook_secret` | Webhook secret key | `whsec_xxxxx` |
| `admin_email` | Admin email | `admin@colivraison.com` |
| `admin_password` | Admin password | `secure_password` |
| `partner_id` | Test partner ID | `PARTNER-001` |
| `test_order_id` | Test order ID | `ORD-TEST-001` |
| `test_tracking_number` | Test tracking number | `CLV-TEST-001` |

## Running Tests

### Manual Testing
1. Select a request from the collection
2. Review/modify the request body if needed
3. Click "Send"
4. Verify the response

### Automated Testing
You can run the entire collection using:

```bash
newman run Colivraison_Gateway_API.postman_collection.json \
  -e environment.json \
  --env-var "api_token=YOUR_TOKEN" \
  --env-var "webhook_secret=YOUR_SECRET"
```

## Response Examples

### Successful Webhook Dispatch
```json
{
  "success": true,
  "message": "Webhook dispatched successfully",
  "dispatch_id": "DSP-456789",
  "timestamp": "2024-01-10T10:30:01Z"
}
```

### Failed Request
```json
{
  "success": false,
  "error": {
    "code": "AUTHENTICATION_FAILED",
    "message": "Invalid API token"
  }
}
```

## Troubleshooting

### Common Issues

1. **401 Unauthorized**
   - Check your `api_token` and `webhook_secret`
   - Ensure authentication headers are included

2. **404 Not Found**
   - Verify the `base_url` is correct
   - Check if the endpoint path is correct

3. **500 Internal Server Error**
   - Check the request payload format
   - Verify all required fields are present

### Debug Tips

- Use Postman Console (View → Show Postman Console) to see detailed request/response logs
- Check the "Headers" tab in responses for additional debugging information
- Use the "Pre-request Script" tab to add logging or dynamic values

## Support

For issues or questions about the API, contact the development team or check the main API documentation.