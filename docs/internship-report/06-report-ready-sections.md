# Report-Ready Sections

## Project Presentation

The Server Room Supervision project is a web application developed to monitor, manage, and document the operational state of a server room. The system provides a centralized interface for environmental telemetry, server metrics, maintenance operations, incident documentation, user management, reporting, alerts, and AI-assisted analysis.

The application was designed for technical teams responsible for infrastructure supervision. It helps users visualize important indicators such as temperature, humidity, CPU usage, RAM usage, disk usage, network status, and server availability. It also supports maintenance planning, communication, and reporting.

## Problem Statement

Server rooms are sensitive environments where technical problems can quickly affect service availability. Temperature increases, humidity variations, high server resource usage, hardware failures, or unplanned maintenance can lead to serious incidents. Traditional manual supervision is often reactive and does not always provide real-time visibility or historical traceability.

The objective of this project is to provide a digital supervision platform capable of collecting real-time data, detecting abnormal situations, notifying responsible users, documenting incidents, and helping the team make informed decisions.

## Project Objectives

The main objectives of the project are:

- Monitor environmental conditions such as temperature and humidity.
- Monitor server health and resource usage.
- Display real-time data in dashboards and charts.
- Manage maintenance tasks and planning.
- Generate daily, weekly, and monthly reports.
- Provide AI-assisted summaries and recommendations.
- Notify users through in-app notifications, email, and Telegram.
- Support role-based access for Department Heads and staff.
- Document problems and solutions.
- Integrate automation through n8n workflows.

## Functional Scope

The functional scope includes dashboard supervision, environmental telemetry, server monitoring, maintenance management, calendar planning, reports, AI Chat, problems and solutions, chat, notifications, Telegram alerts, email alerts, user administration, and role-based security.

The Department Head role has administrative privileges, including user approval, role management, server management, and maintenance task management. Staff users can access operational modules, update assigned tasks, communicate through chat, and document problems or solutions.

## Technical Scope

The application is based on a Laravel backend and a Blade/Tailwind/JavaScript frontend. Data is stored in MySQL. Real-time features are prepared through Laravel Reverb and Echo. Hardware data is collected from an ESP32 connected to a DHT22 sensor. Server metrics are collected from software agents and sent to the Laravel API.

External services are integrated for specific needs:

- Brevo SMTP for email delivery.
- Telegram Bot API for mobile alerts.
- Groq API for AI summaries and AI Chat.
- n8n webhook for automation.
- FastAPI/scikit-learn for local prediction service.

## Work Done During Internship

During the internship, the project was developed progressively over six sprints. The first sprint focused on analysis, requirements, and architecture. The second sprint implemented authentication, authorization, and user management. The third sprint added telemetry collection, sensor integration, server monitoring, and dashboard visualization. The fourth sprint implemented maintenance workflows, calendar planning, chat, and the knowledge base. The fifth sprint added reports, AI features, Telegram alerts, and n8n automation. The final sprint focused on testing, integration, deployment preparation, and documentation.

This progression allowed the project to move from a basic web application to a complete supervision platform with real-time monitoring, operational management, intelligent reporting, and communication features.

## Technologies Used

The frontend uses Blade, Tailwind CSS, Alpine.js, React, ApexCharts, Three.js, GSAP, and Vite. The backend uses Laravel 12, PHP, Eloquent ORM, Laravel services, events, middleware, policies, and form requests. The database is MySQL. Authentication is based on Laravel Breeze with custom approval, role, and two-factor authentication logic.

The application also uses Laravel Reverb and Echo for real-time communication, Laravel Mail and Notifications with Brevo SMTP for email delivery, Telegram Bot API for mobile alerts, Groq API for AI features, n8n webhook for automation, and ESP32/DHT22 for environmental monitoring.

## Technical Architecture Description

The application follows a layered architecture. The frontend layer provides the user interface and interactive components. The backend layer manages business logic through controllers and services. The database layer stores all application data. The real-time layer broadcasts important events to connected users. The sensor and server monitoring layer receives physical and system metrics. The AI and automation layer provides report summaries, chat assistance, predictive recommendations, and external workflow triggering. The notification layer informs users through the web interface, email, and Telegram.

This architecture makes the system modular, maintainable, and extensible. Each module has a defined responsibility, and the application can be extended with new sensors, new alert channels, or new automation workflows in the future.

## Value Of The System

The Server Room Supervision system improves the supervision of technical infrastructure by centralizing information that is usually scattered across different tools or manual processes. It gives the technical team a clearer view of the server room state, helps detect anomalies earlier, and supports better maintenance organization.

The use of reports and AI summaries makes the system useful not only for real-time monitoring but also for documentation and decision-making. The integration of Brevo, Telegram, and n8n extends the system beyond the web interface and makes alerts and workflows more practical in real operational conditions.

## Conclusion

The project represents a complete full-stack solution for server room monitoring and management. It combines web development, database design, hardware integration, real-time communication, automation, and AI-assisted analysis.

By the end of the internship, the application provides a functional platform that can monitor environmental and server conditions, manage users and roles, organize maintenance tasks, generate reports, send alerts, and support technical decision-making. Its modular architecture allows future improvements such as additional sensors, advanced prediction models, richer dashboards, and deeper automation workflows.
