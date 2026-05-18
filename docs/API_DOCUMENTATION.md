# HireSmart APIs

Postman documentation for the HireSmart AI Laravel API, organized in the same collection style as the PayPal/Postman reference.

## Base URL

```text
http://127.0.0.1:8000/api
```

## Postman Environment Variables

| Variable | Value |
| --- | --- |
| `base_url` | `http://127.0.0.1:8000/api` |
| `token` | Paste the token returned by Register or Login |
| `resume_id` | Paste the UUID returned by Upload Resume |
| `job_id` | Paste a job UUID when testing job matching |

## Collection Folder Structure

```text
HireSmart APIs
 ├── Authorization
 │    ├── POST Register
 │    ├── POST Login
 │
 ├── Resume
 │    ├── POST Upload Resume
 │    ├── GET List Resumes
 │    ├── GET Show Resume
 │    ├── DELETE Delete Resume
 │
 ├── Analysis
 │    ├── POST Analyze Resume
 │    ├── GET Dashboard
 │    ├── GET Show Analysis
 │
 ├── Gateway / External Services
 │    ├── GET Gateway Route Map
 │    ├── POST Register via Site 1 Gateway
 │    ├── POST Login via Site 1 Gateway
 │    ├── GET Profile via Site 2 Gateway
 │    ├── PUT Update Profile via Site 2 Gateway
 │    ├── POST Logout via Site 2 Gateway
 │
 ├── Error Handling
 │    ├── 400 Bad Request
 │    ├── 401 Unauthorized
 │    ├── 403 Forbidden
 │    ├── 422 Validation Error
 │    ├── 500 Internal Server Error
```

## Authorization

### POST Register

Creates a new user and returns a Bearer token.

**URL**

```http
POST http://127.0.0.1:8000/api/auth/register
```

**Headers**

```http
Accept: application/json
Content-Type: application/json
```

**Body JSON**

