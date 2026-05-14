# Technologies Used

## Frontend

| Technology | Role In The Project |
|---|---|
| Blade | Main Laravel templating system used to render pages and layouts. |
| Tailwind CSS | Utility-first CSS framework used for responsive interface design. |
| Alpine.js | Lightweight JavaScript framework used for interactive UI components. |
| React | Used for live monitoring widgets and dynamic metric components. |
| Vite | Frontend asset bundler used to build CSS and JavaScript assets. |
| ApexCharts | Chart library used for dashboard telemetry and report visualizations. |
| Three.js | Used for 3D visual elements and server-related visual presentation. |
| GSAP | Animation library used for brand/preloader animation. |
| Axios / Fetch API | Used for frontend HTTP requests and asynchronous UI updates. |

## Backend

| Technology | Role In The Project |
|---|---|
| PHP 8.2+ | Main backend programming language. |
| Laravel 12 | Main backend framework. |
| Laravel Breeze | Authentication scaffolding foundation. |
| Eloquent ORM | Object-relational mapping for database models. |
| Laravel Controllers | Handle HTTP requests for application modules. |
| Laravel Services | Encapsulate business logic such as reports, alerts, metrics, and workflows. |
| Form Requests | Validate incoming data. |
| Middleware | Enforce access rules such as approved users and Department Head access. |
| Policies | Control model-level authorization. |
| Laravel Events | Trigger broadcasts and event-driven workflows. |

## Database

| Technology | Role In The Project |
|---|---|
| MySQL | Main relational database configured for the application. |
| Laravel Migrations | Define database structure and schema evolution. |
| Eloquent Relationships | Connect users, reports, servers, metrics, tasks, notifications, and attachments. |
| Database Sessions | Store user session data. |
| Database Cache | Store cache records. |
| Database Jobs | Support queued job storage. |

Main database entities include:

- `users`
- `servers`
- `server_metrics`
- `sensor_readings`
- `reports`
- `report_ai_summaries`
- `maintenance_tasks`
- `maintenance_task_histories`
- `messages`
- `problems`
- `solutions`
- `problem_attachments`
- `solution_attachments`
- `user_notifications`
- `audit_logs`
- `department_head_invites`
- `login_two_factor_challenges`

## Authentication And Authorization

| Technology / Mechanism | Role In The Project |
|---|---|
| Laravel Authentication | Login, logout, registration, password reset, and session handling. |
| Email Verification | Ensures users verify their email addresses. |
| Email Two-Factor Authentication | Adds an extra login security step using email codes. |
| Role System | Separates Department Head and staff/technician features. |
| User Approval System | Prevents unapproved users from accessing the main application. |
| Department Head Invitations | Controls creation of Department Head accounts. |
| Middleware | Enforces approved-user and Department Head-only access. |
| Policies | Protects actions on maintenance tasks, users, and messages. |

## Real-Time Communication

| Technology | Role In The Project |
|---|---|
| Laravel Reverb | WebSocket server for real-time events. |
| Laravel Echo | Frontend client used to subscribe to broadcast channels. |
| Pusher Protocol | Communication protocol used by Echo/Reverb. |
| Private Channels | Protect user-specific and operational event streams. |
| Broadcast Events | Used for telemetry, server metrics, maintenance updates, reports, and notifications. |

Important broadcast channels:

- `dashboard.telemetry`
- `servers.overview`
- `servers.{serverId}`
- `users.{id}.notifications`
- `users.{id}.maintenance`
- `ops.admin`
- `ops.chat`

## Email System Including Brevo

| Technology | Role In The Project |
|---|---|
| Laravel Mail | Sends transactional email. |
| Laravel Notifications | Sends notification emails to users and Department Heads. |
| SMTP | Mail transport mechanism. |
| Brevo SMTP | Configured email provider for application emails. |

Email use cases:

- Email verification.
- Password reset.
- Email-based two-factor authentication.
- Staff registration pending approval.
- New staff registration notification.
- Account approval and rejection.
- Department Head invitation.
- Suspicious login notification.

No direct Brevo SDK was found; Brevo is used through SMTP configuration.

