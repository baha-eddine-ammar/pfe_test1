# Complete Feature List

## Dashboard

| Field | Description |
|---|---|
| Purpose | Provide a central overview of the server room status. |
| How It Works Technically | The dashboard reads environmental telemetry, local workstation status, and server metrics through Laravel services and displays them as cards and charts. |
| Main Files / Folders | `app/Http/Controllers/DashboardController.php`, `app/Services/EnvironmentalTelemetryService.php`, `app/Services/LocalSystemStatusService.php`, `resources/views/dashboard.blade.php`, `resources/js/monitoring-widgets.jsx` |
| Technologies Used | Laravel, Blade, Tailwind CSS, Alpine.js, React, ApexCharts, MySQL |
| Role In Final Application | Main supervision interface used to quickly understand the current state of the server room. |
| Screenshot / Report Section | Dashboard overview with cards and charts. |

## Environmental Telemetry

| Field | Description |
|---|---|
| Purpose | Monitor temperature and humidity in the server room. |
| How It Works Technically | Sensor readings are stored in the `sensor_readings` table. The telemetry service retrieves the latest values and historical trends for charts. |
| Main Files / Folders | `app/Models/SensorReading.php`, `app/Services/EnvironmentalTelemetryService.php`, `app/Http/Controllers/DashboardController.php` |
| Technologies Used | Laravel, MySQL, Eloquent, ApexCharts |
| Role In Final Application | Tracks environmental conditions that can affect server availability. |
| Screenshot / Report Section | Temperature and humidity graphs. |

## ESP32 / DHT22 Sensor Data Handling

| Field | Description |
|---|---|
| Purpose | Connect physical sensor data to the web application. |
| How It Works Technically | ESP32 sends temperature and humidity readings to `/api/sensor-readings`. The request is authenticated using `X-Sensor-Token`, validated, stored, logged, and broadcast to the dashboard channel. |
| Main Files / Folders | `routes/api.php`, `app/Http/Controllers/Api/SensorReadingController.php`, `app/Events/SensorTelemetryUpdated.php`, `config/sensors.php` |
| Technologies Used | ESP32, DHT22, Laravel API, MySQL, Laravel Reverb |
| Role In Final Application | Provides real-time environmental monitoring from hardware. |
| Screenshot / Report Section | ESP32/DHT22 setup and sensor communication diagram. |

## Server Section

| Field | Description |
|---|---|
| Purpose | Register and monitor servers. |
| How It Works Technically | Servers are stored with identifiers and API tokens. Metrics are linked to each server and transformed into status cards by the monitoring service. |
| Main Files / Folders | `app/Http/Controllers/ServerController.php`, `app/Models/Server.php`, `app/Models/ServerMetric.php`, `app/Services/ServerMonitoringService.php`, `resources/views/servers/` |
| Technologies Used | Laravel, Eloquent, MySQL, Blade, Tailwind CSS |
| Role In Final Application | Allows the team to view server performance and availability. |
| Screenshot / Report Section | Server list and server details page. |

## Server Metrics API

| Field | Description |
|---|---|
| Purpose | Receive automated server performance measurements. |
| How It Works Technically | Monitoring agents post CPU, RAM, disk, storage, temperature, network, and uptime metrics to `/api/server-metrics` using `X-Server-Token`. The service stores the metric, updates `last_seen`, writes an audit log, and broadcasts an event. |
| Main Files / Folders | `app/Http/Controllers/Api/ServerMetricsController.php`, `app/Services/ServerMetricsIngestionService.php`, `app/Events/ServerMetricStored.php` |
| Technologies Used | Laravel API, MySQL, Reverb, Python/psutil agent |
| Role In Final Application | Automates infrastructure health monitoring. |
| Screenshot / Report Section | Server monitoring API flow. |

## Maintenance Section

| Field | Description |
|---|---|
| Purpose | Manage preventive and corrective maintenance tasks. |
| How It Works Technically | Department Heads create and assign tasks. Staff update statuses according to allowed transitions. Changes are recorded in history, notifications are sent, and events are broadcast. |
| Main Files / Folders | `app/Http/Controllers/MaintenanceTaskController.php`, `app/Models/MaintenanceTask.php`, `app/Models/MaintenanceTaskHistory.php`, `app/Services/MaintenanceTaskWorkflowService.php`, `resources/views/maintenance/` |
| Technologies Used | Laravel, MySQL, Policies, Notifications, Reverb |
| Role In Final Application | Organizes technical interventions and tracks their progress. |
| Screenshot / Report Section | Maintenance task list and detail page. |

## Calendar

| Field | Description |
|---|---|
| Purpose | Plan and visualize maintenance activities. |
| How It Works Technically | The calendar controller transforms visible maintenance tasks into calendar events. Department Heads see team tasks, while staff see assigned tasks. |
| Main Files / Folders | `app/Http/Controllers/CalendarController.php`, `resources/views/calendar/index.blade.php`, `resources/js/calendar-workspace.js` |
| Technologies Used | Laravel, Blade, Alpine.js, Tailwind CSS |
| Role In Final Application | Provides a planning interface for interventions. |
| Screenshot / Report Section | Monthly maintenance calendar. |

