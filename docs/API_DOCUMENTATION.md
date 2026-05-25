# HireSmart AI Postman Documentation

This file documents the Laravel API that Postman should call. Third-party keys in `laravel/.env` are used by the backend automatically; do not paste those provider keys into Postman requests.

## Local Setup

```powershell
cd laravel
php artisan config:clear
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8000
```

Base URL:

```text
http://127.0.0.1:8000/api
```

## Postman Environment

Create an environment with these variables:

| Variable | Example |
| --- | --- |
| `base_url` | `http://127.0.0.1:8000/api` |
| `token` | Saved automatically from Register/Login |
| `user_email` | `postman@example.com` |
| `resume_id` | Saved from Upload Resume |
| `analysis_resume_id` | Usually same as `resume_id` |
| `job_id` | Saved from Create Job |
| `other_user_resume_id` | Optional for 403 testing |
| `search_what` | `Laravel Developer` |
| `search_where` | `Cebu` |
| `origin` | `Cebu City Hall` |
| `to_email` | `receiver@example.com` |
| `authToken` | Optional global-style token used in some Postman screenshots |

Common headers:

```http
Accept: application/json
Content-Type: application/json
```

Protected routes also need:

```http
Authorization: Bearer {{token}}
```

## Postman Setup From The Photos

Use this setup when you want the collection to behave like the screenshots.

### Collection folders

Organize requests by feature folder:

```text
HireSmart APIs
|-- Auth
|   |-- POST Login
|   |-- POST Register
|   |-- POST Validate Email
|-- Profile
|-- Resume
|-- Analysis
|-- Jobs
|-- Integrations
|-- Notifications
|-- Gateway Routes
|-- Error Handling
```

### Collection-level Authorization

In the parent collection or protected folder:

```text
Authorization tab
Type: Bearer Token
Token: {{token}}
```

If your Postman workspace uses the global variable from the photo, use this instead:

```text
Token: {{authToken}}
```

For child requests, set:

```text
Authorization tab
Type: Inherit auth from parent
```

### Where To Find `Inherit auth from parent`

Use this when you are inside a single request such as `GET Profile`, `POST Upload Resume`, `POST Analyze Resume`, or `POST Create Job`.

1. Click the request in the left sidebar.
2. Click the **Authorization** tab beside **Params**.
3. Find the **Auth Type** dropdown.
4. Select **Inherit auth from parent**.
5. Save the request.

You should see options like:

```text
No Auth
API Key
Bearer Token
Basic Auth
Inherit auth from parent
```

The parent collection or folder must have the actual token:

```text
Parent collection/folder > Authorization tab
Type: Bearer Token
Token: {{token}}
```

Quick rule:

```text
Parent collection/folder = Bearer Token {{token}}
Child requests = Inherit auth from parent
```

### Login/Register Post-response Script

Add this in the `Scripts` tab under `Post-response` for `POST Login`, `POST Register`, `POST Login via Site 1 Gateway`, and `POST Register via Site 1 Gateway`.

```js
const jsonResponse = pm.response.json();
const token = jsonResponse.token || jsonResponse.data?.token || jsonResponse.access_token;

if (token) {
  pm.environment.set("token", token);
  pm.environment.set("authToken", token);
  pm.globals.set("authToken", token);
  console.log("Auth Token:", token);
}
```

This supports both response styles:

```json
{
  "token": "1|xxxxxxxx"
}
```

and:

```json
{
  "data": {
    "token": "1|xxxxxxxx"
  }
}
```

## External API Keys In `.env`

| Keys | Used by | Postman route to test |
| --- | --- | --- |
| `OPENAI_API_KEY` | Reserved for AI features, but the current `AIService.php` uses local keyword matching and does not call OpenAI yet. | `POST /analyze` still works without OpenAI. |
| `JOOBLE_API_KEY` | Live job search. | `GET /jobs/live` |
| `GEOAPIFY_API_KEY` | Address to latitude/longitude and radius filtering. | `GET /geo/geocode`, `GET /jobs/live` with `origin` |
| `MAILBOXLAYER_API_KEY` | Email validation. | `POST /auth/validate-email`, `POST /auth/register` |
| `BREVO_API_KEY` | Email notification sending. | `POST /notifications/email` |

If an integration key is missing, the API either returns a clear validation/configuration message or skips/falls back depending on the service.

## Collection Folder Structure