```json
{
  "name": "Test User",
  "email": "test@test.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Expected `201 Created`**

```json
{
  "success": true,
  "gateway": "HireSmart API Gateway",
  "message": "User registered successfully",
  "token": "1|xxxxxxxxxxxx"
}
```

**Postman step**

Copy `token` into the `token` environment variable.

### POST Login

Logs in an existing user and returns a Bearer token.

**URL**

```http
POST http://127.0.0.1:8000/api/auth/login
```

**Headers**

```http
Accept: application/json
Content-Type: application/json
```

**Body JSON**

```json
{
  "email": "test@test.com",
  "password": "password123"
}
```

**Expected `200 OK`**

```json
{
  "success": true,
  "gateway": "HireSmart API Gateway",
  "message": "Login successful",
  "token": "1|xxxxxxxxxxxx"
}
```

**Postman step**

Copy `token` into the `token` environment variable.

## Resume

### POST Upload Resume

Uploads a PDF or DOCX resume and creates a resume record. This endpoint requires a Bearer token.

**URL**

```http
POST http://127.0.0.1:8000/api/resumes/upload
```

**Headers**

```http
Authorization: Bearer {{token}}
Accept: application/json
```

**Body: form-data**

| Key | Type | Example |
| --- | --- | --- |
| `title` | Text | `My Resume` |
| `resume` | File | Select a `.pdf` or `.docx` file |

**Expected `201 Created`**

```json
{
  "message": "Resume uploaded successfully",
  "resume": {
    "resume_id": "a7c46ff2-78aa-4f38-a23a-0db237b4e3b1",
    "title": "My Resume",
    "original_filename": "resume.pdf",
    "file_type": "pdf",
    "ats_score": null
  }
}
```

**Postman step**

Copy `resume.resume_id` into the `resume_id` environment variable.

### GET List Resumes

Lists all resumes owned by the logged-in user.

**URL**

```http
GET http://127.0.0.1:8000/api/resumes
```

**Headers**

```http
Authorization: Bearer {{token}}
Accept: application/json
```

**Expected `200 OK`**

```json
[
  {
    "resume_id": "a7c46ff2-78aa-4f38-a23a-0db237b4e3b1",
    "title": "My Resume",
    "original_filename": "resume.pdf",
    "ats_score": 81
  }
]
```

### GET Show Resume

Shows one resume, including the latest analysis and uploaded versions.

**URL**

```http
GET http://127.0.0.1:8000/api/resumes/{{resume_id}}
```

**Headers**

```http
Authorization: Bearer {{token}}
Accept: application/json
```

### DELETE Delete Resume

Deletes one resume owned by the logged-in user.

**URL**

```http
DELETE http://127.0.0.1:8000/api/resumes/{{resume_id}}
```

**Headers**

```http
Authorization: Bearer {{token}}
Accept: application/json
```

**Expected `200 OK`**

```json
{
  "message": "Resume deleted successfully"
}
```

## Analysis

### POST Analyze Resume

Runs the ATS-style resume analysis. The `resume_id` must be the UUID from Upload Resume, not `1`.

**URL**

```http
POST http://127.0.0.1:8000/api/analyze
```

**Headers**

```http
Authorization: Bearer {{token}}
Accept: application/json
Content-Type: application/json
```

**Body JSON**

```json
{
  "resume_id": "{{resume_id}}",
  "job_description": "Generalist role with communication, problem solving, and project management."
}
```

**Expected `200 OK`**

```json
{
  "message": "Analysis complete",
  "analysis": {
    "resume_id": "a7c46ff2-78aa-4f38-a23a-0db237b4e3b1",
    "total_score": 81,
    "strengths": [
      "Clear professional profile",
      "Relevant skills are easy to scan",
      "Readable resume structure"
    ],
    "weaknesses": [
      "Could include more measurable achievements",
      "Could include more job-specific keywords"
    ],
    "missing_keywords": [
      "project",
      "management"
    ],
    "summary": "Your resume is ready for baseline screening. Improve it further by adding measurable outcomes and matching more job keywords."
  },
  "recommendations": [
    "project",
    "management"
  ]
}
```

### GET Dashboard

Returns dashboard metrics, resume list, and score trend.

**URL**

```http
GET http://127.0.0.1:8000/api/analysis/dashboard
```

**Headers**

```http
Authorization: Bearer {{token}}
Accept: application/json
```

**Expected `200 OK`**

```json
{
  "total_resumes": 1,
  "average_score": 81,
  "latest_resume": {
    "resume_id": "a7c46ff2-78aa-4f38-a23a-0db237b4e3b1",
    "title": "My Resume",
    "ats_score": 81
  },
  "resumes": [],
  "score_trend": []
}
```

### GET Show Analysis

Shows the latest analysis for one resume.

**URL**

```http
GET http://127.0.0.1:8000/api/analysis/{{resume_id}}
```

**Headers**

```http
Authorization: Bearer {{token}}
Accept: application/json
```

## Extra Useful URLs

### GET API Health Check

```http
GET http://127.0.0.1:8000/api/test
```

Expected:

```json
{
  "message": "API is working!"
}
```

### GET Ping

```http
GET http://127.0.0.1:8000/api/ping
```

Expected:

```json
{
  "message": "pong",
  "timestamp": "2026-05-16T00:00:00.000000Z"
}
```

### GET Gateway Routes

```http
GET http://127.0.0.1:8000/api/gateway/routes
```

Expected:

```json
{
  "success": true,
  "gateway": "HireSmart API Gateway",
  "routes": {}
}
```

## Gateway / External Services

Use this folder to prove that Postman is calling the gateway-style URLs instead of calling the direct auth/profile URLs. In this Laravel implementation, the `/api/site1/*` routes represent User Service 1 and the `/api/site2/*` routes represent User Service 2 behind the gateway.

### GET Gateway Route Map

Shows which gateway routes point to which backend service group.

**URL**

```http
GET http://127.0.0.1:8000/api/gateway/routes
```

**Headers**

```http
Accept: application/json
```

**Expected `200 OK`**

```json
{
  "success": true,
  "gateway": "HireSmart API Gateway",
  "routes": {
    "ddsbe_user_service_one": {
      "folder": "microservices/ddsbe"
    },
    "ddsbe2_user_service_two": {
      "folder": "microservices/ddsbe2"
    },
    "ddsgateway": {
      "folder": "microservices/ddsgateway"
    }
  }
}
```

### POST Register via Site 1 Gateway

Creates a user through the gateway route for User Service 1.

**URL**

```http
POST http://127.0.0.1:8000/api/site1/register
```

**Headers**

```http
Accept: application/json
Content-Type: application/json
```

**Body JSON**

```json
{
  "name": "Test User",
  "email": "test@test.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Expected `201 Created`**

```json
{
  "success": true,
  "gateway": "HireSmart API Gateway",
  "message": "User registered successfully",
  "token": "1|xxxxxxxxxxxx"
}
```

### POST Login via Site 1 Gateway

Logs in through the gateway route for User Service 1.

**URL**

```http
POST http://127.0.0.1:8000/api/site1/login
```

**Headers**

```http
Accept: application/json
Content-Type: application/json
```

**Body JSON**

```json
{
  "email": "test@test.com",
  "password": "password123"
}
```

**Expected `200 OK`**

```json
{
  "success": true,
  "gateway": "HireSmart API Gateway",
  "message": "Login successful",
  "token": "1|xxxxxxxxxxxx"
}
```

**Postman Tests script**

```js
const json = pm.response.json();
pm.environment.set("token", json.token);
```

### GET Profile via Site 2 Gateway

Calls the protected User Service 2 profile route through the gateway.

**URL**

```http
GET http://127.0.0.1:8000/api/site2/users/profile
```

**Headers**

```http
Accept: application/json
Authorization: Bearer {{token}}
```

**Expected `200 OK`**

```json
{
  "success": true,
  "gateway": "HireSmart API Gateway",
  "user": {
    "id": 1,
    "name": "Test User",
    "email": "test@test.com"
  }
}
```

### PUT Update Profile via Site 2 Gateway

Updates the authenticated user profile through the gateway route for User Service 2.

**URL**

```http
PUT http://127.0.0.1:8000/api/site2/users/profile
```

**Headers**

```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer {{token}}
```

**Body JSON**

```json
{
  "name": "Updated Test User",
  "phone": "09123456789",
  "bio": "Testing gateway to external user service."
}
```

**Expected `200 OK`**

```json
{
  "success": true,
  "gateway": "HireSmart API Gateway",
  "message": "Profile updated"
}
```

### POST Logout via Site 2 Gateway

Logs out the current token through the gateway route for User Service 2.

**URL**

```http
POST http://127.0.0.1:8000/api/site2/logout
```

**Headers**

```http
Accept: application/json
Authorization: Bearer {{token}}
```

**Expected `200 OK`**

```json
{
  "success": true,
  "gateway": "HireSmart API Gateway",
  "message": "Logged out successfully"
}
```

### Successful Gateway Test Checklist

1. Start MySQL.
2. Run `php artisan migrate` inside the `laravel` folder.
3. Run `php artisan serve --host=127.0.0.1 --port=8000`.
4. In Postman, call `Gateway / External Services / POST Register via Site 1 Gateway`.
5. Call `Gateway / External Services / POST Login via Site 1 Gateway`.
6. Save the returned token to `{{token}}`.
7. Call `Gateway / External Services / GET Profile via Site 2 Gateway`.

The proof is the route path: use `/api/site1/login`, not `/api/auth/login`, when demonstrating the gateway/external-service call.

## Error Handling

Create these examples inside the `Error Handling` folder, like the PayPal collection reference.

### 400 Bad Request

Use malformed JSON to test a bad request.

**URL**

```http
POST http://127.0.0.1:8000/api/auth/login
```

**Headers**

```http
Accept: application/json
Content-Type: application/json
```

**Wrong Body**

```json
{
  "email": "test@test.com",
```

**Expected**

```json
{
  "message": "Bad request."
}
```

### 401 Unauthorized

Call a protected route without a Bearer token.

**URL**

```http
GET http://127.0.0.1:8000/api/analysis/dashboard
```

**Headers**

```http
Accept: application/json
```

**Expected**

```json
{
  "message": "Unauthenticated. Login first and send a Bearer token."
}
```

### 403 Forbidden

Use another user's resume ID with your token.

**URL**

```http
POST http://127.0.0.1:8000/api/analyze
```

**Headers**

```http
Authorization: Bearer {{token}}
Accept: application/json
Content-Type: application/json
```

**Body JSON**

```json
{
  "resume_id": "{{other_user_resume_id}}"
}
```

**Expected**

```json
{
  "message": "Unauthorized"
}
```

### 422 Validation Error: Missing Password

Laravel validation returns `422` for missing fields.

**URL**

```http
POST http://127.0.0.1:8000/api/auth/login
```

**Headers**

```http
Accept: application/json
Content-Type: application/json
```

**Wrong Body**

```json
{
  "email": "test@test.com"
}
```

**Expected**

```json
{
  "success": false,
  "gateway": "HireSmart API Gateway",
  "errors": {
    "password": [
      "The password field is required."
    ]
  }
}
```

### 422 Validation Error: Invalid Resume File

Upload an invalid file type.

**URL**

```http
POST http://127.0.0.1:8000/api/resumes/upload
```

**Headers**

```http
Authorization: Bearer {{token}}
Accept: application/json
```

**Body: form-data**

| Key | Type | Example |
| --- | --- | --- |
| `title` | Text | `test` |
| `resume` | File | `image.jpg` |

**Expected**

```json
{
  "errors": {
    "resume": [
      "The resume field must be a file of type: pdf, docx."
    ]
  }
}
```

### 500 Internal Server Error

Use only for testing, then restore immediately.

**Temporary test setup**

```env
DB_PASSWORD=wrongpassword
```

Then call:

```http
POST http://127.0.0.1:8000/api/auth/login
```

**Expected**

```json
{
  "message": "SQLSTATE[HY000] [1045] Access denied for user..."
}
```

Restore the correct `.env` value after testing, then restart Laravel.

## Postman Run Order

1. `Authorization / POST Register`
2. `Authorization / POST Login`
3. Save the returned `token`.
4. `Resume / POST Upload Resume`
5. Save `resume.resume_id` as `resume_id`.
6. `Analysis / POST Analyze Resume`
7. `Analysis / GET Dashboard`
8. `Gateway / External Services / GET Gateway Route Map`
9. `Gateway / External Services / POST Login via Site 1 Gateway`
10. `Gateway / External Services / GET Profile via Site 2 Gateway`
11. Run the Error Handling examples.