## Reports Section

| Field | Description |
|---|---|
| Purpose | Generate operational reports for selected periods. |
| How It Works Technically | Reports can be daily, weekly, or monthly. The system collects sensor and server data, calculates metrics, detects anomalies, stores the report, creates AI summaries, notifies users, and can trigger n8n automation. |
| Main Files / Folders | `app/Http/Controllers/ReportController.php`, `app/Services/ReportGenerationService.php`, `app/Services/ReportMetricsCalculator.php`, `app/Services/DatabaseSensorDataProvider.php`, `resources/views/reports/` |
| Technologies Used | Laravel, MySQL, Groq API, n8n, Telegram, Blade |
| Role In Final Application | Provides formal documentation of server room conditions and incidents. |
| Screenshot / Report Section | Generated report page with metrics and AI summary. |

## AI Report Summaries

| Field | Description |
|---|---|
| Purpose | Transform raw report metrics into understandable analysis. |
| How It Works Technically | The Groq report summary service sends report data to a Groq chat completions endpoint and expects structured output containing summary, observations, and recommendations. If the external service fails, deterministic fallback text is generated. |
| Main Files / Folders | `app/Services/GroqReportSummaryService.php`, `app/Models/ReportAiSummary.php`, `app/Services/ReportGenerationService.php` |
| Technologies Used | Groq API, Laravel HTTP Client, MySQL |
| Role In Final Application | Makes reports more useful for decision-making and documentation. |
| Screenshot / Report Section | AI summary block in report detail. |

## Predictive Maintenance

| Field | Description |
|---|---|
| Purpose | Provide preventive recommendations before incidents become critical. |
| How It Works Technically | A rule-based service checks trends such as rising temperature, humidity drift, and critical latest status. It returns recommendations for maintenance or inspection. |
| Main Files / Folders | `app/Services/PredictiveMaintenanceService.php` |
| Technologies Used | Laravel service logic |
| Role In Final Application | Supports proactive maintenance decisions. |
| Screenshot / Report Section | Predictive insights in report or architecture section. |

## AI Chat / IA Assistant

| Field | Description |
|---|---|
| Purpose | Provide an intelligent assistant for server room supervision questions. |
| How It Works Technically | The AI Chat controller manages the page and session history. The AI service sends prompts and conversation history to Groq Chat Completions. Fallback suggestions are available for common operational topics. |
| Main Files / Folders | `app/Http/Controllers/AIChatController.php`, `app/Services/AIChatService.php`, `resources/views/ai-chat/index.blade.php` |
| Technologies Used | Groq API, Laravel, Blade, Tailwind CSS |
| Role In Final Application | Helps users analyze problems and get operational guidance. |
| Screenshot / Report Section | AI Chat conversation screen. |

## Chat

| Field | Description |
|---|---|
| Purpose | Enable team communication inside the supervision platform. |
| How It Works Technically | Users send and delete messages through Laravel routes. The chat workspace synchronizes messages from the backend, supports filtering, detects mentions, and triggers notifications. |
| Main Files / Folders | `app/Http/Controllers/ChatController.php`, `app/Services/ChatWorkspaceService.php`, `app/Models/Message.php`, `resources/views/chat/index.blade.php`, `resources/js/chat-workspace.js` |
| Technologies Used | Laravel, MySQL, Alpine.js, Fetch API |
| Role In Final Application | Improves communication between Department Heads and staff. |
| Screenshot / Report Section | Team chat with messages and mentions. |

## Problems Section

| Field | Description |
|---|---|
| Purpose | Document technical problems and incidents. |
| How It Works Technically | Users create problems with title, description, metadata, and attachments. Department Heads are notified when a new problem is created. |
| Main Files / Folders | `app/Http/Controllers/ProblemController.php`, `app/Models/Problem.php`, `app/Models/ProblemAttachment.php`, `resources/views/problems/` |
| Technologies Used | Laravel, MySQL, File Storage, Notifications |
| Role In Final Application | Creates a traceable incident knowledge base. |
| Screenshot / Report Section | Problem list and problem detail page. |

## Solutions Section

| Field | Description |
|---|---|
| Purpose | Document solutions and corrective actions. |
| How It Works Technically | Users create solutions linked to problems or operational knowledge. Attachments can be uploaded and safely downloaded. |
| Main Files / Folders | `app/Http/Controllers/SolutionController.php`, `app/Models/Solution.php`, `app/Models/SolutionAttachment.php`, `app/Http/Controllers/AttachmentDownloadController.php`, `resources/views/solutions/` |
| Technologies Used | Laravel, MySQL, File Storage |
| Role In Final Application | Builds a reusable knowledge base for future incidents. |
| Screenshot / Report Section | Solution detail and attachments. |

