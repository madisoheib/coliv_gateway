# Colivraison Gateway

## Overview

Colivraison Gateway is a centralized API gateway service built with Laravel that serves as the main entry point for all external webhook integrations and API communications in the Colivraison ecosystem.

## Role

The Colivraison Gateway acts as a **unified gateway** that:

- **Routes incoming webhooks** from various external services to appropriate internal microservices
- **Manages authentication and authorization** for all incoming API requests
- **Provides centralized logging and monitoring** of all webhook events
- **Handles rate limiting and request throttling** to protect downstream services
- **Transforms and validates** incoming data before forwarding to internal services
- **Implements retry logic and error handling** for reliable message delivery

## Key Features

- **Webhook Management**: Centralized handling of webhooks from multiple providers
- **Request Routing**: Intelligent routing of requests to appropriate backend services
- **Security Layer**: Authentication, authorization, and request validation
- **Rate Limiting**: Protection against abuse and traffic spikes
- **Monitoring**: Real-time tracking of webhook events and API usage
- **Scalability**: Built to handle high volumes of concurrent requests

## Architecture

The gateway serves as the single entry point for external communications, sitting between:
- **External Services**: Third-party APIs, webhook providers, partner systems
- **Internal Services**: Colivraison microservices, databases, message queues

## Integration Endpoints

### API Endpoints

#### Public Endpoints
- `GET /api/health` - Gateway health check
- `GET /api/status-mappings` - Get status mapping configurations

#### Webhook Dispatch (Authenticated)
- `POST /api/dispatch` - Dispatch webhooks to configured endpoints
- `POST /api/webhook/dispatch` - Dispatch webhooks to partner systems

*Note: Webhook endpoints require internal authentication middleware*

### Admin Panel Routes

#### Authentication
- `GET /login` - Admin login page
- `POST /login` - Process login
- `POST /logout` - Logout

#### Dashboard (Admin Protected)
- `GET /admin/dashboard` - Admin dashboard

#### Webhook Management (Admin Protected)
- `GET /admin/webhooks` - List all webhooks
- `GET /admin/webhooks/create` - Create webhook form
- `POST /admin/webhooks` - Store new webhook
- `GET /admin/webhooks/{id}` - View webhook details
- `GET /admin/webhooks/{id}/edit` - Edit webhook form
- `PUT /admin/webhooks/{id}` - Update webhook
- `DELETE /admin/webhooks/{id}` - Delete webhook
- `POST /admin/webhooks/{id}/toggle` - Enable/disable webhook

### Authentication

- **API Endpoints**: Protected by `internal` and `webhook.auth` middleware
- **Admin Panel**: Protected by `admin` middleware with session-based authentication

## Built With

- **Laravel** - PHP Framework
- **MySQL** - Database for webhook logs and configuration
- **Redis** - Caching and rate limiting
- **Queue System** - Asynchronous processing of webhook events

## License

This project is proprietary software for Colivraison.
