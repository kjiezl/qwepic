# QwePic - Photography Booking Platform

A full-stack web application for photography booking and portfolio management built with Symfony 7.4.

---

## Tech Stack

- **Backend:** PHP 8.2, Symfony 7.4
- **Database:** MySQL 8.0 (local), PostgreSQL 16 (production)
- **Authentication:** JWT (LexikJWTAuthenticationBundle)
- **API:** API Platform + custom REST controllers
- **Frontend:** Twig, Tailwind CSS
- **Deployment:** Docker, Render

---

## Requirements

- PHP 8.2+
- Composer 2
- MySQL 8.0 (or Docker)
- OpenSSL (for JWT key generation)
- Symfony CLI (optional, for local dev server)

---

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/your-username/qwepic.git
cd qwepic
```

### 2. Install dependencies

```bash
composer install
```

### 3. Configure environment

Copy `.env` to `.env.local` and update values:

```bash
cp .env .env.local
```

Required variables in `.env.local`:

```env
DATABASE_URL="mysql://user:password@127.0.0.1:3307/qwepic_db?serverVersion=8.0&charset=utf8mb4"
JWT_PASSPHRASE=your_passphrase
BREVO_API_KEY=your_brevo_api_key
BREVO_SENDER_EMAIL=verified@email.com
BREVO_SENDER_NAME="QwePic Contact Form"
BREVO_TO_EMAIL=recipient@email.com
BREVO_TO_NAME="QwePic Team"
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
MAILER_DSN=your_smtp_brevo_dsn
```

### 4. Generate JWT keys

```bash
php bin/console lexik:jwt:generate-keypair
```

### 5. Create database and run migrations

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 6. Build Tailwind CSS

```bash
php bin/console tailwind:build
```

### 7. Start the development server

```bash
symfony server:start
```

The app is available at `https://127.0.0.1:8000`.

---

## User Roles

| Role | Description |
|------|-------------|
| ROLE_USER | Customer. Can browse photographers, create/cancel bookings, manage profile. |
| ROLE_PHOTOGRAPHER | Staff. Can manage albums, photos, and handle booking requests. |
| ROLE_ADMIN | Full access. Can manage all users, bookings, albums, photos, and view activity logs. |

---

## API Documentation

Base URL: `https://qwepic.onrender.com` (production) or `https://127.0.0.1:8000` (local)

Auto-generated OpenAPI docs available at: `/api/docs`

### Authentication

All protected endpoints require a JWT token in the `Authorization` header:

```
Authorization: Bearer <token>
```

Token TTL: 3600 seconds (1 hour).

---

### Auth Endpoints

#### POST /api/auth/login

Authenticate and receive a JWT token.

**Request:**
```json
{
  "username": "john_doe",
  "password": "secret123"
}
```

