# UnSet BIM API — v1 contract

Reference for every client of the API (the Revit plugin first, future apps later).
The server is the source of truth: clients display and cache what it returns, but
never decide access on their own.

> Covered by `tests/Feature/Api` (AuthTest, AccessTest, CatalogTest, DownloadTest).
> Any change to this contract must update this file and those tests together.

---

## 1. Basics

| Item | Value |
|---|---|
| Base URL | `https://unset.com.br/api/v1` (local: `{APP_URL}/api/v1`) |
| Format | JSON, UTF-8. The server forces `Accept: application/json` on every `/api/*` request, so errors are always JSON even if the client forgets the header. |
| Auth | Laravel Sanctum personal access token: `Authorization: Bearer {token}` |
| Dates | ISO 8601 with offset (`2026-10-27T15:52:00-03:00`) |
| Money | `price` is a number in BRL (`49.9`), `formatted_price` is a ready-to-show string |
| Language | Field names and codes in English; `message` texts are user-facing, in Portuguese — show them as they are |
| Account creation | Not available through the API. Sign-up, e-mail verification and password reset happen on the site only. |
| CORS | Browser calls are accepted only from the site's own origin (`APP_URL`). Desktop clients are not affected by CORS. |

### Recommended headers

```
Accept: application/json
Authorization: Bearer {token}        # every endpoint except /auth/login
User-Agent: UnSetBIM-Revit/{plugin version} (Revit {year})
```

---

## 2. Endpoints at a glance

| Method | Path | Auth | Rate limit | Purpose |
|---|---|---|---|---|
| GET | `/plugin/latest` | — | 60/min per IP | Latest plugin release (update check) |
| POST | `/auth/login` | — | 20/min per IP + 5 failed attempts per e-mail+IP | Sign in, get a token |
| POST | `/auth/device` | — | 10/min per IP | Start a browser sign-in |
| POST | `/auth/device/token` | — | 30/min per IP | Poll for the browser sign-in token |
| GET | `/auth/me` | Bearer | 120/min | Current user + access block |
| POST | `/auth/logout` | Bearer | 120/min | Revoke the current token |
| GET | `/categories` | Bearer | 120/min | Categories with subcategories |
| GET | `/brands` | Bearer | 120/min | Brands |
| GET | `/families` | Bearer | 120/min | Paginated catalog with filters |
| GET | `/families/{slug}` | Bearer | 120/min | Family detail + download decision |
| GET | `/families/{slug}/download` | Bearer | 120/min + 20/min | Download the `.rfa` |
| GET | `/me/favorites` | Bearer | 120/min | The user's favorite families |
| POST | `/families/{slug}/favorite` | Bearer | 120/min | Add a favorite |
| DELETE | `/families/{slug}/favorite` | Bearer | 120/min | Remove a favorite |
| GET | `/me/downloads` | Bearer | 120/min | The user's download history |

Every authenticated endpoint also re-checks that the account is still active
(see `account_inactive` below).

---

## 3. Authentication

### 3.1 `POST /auth/login`

**Body (JSON)**

| Field | Type | Rules |
|---|---|---|
| `email` | string | required, valid e-mail |
| `password` | string | required |
| `device_name` | string | required, max 100. Identifies the device/installation (e.g. `"Revit 2025 - DESKTOP-ABC"`). |

**Device rules**

- Signing in again with the same `device_name` **replaces** that device's previous token (the old one stops working).
- At most **5 devices** per user. Signing in a 6th drops the oldest token(s).
- Tokens expire after `SANCTUM_EXPIRATION` minutes (default 43 200 = 30 days). `expires_at` tells the exact moment; `null` means it never expires.

**200 OK**

```json
{
  "token": "12|Qm9yZGVyLXRva2VuLWV4YW1wbGU...",
  "token_type": "Bearer",
  "expires_at": "2026-11-26T15:52:00-03:00",
  "user": {
    "id": 7,
    "name": "Maria Souza",
    "email": "maria@example.com",
    "avatar_url": "https://unset.com.br/storage/avatars/7.webp"
  },
  "access": { "...": "see section 4" }
}
```

