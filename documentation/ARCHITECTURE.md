# Architecture Decisions

## Backend (PHP)
- **Vanilla PHP MVC**: Built without bloated frameworks to demonstrate deep understanding of routing, middleware, and request lifecycles.
- **Stateless Auth**: JWT is used for stateless authentication. A `token_blacklist` table ensures immediate invalidation of revoked tokens.
- **Custom Router**: Handles dynamic parameters (e.g., `/tasks/{id}`) using regex and injects `Request` context.
- **Queue System**: A database-driven queue (`jobs` table) with a dedicated CLI worker handles heavy tasks (thumbnail generation, bulk updates, exports) asynchronously. `SELECT ... FOR UPDATE SKIP LOCKED` ensures job concurrency safety.
- **Real-time SSE**: Implemented via a separate, non-blocking `sse.php` loop that pushes database updates to connected clients using Server-Sent Events.

## Frontend (Next.js)
- **Pages Router**: Used for its simplicity and directness in building standard CRUD applications.
- **Standard CSS**: Intentionally avoided heavy UI libraries (Tailwind, Material UI) to adhere strictly to the "plain test" aesthetic requirements.
- **EventSource**: Used natively to consume the backend SSE stream for real-time task and comment updates.
- **Custom Toasts & Contexts**: State management relies entirely on native React Hooks (`useState`, `useContext`) to keep the footprint light.
