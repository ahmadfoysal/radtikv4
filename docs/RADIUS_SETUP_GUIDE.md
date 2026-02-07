# FreeRADIUS + SQLite Setup Guide for RadTik (Ubuntu 22.04)

This guide installs and configures **FreeRADIUS** with **SQLite** backend for use with **RadTik**.

It includes:

* FreeRADIUS installation
* SQLite database setup
* SQL module configuration
* Client configuration
* Disable radpostauth logging
* Disable accounting (optional)
* Test authentication

---

## Requirements

* Ubuntu 22.04 server
* Root or sudo access

---

# 1️⃣ Install FreeRADIUS + Tools

```bash
sudo apt update
sudo apt install freeradius freeradius-utils sqlite3
```

Verify installation:

```bash
freeradius -v
```

---

# 2️⃣ Create SQLite Database

Create database directory:

```bash
sudo mkdir -p /etc/freeradius/3.0/sqlite
```

Create DB:

```bash
sudo sqlite3 /etc/freeradius/3.0/sqlite/radius.db
.quit
```

---

# 3️⃣ Import FreeRADIUS SQLite Schema

```bash
sudo sqlite3 /etc/freeradius/3.0/sqlite/radius.db \
< /etc/freeradius/3.0/mods-config/sql/main/sqlite/schema.sql
```

---

# 4️⃣ Fix Database Permissions

```bash
sudo chown freerad:freerad /etc/freeradius/3.0/sqlite/radius.db
sudo chmod 664 /etc/freeradius/3.0/sqlite/radius.db
```

---

# 5️⃣ Configure SQL Module

Open SQL config:

```bash
sudo nano /etc/freeradius/3.0/mods-available/sql
```

Set:

```
driver = rlm_sql_sqlite
dialect = sqlite
```

Find sqlite block:

```
sqlite {
    filename = /etc/freeradius/3.0/sqlite/radius.db
}
```

Save & exit.

---

# 6️⃣ Enable SQL Module

```bash
sudo ln -s /etc/freeradius/3.0/mods-available/sql \
/etc/freeradius/3.0/mods-enabled/sql
```

---

# 7️⃣ Allow RadTik / Clients

Edit:

```bash
sudo nano /etc/freeradius/3.0/clients.conf
```

Add:

```
client radtik {
    ipaddr = 0.0.0.0/0
    secret = testing123
    require_message_authenticator = no
}
```

> ⚠ Replace secret in production.

---

# 8️⃣ Disable radpostauth Logging (Important)

Edit:

```bash
sudo nano /etc/freeradius/3.0/sites-enabled/default
```

Remove/comment `sql` inside:

```
post-auth { }
Post-Auth-Type REJECT { }
Post-Auth-Type Challenge { }
```

This prevents SQLite lock errors.

---

# 9️⃣ Disable Accounting (Optional)

Inside same file:

```
accounting { }
```

---

# 🔟 Restart FreeRADIUS

```bash
sudo systemctl restart freeradius
```

Check status:

```bash
sudo systemctl status freeradius
```

---

# 1️⃣1️⃣ Add Test User

```bash
sudo sqlite3 /etc/freeradius/3.0/sqlite/radius.db \
"INSERT INTO radcheck (username, attribute, op, value)
VALUES ('testuser','Cleartext-Password',':=','testpass');"
```

---

# 1️⃣2️⃣ Test Authentication

Run:

```bash
radtest testuser testpass 127.0.0.1 0 testing123
```

Expected result:

```
Access-Accept
```

---

# Debug Mode (Optional)

```bash
sudo freeradius -X
```

Shows live authentication logs.

---

# Notes for RadTik

* RadTik uses `radcheck` for user authentication
* SQLite is fine for small/medium deployments
* Disable radpostauth logging to avoid DB locks
* Use strong secrets in production

---

# Done ✅

FreeRADIUS is now configured for RadTik authentication.
