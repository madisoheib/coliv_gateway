-- Query to select email addresses of users with "client pro" role
-- These users can be configured to receive webhooks

-- Basic query to get client pro users with their emails
SELECT DISTINCT
    u.id as user_id,
    u.email,
    u.name,
    r.name as role_name,
    u.created_at as user_created_at,
    CASE 
        WHEN uw.id IS NOT NULL THEN 'Has Webhook'
        ELSE 'No Webhook'
    END as webhook_status,
    COUNT(DISTINCT uw.id) as webhook_count
FROM users u
INNER JOIN model_has_roles mhr ON u.id = mhr.model_id AND mhr.model_type = 'App\\Models\\User'
INNER JOIN roles r ON mhr.role_id = r.id
LEFT JOIN user_webhooks uw ON u.id = uw.user_id AND uw.is_active = 1
WHERE r.name = 'client pro'
GROUP BY u.id, u.email, u.name, r.name, u.created_at
ORDER BY u.name ASC;

-- Query to get client pro users without webhooks configured
SELECT 
    u.id as user_id,
    u.email,
    u.name
FROM users u
INNER JOIN model_has_roles mhr ON u.id = mhr.model_id AND mhr.model_type = 'App\\Models\\User'
INNER JOIN roles r ON mhr.role_id = r.id
LEFT JOIN user_webhooks uw ON u.id = uw.user_id AND uw.is_active = 1
WHERE r.name = 'client pro'
    AND uw.id IS NULL
ORDER BY u.name ASC;

-- Query to get client pro users with their webhook configurations
SELECT 
    u.id as user_id,
    u.email,
    u.name,
    uw.id as webhook_id,
    uw.name as webhook_name,
    uw.endpoint_url,
    uw.event_type,
    uw.service_type,
    uw.lang as language,
    uw.is_production,
    uw.is_active,
    uw.last_triggered_at,
    uw.failure_count
FROM users u
INNER JOIN model_has_roles mhr ON u.id = mhr.model_id AND mhr.model_type = 'App\\Models\\User'
INNER JOIN roles r ON mhr.role_id = r.id
INNER JOIN user_webhooks uw ON u.id = uw.user_id
WHERE r.name = 'client pro'
ORDER BY u.name ASC, uw.created_at DESC;

-- Insert sample webhook for a client pro user (example)
-- Replace {user_id} with actual user ID from the query above
/*
INSERT INTO user_webhooks (
    user_id,
    name,
    endpoint_url,
    event_type,
    service_type,
    security_type,
    security_token,
    lang,
    is_production,
    is_active
) VALUES (
    {user_id},
    'Status Update Webhook',
    'https://partner-api.example.com/webhook/status',
    'status_change',
    'all',
    'bearer',
    'encrypted_token_here',
    'fr',
    1,
    1
);
*/

-- Update webhook configuration for client pro users
/*
UPDATE user_webhooks uw
INNER JOIN users u ON uw.user_id = u.id
INNER JOIN model_has_roles mhr ON u.id = mhr.model_id AND mhr.model_type = 'App\\Models\\User'
INNER JOIN roles r ON mhr.role_id = r.id
SET 
    uw.is_active = 1,
    uw.lang = 'en'
WHERE r.name = 'client pro'
    AND uw.event_type = 'status_change';
*/