```text
HireSmart APIs
|-- Health
|-- Auth
|-- Profile
|-- Resume
|-- Analysis
|-- Recommendations
|-- Jobs
|-- Integrations
|-- Notifications
|-- Gateway Routes
|-- Error Handling
```

## Health

### GET Test

```http
GET {{base_url}}/test
```

Expected:

```json
{
  "message": "API is working!"
}
```

### GET Ping

```http
GET {{base_url}}/ping
```

## Auth

### POST Register

```http
POST {{base_url}}/auth/register
```

```json
{
  "name": "Postman User",
  "email": "{{user_email}}",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "job_seeker"
}
```

Postman Tests:

```js
const json = pm.response.json();
if (json.token) pm.environment.set("token", json.token);
```

### POST Login

```http
POST {{base_url}}/auth/login
```

```json
{
  "email": "{{user_email}}",
  "password": "password123"
}
```

Postman Tests:

```js
const json = pm.response.json();
if (json.token) pm.environment.set("token", json.token);
```

### POST Validate Email

```http
POST {{base_url}}/auth/validate-email
```

```json
{
  "email": "candidate@example.com"
}
```

## Profile

### GET Profile

```http
GET {{base_url}}/users/profile
Authorization: Bearer {{token}}
```

### PUT Update Profile

```http
PUT {{base_url}}/users/profile
Authorization: Bearer {{token}}
```

```json
{
  "name": "Updated Postman User",
  "phone": "09123456789",
  "bio": "Testing HireSmart AI API from Postman.",
  "preferences": {
    "location": "Cebu",
    "remote": true
  }
}
```

### POST Logout

```http
POST {{base_url}}/auth/logout
Authorization: Bearer {{token}}
```

## Resume

### POST Upload Resume

```http
POST {{base_url}}/resumes/upload
Authorization: Bearer {{token}}
```

Body type: `form-data`

| Key | Type | Value |
| --- | --- | --- |
| `title` | Text | `My Resume` |
| `resume` | File | Select `.pdf` or `.docx` |

Postman Tests:

```js
const json = pm.response.json();
if (json.resume?.resume_id) {
  pm.environment.set("resume_id", json.resume.resume_id);
  pm.environment.set("analysis_resume_id", json.resume.resume_id);
}
```

### GET List Resumes

```http
GET {{base_url}}/resumes
Authorization: Bearer {{token}}
```

### GET Show Resume

```http
GET {{base_url}}/resumes/{{resume_id}}
Authorization: Bearer {{token}}
```

### PUT Update Resume

```http
PUT {{base_url}}/resumes/{{resume_id}}
Authorization: Bearer {{token}}
```

```json
{
  "title": "Updated Resume Title",
  "is_active": true
}
```

### POST Activate Resume

```http
POST {{base_url}}/resumes/{{resume_id}}/activate
Authorization: Bearer {{token}}
```

### GET Download Resume URL

```http
GET {{base_url}}/resumes/{{resume_id}}/download
Authorization: Bearer {{token}}
```

### GET Compare Resumes

```http
GET {{base_url}}/resumes/compare/{{resume_id}}/{{analysis_resume_id}}
Authorization: Bearer {{token}}
```

### DELETE Resume

```http
DELETE {{base_url}}/resumes/{{resume_id}}
Authorization: Bearer {{token}}
```

## Analysis

### POST Analyze Resume

```http
POST {{base_url}}/analyze
Authorization: Bearer {{token}}
```

```json
{
  "resume_id": "{{resume_id}}",
  "job_description": "Laravel developer with PHP, MySQL, REST API, Git, Docker, communication, and problem solving."
}
```

### GET Analysis Dashboard

```http
GET {{base_url}}/analysis/dashboard
Authorization: Bearer {{token}}
```

### GET Show Analysis

```http
GET {{base_url}}/analysis/{{resume_id}}
Authorization: Bearer {{token}}
```

## Recommendations

### GET Resume Recommendations

```http
GET {{base_url}}/recommendations/resumes/{{resume_id}}
Authorization: Bearer {{token}}
```

## Jobs

### GET Jobs

```http
GET {{base_url}}/jobs
Authorization: Bearer {{token}}
```

### POST Create Job

```http
POST {{base_url}}/jobs
Authorization: Bearer {{token}}
```

