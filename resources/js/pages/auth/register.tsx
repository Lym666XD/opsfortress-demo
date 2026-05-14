// Public registration was disabled in Phase 0 (see config/fortify.php).
// This file is kept as a stub so vite does not surface a pre-transform
// error for unresolved `@/routes/register` imports during HMR. It is
// never rendered — Fortify no longer registers a /register route, and
// the Welcome / Login pages no longer link here.
//
// New users are provisioned via the admin invite flow (M9-M10), not
// through public self-registration.
//
// If we ever re-enable Fortify registration, restore this file from
// git history.

export default function Register() {
    return null;
}