The client should store `token` securely (e.g. DPAPI on Windows) together with
`expires_at`, and cache `user` + `access` for display.

**Errors**

| Status | When | Body |
|---|---|---|
| 422 | Missing/invalid fields | Validation shape (section 9.2) |
| 422 | Wrong e-mail or password — including Google-only accounts that have no password | `errors.email[0]` = `"E-mail ou senha incorretos. Se você criou a conta com o Google, defina uma senha em Minha conta > Senha, no site."` |
| 403 | Correct password, account deactivated | `{"message": "...", "code": "account_inactive"}` |
| 403 | Correct password, e-mail not verified yet | `{"message": "...", "code": "email_not_verified"}` |
| 429 | 5 failed attempts for this e-mail+IP (shared with the site login) | `{"message": "Muitas tentativas de acesso. Tente novamente em N segundos.", "code": "too_many_attempts"}` + `Retry-After` header |
| 429 | More than 20 login requests per minute from the IP | `{"message": "Too Many Attempts."}` + `Retry-After` (no `code`) |

The wrong-credentials answer is identical whether or not the e-mail exists, so it
never reveals registered accounts. `account_inactive` and `email_not_verified`
are only returned after the password is confirmed.

### 3.2 `GET /auth/me`

Use it on plugin start to validate a stored token and refresh the access block.

**200 OK**

```json
{
  "data": {
    "id": 7,
    "name": "Maria Souza",
    "email": "maria@example.com",
    "avatar_url": null,
    "access": { "...": "see section 4" }
  }
}
```

Note: `login` returns `user` and `access` side by side; `me` returns them merged
inside `data`.

**Errors:** 401, 403 `account_inactive`, 429 (section 9).

### 3.3 `POST /auth/logout`

Revokes only the token used in the request (other devices stay signed in).

**204 No Content** — empty body. The client deletes its stored token either way.

**Errors:** 401 (token already invalid — treat as logged out), 429.

### 3.4 Browser sign-in (device authorization)

Alternative to the password login, based on the OAuth 2.0 device authorization
flow (RFC 8628). The user approves the plugin on the site, signed in there with
a password **or Google**, so it also works for Google-only accounts and the
password never passes through the plugin.

```
Plugin                         API                          Site (browser)
  | POST /auth/device  -------->  |                                |
  | <-- device_code, user_code    |                                |
  | open verification_uri_complete -----------------------------> |
  | show user_code                |      user signs in, compares   |
  | POST /auth/device/token ----> |      the code, clicks Autorizar|
  | <-- 400 authorization_pending |  <---------------------------- |
  | POST /auth/device/token ----> |                                |
  | <-- 200 token (same as login) |                                |
```

#### `POST /auth/device`

**Body:** `device_name` (string, required, max 100) — same meaning as in the login.

**200 OK**

```json
{
  "device_code": "4f1c...e9 (64 hex chars, secret)",
  "user_code": "KXQP-WMZB",
  "verification_uri": "https://unset.com.br/conectar",
  "verification_uri_complete": "https://unset.com.br/conectar?codigo=KXQP-WMZB",
  "expires_in": 600,
  "interval": 5
}
```

| Field | Notes |
|---|---|
| `device_code` | Secret used only to poll. Keep it in memory, never show or log it. |
| `user_code` | Show it big in the plugin; the user checks it matches the site. 8 consonants (no vowels, no look-alike characters). |
| `verification_uri_complete` | Open it in the default browser. If the user is not signed in on the site, they sign in first and come back to it. |
| `expires_in` | Seconds until the request expires (10 minutes). |
| `interval` | Minimum seconds between polls. |

**Errors:** 422 (`device_name`), 429.

#### `POST /auth/device/token`

**Body:** `device_code` (string, required). Poll every `interval` seconds until
one of the final answers.