```json
{
  "title": "Laravel Developer",
  "company": "HireSmart Demo Co.",
  "location": "Cebu City",
  "description": "Build Laravel APIs and integrate third-party services.",
  "required_skills": ["PHP", "Laravel", "MySQL", "REST API", "Git"],
  "nice_to_have_skills": ["Docker", "AWS"],
  "employment_type": "Full-time",
  "experience_level": "Mid-level",
  "salary_min": 35000,
  "salary_max": 70000,
  "application_deadline": "2026-12-31",
  "is_active": true
}
```

Postman Tests:

```js
const json = pm.response.json();
if (json.job?.job_id) pm.environment.set("job_id", json.job.job_id);
```

### GET Show Job

```http
GET {{base_url}}/jobs/{{job_id}}
Authorization: Bearer {{token}}
```

### PUT Update Job

```http
PUT {{base_url}}/jobs/{{job_id}}
Authorization: Bearer {{token}}
```

```json
{
  "salary_max": 80000,
  "required_skills": ["PHP", "Laravel", "MySQL", "REST API", "Git", "Docker"]
}
```

### POST Match Job To Resume

```http
POST {{base_url}}/jobs/{{job_id}}/match
Authorization: Bearer {{token}}
```

```json
{
  "resume_id": "{{resume_id}}"
}
```

### DELETE Job

```http
DELETE {{base_url}}/jobs/{{job_id}}
Authorization: Bearer {{token}}
```

## Integrations

### GET Geocode Address

Requires `GEOAPIFY_API_KEY`.

```http
GET {{base_url}}/geo/geocode?address=Cebu%20City%20Hall
Authorization: Bearer {{token}}
```

### GET Live Jobs

Requires `JOOBLE_API_KEY`. Distance fields require job coordinates from Jooble and either `origin`, or `origin_lat` plus `origin_lng`.

```http
GET {{base_url}}/jobs/live?what={{search_what}}&where={{search_where}}&origin={{origin}}&radius_km=20&results_per_page=5&sort_by=relevance
Authorization: Bearer {{token}}
```

## Notifications

### GET Notifications

```http
GET {{base_url}}/notifications
Authorization: Bearer {{token}}
```

### POST Send Notification Email

Requires `BREVO_API_KEY`.

```http
POST {{base_url}}/notifications/email
Authorization: Bearer {{token}}
```

```json
{
  "to_email": "{{to_email}}",
  "to_name": "Test Receiver",
  "subject": "New job matches found",
  "message": "Hi! We found new roles that match your profile."
}
```

## Gateway Routes

The project also exposes gateway-style aliases:

| Direct route | Gateway route |
| --- | --- |
| `POST /auth/register` | `POST /site1/register` |
| `POST /auth/login` | `POST /site1/login` |
| `POST /auth/validate-email` | `POST /site1/validate-email` |
| `GET /users/profile` | `GET /site2/users/profile` |
| `PUT /users/profile` | `PUT /site2/users/profile` |
| `POST /auth/logout` | `POST /site2/logout` |

Route map:

```http
GET {{base_url}}/gateway/routes
```

## Error Handling

Each request in the Postman collection now includes response examples where applicable. Use this matrix as the quick guide.

### Common Error Response Examples

**400 Bad Request**

```json
{
  "message": "Bad Request: Content-Type application/json is required."
}
```

**401 Unauthorized**

```json
{
  "message": "Unauthenticated."
}
```

**403 Forbidden**

```json
{
  "message": "Unauthorized"
}
```

**404 Not Found**

```json
{
  "message": "No query results for model."
}
```

**422 Validation Error**

```json
{
  "errors": {
    "field": [
      "The field is required or invalid."
    ]
  }
}
```

**500 Server Error**

```json
{
  "message": "Server error. Check Laravel logs and .env configuration."
}
```

### Error Examples Per Request

