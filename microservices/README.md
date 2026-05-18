# HireSmart AI Microservices Layout

This folder mirrors the `ddsbe`, `ddsbe2`, and `ddsgateway` style from `C:\xampp\htdocs\microservices`.

## Services

- `ddsbe` - User Service 1. Handles public account actions such as register and login.
- `ddsbe2` - User Service 2. Handles authenticated user profile and logout actions.
- `ddsgateway` - API Gateway. Exposes `site1` and `site2` routes and forwards requests to the two services.

## Suggested Local Ports

- Gateway: `http://127.0.0.1:8100`
- User Service 1: `http://127.0.0.1:8101`
- User Service 2: `http://127.0.0.1:8102`

The main Laravel API still runs from `laravel/`. These folders document and scaffold the split-service architecture so the project source clearly has two backend services and one gateway.
