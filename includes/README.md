# includes/

Shared PHP building blocks reused across the dynamic pages.

Direct browser access to this folder is blocked by `.htaccess`.

## Helpers (Phase 3)

| File | Contents |
|------|----------|
| `database.php` | `db()` - the shared PDO connection (Phase 2). |
| `csrf.php` | `csrf_token()`, `csrf_field()`, `csrf_regenerate()`, `csrf_verify()`. One random token per session, compared with `hash_equals()`. |
| `validation.php` | `v_required`, `v_length`, `v_email`, `v_match`, `v_password`, `v_phone_optional` - server-side field checks that fill an `$errors` array. |
| `auth.php` | Session bootstrap and authorisation: `auth_boot()`, `current_user()`, `is_logged_in()`, `login_user()`, `logout_user()`, `require_login()`, `require_role()`, `require_guest()`, `dashboard_url_for()`, `redirect()`, `safe_return_to()`, `flash_set()/flash_get()`, `e()`. |

`auth.php` pulls in `database.php`, `csrf.php` and `validation.php`, so a
page only needs:

```php
require_once __DIR__ . '/../includes/auth.php';
auth_boot();
```

## Shared page partials (Phase 3; extended in Phase 7)

| File | Purpose |
|------|---------|
| `header.php` | Opens the document, links the existing `css/style.css` and `js/script.js`, renders the site header, and prints any one-time flash message. Expects `$page_title`. |
| `nav.php` | Primary navigation; shows Log in / Register when signed out and Dashboard / Log out when signed in. |
| `footer.php` | Closes `<main>` and the document. |

These are used **only** by the pages in `auth/` and `dashboard/`. The six
existing public `.html` pages are untouched until Phase 7.

## Authorisation model

`require_role()` decides access from the **current database role**, looked
up by user id on each request (`current_user()`), not from any value in a
cookie or form. Navigation visibility is a convenience only.