| Status | `code` | Meaning | Client action |
|---|---|---|---|
| 200 | — | Approved. Body identical to `POST /auth/login` (token, `token_type`, `expires_at`, `user`, `access`); same device rules (same `device_name` replaces its token, max 5 devices). | Store the token, stop polling |
| 400 | `authorization_pending` | Not answered yet | Keep polling |
| 429 | `slow_down` | Polled faster than `interval` (`Retry-After` header) | Wait, then keep polling |
| 403 | `access_denied` | The user clicked "Negar" | Stop, show message |
| 403 | `account_inactive` | Account deactivated after the approval | Stop, show message |
| 410 | `expired_token` | Expired, already used, or unknown device code | Stop; offer to start again |
| 422 | — | Missing `device_code` | Fix the request |

The token is handed out **once**: a second successful poll answers 410.

---

## 4. The `access` block

Returned by `login` (as `access`) and `me` (as `data.access`).

```json
{
  "has_access": true,
  "modules": ["architectural"],
  "permissions": ["can_rate"],
  "subscription": {
    "status": "active",
    "status_label": "Ativa",
    "billing_cycle": "monthly",
    "plan": { "slug": "arquitetonico", "name": "Arquitetônico" },
    "trial_ends_at": null,
    "renews_at": "2026-11-27T10:00:00-03:00",
    "expires_at": null
  },
  "quota": {
    "limit": 30,
    "used": 4,
    "resets_at": "2026-11-01T00:00:00-03:00"
  }
}
```

| Field | Type | Meaning |
|---|---|---|
| `has_access` | bool | `true` only while a subscription currently grants access (active; trial before `trial_ends_at`; canceled before `expires_at`). |
| `modules` | string[] | Modules of the granting plan. Known keys: `architectural` (architecture tools + families plugin), `structural`. Empty when `has_access` is `false`. |
| `permissions` | string[] | Extra plan permissions. Known key: `can_rate`. Favorites are open to every verified user, so they are not listed here. Empty without access. |
| `subscription` | object \| null | The granting subscription; if none grants access, the **latest** subscription in any status (so the client can explain why, e.g. `past_due`). `null` if the user never subscribed. |
| `subscription.status` | string | `active`, `trial`, `canceled`, `past_due`, `expired`, `pending`. |
| `subscription.status_label` | string | Portuguese label ready to show (`Ativa`, `Trial`, `Cancelada`, `Pagamento pendente`, `Expirada`, `Aguardando pagamento`). |
| `subscription.billing_cycle` | string | `monthly` or `yearly`. |
| `subscription.plan` | object | `slug` and `name` of the plan (both may be `null` if the plan was removed). |
| `quota` | object \| null | Monthly download quota. `null` when `has_access` is `false`. |
| `quota.limit` | int \| null | Paid families per calendar month; `null` = unlimited. |
| `quota.used` | int | Distinct paid families downloaded this month (free and purchased families never count; downloading the same family again in the month does not count twice). |
| `quota.resets_at` | datetime | First day of next month, 00:00. |

A user with no plan gets:

```json
{ "has_access": false, "modules": [], "permissions": [], "subscription": null, "quota": null }
```

**Client guidance:** `modules` gate the module-specific tools in the client
(e.g. the Architectural or Structural tab), never the families. The catalog and
downloads are available to every verified user; for each family, the
authoritative answer is `download.allowed` in the detail endpoint (section 5.4),
and the download endpoint enforces it again.

---

## 5. Catalog

Only **published** families are ever returned. Categories, subcategories and
brands appear only when active and with at least one published family.

### 5.1 `GET /categories`

No parameters, no pagination. Ordered by the display order set in the admin.

**200 OK**

```json
{
  "data": [
    {
      "id": 3,
      "name": "Portas",
      "slug": "portas",
      "icon": "door",
      "families_count": 42,
      "subcategories": [
        { "id": 11, "name": "Portas de giro", "slug": "portas-de-giro" }
      ]
    }
  ]
}
```

- `icon`: icon name or `null`.
- `families_count`: published families in the category.
- `subcategories`: active subcategories that have published families (may be `[]`).
- A subcategory `slug` is unique only inside its category.

### 5.2 `GET /brands`

No parameters, no pagination, ordered by name.

**200 OK**

