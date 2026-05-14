# Screenshot Suggestions

| Screenshot | Recommended Report Section | Purpose |
|---|---|---|
| Login page | Authentication and security | Shows the application entry point. |
| Registration page | Authentication and security | Shows how new users request access. |
| Email two-factor authentication page | Authentication and security | Demonstrates login protection. |
| Main dashboard | Project overview / Dashboard | Shows the central supervision interface. |
| Temperature and humidity charts | Environmental telemetry | Shows DHT22 sensor data visualization. |
| Server list page | Server monitoring | Shows monitored servers and status overview. |
| Server detail page | Server monitoring | Shows detailed CPU, RAM, disk, network, and uptime metrics. |
| ESP32/DHT22 hardware photo or diagram | Sensor communication | Explains the physical data source. |
| Maintenance task list | Maintenance management | Shows task tracking and priorities. |
| Maintenance task detail | Maintenance management | Shows assignment, status, and task information. |
| Calendar page | Planning | Shows scheduled maintenance activities. |
| Reports list | Reporting | Shows generated daily, weekly, and monthly reports. |
| Report detail page | Reporting | Shows metrics, anomalies, and summary. |
| AI report summary block | AI / IA features | Shows AI-assisted analysis. |
| AI Chat page | AI / IA features | Shows the operational assistant. |
| Chat workspace | Collaboration | Shows internal team communication. |
| Problem list or problem detail | Knowledge base | Shows incident documentation. |
| Solution list or solution detail | Knowledge base | Shows corrective action documentation. |
| Admin users management page | Administration | Shows approval and role management. |
| Notification list or dropdown | Notifications and alerts | Shows in-app notifications. |
| Profile page with Telegram connection | Telegram integration | Shows account linking. |
| Telegram alert message | Telegram integration | Shows mobile alert delivery. |
| n8n workflow canvas | Automation | Shows external workflow automation. |
| Database diagram | Technical architecture | Explains main entities and relationships. |
| Architecture diagram | Technical architecture | Shows frontend, backend, database, sensors, AI, and notification layers. |

## Suggested Screenshot Order In The Report

1. Application login page.
2. Main dashboard.
3. Environmental telemetry charts.
4. Server monitoring page.
5. Maintenance task page.
6. Calendar page.
7. Reports page.
8. AI report summary.
9. AI Chat page.
10. Admin users management page.
11. Telegram alert screenshot.
12. Architecture diagram.

## Diagram Suggestions

Recommended diagrams to include:

- System architecture diagram showing frontend, backend, database, real-time layer, sensor layer, AI layer, and notification layer.
- ESP32/DHT22 communication diagram showing sensor data sent to Laravel API.
- Server metrics flow diagram showing server agent data sent to `/api/server-metrics`.
- Database entity relationship diagram showing users, servers, metrics, reports, tasks, messages, problems, solutions, and notifications.
- Notification flow diagram showing in-app, Brevo email, Telegram, and n8n paths.
