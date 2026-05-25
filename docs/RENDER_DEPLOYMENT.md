# Render Deployment

This project is deployed on Render as a Docker web service that starts Laravel with:

```sh
php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
```

Use the repository root as the Docker context. The Laravel app depends on sibling folders such as `frontend/` and `docs/`, so do not set the Render root directory to `laravel/`.

## Render Setup

1. Push the repository to GitHub.
2. In Render, create a new Blueprint from `render.yaml`, or create a Web Service manually.
3. If creating manually:
   - Runtime: Docker
   - Dockerfile path: `./Dockerfile`
   - Docker context: `.`
   - Root directory: leave blank
4. Add the required environment variables.

## Required Environment Variables

```env
APP_KEY=base64:your-generated-laravel-key
APP_URL=https://your-render-service.onrender.com

DB_CONNECTION=mysql
DB_HOST=your-mysql-host
DB_PORT=3306
DB_DATABASE=your-database-name
DB_USERNAME=your-database-user
DB_PASSWORD=your-database-password
```

Render provides PostgreSQL natively. If the app stays on MySQL, use an external hosted MySQL database and paste those credentials into Render.

## Generate APP_KEY

Run this locally inside `laravel/`:

```sh
php artisan key:generate --show
```

Copy the generated value into Render as `APP_KEY`.

## Migrations

`RUN_MIGRATIONS=true` makes the container run:

```sh
php artisan migrate --force
```

before starting the web server. Set `RUN_MIGRATIONS=false` if you want to run migrations manually.
