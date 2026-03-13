### Authentication Comparison: React Native Add-On

#### 1. Session/Cookie-Based Authentication
This approach reuses the existing web authentication mechanism where the server sets a session cookie (`PHPSESSID`) upon login.

*   **Required `security.yaml` Changes**: Minimal. The `main` firewall already supports this. Requires ensuring `/api` routes are covered and potentially disabling CSRF for API `POST` endpoints.
*   **Impact on React Native Flow**: Mobile app must capture the `Set-Cookie` header and include it in subsequent requests. Handled automatically by some networking libraries if configured.
*   **Impact on Caching League Context**: League context is server-side (session-tied). App caches `league_id` for UI filtering.
*   **Risks to Web App**: **Low to Moderate.** Reusing the `main` firewall is safe, but global session cookie settings might need adjustment.
*   **Long-term Fit**: **Low.** Sessions are stateful and tied to a single domain, which is less ideal for modern mobile apps.

#### 2. Token-Based API Authentication (Recommended)
This approach uses a stateless token (e.g., JWT or API Token) sent in the `Authorization: Bearer <token>` header.

*   **Required `security.yaml` Changes**: Moderate. Requires a new, separate `api` firewall with its own authenticator.
*   **Impact on React Native Flow**: App receives a token on login, stores it securely, and manually attaches it to every API request header.
*   **Impact on Caching League Context**: App stores `league_id` alongside the token; server extracts user context from the token/database.
*   **Risks to Web App**: **Very Low.** Separate firewalls ensure the existing web logic remains entirely untouched.
*   **Long-term Fit**: **High.** Industry standard for mobile apps; stateless, scalable, and resilient to mobile network issues.

---

### Recommendation: Token-Based API Authentication

**Reasoning**:
1.  **Isolation**: Dedicated `api` firewall guarantees zero risk to the existing web application.
2.  **Statelessness**: Better suited for mobile connectivity fluctuations and app lifecycle.
3.  **Modern Standard**: Provides a robust foundation for a standalone mobile experience.
4.  **Security**: Avoids cookie-based vulnerabilities like CSRF.

**Implementation Note**: Phase 1 will implement a simple token-based system where `/api/login` generates a unique string for validation via a custom Symfony Authenticator.
