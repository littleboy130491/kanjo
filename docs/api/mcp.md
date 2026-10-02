# Kanjo MCP Server — Setup and Connecting AI Clients

Kanjo exposes an MCP (Model Context Protocol) server at `{origin}/mcp`, for example `https://portal.imajiner.id/mcp`. Any Kanjo user with admin panel access can connect Claude, ChatGPT, or another MCP client to it. From the chat they can then look up clients and documents, and create or update proposals, invoices, and SPKs.

The server wraps the Remote Document API (`docs/api/agent-guide.md`). The agent reads that guide through the `read_guide` tool. Sign-in uses OAuth: the AI client opens Kanjo's login page, you sign in with your normal admin account, and you approve the connection.

---

## 1. Server setup (once per environment)

1. Install and migrate (adds the `oauth_*` tables):

   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   npm ci && npm run build   # consent screen styles
   ```

2. Create the OAuth signing keys, **once**. Regenerating them disconnects every connected client.

   ```bash
   php artisan passport:keys
   ```

   This writes `storage/oauth-private.key` (mode 600) and `storage/oauth-public.key` (mode 660). Both are gitignored. You can instead put the PEM contents in `PASSPORT_PRIVATE_KEY` / `PASSPORT_PUBLIC_KEY` in `.env`.

   If `passport:keys` crashes (some static PHP builds cannot generate RSA keys), use OpenSSL:

   ```bash
   openssl genrsa -out storage/oauth-private.key 4096
   openssl rsa -in storage/oauth-private.key -pubout -out storage/oauth-public.key
   chmod 600 storage/oauth-private.key && chmod 660 storage/oauth-public.key
   ```

3. Check `.env`:

   | Variable | Notes |
   |---|---|
   | `APP_URL` | Must be the public **https** URL. OAuth metadata URLs are built from it. Claude and ChatGPT only connect over HTTPS. |
   | `MCP_REDIRECT_DOMAINS` | Comma-separated callback origins allowed to register. Default `https://claude.ai,https://claude.com,https://chatgpt.com,http://localhost`. Add an origin if another client fails with `invalid_redirect_uri`. `*` allows any. |

4. **RunCloud only: allow OAuth discovery through nginx.** RunCloud's default nginx config denies every dot-path (`location ~ /\. { deny all; }`), so `/.well-known/oauth-*` returns 403. Claude then reports *"Couldn't register with … sign-in service"*. In RunCloud go to **Web Application → NGINX Config → Add a New Config**, choose *I want to write my own config*, set **Type** to `location.main-before`, name it `mcp-oauth-discovery`, and paste:

   ```nginx
   # MCP OAuth discovery: let /.well-known/oauth-* reach Laravel (served at /oauth-discovery/*).
   location ^~ /.well-known/oauth- {
       rewrite ^/\.well-known/(oauth-authorization-server|oauth-protected-resource)(/.*)?$ /oauth-discovery/$1$2 last;
       return 404;
   }
   ```

   Click **Run and Debug**, then save. `^~` wins over the dot-file regex. The rewrite turns the request into a normal path, which nginx hands to PHP like any other route, on both the hybrid and native stacks. Verify with:

   ```bash
   curl -s https://{host}/.well-known/oauth-authorization-server   # JSON, not 403
   ```

5. If you cache routes or config in production, re-run `php artisan optimize` after deploying.

6. Optional: purge expired tokens now and then with `php artisan passport:purge`.

Smoke test: `curl -i -X POST https://{host}/mcp` should return `401` with a `WWW-Authenticate: Bearer ... resource_metadata=...` header.

---

## 2. Connect an AI client

Server URL for every client: `https://{host}/mcp`

### Claude (claude.ai, Claude Desktop, Claude mobile)

1. **Settings → Connectors → Add custom connector.** On Team/Enterprise plans an owner adds it under organization settings first.
2. Name: `Kanjo`. URL: `https://{host}/mcp`. Leave the advanced OAuth fields empty, because Kanjo registers the client automatically.
3. Click **Connect**. A Kanjo window opens. Sign in to the admin panel if asked, then click **Authorize**.
4. In a chat, enable the Kanjo connector from the tools menu and ask, for example: *"Create a proposal from Imajiner for PT Contoh (Budi, budi@contoh.test), Business Package 25jt, renewal 3jt, default content."*

### ChatGPT

1. **Settings → Apps & Connectors → Advanced settings**, and turn on **Developer mode** (needs a plan that supports custom connectors).
2. **Create** a connector: name `Kanjo`, MCP server URL `https://{host}/mcp`, authentication **OAuth**.
3. Sign in to Kanjo and click **Authorize** when prompted.
4. In a chat, choose Developer mode and select the Kanjo connector.

### Claude Code

```bash
claude mcp add --transport http kanjo https://{host}/mcp
```

Then run `/mcp` inside Claude Code, choose `kanjo`, and authenticate in the browser.

### Cursor, VS Code, other clients

Use the server URL with OAuth. Clients that use a custom-scheme callback (for example `cursor://`) also need that scheme added to `custom_schemes` in `config/mcp.php`.

### Local testing

```bash
php artisan mcp:inspector mcp
```

The inspector walks through the same OAuth flow against your local `APP_URL`.

---

## 3. What the assistant can do

| Tool | Purpose |
|---|---|
| `read_guide` | Operating manual. The assistant should call it once per chat. |
| `search_records` | List/search companies, clients, proposals, invoices, SPKs, services |
| `get_record` | One record plus its related documents |
| `get_content_defaults` | Proposal content-default packs / SPK defaults |
| `get_skeleton` | Create template for a proposal, invoice, or SPK |
| `create_proposal`, `create_invoice`, `create_spk` | Create published documents (dry-run first) |
| `create_invoice_from_proposal`, `create_spk_from_proposal` | Create from an existing proposal |
| `update_record` | Partial update of any record (dry-run first) |

Rules:

- Documents are **published immediately** and authored by the user who connected.
- Creates and updates follow the same role permissions as the admin panel. For example, an Editor cannot update companies.
- Nothing can be deleted through MCP. Use the admin panel.

---

## 4. Managing access

- Each authorization creates an OAuth client (`oauth_clients`) and tokens (`oauth_access_tokens`) for that user. To disconnect a client, remove the connector in the AI app. To force it, revoke the user's tokens (`oauth_access_tokens.revoked = 1`).
- Removing a user's panel role blocks MCP calls right away (403), even with a valid token.
