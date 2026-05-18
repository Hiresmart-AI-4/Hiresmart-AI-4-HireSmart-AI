# ddsgateway - API Gateway

The gateway exposes two route groups:

- `api/site1/*` forwards to `ddsbe`
- `api/site2/*` forwards to `ddsbe2`

## Routes

- `GET /health`
- `GET /api/gateway/routes`
- `POST /api/site1/register`
- `POST /api/site1/login`
- `GET /api/site2/users/profile`
- `PUT /api/site2/users/profile`
- `POST /api/site2/logout`

This gateway maps to the main Laravel implementation in `laravel/app/Gateways/ApiGateway.php`.