| Folder | Request | Error examples to test |
| --- | --- | --- |
| Health | `GET /test`, `GET /ping` | `404` if wrong URL, `500` if Laravel server has config/runtime issue |
| Auth | `POST /auth/register` | `422` missing fields, invalid email, duplicate email, weak password; `500` DB/config issue |
| Auth | `POST /auth/login` | `422` missing password/email or wrong credentials; `500` DB/config issue |
| Auth | `POST /auth/validate-email` | `422` invalid email; provider result may be `skipped` if API key is missing |
| Profile | `GET /users/profile` | `401` missing/expired token |
| Profile | `PUT /users/profile` | `401` missing token; `422` invalid name/phone/preferences |
| Profile | `POST /auth/logout` | `401` missing/expired token |
| Resume | `POST /resumes/upload` | `401` missing token; `422` missing title, missing file, invalid file type, file too large; `500` storage/parser issue |
| Resume | `GET /resumes` | `401` missing token |
| Resume | `GET /resumes/{id}` | `401` missing token; `403` another user's resume; `404` resume UUID not found |
| Resume | `PUT /resumes/{id}` | `401`; `403`; `404`; `422` invalid title or `is_active` |
| Resume | `DELETE /resumes/{id}` | `401`; `403`; `404`; `500` file storage delete issue |
| Resume | `POST /resumes/{id}/activate` | `401`; `403`; `404` |
| Resume | `GET /resumes/{id}/download` | `401`; `403`; `404` |
| Resume | `GET /resumes/compare/{originalId}/{improvedId}` | `401`; `403` if either resume belongs to another user; `404` if either UUID is missing |
| Analysis | `POST /analyze` | `401`; `403`; `422` missing/invalid `resume_id`; `404` resume missing |
| Analysis | `GET /analysis/dashboard` | `401` missing token |
| Analysis | `GET /analysis/{resumeId}` | `401`; `403`; `404` no analysis found |
| Recommendations | `GET /recommendations/resumes/{resumeId}` | `401`; `403`; `404` resume missing |
| Jobs | `GET /jobs` | `401` missing token |
| Jobs | `POST /jobs` | `401`; `422` missing title/company/location/description/skills/employment fields |
| Jobs | `GET /jobs/{id}` | `401`; `404` job UUID missing |
| Jobs | `PUT /jobs/{id}` | `401`; `403` not recruiter/owner; `404`; `422` invalid update fields |
| Jobs | `DELETE /jobs/{id}` | `401`; `403`; `404` |
| Jobs | `POST /jobs/{id}/match` | `401`; `403` resume owner mismatch; `404`; `422` missing/invalid `resume_id` |
| Integrations | `GET /geo/geocode` | `401`; `422` missing `address`; `400` unable to geocode or Geoapify key/config missing |
| Integrations | `GET /jobs/live` | `401`; `422` invalid query/radius fields; `200` with `configured:false` if Jooble key is missing |
| Notifications | `GET /notifications` | `401` missing token |
| Notifications | `POST /notifications/email` | `401`; `422` invalid email/subject/message or missing Brevo key |
| Gateway Routes | `/site1/*`, `/site2/*` | Same errors as their direct Auth/Profile equivalents |
| Error Handling | `POST /debug/400` | Reliable `400` demo |
| Error Handling | `GET /debug/401` | Reliable `401` demo |
| Error Handling | `/debug/422/{example}` | Reliable `422` demos for GET/POST/PUT/DELETE: `validation`, `duplicate-email`, `invalid-credentials`, `missing-password`, `invalid-file` |
| Error Handling | `GET /debug/403` | Reliable `403` demo |
| Error Handling | `GET /debug/404` | Reliable `404` demo |
| Error Handling | `GET /debug/500` | Reliable `500` demo |

### 400 Bad Request

```http
POST {{base_url}}/debug/400
Content-Type: text/plain
```

Body:

```text
hello
```

### 401 Unauthorized

Use the reliable demo route, or call any protected route without `Authorization`.

```http
GET {{base_url}}/debug/401
```

### 403 Forbidden

Use the reliable demo route, or use a resume owned by another user:

```http
GET {{base_url}}/debug/403
```

### 404 Not Found

```http
GET {{base_url}}/debug/404
```

### 422 Validation Error

```http
GET {{base_url}}/debug/422/validation
```

### 500 Server Error

```http
GET {{base_url}}/debug/500
```

## Recommended Run Order

1. `Health / GET Test`
2. `Auth / POST Register`
3. `Auth / POST Login`
4. `Profile / GET Profile`
5. `Resume / POST Upload Resume`
6. `Analysis / POST Analyze Resume`
7. `Analysis / GET Analysis Dashboard`
8. `Recommendations / GET Resume Recommendations`
9. `Jobs / POST Create Job`
10. `Jobs / POST Match Job To Resume`
11. `Integrations / GET Geocode Address`
12. `Integrations / GET Live Jobs`
13. `Notifications / POST Send Notification Email`
14. `Gateway Routes / GET Gateway Route Map`
15. `Error Handling` examples
