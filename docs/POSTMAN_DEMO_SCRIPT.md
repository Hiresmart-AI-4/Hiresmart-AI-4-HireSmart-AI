# HireSmart AI Postman Demo Script

Use this script for a live class/demo walkthrough. Import `docs/HireSmart_AI_Postman_Collection.json` first and select the `HireSmart AI - Local` environment.

## Setup

1. Start Laravel:

```powershell
cd laravel
php artisan serve --host=127.0.0.1 --port=8000
```

2. Check environment variables:

| Variable | Value |
| --- | --- |
| `base_url` | `http://127.0.0.1:8000/api` |
| `user_email` | `postman@example.com` |
| `token` | Blank before login/register |
| `resume_id` | Blank before upload |
| `job_id` | Blank before creating job |

## Demo Flow

### 1. Health Check

Run:

```http
GET {{base_url}}/test
```

Say:

> The backend is reachable and returning JSON from Laravel.

Expected:

```json
{
  "message": "API is working!"
}
```

### 2. Register User

Run:

```http
POST {{base_url}}/auth/register
```

Body:

```json
{
  "name": "Postman User",
  "email": "{{user_email}}",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "job_seeker"
}
```

Say:

> Register creates the account and saves the Bearer token automatically.

If the user already exists, run Login instead.

### 3. Login

Run:

```http
POST {{base_url}}/auth/login
```

Body:

```json
{
  "email": "{{user_email}}",
  "password": "password123"
}
```

Say:

> Login confirms the same registered email and refreshes the token.

### 4. Profile

Run:

```http
GET {{base_url}}/users/profile
Authorization: Bearer {{token}}
```

Say:

> Protected routes require the token. This proves Sanctum auth is working.

### 5. Upload Resume

Run:

```http
POST {{base_url}}/resumes/upload
Authorization: Bearer {{token}}
```

Body type: `form-data`

| Key | Type | Value |
| --- | --- | --- |
| `title` | Text | `Demo Resume` |
| `resume` | File | Select a PDF or DOCX resume |

Say:

> The resume upload stores the file metadata and returns a UUID. The collection saves it as `resume_id`.

Copy `resume.resume_id` into `resume_id` if the variable does not auto-save.

### 6. Analyze Resume

Run:

```http
POST {{base_url}}/analyze
Authorization: Bearer {{token}}
```

Body:

```json
{
  "resume_id": "{{resume_id}}",
  "job_description": "Laravel developer with PHP, MySQL, REST API, Git, Docker, communication, and problem solving."
}
```

Say:

> The analyzer compares resume content with target job keywords and returns score, missing keywords, and recommendations.

### 7. Resume Recommendations

Run:

```http
GET {{base_url}}/recommendations/resumes/{{resume_id}}
Authorization: Bearer {{token}}
```

Say:

> Recommendations give practical resume improvements based on parsed skills.

### 8. Create Internal Job

Run:

```http
POST {{base_url}}/jobs
Authorization: Bearer {{token}}
```

Body:

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

Say:

> Recruiters can create internal jobs with required skills. The collection saves the returned `job_id`.

Copy `job.job_id` into `job_id` if the variable does not auto-save.

### 9. Job Matching

Run:

```http
POST {{base_url}}/jobs/{{job_id}}/match
Authorization: Bearer {{token}}
```

Body:

```json
{
  "resume_id": "{{resume_id}}"
}
```

Say:

> Job matching compares the resume skills with the job's required skills and returns match score, matching skills, missing skills, and improvement recommendations.

Expected fields:

```json
{
  "message": "Job match generated",
  "match": {
    "match_score": 80,
    "skill_match": ["PHP", "Laravel"],
    "missing_skills": ["Docker"],
    "recommendations": ["Consider adding Docker to your skills section or project examples."]
  }
}
```

### 10. Live Job Recommendations

Run:

```http
GET {{base_url}}/jobs/live?what=Laravel%20Developer&where=Cebu&origin=Cebu%20City%20Hall&radius_km=50
Authorization: Bearer {{token}}
```

Say:

> Live job recommendations call the configured provider. If the API key is missing, the response still explains the provider status.

### 11. Error Examples

Show these examples from the collection's saved error responses:

```http
POST {{base_url}}/debug/422/duplicate-email
```

Expected:

```json
{
  "errors": {
    "email": [
      "The email has already been taken."
    ]
  }
}
```

```http
GET {{base_url}}/debug/401
```

Expected:

```json
{
  "message": "Unauthenticated."
}
```

```http
GET {{base_url}}/debug/500
```

Expected:

```json
{
  "message": "Server error. Check Laravel logs and .env configuration."
}
```

Say:

> The Postman examples are configured to return the exact JSON shown in the example body, so the audience can see both success and failure flows clearly.