**Response (200):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhb..."
}
```

**Error (401):**
```json
{
  "code": 401,
  "message": "Invalid credentials."
}
```

---

#### POST /api/auth/register

Register a new user account.

**Request:**
```json
{
  "username": "jane_doe",
  "email": "jane@example.com",
  "password": "secret123",
  "accountType": "customer"
}
```

`accountType` accepts `"customer"` or `"photographer"`.

**Response (201):**
```json
{
  "message": "User registered successfully",
  "user": {
    "id": 1,
    "verification_token": "abc123...",
    "username": "jane_doe",
    "email": "jane@example.com",
    "roles": ["ROLE_USER"]
  }
}
```

**Error (409):**
```json
{
  "message": "Username already exists"
}
```

---

#### GET /api/auth/verify/{token}

Verify user email with the token received during registration.

**Response (200):**
```json
{
  "message": "Email verified successfully.",
  "user": {
    "id": 1,
    "username": "jane_doe",
    "is_verified": true
  }
}
```

---

#### GET /api/auth/me

Get the currently authenticated user. Requires JWT.

**Response (200):**
```json
{
  "id": 1,
  "username": "john_doe",
  "email": "john@example.com",
  "roles": ["ROLE_USER"],
  "is_active": true
}
```

---

### Customer Profile Endpoints

Requires: `ROLE_USER`

#### GET /api/customer/profile

Get current user profile.

**Response (200):**
```json
{
  "id": 1,
  "username": "john_doe",
  "email": "john@example.com",
  "roles": ["ROLE_USER"],
  "is_active": true,
  "is_verified": true,
  "created_at": "2025-12-07T18:18:45+00:00",
  "updated_at": "2025-12-09T06:47:33+00:00"
}
```

---

#### PATCH /api/customer/profile

Update current user profile.

**Request:**
```json
{
  "username": "new_username",
  "email": "new@email.com",
  "password": "newpassword123"
}
```

All fields are optional. Only include fields you want to update.

**Response (200):**
```json
{
  "message": "Profile updated successfully",
  "user": {
    "id": 1,
    "username": "new_username",
    "email": "new@email.com",
    "roles": ["ROLE_USER"],
    "is_active": true,
    "is_verified": true,
    "updated_at": "2026-05-21T08:00:00+00:00"
  }
}
```

---

### Customer Booking Endpoints

Requires: `ROLE_USER`

#### GET /api/customer/bookings

List all bookings for the authenticated customer.

**Response (200):**
```json
[
  {
    "id": 1,
    "photographer": {
      "id": 2,
      "username": "photo_pro"
    },
    "status": "requested",
    "start_at": "2026-06-01T10:00:00+00:00",
    "end_at": "2026-06-01T12:00:00+00:00",
    "location": "Central Park",
    "notes": "Outdoor portrait session",
    "rejection_reason": null,
    "created_at": "2026-05-21T08:00:00+00:00",
    "updated_at": "2026-05-21T08:00:00+00:00"
  }
]
```

---

#### GET /api/customer/bookings/{id}

Get a single booking by ID. Only the booking owner can access it.

**Response (200):** Same structure as list item above with additional photographer email field.

**Error (403):**
```json
{
  "message": "Access denied"
}
```

---

#### POST /api/customer/bookings

Create a new booking.

**Request:**
```json
{
  "photographer_id": 2,
  "start_at": "2026-06-01T10:00:00+00:00",
  "end_at": "2026-06-01T12:00:00+00:00",
  "location": "Central Park",
  "notes": "Outdoor portrait session"
}
```

**Response (201):**
```json
{
  "message": "Booking created successfully",
  "booking": {
    "id": 1,
    "photographer": {
      "id": 2,
      "username": "photo_pro"
    },
    "status": "requested",
    "start_at": "2026-06-01T10:00:00+00:00",
    "end_at": "2026-06-01T12:00:00+00:00",
    "location": "Central Park",
    "notes": "Outdoor portrait session",
    "created_at": "2026-05-21T08:00:00+00:00"
  }
}
```

---

#### PATCH /api/customer/bookings/{id}/cancel

Cancel a booking. Only bookings with status `"requested"` can be cancelled.

**Response (200):**
```json
{
  "message": "Booking cancelled successfully",
  "booking": {
    "id": 1,
    "status": "cancelled",
    "updated_at": "2026-05-21T09:00:00+00:00"
  }
}
```

**Error (422):**
```json
{
  "message": "Only bookings with status \"requested\" can be cancelled"
}
```

---

### Photographer Listing Endpoints

Public access (no authentication required).

#### GET /api/customer/photographers

List all active photographers.

**Response (200):**
```json
[
  {
    "id": 2,
    "username": "photo_pro",
    "email": "photo@example.com",
    "albums_count": 3,
    "photos_count": 45,
    "created_at": "2025-12-07T18:18:45+00:00"
  }
]
```

---

#### GET /api/customer/photographers/{id}

Get photographer details with public albums and photos.

**Response (200):**
```json
{
  "id": 2,
  "username": "photo_pro",
  "email": "photo@example.com",
  "albums_count": 2,
  "albums": [
    {
      "id": 1,
      "title": "Wedding Portfolio",
      "description": "Best wedding shots",
      "cover_image_path": "/uploads/covers/wedding.jpg",
      "photos_count": 12,
      "photos": [
        {
          "id": 1,
          "title": "Sunset ceremony",
          "caption": "Golden hour moment",
          "storage_path": "/uploads/photos/sunset.jpg",
          "thumbnail_path": "/uploads/thumbnails/sunset.jpg",
          "created_at": "2025-12-09T08:01:38+00:00"
        }
      ],
      "created_at": "2025-12-09T06:47:33+00:00"
    }
  ],
  "created_at": "2025-12-07T18:18:45+00:00"
}
```

---

### Admin/Staff API (API Platform)

Full CRUD for entities is available via API Platform at `/api`. Access requires appropriate role.

| Resource | Endpoint | Roles |
|----------|----------|-------|
| Users | /api/users | ROLE_ADMIN |
| Albums | /api/albums | ROLE_ADMIN, ROLE_PHOTOGRAPHER |
| Photos | /api/photos | ROLE_ADMIN, ROLE_PHOTOGRAPHER |
| Bookings | /api/bookings | ROLE_ADMIN, ROLE_PHOTOGRAPHER |

Refer to `/api/docs` for full OpenAPI specification.

---

## Error Response Format

All API errors follow a consistent format:

```json
{
  "message": "Description of the error",
  "errors": {
    "field_name": "Validation message"
  }
}
```

Standard HTTP status codes used:

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 400 | Bad request / Validation error |
| 401 | Not authenticated |
| 403 | Access denied |
| 404 | Resource not found |
| 409 | Conflict (duplicate) |
| 422 | Unprocessable entity |
| 500 | Server error |

---

## Deployment (Render)

The application is deployed at: `https://qwepic.onrender.com`

