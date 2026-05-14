# Technical Architecture

## General Architecture

The Server Room Supervision application follows a layered architecture. Each layer has a clear responsibility and communicates with the others through Laravel routes, controllers, services, models, events, and frontend components.

Main layers:

- Frontend layer.
- Backend layer.
- Database layer.
- Real-time communication layer.
- Sensor and server monitoring layer.
- AI and automation layer.
- Notification layer.

## Frontend Layer

The frontend is rendered mainly with Laravel Blade templates. Tailwind CSS is used to create a responsive and modern interface. Alpine.js is used for interactive components such as calendar behavior, chat workspace behavior, sidebar state, and lightweight UI interactions.

React is used for live monitoring widgets, while ApexCharts is used to display telemetry trends. Three.js and GSAP are used for visual and animated elements.

Important frontend files and folders:

- `resources/views/`
- `resources/views/layouts/`
- `resources/views/dashboard.blade.php`
- `resources/views/reports/`
- `resources/views/maintenance/`
- `resources/views/calendar/`
- `resources/views/chat/`
- `resources/views/ai-chat/`
- `resources/views/admin/`
- `resources/css/app.css`
- `resources/js/app.js`
- `resources/js/bootstrap.js`
- `resources/js/monitoring-widgets.jsx`
- `resources/js/calendar-workspace.js`
- `resources/js/chat-workspace.js`
- `resources/js/server-3d.js`

## Backend Layer

The backend is built with Laravel. Controllers receive HTTP requests and delegate complex operations to service classes. Services contain business logic such as telemetry processing, server metric ingestion, report generation, AI summaries, Telegram messaging, notification creation, and maintenance workflows.

Important backend areas:

- `app/Http/Controllers/`
- `app/Http/Controllers/Api/`
- `app/Http/Controllers/Admin/`
- `app/Http/Controllers/Auth/`
- `app/Services/`
- `app/Models/`
- `app/Events/`
- `app/Policies/`
- `app/Http/Middleware/`

This separation keeps the application easier to maintain because controllers remain focused on request/response handling, while services contain reusable business rules.

## Database Layer

The database layer uses MySQL and Laravel Eloquent. Migrations define the structure of all important entities.

Main database responsibilities:

- Store users and roles.
- Store authentication and two-factor challenge data.
- Store server definitions and server metrics.
- Store ESP32/DHT22 sensor readings.
- Store generated reports and AI summaries.
- Store maintenance tasks and task histories.
- Store messages, problems, solutions, and attachments.
- Store notifications and audit logs.
- Store Department Head invitations.

Important models:

- `User`
- `Server`
- `ServerMetric`
- `SensorReading`
- `Report`
- `ReportAiSummary`
- `MaintenanceTask`
- `MaintenanceTaskHistory`
- `Message`
- `Problem`
- `Solution`
- `UserNotification`
- `AuditLog`
- `DepartmentHeadInvite`
- `LoginTwoFactorChallenge`

## Real-Time Communication Layer

The application is prepared for real-time updates using Laravel Reverb, Laravel Echo, and private broadcast channels.

Events include:

- `SensorTelemetryUpdated`
- `ServerMetricStored`
- `MaintenanceTaskChanged`
- `UserNotificationCreated`
- `ReportGenerated`
- `ChatMessageCreated`

Important files:

- `routes/channels.php`
- `resources/js/bootstrap.js`
- `app/Events/`

The frontend subscribes to private channels and converts broadcast events into browser events. This allows dashboard widgets, notifications, and operational pages to react quickly to new information.

## Sensor / ESP32 Layer

The environmental monitoring layer is based on an ESP32 microcontroller connected to a DHT22 temperature and humidity sensor.

Technical flow:

1. DHT22 measures temperature and humidity.
2. ESP32 sends an HTTP POST request to `/api/sensor-readings`.
3. Laravel validates the token from `X-Sensor-Token`.
4. Laravel validates the payload values.
5. A `SensorReading` record is stored in MySQL.
6. The application writes an audit log.
7. A real-time telemetry event is broadcast.
8. Dashboard charts can display updated values.

