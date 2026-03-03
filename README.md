# Server Room Supervision

Laravel web application for supervising an IT server room.

## Current Stack

- Laravel 12
- PHP 8.2
- MySQL
- Tailwind CSS + Vite
- Laravel Breeze
- Mailpit for local mail testing
- XAMPP for MySQL and phpMyAdmin

## What Is Implemented So Far

- Authentication with Laravel Breeze
- Registration and login
- Password reset via Mailpit
- Email verification
- Registration restricted to `@draxmailer`
- Department selection at registration
- Role selection at registration
- Dashboard showing current user info
- Department Head-only admin area
- Policy-protected users list
- Initial approval flow with `is_approved`

## Current Roles

- `department_head`
- `it_staff`

## Important Current Rule

- `department_head` users can access `/admin` only if `is_approved = 1`
- New `it_staff` users are auto-approved
- New `department_head` users are not auto-approved

## Required Software

Install these tools before running the project:

1. XAMPP
2. Composer
3. Node.js LTS
4. Git
5. Mailpit

## Windows Setup

### 1. Start XAMPP

Open XAMPP Control Panel and start:

- Apache
- MySQL

Useful URLs:

- phpMyAdmin: `http://localhost/phpmyadmin`

### 2. Clone The Repository

```bash
git clone https://github.com/baha-eddine-ammar/pfe_test1.git
cd pfe_test1
```

### 3. Install Dependencies

```bash
composer install
npm install
```

### 4. Create Environment File

Copy `.env.example` to `.env`.

On Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

### 5. Generate App Key

```bash
php artisan key:generate
```

### 6. Create Database

Create this MySQL database in phpMyAdmin:

- `server_room_supervision`

Or use terminal:

```bash
"C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS server_room_supervision CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 7. Run Migrations

```bash
php artisan migrate
```

### 8. Start Mailpit

Run Mailpit, then open:

- Mailpit UI: `http://127.0.0.1:8025`

Mailpit defaults used by this project:

- SMTP host: `127.0.0.1`
- SMTP port: `1025`

### 9. Start The App

Terminal 1:

```bash
php artisan serve
```

Terminal 2:

```bash
npm run dev
```

Open:

- App: `http://127.0.0.1:8000`
- Register: `http://127.0.0.1:8000/register`
- Login: `http://127.0.0.1:8000/login`
- Forgot password: `http://127.0.0.1:8000/forgot-password`
- Admin: `http://127.0.0.1:8000/admin`
- Admin users: `http://127.0.0.1:8000/admin/users`

## Mail Testing

This project uses Mailpit for local mail testing.

Use it to test:

- Password reset emails
- Email verification emails

## Development Notes

### Registering Users

- Email must end with `@draxmailer`
- Allowed departments:
  - `Network`
  - `Security`
  - `Systems`
  - `Infrastructure`
- Allowed roles:
  - `department_head`
  - `it_staff`

### Department Head Approval

If a new Department Head gets `403 Forbidden` on `/admin`, approve the account manually for now.

Use Laravel Tinker:

```bash
php artisan tinker
```

Then run:

```php
App\Models\User::where('email', 'friend@draxmailer')->update(['is_approved' => true]);
```

Exit:

```php
exit
```

## Collaboration Workflow

Recommended workflow for both developers:

1. Pull latest changes before starting work
2. Create a feature branch
3. Commit only relevant changes
4. Push the branch
5. Open a Pull Request

Example:

```bash
git checkout main
git pull origin main
git checkout -b feature/your-task-name
```

## Notes About Secrets

- Do not commit `.env`
- Do not commit local database dumps unless needed
- Do not commit `vendor` or `node_modules`

## First Troubleshooting Checks

If the app does not run, check:

1. XAMPP MySQL is running
2. Mailpit is running
3. `.env` exists
4. Database `server_room_supervision` exists
5. `php artisan migrate` was executed
6. `composer install` and `npm install` completed successfully
7. `php artisan key:generate` was executed
