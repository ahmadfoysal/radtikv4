# Telegram Voucher Reset - Implementation Plan

## Overview

Allow admins and resellers to reset a voucher (remove active session, cookie, and RADIUS MAC binding) directly from Telegram, without logging into RADTik. This is a new entry point onto the existing `VoucherService::resetVoucher()` logic — no changes to MikroTik/RADIUS reset behavior itself.

## Scope

-   **Who can use it:** Admins (their own routers) and Resellers (routers assigned to them, gated by the existing `reset_voucher` permission). End customers/voucher holders are **out of scope**.
-   **Identity linking:** A RADTik user links their Telegram account by generating a one-time code from the RADTik UI and sending it to the bot. No self-service linking without an authenticated RADTik session.
-   **Authorization:** Fully reuses `User::getAuthorizedRouter()` (via `VoucherService`) and Spatie's `reset_voucher` permission. No new authorization rules are introduced.

## Current System State

-   `App\Services\VoucherService::resetVoucher(User $user, int $voucherId)` (`app/Services/VoucherService.php`) already:
    -   Loads the voucher, verifies router access via `$user->getAuthorizedRouter()`.
    -   Calls `HotspotUserManager::resetVoucher()` (MikroTik session/cookie cleanup).
    -   Calls `RadiusApiService::resetVoucherMacBinding()` (RADIUS MAC unbind).
    -   Logs the action via `VoucherLogger::log()`.
-   Permission check (`$this->authorize('reset_voucher')`) currently lives only in `App\Livewire\Voucher\Index::resetVoucher()`, **not** inside the service.
-   No Telegram integration exists anywhere in the codebase today (no package, no config, no webhook route).
-   Existing webhook precedent to follow: `App\Http\Controllers\Api\DeployController` (GitHub webhook, HMAC signature verification pattern).

## Architecture

```
Telegram user ──/reset CODE123──▶ Telegram Bot API ──▶ POST /api/telegram/webhook
                                                              │
                                                    verify secret token header
                                                              │
                                                  TelegramWebhookController
                                                              │
                                         resolve User by telegram_chat_id
                                                              │
                                     check $user->can('reset_voucher')
                                                              │
                                     find Voucher by username (scoped)
                                                              │
                          send inline keyboard confirmation (Confirm/Cancel)
                                                              │
                                on confirm ──▶ VoucherService::resetVoucher()
                                                              │
                                          reply with result via sendMessage
```

## Implementation Components

### 1. Database