Main files:

- `routes/api.php`
- `app/Http/Controllers/Api/SensorReadingController.php`
- `app/Models/SensorReading.php`
- `app/Events/SensorTelemetryUpdated.php`
- `config/sensors.php`

## Server Monitoring Layer

The server monitoring layer receives metrics from monitored servers. A server agent can collect system information such as CPU usage, RAM usage, disk usage, storage usage, temperature, network state, and uptime.

Technical flow:

1. Server agent collects metrics.
2. Agent sends HTTP POST request to `/api/server-metrics`.
3. Laravel validates the `X-Server-Token`.
4. Laravel stores a new `ServerMetric`.
5. The server `last_seen` timestamp is updated.
6. The monitoring service computes status such as normal, warning, critical, or offline.
7. A real-time event is broadcast to dashboard/server channels.

Main files:

- `app/Http/Controllers/Api/ServerMetricsController.php`
- `app/Services/ServerMetricsIngestionService.php`
- `app/Services/ServerMonitoringService.php`
- `app/Models/Server.php`
- `app/Models/ServerMetric.php`
- `app/Events/ServerMetricStored.php`

## AI And Automation Layer

The AI layer uses Groq's OpenAI-compatible API for two main purposes:

- AI-generated report summaries.
- AI Chat assistant.

The report system sends metrics and anomaly data to the AI service. The service returns a structured summary, observations, and recommendations. If the AI service is unavailable, fallback text is generated to keep the reporting feature reliable.

The AI Chat feature allows users to ask operational questions related to server room supervision. The application sends conversation history and a system prompt to the AI service.

The automation layer includes optional n8n webhook integration. After a report is generated, the system can send report metadata to n8n so external workflows can be triggered.

Main files:

- `app/Services/GroqReportSummaryService.php`
- `app/Services/AIChatService.php`
- `app/Services/PredictiveMaintenanceService.php`
- `app/Services/TemperaturePredictionService.php`
- `app/Services/ReportGenerationService.php`
- `ml_service/`
- `config/services.php`

## Notification Layer

The notification layer combines several channels:

- In-app notifications stored in the database.
- Real-time notification broadcasts.
- Email notifications through Laravel Mail and Brevo SMTP.
- Telegram messages through Telegram Bot API.

Typical notification events:

- New staff registration.
- Account approval or rejection.
- Maintenance task assignment.
- Chat mention.
- Report generation.
- Critical report alert.
- Two-factor authentication code.
- Suspicious login warning.

Main files:

- `app/Services/NotificationService.php`
- `app/Services/TelegramService.php`
- `app/Models/UserNotification.php`
- `app/Events/UserNotificationCreated.php`
- `app/Notifications/`
- `config/mail.php`
- `config/services.php`

## Authentication And Authorization Architecture

The security architecture combines authentication, verification, role-based access, approval status, middleware, and policies.

Main concepts:

- Users register as pending users.
- Department Heads approve or reject users.
- Approved users can access the main application.
- Department Heads can manage users, servers, and maintenance.
- Staff users access assigned operational features.
- Login requires email-based two-factor authentication.
- Important actions are logged using audit logs.

Main files:

- `routes/auth.php`
- `app/Http/Controllers/Auth/`
- `app/Http/Middleware/EnsureApprovedUser.php`
- `app/Http/Middleware/EnsureDepartmentHead.php`
- `app/Policies/`
- `app/Services/LoginTwoFactorService.php`
- `app/Services/AuthenticationSecurityService.php`

## Architecture Flow Summary

Sensor and server data enters the application through protected API endpoints. Laravel validates and stores this data in MySQL. Services calculate status, trends, reports, anomalies, and recommendations. The frontend displays results through dashboards, cards, charts, tables, and calendars. Real-time events update the UI. Notifications are sent through in-app alerts, Brevo email, and Telegram. AI and automation services add intelligent analysis and workflow extension.