### Infrastructure

- **Web Service:** Docker container (PHP 8.2 + Apache)
- **Database:** Render Managed PostgreSQL (free tier)
- **Blueprint:** `render.yaml` defines all services

### Environment Variables (Render)

| Variable | Purpose |
|----------|---------|
| APP_ENV | `prod` |
| APP_SECRET | Application secret (auto-generated) |
| DATABASE_URL | PostgreSQL connection string (from Render DB) |
| JWT_PASSPHRASE | Passphrase for JWT key encryption |
| JWT_SECRET_KEY | Path to private key |
| JWT_PUBLIC_KEY | Path to public key |
| CORS_ALLOW_ORIGIN | Allowed origins regex |
| BREVO_API_KEY | Brevo API key for contact form |
| BREVO_SENDER_EMAIL | Verified sender email |
| BREVO_TO_EMAIL | Contact form recipient |

### Deploy from Blueprint

1. Push code to GitHub.
2. Go to Render Dashboard > New > Blueprint.
3. Connect repository. Render reads `render.yaml` and provisions services.
4. Add Brevo environment variables manually.

---

## Project Structure

```
qwepic/
├── config/                 # Symfony configuration
│   ├── packages/           # Bundle configs (security, doctrine, jwt, cors)
│   └── routes/             # Route definitions
├── migrations/             # Doctrine migration files
├── public/                 # Web root (index.php, .htaccess)
├── src/
│   ├── Controller/
│   │   ├── Api/            # REST API controllers
│   │   ├── Dashboard/      # Admin/Staff web controllers
│   │   └── HomeController  # Public pages
│   ├── Entity/             # Doctrine entities (User, Booking, Album, Photo, etc.)
│   ├── EventSubscriber/    # JWT enrichment, API exception handling
│   ├── Repository/         # Doctrine repositories
│   ├── Security/           # Authenticators, UserChecker
│   └── State/              # API Platform state processors
├── templates/              # Twig templates
├── Dockerfile              # Production Docker image
├── docker-entrypoint.sh    # Container startup script
├── render.yaml             # Render Blueprint
└── composer.json           # PHP dependencies
```

---

## Database Schema

| Entity | Table | Description |
|--------|-------|-------------|
| User | user | Customers, photographers, admins |
| Booking | booking | Photography session bookings |
| Album | album | Photo albums (per photographer) |
| Photo | photo | Individual photos in albums |
| BookingAttachment | booking_attachment | Files attached to bookings |
| ActivityLog | activity_log | Audit trail of system actions |

---

## License

Proprietary.
