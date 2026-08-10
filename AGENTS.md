> You are in **rushing/laravel-auth-vault** — a generic per-tenant credential vault for Laravel: encrypted, append-only secret storage with safe rotation and an OAuth token-refresh primitive.

Stores API keys and OAuth tokens on one polymorphic table. It knows nothing of connectors, tenancy, or billing — it stores a secret and refreshes an OAuth token, nothing more.