## Admin Section And Users Management

| Field | Description |
|---|---|
| Purpose | Manage users, access, approvals, and roles. |
| How It Works Technically | Department Heads access the admin area to approve or reject users, promote or demote roles, and manage Department Head invitations. Actions are notified and logged. |
| Main Files / Folders | `app/Http/Controllers/Admin/AdminController.php`, `app/Http/Controllers/Admin/UserManagementController.php`, `app/Http/Controllers/Admin/DepartmentHeadInviteController.php`, `app/Services/DepartmentHeadInviteService.php`, `resources/views/admin/` |
| Technologies Used | Laravel, Policies, Middleware, Mail, MySQL |
| Role In Final Application | Provides governance and secure access management. |
| Screenshot / Report Section | Users management dashboard. |

## Authentication And Security

| Field | Description |
|---|---|
| Purpose | Protect access to the application and its modules. |
| How It Works Technically | Users authenticate through Laravel sessions. Registration creates pending users. Approved users can access the application. Login triggers email-based two-factor authentication. Department Head routes are protected by middleware. |
| Main Files / Folders | `routes/auth.php`, `app/Http/Controllers/Auth/`, `app/Services/LoginTwoFactorService.php`, `app/Models/LoginTwoFactorChallenge.php`, `app/Models/User.php` |
| Technologies Used | Laravel Breeze, Sessions, Mail, Middleware, Policies |
| Role In Final Application | Ensures only authorized users access supervision features. |
| Screenshot / Report Section | Login, registration, 2FA, and approval workflow. |

## Notifications And Alerts

| Field | Description |
|---|---|
| Purpose | Inform users about important events. |
| How It Works Technically | Notifications are stored in the database, broadcast in real time, and optionally sent through email or Telegram depending on the event. |
| Main Files / Folders | `app/Services/NotificationService.php`, `app/Models/UserNotification.php`, `app/Events/UserNotificationCreated.php`, `app/Http/Controllers/NotificationController.php` |
| Technologies Used | Laravel Notifications, MySQL, Reverb, Brevo SMTP, Telegram API |
| Role In Final Application | Reduces response time and improves communication. |
| Screenshot / Report Section | Notification list or dropdown. |

## Telegram Integration

| Field | Description |
|---|---|
| Purpose | Send mobile alerts and link user accounts to Telegram. |
| How It Works Technically | Users generate a Telegram connection token from their profile. The Telegram bot receives `/start connect_token` through a webhook, links the chat ID to the user, and confirms the connection. |
| Main Files / Folders | `app/Http/Controllers/TelegramController.php`, `app/Services/TelegramService.php`, `resources/views/profile/` |
| Technologies Used | Telegram Bot API, Laravel HTTP Client, Webhooks |
| Role In Final Application | Provides fast external alerting. |
| Screenshot / Report Section | Telegram connection and alert message. |

## n8n Automation

| Field | Description |
|---|---|
| Purpose | Connect the application with external automation workflows. |
| How It Works Technically | After report generation, the application can send a webhook payload to n8n containing report metadata and alert counts. |
| Main Files / Folders | `app/Services/ReportGenerationService.php`, `config/services.php` |
| Technologies Used | n8n, Webhook, Laravel HTTP Client |
| Role In Final Application | Enables future workflow automation outside the application. |
| Screenshot / Report Section | n8n workflow canvas. |

## Real-Time Updates

| Field | Description |
|---|---|
| Purpose | Update the interface without manual refresh. |
| How It Works Technically | Laravel events broadcast changes to private channels. The frontend subscribes using Echo and dispatches browser events to update components. |
| Main Files / Folders | `routes/channels.php`, `resources/js/bootstrap.js`, `app/Events/` |
| Technologies Used | Laravel Reverb, Echo, Pusher protocol, JavaScript events |
| Role In Final Application | Supports live supervision and immediate alerts. |
| Screenshot / Report Section | Real-time architecture diagram. |

## Profile

| Field | Description |
|---|---|
| Purpose | Allow users to manage account details and Telegram connection. |
| How It Works Technically | Users update personal profile data, delete accounts, and generate Telegram connection links. |
| Main Files / Folders | `app/Http/Controllers/ProfileController.php`, `resources/views/profile/` |
| Technologies Used | Laravel, Blade, Telegram integration |
| Role In Final Application | Provides user self-service features. |
| Screenshot / Report Section | Profile page with Telegram connect button. |

## Audit Logs

| Field | Description |
|---|---|
| Purpose | Keep a trace of important security and operational actions. |
| How It Works Technically | Controllers and services write audit entries with actor, action, target, and metadata. |
| Main Files / Folders | `app/Models/AuditLog.php`, `app/Services/AuditLogService.php` |
| Technologies Used | Laravel, MySQL |
| Role In Final Application | Improves accountability and traceability. |
| Screenshot / Report Section | Security and administration section. |