**Migration:** add nullable, unique `telegram_chat_id` to `users` table.

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('telegram_chat_id')->nullable()->unique()->after('email');
});
```

No new table needed for the link itself — one chat per user, one user per chat. The one-time linking **code** is transient and belongs in cache (`Cache::put`), not the database.

### 2. Config

`config/services.php` — add a `telegram` block:

```php
'telegram' => [
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
],
```

`.env` additions: `TELEGRAM_BOT_TOKEN`, `TELEGRAM_WEBHOOK_SECRET` (random string, set via `setWebhook` `secret_token` param).

### 3. Telegram Bot Service

**File:** `app/Services/TelegramBotService.php` (same style as `RadiusApiService` — thin HTTP wrapper).

Responsibilities:

-   `sendMessage(string $chatId, string $text, ?array $replyMarkup = null)`
-   `answerCallbackQuery(string $callbackQueryId, string $text)`
-   `editMessageText(string $chatId, int $messageId, string $text, ?array $replyMarkup = null)`
-   Helper to build the inline keyboard for reset confirmation (`Confirm` / `Cancel` callback buttons).

### 4. Webhook Route & Controller

**Route** (`routes/web.php` or new `routes/telegram.php`):

```php
Route::post('/api/telegram/webhook', [TelegramWebhookController::class, 'handle'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->middleware('throttle:30,1');
```

**Controller:** `app/Http/Controllers/Api/TelegramWebhookController.php`

-   Verify `X-Telegram-Bot-Api-Secret-Token` header against `config('services.telegram.webhook_secret')` before processing anything (mirror `DeployController::verifySignature`).
-   Parse the Telegram `Update` payload (`message` or `callback_query`).
-   Command handling:
    -   `/start <code>` → resolve linking code from cache → attach `telegram_chat_id` to the matching user → reply confirmation. Invalidate code after use.
    -   `/reset <voucher_username>` → resolve `$user` via `User::where('telegram_chat_id', $chatId)->first()`. If none, reply "Not linked" with instructions. If found, check `$user->can('reset_voucher')` (mirrors the Livewire `authorize()` call); if denied, reply "Not authorized". Otherwise look up the voucher by username and send a confirmation prompt (inline keyboard) instead of resetting immediately.
    -   `callback_query` (`reset_confirm:{voucher_id}` / `reset_cancel`) → on confirm, call `VoucherService::resetVoucher($user, $voucherId)` and reply with the result message/actions; on cancel, edit the message to say cancelled.
-   Keep the controller thin — delegate message formatting to `TelegramBotService`, and reset logic entirely to the existing `VoucherService`.

### 5. Permission Check Consolidation (recommended, small refactor)

Move the `reset_voucher` authorization check from `Livewire\Voucher\Index::resetVoucher()` into `VoucherService::resetVoucher()` itself (e.g. throw an `AuthorizationException` or return a `success: false` result if `!$user->can('reset_voucher')`). This way both the web UI and the Telegram controller get consistent enforcement from a single place, instead of duplicating the check in the webhook controller.

### 6. Account Linking UI

**File:** `app/Livewire/Settings/TelegramLink.php` + `resources/views/livewire/settings/telegram-link.blade.php`

-   Shows current link status (linked chat / not linked).
-   "Generate Link Code" button → creates a 6–8 char alphanumeric code, `Cache::put("telegram_link:{$code}", $user->id, now()->addMinutes(10))`.
-   Displays the code plus a deep link/QR: `https://t.me/<BotUsername>?start=<code>`.
-   "Unlink" button clears `telegram_chat_id` on the user.
-   Add a nav entry under user settings/profile.

### 7. Audit Logging

Extend the `meta`/context passed to `VoucherLogger::log()` in `VoucherService::resetVoucher()` with a `channel` field (`web` vs `telegram`), so reset history can distinguish where the action originated. `reset_by` already captures the acting user, which is correct for both channels since the Telegram action is tied to a linked RADTik user.

### 8. Telegram App Setup (one-time, manual)

1. Create bot via `@BotFather`, obtain token.
2. Set commands list (`/setcommands`): `reset - Reset a voucher`, `start - Link your account`.
3. Register webhook: `POST https://api.telegram.org/bot<token>/setWebhook` with `url` and `secret_token`.
4. Confirm with `getWebhookInfo` that Telegram can reach the endpoint (requires HTTPS/public domain — already true for production).

## Security Considerations

-   Webhook route must verify `X-Telegram-Bot-Api-Secret-Token` on every request; reject otherwise.
-   Rate-limit the webhook route (`throttle` middleware) to blunt abuse/spam.
-   Linking codes are single-use, short-lived (10 min), and can only be generated from an authenticated RADTik session — prevents an attacker from linking their own Telegram to someone else's account.
-   `telegram_chat_id` is not a secret, but keep it unique to prevent hijacking another user's link.
-   Never log the bot token; keep it in `.env` only.
-   Reset action requires an explicit "Confirm" tap (inline keyboard) before executing, mirroring the existing `confirm()` dialog in the web UI, to avoid accidental resets from a stray message.
-   All router-scoping/authorization still flows through `getAuthorizedRouter()` — no new bypass path is introduced.

## Testing Plan

-   **Unit:** `VoucherService::resetVoucher()` permission-check behavior (once moved into the service).
-   **Feature:** `TelegramWebhookController` — valid/invalid secret token, `/start` linking flow (valid code, expired code, already-used code), `/reset` flow for authorized/unauthorized users, reseller scoped to assigned router only, admin scoped to own router only, confirm/cancel callback handling.
-   **Manual:** End-to-end against a real Telegram test bot and a staging router.

## Rollout Steps

1. Migration + config + `.env` entries.
2. `TelegramBotService` (send/receive helpers).
3. Move permission check into `VoucherService` (small refactor, covered by existing tests + new ones).
4. `TelegramWebhookController` + route.
5. Account linking Livewire component + UI entry point.
6. Create bot with BotFather, register webhook on staging, test linking + reset end-to-end.
7. Update `docs/PROJECT_DOCUMENTATION.md` and `TODO.md` once shipped.
8. Register webhook on production, announce to admins/resellers.

## Open Questions / Future Ideas

-   Should resellers without the `reset_voucher` permission be told *why* they were denied, or just get a generic "not authorized"? (Current web UI just hides the button; Telegram has no such hiding, so a clear denial message is needed.)
-   Should the bot support listing a user's recent/active vouchers (`/vouchers`) to avoid needing to remember exact usernames?
-   Consider extending the same bot later for other admin actions (e.g. router status alerts) once the webhook/service scaffolding exists.