```json
{
  "data": [
    { "id": 2, "name": "Deca", "slug": "deca", "country": "BR", "logo_url": "https://unset.com.br/storage/brands/deca-thumb.webp" }
  ]
}
```

`country` (ISO 3166-1 alpha-2) and `logo_url` may be `null`.

### 5.3 `GET /families`

**Query parameters** (all optional)

| Param | Type | Rules | Effect |
|---|---|---|---|
| `q` | string | max 100 | Search in name, description and tags |
| `category` | string | max 100; **required when `subcategory` is sent** | Category slug |
| `subcategory` | string | max 100 | Subcategory slug (inside `category`) |
| `brand` | string | max 100 | Brand slug |
| `free` | boolean | `1` or `0` | `1` = only free families, `0` = only paid families, absent = both |
| `revit_version` | int | 2000–2100 | Only families this Revit version can open (`family.revit_version <= value`). The plugin should always send the running Revit year. |
| `sort` | string | `recent` (default), `downloads`, `views`, `name` | Newest first / most downloaded / most viewed / A–Z |
| `per_page` | int | 1–50, default 20 | Page size |
| `page` | int | min 1, default 1 | Page number |

Unknown slugs are not errors: they just return an empty list. Invalid values
(wrong type, out of range, unknown `sort`, `subcategory` without `category`)
return **422**.

**200 OK** — standard Laravel paginated resource:

```json
{
  "data": [
    {
      "id": 120,
      "slug": "porta-giro-80x210",
      "name": "Porta de giro 80x210",
      "category":    { "id": 3,  "name": "Portas", "slug": "portas" },
      "subcategory": { "id": 11, "name": "Portas de giro", "slug": "portas-de-giro" },
      "brand": null,
      "revit_version": 2022,
      "is_parametric": true,
      "is_free": false,
      "price": 19.9,
      "formatted_price": "R$ 19,90",
      "thumbnail_url": "https://unset.com.br/storage/families/porta-giro-80x210-thumb.webp",
      "is_favorite": false
    }
  ],
  "links": {
    "first": "https://unset.com.br/api/v1/families?page=1",
    "last": "https://unset.com.br/api/v1/families?page=7",
    "prev": null,
    "next": "https://unset.com.br/api/v1/families?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 7,
    "path": "https://unset.com.br/api/v1/families",
    "per_page": 20,
    "to": 20,
    "total": 132,
    "links": []
  }
}
```

- `links.*` keep the current filters (query string preserved). Follow `links.next` until it is `null`.
- `meta.links` is Laravel's page-button list; clients can ignore it.

**Family list item**

| Field | Type | Notes |
|---|---|---|
| `id` | int | |
| `slug` | string | Stable identifier used in every family URL |
| `name` | string | |
| `category` / `subcategory` / `brand` | `{id, name, slug}` \| null | |
| `revit_version` | int | Minimum Revit year the file opens in (saved version) |
| `is_parametric` | bool | |
| `is_free` | bool | |
| `price` | number \| null | BRL. `null` or `0` on a non-free family = subscribers only (cannot be bought separately) |
| `formatted_price` | string | `"Gratuito"`, `"Exclusivo para assinantes"` or `"R$ 19,90"` |
| `thumbnail_url` | string \| null | Thumbnail, falling back to the main image |
| `is_favorite` | bool | Whether the signed-in user favorited it |

### 5.4 `GET /families/{slug}`

Everything in the list item, plus:

```json
{
  "data": {
    "id": 120,
    "slug": "porta-giro-80x210",
    "name": "Porta de giro 80x210",
    "...": "list item fields",
    "description": "Porta de giro paramétrica com batente e guarnição...",
    "tags": ["porta", "giro", "madeira"],
    "image_url": "https://unset.com.br/storage/families/porta-giro-80x210.webp",
    "download_count": 318,
    "updated_at": "2026-10-20T09:14:00-03:00",
    "file": { "size_bytes": 2516582, "formatted_size": "2.4 MB" },
    "page_url": "https://unset.com.br/revit/familias/porta-giro-80x210",
    "download": {
      "url": "https://unset.com.br/api/v1/families/porta-giro-80x210/download",
      "allowed": false,
      "message": "Esta família é exclusiva para assinantes ou compra avulsa.",
      "code": "access_required",
      "purchase_url": "https://unset.com.br/revit/familias/porta-giro-80x210/comprar"
    }
  }
}
```

