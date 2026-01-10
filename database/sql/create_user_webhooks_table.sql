-- Create user_webhooks table for storing webhook configurations
-- This table links users to their webhook endpoints

CREATE TABLE IF NOT EXISTS `user_webhooks` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'Webhook configuration name',
  `endpoint_url` varchar(500) NOT NULL COMMENT 'Partner webhook URL',
  `event_type` varchar(50) NOT NULL DEFAULT 'all' COMMENT 'Type of events: all, status_change, order_created, order_updated',
  `service_type` varchar(50) NOT NULL DEFAULT 'all' COMMENT 'Service type: all, delivery, express, etc',
  `security_type` varchar(50) NOT NULL DEFAULT 'none' COMMENT 'Security type: none, bearer, api_key, custom',
  `security_token` text DEFAULT NULL COMMENT 'Encrypted security token',
  `lang` varchar(5) DEFAULT 'fr' COMMENT 'Response language: fr, en, ar',
  `is_production` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Production webhook flag',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Active status',
  `response_status_code` int(11) DEFAULT NULL COMMENT 'Expected response status code',
  `last_triggered_at` datetime DEFAULT NULL COMMENT 'Last webhook trigger timestamp',
  `last_response_code` int(11) DEFAULT NULL COMMENT 'Last response HTTP code',
  `last_response_body` text DEFAULT NULL COMMENT 'Last response body (truncated)',
  `failure_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Consecutive failure count',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_webhooks_user_id_index` (`user_id`),
  KEY `user_webhooks_event_type_index` (`event_type`),
  KEY `user_webhooks_service_type_index` (`service_type`),
  KEY `user_webhooks_is_active_index` (`is_active`),
  KEY `user_webhooks_is_production_index` (`is_production`),
  CONSTRAINT `user_webhooks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create webhook_logs table for tracking webhook calls
CREATE TABLE IF NOT EXISTS `webhook_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `webhook_id` bigint(20) UNSIGNED NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `payload` json DEFAULT NULL,
  `response_code` int(11) DEFAULT NULL,
  `response_body` text DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `execution_time` float DEFAULT NULL COMMENT 'Execution time in seconds',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `webhook_logs_webhook_id_index` (`webhook_id`),
  KEY `webhook_logs_event_type_index` (`event_type`),
  KEY `webhook_logs_created_at_index` (`created_at`),
  CONSTRAINT `webhook_logs_webhook_id_foreign` FOREIGN KEY (`webhook_id`) REFERENCES `user_webhooks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;