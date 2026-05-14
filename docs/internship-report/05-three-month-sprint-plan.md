# Three-Month Sprint Plan

Total duration: 3 months  
Suggested rhythm: 6 sprints of 2 weeks each

## Sprint 1 - Analysis, Requirements, And Architecture

| Field | Content |
|---|---|
| Duration | Weeks 1-2 |
| Main Objective | Understand the needs of server room supervision and define the foundation of the application. |
| Tasks Completed | Analyzed monitoring requirements, identified user roles, defined main modules, selected Laravel architecture, prepared database design, initialized project structure. |
| Features Developed | Initial Laravel structure, database planning, first authentication foundation, base layout direction. |
| Technologies Used | Laravel, PHP, MySQL, Blade, Tailwind CSS, Composer, npm. |
| Deliverables | Requirements summary, technical architecture draft, initial project skeleton, first database schema. |
| Challenges And Solutions | The main challenge was organizing many supervision needs into a coherent system. The solution was to divide the application into monitoring, maintenance, reporting, communication, administration, and automation modules. |

## Sprint 2 - Authentication, Roles, And User Administration

| Field | Content |
|---|---|
| Duration | Weeks 3-4 |
| Main Objective | Build a secure access system for Department Heads and staff users. |
| Tasks Completed | Implemented registration, login, email verification, user approval, Department Head access, user management, invitations, and email two-factor authentication. |
| Features Developed | Authentication, approved-user workflow, Department Head administration, account approval/rejection, role promotion/demotion, Department Head invite flow, 2FA challenge. |
| Technologies Used | Laravel Breeze, Laravel Mail, Brevo SMTP, middleware, policies, Eloquent, MySQL. |
| Deliverables | Secure authentication module, user management dashboard, role-based authorization system. |
| Challenges And Solutions | The challenge was preventing unauthorized access before approval. This was solved with user status fields, approved-user middleware, Department Head middleware, and authorization policies. |

## Sprint 3 - Telemetry, Sensors, And Dashboard

| Field | Content |
|---|---|
| Duration | Weeks 5-6 |
| Main Objective | Connect the application to real monitoring data and display it in the dashboard. |
| Tasks Completed | Created sensor readings API, server metrics API, environmental telemetry service, server monitoring service, dashboard cards, chart feeds, and live metric components. |
| Features Developed | Dashboard, ESP32/DHT22 integration, environmental telemetry, server section, server metrics ingestion, local system telemetry. |
| Technologies Used | Laravel API, MySQL, ESP32, DHT22, Python/psutil agent, ApexCharts, React widgets, Tailwind CSS. |
| Deliverables | Operational monitoring dashboard, sensor data storage, server metric storage, visual telemetry charts. |
| Challenges And Solutions | The challenge was combining different data sources. This was solved by creating service classes that normalize sensor readings and server metrics before displaying them. |

## Sprint 4 - Maintenance, Calendar, Collaboration, And Knowledge Base

| Field | Content |
|---|---|
| Duration | Weeks 7-8 |
| Main Objective | Add operational workflow management for the technical team. |
| Tasks Completed | Built maintenance task creation, assignment, status transitions, task histories, calendar view, chat workspace, problems module, solutions module, attachments, and notifications. |
| Features Developed | Maintenance section, calendar, chat, problems, solutions, attachment downloads, in-app notifications. |
| Technologies Used | Laravel, MySQL, Blade, Alpine.js, File Storage, Laravel Notifications, Policies. |
| Deliverables | Maintenance management module, planning calendar, team chat, incident knowledge base. |
| Challenges And Solutions | The challenge was keeping tasks traceable. This was solved by adding maintenance task histories, audit logs, and role-based visibility rules. |

## Sprint 5 - Reports, AI, Alerts, Telegram, And n8n

| Field | Content |
|---|---|
| Duration | Weeks 9-10 |
| Main Objective | Transform monitoring data into professional reports and intelligent recommendations. |
| Tasks Completed | Implemented daily, weekly, and monthly reports; metrics calculation; anomaly detection; AI summaries; predictive maintenance recommendations; Telegram alerts; and n8n webhook integration. |
| Features Developed | Reports section, AI report summaries, AI Chat, predictive maintenance, Telegram connection and alerts, n8n automation. |
| Technologies Used | Groq API, Laravel HTTP Client, Telegram Bot API, n8n Webhook, MySQL, Blade. |
| Deliverables | Report generation module, AI-assisted summaries, alert integrations, automation extension point. |
| Challenges And Solutions | The challenge was depending on external services. This was solved by adding fallback behavior for AI summaries and making n8n integration optional through configuration. |

## Sprint 6 - Testing, Integration, Deployment Preparation, And Documentation

| Field | Content |
|---|---|
| Duration | Weeks 11-12 |
| Main Objective | Stabilize the application and prepare final internship documentation. |
| Tasks Completed | Reviewed end-to-end flows, organized feature tests, checked module integration, prepared deployment configuration guidance, documented features, technologies, architecture, and sprint work. |
| Features Developed | Final integration, testing support, deployment checklist, report documentation. |
| Technologies Used | PHPUnit, Laravel testing tools, Composer, npm, Vite, Reverb, Laravel queues. |
| Deliverables | Final application, technical documentation, sprint report, deployment notes, screenshot plan. |
| Challenges And Solutions | The challenge was presenting a complex system clearly. This was solved by documenting the architecture by layers and organizing work into realistic sprint phases. |

## Overall Progression

The internship progression follows a realistic software development lifecycle:

1. Understand the problem and define the architecture.
2. Secure the application with authentication and authorization.
3. Connect real data sources and build monitoring dashboards.
4. Add operational workflows for maintenance and collaboration.
5. Add intelligent reporting, alerts, and automation.
6. Test, integrate, document, and prepare for deployment.