| Field | Type | Notes |
|---|---|---|
| `description` | string \| null | |
| `tags` | string[] | |
| `image_url` | string \| null | Main image (larger than the thumbnail) |
| `download_count` | int | |
| `updated_at` | datetime \| null | Use to invalidate a local cache of the `.rfa` |
| `file` | object \| null | `.rfa` size; `null` if the family has no file (download will answer 404) |
| `page_url` | string | Public page on the site (open in the browser) |
| `download.url` | string | Endpoint to call (section 6) |
| `download.allowed` | bool | Server decision for **this user**, right now |
| `download.message` | string \| null | Portuguese reason when `allowed` is `false`; `null` when allowed |
| `download.code` | string \| null | `access_required`, `quota_exceeded`, or `null` when allowed (section 9.4) |
| `download.purchase_url` | string \| null | Site page to buy the family; only when not allowed **and** `price > 0`. Purchases never happen inside the plugin — open it in the browser (the API token does not sign the user in on the site, so they may be asked to log in there). |

**Download rule** (same as the site, `FamilyPolicy::download`), in order:

1. Free family → allowed.
2. Purchased by the user → allowed (never uses quota).
3. Active subscription → allowed while `quota.used < quota.limit` (or unlimited); a family already counted this month is always allowed again.
   Otherwise denied with `quota_exceeded`: `"Você atingiu o limite de downloads do seu plano neste mês."`
4. Anything else → denied with `access_required`: `"Esta família é exclusiva para assinantes ou compra avulsa."`

Plan **modules are not part of this rule**: families are open to every verified
user under the rule above, whatever modules the plan has.

**Errors:** 401, 403 `account_inactive`, **404** (slug does not exist or family is not published), 429.

---

## 6. Download

### `GET /families/{slug}/download`

Always goes through the server: it checks the rule above, records the download
(history + counter, in one transaction) and streams the file.

**200 OK** — binary body (not JSON):

```
Content-Type: application/octet-stream
Content-Disposition: attachment; filename={slug}.rfa
Content-Length: {bytes}
```

Save to a temporary file first and move it into place only after the transfer
completes; a broken transfer must not leave a half-written `.rfa`.

**Errors (JSON)**

| Status | When | Recorded? |
|---|---|---|
| 401 | Missing/expired/revoked token | no |
| 403 | `account_inactive` (has `code`) | no |
| 403 | Download not allowed — `{"message": "...", "code": "access_required" \| "quota_exceeded"}` | no |
| 404 | Unknown slug, unpublished family, no `.rfa` attached, or file missing on disk | no |
| 429 | More than 20 downloads per minute (or 120 requests/min overall) | no |

A download is recorded **only** when the file is actually served.

Clients should check `download.allowed` from the detail before calling this
endpoint, but must still handle 403 (quota can run out between the two calls).

---

## 7. The user's lists

### 7.1 Favorites

Every verified user may favorite families; it is not a plan permission. The
site and the API share the same list.

#### `POST /families/{slug}/favorite` and `DELETE /families/{slug}/favorite`

No body. Both are **idempotent**: adding an existing favorite or removing a
missing one is not an error.

**200 OK**

```json
{ "data": { "slug": "porta-giro-80x210", "is_favorite": true } }
```

**Errors:** 401, 403 `account_inactive`, 404 (POST: unknown slug or unpublished
family; DELETE: unknown slug only — unpublished favorites can always be
removed), 429.

#### `GET /me/favorites`

| Param | Type | Rules |
|---|---|---|
| `per_page` | int | 1–50, default 20 |
| `page` | int | min 1, default 1 |

**200 OK** — paginated like `/families` (section 5.3). Each item is a family
list item plus:

| Field | Type | Notes |
|---|---|---|
| `favorited_at` | datetime | When it was favorited. The list is ordered by it, newest first. |

Unpublished families are hidden but stay saved: they come back if published again.

**Errors:** 401, 403 `account_inactive`, 422 (page parameters), 429.

### 7.2 Download history

#### `GET /me/downloads`

Same page parameters as `/me/favorites`. One item **per family** (not per
download), most recently downloaded first. Only published families are listed.

**200 OK** — paginated; each item is a family list item plus:

| Field | Type | Notes |
|---|---|---|
| `last_downloaded_at` | datetime | Last time this user downloaded it |
| `downloads_count` | int | How many times this user downloaded it |

**Errors:** 401, 403 `account_inactive`, 422 (page parameters), 429.

---

## 8. Plugin updates

### `GET /plugin/latest`

**Public** (no token): an old or signed-out plugin must still learn that it has
to update. The data comes from the latest **GitHub Release** of the plugin
repository (drafts and pre-releases are ignored), cached for 15 minutes.

| Param | Type | Rules |
|---|---|---|
| `current` | string | optional; the running plugin version, `1.2.0` or `v1.2.0` (1 to 4 numeric parts) |

**200 OK**

```json
{
  "data": {
    "version": "1.4.0",
    "name": "UnSet BIM v1.4.0",
    "notes": "- Correções...",
    "released_at": "2026-10-27T18:00:00Z",
    "release_url": "https://github.com/ronaldoaires/unset-bim-revit/releases/tag/v1.4.0",
    "download_url": "https://github.com/ronaldoaires/unset-bim-revit/releases/download/v1.4.0/UnSetBIM-Setup.msi",
    "file_name": "UnSetBIM-Setup.msi",
    "size_bytes": 5242880,
    "sha256": "9f2c...e1",
    "min_version": "1.0.0",
    "current": "1.2.0",
    "update_available": true,
    "update_required": false
  }
}
```

| Field | Type | Notes |
|---|---|---|
| `version` | string | Latest version, without the `v` prefix |
| `notes` | string \| null | Release notes (Markdown, as written on GitHub) |
| `download_url` | string \| null | Installer asset (`.msi`/`.exe`); `null` if the release has none — then open `release_url` |
| `sha256` | string \| null | SHA-256 of the installer as published by GitHub. The client must verify the downloaded file against it before running it. |
| `min_version` | string | Oldest version still supported |
| `current` | string \| null | Echo of `current`, normalized |
| `update_available` | bool \| null | `current < version`; `null` without `current` |
| `update_required` | bool \| null | `current < min_version`: the client must block use until updated; `null` without `current` |

Version comparison follows PHP's `version_compare`: plain numeric versions
(`1.4.0`) only, no pre-release suffixes.

**Errors:** 422 (invalid `current`), 429, **503** `update_info_unavailable`
(GitHub never answered since the cache was empty; `Retry-After: 60`). When
GitHub is down but a release was read before, that one keeps being served.

---

## 9. Errors

### 9.1 Shape

Every error is JSON with at least `message`. Some carry a machine-readable `code`:

```json
{ "message": "Sua conta está desativada. Entre em contato com o suporte.", "code": "account_inactive" }
```

Clients branch on **HTTP status + `code`** and show `message` to the user.
Never parse `message` text; it may change.

### 9.2 Validation (422)

```json
{
  "message": "O campo per page deve estar entre 1 e 50.",
  "errors": {
    "per_page": ["O campo per page deve estar entre 1 e 50."]
  }
}
```

### 9.3 Status reference