## Telegram Bot / Telegram API Integrations

| Technology | Role In The Project |
|---|---|
| Telegram Bot API | Sends Telegram messages to linked users. |
| Telegram Webhook | Receives bot commands and account-linking requests. |
| Telegram Connect Token | Links a web account to a Telegram chat ID. |

Telegram use cases:

- User links Telegram from profile.
- Bot receives `/start connect_token`.
- Application stores `telegram_chat_id`.
- Maintenance assignment alerts are sent to assigned users.
- Chat mention alerts can be sent through Telegram.
- Critical report alerts are sent to Department Heads.

## n8n Automation Workflows

| Technology | Role In The Project |
|---|---|
| n8n Webhook | Optional automation endpoint called after report generation. |
| Laravel HTTP Client | Sends report payload to n8n. |

n8n receives report information such as report type, report ID, warning count, critical count, number of readings, generated summary, and report URL. This can be used to automate external notifications, logging, or workflow actions.

## AI / IA Features

| Technology | Role In The Project |
|---|---|
| Groq API | Provides OpenAI-compatible chat completions. |
| AI Report Summary Service | Generates summaries, observations, and recommendations for reports. |
| AI Chat Service | Provides an operational assistant inside the application. |
| Predictive Maintenance Service | Adds rule-based preventive maintenance recommendations. |
| Local FastAPI Service | Provides a temperature prediction endpoint. |
| scikit-learn | Machine learning library used by the prediction service. |

AI-related files include:

- `app/Services/GroqReportSummaryService.php`
- `app/Services/AIChatService.php`
- `app/Services/PredictiveMaintenanceService.php`
- `app/Services/TemperaturePredictionService.php`
- `ml_service/`

## APIs Used In The Application

| API | Purpose |
|---|---|
| `/api/sensor-readings` | Receives ESP32/DHT22 temperature and humidity readings. |
| `/api/server-metrics` | Receives server performance metrics. |
| Groq Chat Completions API | Generates AI chat responses and report summaries. |
| Telegram Bot API | Sends alerts and handles account-linking commands. |
| n8n Webhook | Sends report-generation payloads to automation workflows. |
| Local FastAPI Prediction API | Predicts temperature-related risk using a local model. |

## Server Monitoring / Sensor Communication

| Technology | Role In The Project |
|---|---|
| ESP32 | Microcontroller used to send environmental readings. |
| DHT22 | Temperature and humidity sensor. |
| Sensor API Token | Secures incoming ESP32 requests. |
| Python Agent | Sends machine metrics to the Laravel API. |
| psutil | Collects CPU, RAM, disk, network, and uptime metrics. |
| PowerShell | Used for local workstation telemetry collection on Windows. |

## Charts, Dashboards, And UI Components

| Technology | Role In The Project |
|---|---|
| ApexCharts | Temperature, humidity, and trend charts. |
| Tailwind Components | Cards, tables, buttons, forms, status badges, layouts. |
| Alpine Components | Calendar, chat workspace, theme/sidebar state. |
| React Widgets | Live monitoring cards and metric components. |
| Custom Blade Components | Application layout, sidebar, topbar, forms, and UI sections. |

## Deployment, Hosting, Environment, And Configuration Tools

| Tool | Role In The Project |
|---|---|
| Composer | PHP dependency management. |
| npm | JavaScript dependency management. |
| Vite Build | Production frontend asset build. |
| `.env` | Environment-specific configuration. |
| Laravel Config Files | Configure services, database, broadcasting, mail, sensors, queues, and cache. |
| Laravel Queues | Support background jobs and notifications. |
| Laravel Scheduler | Can support scheduled tasks and report automation. |
| Laravel Reverb | Real-time WebSocket service. |
| Nginx / Apache | Suggested production web server options. |
| Supervisor / systemd | Suggested process managers for queue and Reverb services. |

## Testing

| Technology | Role In The Project |
|---|---|
| PHPUnit | Automated testing framework. |
| Laravel Feature Tests | Validate user flows and application modules. |

Test coverage exists for areas such as authentication, admin, AI chat, dashboard, reports, calendar, chat, maintenance, servers, and knowledge attachments.
