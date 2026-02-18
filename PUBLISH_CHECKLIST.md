# Publishing radtik-radius Public Repository - Checklist

## ✅ Completed Updates

All code has been updated to use your public repository:
- ✅ Repository URL: `https://github.com/ahmadfoysal/radtik-radius.git`
- ✅ Bootstrap installer updated
- ✅ Laravel configuration updated
- ✅ SSH service updated
- ✅ Documentation updated
- ✅ README.md updated with one-line install

## 📋 Next Steps to Publish

### 1. Prepare the Public Repository

The `radtik-radius` folder in your private Laravel project needs to be copied to the public repository.

**Files to copy to public repo:**
```
radtik-radius/
├── bootstrap-install.sh    ← NEW - One-line installer
├── install.sh
├── validate.sh
├── README.md
├── QUICKSTART.md
├── API_QUICKSTART.md
├── CHANGELOG.md
├── VERSION
├── LICENSE
├── requirements.txt
├── clients.conf
├── radtik-radius-api.service
├── scripts/
├── mods-available/
├── mods-config/
├── sites-enabled/
└── sqlite/
```

### 2. Copy Files to Public Repository

**Option A: Manual Copy** (Simplest)
```bash
# On your local machine
cd K:\Laravel\radtikv4
cp -r radtik-radius/ C:\path\to\radtik-radius-public\

# Or on Linux/Mac:
cd ~/radtikv4
cp -r radtik-radius/ ~/radtik-radius-public/
```

**Option B: Git Subtree** (Keeps history)
```bash
# In your private radtikv4 repository
git subtree push --prefix=radtik-radius \
  https://github.com/ahmadfoysal/radtik-radius.git main
```

### 3. Verify Public Repository

✅ Check that `bootstrap-install.sh` is accessible:
```bash
curl -fsSL https://raw.githubusercontent.com/ahmadfoysal/radtik-radius/main/bootstrap-install.sh
```

✅ Should return the script content (not 404)

### 4. Test Installation

Test on a clean Ubuntu 22.04 server:

```bash
# SSH to test server
ssh root@your-test-server

# Run one-line installer
curl -fsSL https://raw.githubusercontent.com/ahmadfoysal/radtik-radius/main/bootstrap-install.sh | sudo bash

# Verify services
sudo systemctl status freeradius
sudo systemctl status radtik-radius-api

# Test API
curl http://localhost:5000/health
```

### 5. Update Laravel .env

In your private Laravel project's `.env`:

```ini
RADTIK_REPO_URL=https://github.com/ahmadfoysal/radtik-radius.git
RADTIK_BRANCH=main
```

### 6. Test Laravel Admin Panel Installation

1. Add a RADIUS server in Laravel admin
2. Enter server IP and SSH credentials
3. Click "Install RADIUS Server" button
4. Wait 5-10 minutes
5. Refresh and verify services are active

## 📝 Files Updated in Private Repo

These files in your private Laravel project now reference the public repo:

- ✅ `radtik-radius/bootstrap-install.sh` - Uses public repo
- ✅ `radtik-radius/README.md` - Shows one-line install
- ✅ `radtik-radius/QUICKSTART.md` - Updated with public URL
- ✅ `config/app.php` - Default repo URL set
- ✅ `.env.example` - Example configuration added
- ✅ `app/Services/RadiusServerSshService.php` - Uses public repo
- ✅ `app/Livewire/Radius/Show.php` - Calls SSH service
- ✅ `resources/views/livewire/radius/show.blade.php` - Install buttons added
- ✅ `docs/RADIUS_AUTOMATED_INSTALLATION.md` - Complete guide

## 🔒 Security Notes

**In Public Repository:** ✅ SAFE
- Installation scripts
- Configuration templates
- Documentation
- Default configurations (no secrets)

**Keep in Private Repository:** ⚠️ DO NOT PUBLISH
- `.env` file (contains secrets)
- Laravel application code
- Database with user data
- SSH credentials
- API tokens
- Any custom modifications with sensitive data

## 🎯 One-Line Installation Command

After publishing, users can install with:

```bash
curl -fsSL https://raw.githubusercontent.com/ahmadfoysal/radtik-radius/main/bootstrap-install.sh | sudo bash
```

Or from Laravel admin panel by clicking **"Install RADIUS Server"** button!

## 📚 Documentation Links

After publishing, update these in your main project README:

- Public Repo: https://github.com/ahmadfoysal/radtik-radius
- Installation Guide: https://github.com/ahmadfoysal/radtik-radius#-quick-installation
- Issue Tracker: https://github.com/ahmadfoysal/radtik-radius/issues

## ✨ Ready to Publish!

Once you copy the `radtik-radius` folder to your public repository and push, the one-line installation will work immediately.