| Status | `code` | Meaning | Client action |
|---|---|---|---|
| 200 | — | OK | |
| 204 | — | Logout done | Clear token |
| 401 | — | `{"message": "Unauthenticated."}` — token missing, expired, revoked (logout, same device signed in again, pushed out by the 5-device limit) | Clear stored token, show login |
| 403 | `account_inactive` | Account deactivated. On authenticated routes the server also **revokes** the token used. | Clear token, show message |
| 403 | `email_not_verified` | Login only: e-mail not confirmed | Show message (link is in the user's inbox) |
| 403 | `access_required` | Download denied: needs a subscription or a purchase | Show message; offer `purchase_url` (if present) and the plans page |
| 403 | `quota_exceeded` | Download denied: monthly quota used up | Show message and `quota.resets_at` |
| 404 | — | Family not found / not published / file missing. The `message` is not guaranteed — rely on the status. | "Família indisponível" |
| 422 | — | Validation error or wrong credentials | Show `errors` |
| 429 | `too_many_attempts` (login only) or none | Rate limited | Wait `Retry-After` seconds |
| 503 | `update_info_unavailable` | Update information temporarily unavailable | Skip the update check silently |
| 5xx | — | Server error | Generic error, allow retry |

### 9.4 Error codes

| Code | Endpoint(s) | Status |
|---|---|---|
| `account_inactive` | login, auth/device/token, every authenticated route | 403 |
| `email_not_verified` | login | 403 |
| `too_many_attempts` | login | 429 |
| `access_required` | family detail (`download.code`), download | 403 |
| `quota_exceeded` | family detail (`download.code`), download | 403 |
| `update_info_unavailable` | plugin/latest | 503 |
| `authorization_pending` | auth/device/token | 400 |
| `slow_down` | auth/device/token | 429 |
| `access_denied` | auth/device/token | 403 |
| `expired_token` | auth/device/token | 410 |

---

## 10. Recommended client flow (Revit plugin)

0. **Update check:** `GET /plugin/latest?current={version}` (no token needed). `update_required` → block and offer the installer; `update_available` → offer it.
1. **Start:** if a token is stored and not past `expires_at`, call `GET /auth/me`.
   200 → update cached user/access. 401/403 → clear token, show login.
2. **Login:** offer both options with a stable `device_name` per installation — "Entrar com e-mail e senha" (`POST /auth/login`) and "Entrar pelo navegador" (section 3.4, needed for Google-only accounts). A "Criar conta" button opens the site's sign-up page. Store the token securely.
3. **Catalog:** load `/categories` and `/brands` once per session; list `/families` always sending `revit_version` = running Revit year.
4. **Detail:** `GET /families/{slug}` → enable "Download" when `download.allowed`, otherwise show `download.message` and, if present, a button opening `download.purchase_url` in the browser.
5. **Download:** `GET download.url` → save to temp → load into the project. Refresh `/auth/me` afterwards if the quota is shown.
6. **Logout:** `POST /auth/logout`, then delete the token locally regardless of the answer.

---

## 11. Security model

The API is public in the sense that anyone can reach its URL, like any website.
What protects it is **who** is calling, not **which program**:

- Only `POST /auth/login`, the browser sign-in (`/auth/device*`) and
  `GET /plugin/latest` work without a token. Login is rate limited (20/min per
  IP, 5 failed attempts per e-mail+IP, shared with the site); the browser
  sign-in issues a token only after the signed-in user approves the code on
  the site, and only once; the update check only returns public release data.
- Every other endpoint needs a valid token of an **active, verified** user.
  Tokens are stored hashed, expire, can be revoked and are limited to 5 devices.
- Access to paid content is decided on the server on every call
  (`FamilyPolicy::download`); the client never decides.
- The `.rfa` files live on a private disk and are only served through the
  download endpoint, which records every download.

There is deliberately **no "app key"** restricting the API to the plugin: any
secret shipped inside a desktop plugin can be extracted from the DLL, so it
would add no real protection. A client identification header (e.g. a
`User-Agent` with the plugin version) is useful for statistics and for asking
old versions to update, never for security.

The repository is public, so no secret is ever committed: `.env` is git-ignored
and `.env.example` lists the variables with empty values. Production must run
with `APP_ENV=production` and `APP_DEBUG=false` (debug mode puts stack traces in
JSON errors).

## 12. Versioning

- Breaking changes go to a new prefix (`/api/v2`); `/api/v1` keeps working until clients migrate.
- Adding new fields or new endpoints is **not** breaking: clients must ignore unknown fields.
