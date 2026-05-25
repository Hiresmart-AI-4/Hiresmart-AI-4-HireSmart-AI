# Render Deployment (PostgreSQL Blueprint)

This project is configured to deploy to Render as a Docker-based Web Service integrated with your existing native PostgreSQL database using a Render Blueprint (`render.yaml`).

## Zero-Configuration Blueprint Setup

1. In the [Render Dashboard](https://dashboard.render.com/), click **New +** > **Blueprint**.
2. Select and connect your repository: **`Hiresmart-AI-4-HireSmart-AI`**.
3. Render will analyze the repository and configure the Web Service automatically using your existing database.

## Required Secrets on Setup

On the Blueprint setup page, enter:

* **`APP_KEY`**: Paste your secure production key.
  * Local-generated key: `base64:aB/8R6bWPx0utUhlrOZlCciTIIWe6Tx34HVMJq0avNY=`
* **`APP_URL`**: Your deployed service URL (e.g., `https://your-service-name.onrender.com`).
* **`DB_URL`**: Paste the **Internal Database URL** of your existing Render PostgreSQL database (`hiresmart-db`).
  * You can find this connection URL under the **Connections** section on your database's page in the Render dashboard. It starts with `postgresql://`.

All other database connections (`DB_CONNECTION=pgsql` and migrations) are automatically wired up by the Blueprint.

## Startup and Migrations

Upon starting, the container executes `sh docker/render-start.sh` which:
1. Performs optimal caching configuration (`php artisan config:cache`, `php artisan view:cache`).
2. Runs database migrations automatically (`php artisan migrate --force` if `RUN_MIGRATIONS=true`).
3. Starts the server on port `10000`.

Check the **Logs** tab in Render to monitor database migrations and runtime status. Visit `/workspace` to confirm live connection to the database.
