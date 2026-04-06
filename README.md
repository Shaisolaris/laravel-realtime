# laravel-realtime

![CI](https://github.com/Shaisolaris/laravel-realtime/actions/workflows/ci.yml/badge.svg)

Laravel 11 real-time platform with broadcasting via Reverb/Pusher, WebSocket channels (private, presence, public), queued jobs, live notifications, messaging with typing indicators, presence tracking, activity feeds, live dashboard metrics, and outbound webhook delivery with HMAC signatures and retry logic.

## Stack

- **Framework:** Laravel 11, PHP 8.2+
- **Broadcasting:** Laravel Reverb (self-hosted) or Pusher
- **Queue:** Redis-backed Laravel Queue for async jobs
- **Auth:** Laravel Sanctum
- **WebSocket Channels:** Private, Presence, Public

## Real-Time Features

### Messaging
- Private channel per conversation (`conversation.{id}`)
- `NewMessage` broadcast on send (ShouldBroadcastNow)
- Typing indicators via presence channel (`UserTyping` event)
- Read receipts with bulk mark-read endpoint
- Message types: text, image, file
- Paginated message history

### Notifications
- Per-user private channel (`user.{id}`)
- `NotificationPushed` broadcast for instant delivery
- Unread count endpoint for badge UI
- Mark individual or all as read
- Push notification API for server-side triggers

### Presence
- Team-wide presence channel (`team.{id}`)
- Online/away/offline status tracking
- `UserPresenceUpdated` broadcast on status change
- Team member presence list endpoint
- Presence channel auth returns user data (id, name, status)

### Activity Feed
- Public team channel (`team.{id}.activity`)
- `ActivityFeedUpdated` broadcast via queue (ShouldBroadcast)
- Structured activity log: actor, action, subject, description
- Paginated activity history with user details

### Live Dashboard
- Private team channel (`team.{id}.dashboard`)
- `LiveDashboardUpdate` broadcast via queue
- Metric name, current value, previous value for delta calculation
- `BroadcastDashboardMetric` queued job for decoupled publishing

### Webhooks
- Outbound webhook delivery to registered URLs
- HMAC-SHA256 signature verification (`X-Webhook-Signature`)
- Event filtering (subscribe to specific events or `*` wildcard)
- Delivery logging with response status, body, duration
- Test endpoint for debugging
- Toggle active/inactive
- Queued delivery with 3 retries and exponential backoff (10s, 60s, 300s)
- Secret auto-generated per webhook

## Channel Authorization

| Channel | Type | Auth Logic |
|---|---|---|
| `user.{userId}` | Private | User ID must match |
| `conversation.{conversationId}` | Private/Presence | User must be a participant |
| `team.{teamId}` | Presence | User must belong to team, returns user data |
| `team.{teamId}.dashboard` | Private | User must belong to team |
| `team.{teamId}.activity` | Public | Open to all (team activity) |

## API Endpoints

### Messaging
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/conversations` | List user's conversations |
| GET | `/api/conversations/{id}/messages` | Paginated messages |
| POST | `/api/conversations/{id}/messages` | Send message (broadcasts) |
| POST | `/api/conversations/{id}/typing` | Typing indicator |
| POST | `/api/conversations/{id}/read` | Mark messages as read |

### Notifications
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/notifications` | Paginated notifications |
| GET | `/api/notifications/unread-count` | Unread badge count |
| POST | `/api/notifications/{id}/read` | Mark one as read |
| POST | `/api/notifications/read-all` | Mark all as read |
| POST | `/api/notifications/push` | Push notification (broadcasts) |

### Activity & Presence
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/activities` | Team activity feed |
| POST | `/api/activities` | Log activity (broadcasts) |
| POST | `/api/presence` | Update presence status |
| GET | `/api/presence/team` | Team members with status |

### Webhooks
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/webhooks` | List webhooks with delivery count |
| POST | `/api/webhooks` | Create webhook (returns secret once) |
| GET | `/api/webhooks/{id}/deliveries` | Delivery history |
| POST | `/api/webhooks/{id}/toggle` | Toggle active/inactive |
| POST | `/api/webhooks/{id}/test` | Send test delivery |
| DELETE | `/api/webhooks/{id}` | Delete webhook |

## Architecture

```
app/
├── Events/
│   ├── NewMessage.php              # ShouldBroadcastNow → private channel
│   ├── UserTyping.php              # ShouldBroadcastNow → presence channel
│   ├── NotificationPushed.php      # ShouldBroadcastNow → private channel
│   ├── ActivityFeedUpdated.php     # ShouldBroadcast (queued) → public channel
│   ├── UserPresenceUpdated.php     # ShouldBroadcastNow → presence channel
│   ├── LiveDashboardUpdate.php     # ShouldBroadcast (queued) → private channel
│   └── WebhookDispatched.php       # Internal event (not broadcast)
├── Jobs/
│   ├── DispatchWebhookJob.php      # Queued webhook delivery with retries
│   └── BroadcastDashboardMetric.php # Queued dashboard metric broadcast
├── Services/
│   └── WebhookService.php          # HMAC signing, HTTP delivery, logging
├── Models/
│   ├── Message.php                 # Conversation messages
│   ├── Conversation.php            # Direct/group with participants
│   ├── Notification.php            # User notifications with read tracking
│   ├── ActivityLog.php             # Team activity feed entries
│   ├── Webhook.php                 # Outbound webhook config with event filtering
│   ├── WebhookDelivery.php         # Delivery attempt logs
│   └── User.php                    # With presence_status
├── Http/Controllers/Api/
│   ├── MessageController.php       # CRUD + typing + read receipts
│   ├── NotificationController.php  # CRUD + push + unread count
│   ├── ActivityController.php      # Feed + log activity
│   ├── WebhookController.php       # CRUD + test + toggle + deliveries
│   └── PresenceController.php      # Update status + team list
├── config/broadcasting.php         # Reverb + Pusher + Redis + log drivers
├── routes/api.php                  # All REST endpoints
└── routes/channels.php             # Channel authorization rules
```

## Setup

```bash
git clone https://github.com/Shaisolaris/laravel-realtime.git
cd laravel-realtime
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate

# Start Reverb WebSocket server
php artisan reverb:start

# Start queue worker
php artisan queue:work redis --queue=broadcasts,webhooks,default

# Start HTTP server
php artisan serve
```

## Key Design Decisions

**ShouldBroadcastNow vs ShouldBroadcast.** Messages and typing indicators use `ShouldBroadcastNow` for zero-latency delivery. Activity feeds and dashboard metrics use `ShouldBroadcast` (queued) because slight delay is acceptable and it reduces WebSocket server load during bursts.

**Presence channels for typing and team status.** Presence channels automatically track who is subscribed, enabling "who's online" without manual heartbeats. The channel auth callback returns user data (id, name, status) that the frontend receives on join/leave events.

**Webhook HMAC signatures.** Every webhook delivery includes an `X-Webhook-Signature` header containing `HMAC-SHA256(payload, secret)`. Receivers verify this to ensure the payload hasn't been tampered with and originated from the platform.

**Webhook retries via queued jobs.** `DispatchWebhookJob` runs on the `webhooks` queue with 3 tries and exponential backoff (10s, 60s, 300s). Each delivery attempt is logged with response status, body, and duration for debugging.

**Activity feed as broadcast + persistence.** Activities are both stored in the database (for pagination/history) and broadcast in real-time. The public channel means any team member receives updates without explicit subscription.

## License

MIT
