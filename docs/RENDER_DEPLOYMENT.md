# Render Deployment (PostgreSQL + Docker)

This project deploys to Render as a Docker web service. The Laravel application lives in `laravel/` and connects to Render PostgreSQL using environment variables.

## Blueprint setup

1. In the [Render Dashboard](https://dashboard.render.com/), click **New +** > **Blueprint**.
2. Connect this repository.
3. Render reads `render.yaml` and creates the web service.

## Required environment variables

Set these in the Render service (Blueprint prompts or **Environment** tab):

| Variable | Description |
|----------|-------------|
| `APP_KEY` | Run `php artisan key:generate --show` locally and paste the `base64:...` value |
| `APP_URL` | Public URL, e.g. `https://hiresmart-ai.onrender.com` |
| `DB_CONNECTION` | `pgsql` (set in blueprint) |
| `DB_HOST` | PostgreSQL host from Render |
| `DB_PORT` | `5432` |
| `DB_DATABASE` | Database name |
| `DB_USERNAME` | Database user |
| `DB_PASSWORD` | Database password |

Alternatively, set **`DB_URL`** to the Render **Internal Database URL** (`postgresql://...`). Laravel reads `DB_URL` or `DATABASE_URL`.

## Container startup

`docker/render-start.sh` runs on each deploy:

1. Ensures storage/bootstrap cache directories exist
2. Runs `php artisan migrate --force` when `RUN_MIGRATIONS=true`
3. Caches config, routes, and views
4. Starts `php artisan serve` on `PORT` (default `10000`)

## Verify deployment

- **Logs**: migration output and `Server running`
- **Health**: `GET /up`
- **API**: `GET /api/test`
- **Workspace**: `GET /workspace`

## Local Docker test

```bash
docker build -t hiresmart-ai .
docker run --rm -p 10000:10000 \
  -e APP_KEY=base64:YOUR_KEY \
  -e APP_URL=http://127.0.0.1:10000 \
  -e DB_CONNECTION=pgsql \
  -e DB_URL=postgresql://user:pass@host:5432/dbname \
  hiresmart-ai
```
