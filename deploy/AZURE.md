# ☁️ Deploy Kairo CORE on Microsoft Azure (Free via GitHub Education)

A complete beginner-friendly guide to hosting your multi-tenant school management
system on Microsoft Azure **for free** using the **Azure for Students** offer.

---

## What you get free

- **$100 in Azure credits** (valid 12 months from signup)
- **750 free VM hours/month** on low-tier burstable Linux VMs
- **No credit card required**
- Renews **annually while you're a student** (your GitHub Education is verified
  until **Aug 2028**)

> ⚠️ **Free-tier VM sizes are being retired/unavailable in some regions.**
> In **South Africa North**, the available free-tier sizes are **`B1s`** and
> **`B2ats_v2`** (Azure confirms these are "not charged for up to 750 hours").
> `B2pts_v2` / `B1ms` etc. may be unavailable — use `B2ats_v2` if offered, else `B1s`.

---

## Before you start

1. Your domain is **`kairocore.me`** (from the free Namecheap/Name.com student offer).
2. Your code is on GitHub: **`TinotendaHlatywayo/KairoCORE`**.
3. **SECURITY FIRST:** rotate your exposed GitHub token (steps below).

---

## Phase 0 — Rotate your GitHub token (security fix)

Your old token is stored in plaintext at `~/.git-credentials`. It's been exposed,
so let's replace it:

1. Open **https://github.com/settings/tokens** in your browser.
2. Find the existing token (or create a new one) with `repo` scope.
3. **Regenerate** it and copy the new token (starts with `ghp_`).
4. On your local machine, run the helper script to update all references:

```bash
bash deploy/rotate-gh-token.sh ghp_YOUR_NEW_TOKEN_HERE
```

5. **Delete the old token** from GitHub settings so the leaked one stops working.

---

## Phase 1 — Claim Azure for Students

1. Go to **https://portal.azure.com**.
2. Sign in with your **Microsoft / school account** (the one linked to GitHub Education).
3. Azure will detect your student status and create the **Azure for Students** subscription.
4. Confirm you see **$100 credit** and the list of **free services**.
5. Note: **no credit card is required**.

---

## Phase 2 — Create the free Ubuntu VM

1. Azure Portal → **Virtual machines** → **+ Create** → **Azure virtual machine**.
2. Fill in (on the **Basics** tab):
   - **Subscription**: Your Azure for Students subscription
   - **Resource group**: Create new → `kairocore-rg`
   - **Virtual machine name**: `kairocore-vm`
   - **Region**: Pick one near you (e.g. South Africa North if you're in ZA, else East US / West Europe)
   - **Image**: **Ubuntu Server 24.04 LTS x64 - Gen2** (if unavailable in your region,
     **22.04 LTS** works identically with the setup script — it installs PHP 8.3 via a PPA)
   - **Size**: Choose a **free burstable tier**. Use the **"See all sizes"** link and type `B` to filter. Preferred: `Standard_B2ats_v2` (available in South Africa North). Fallback: `Standard_B1s`.
   - **Authentication type**: *SSH public key* (recommended) or *Password* — write it down if password.
   - **Inbound ports**: select **SSH (22)**, **HTTP (80)**, **HTTPS (443)**.
3. **Networking tab**: leave defaults (Azure creates the NSG with your inbound ports).
4. **Disks tab**: keep **Standard SSD** (do NOT choose premium — it consumes credit).
5. **Management tab**: enable **Auto-shutdown** and set an off time to save credit.
6. **DO NOT** select an Availability Zone (it hides free VM sizes).
7. Click **Review + create** → **Create**.
8. Wait a minute, then open the VM and note its **public IP address**.

> 💡 If the size you want is greyed out with "not available in this region",
> switch the **Region** and retry — free tiers vary by region.

---

## Phase 3 — Point `kairocore.me` at the VM (DNS)

At your domain registrar (Namecheap/Name.com where you got `kairocore.me`):

1. Log in to the registrar dashboard.
2. Open **Advanced DNS** / **Advanced DNS Records** for `kairocore.me`.
3. Delete any default placeholder records.
4. Add these A records:

| Type | Host | Value |
|---|---|---|
| **A** | `@` | `YOUR_AZURE_PUBLIC_IP` |
| **A** | `*` | `YOUR_AZURE_PUBLIC_IP` |

> ⚠️ The **`*` wildcard record is essential** — it routes every school's
> subdomain (e.g. `chiwariraprimary.kairocore.me`) to your VM for multi-tenancy.

5. Wait **10–30 minutes** for DNS to spread. Verify:

```bash
dig +short kairocore.me
dig +short test.kairocore.me
```

Both should print your VM's public IP.

---

## Phase 4 — Deploy Kairo CORE

1. Connect to your VM over SSH (from your local computer).

```bash
ssh azureuser@YOUR_AZURE_PUBLIC_IP
```

   > Replace `azureuser` with the username you chose when creating the VM.

2. Run the automated setup script (replace `kairocore.me` with your domain):

```bash
curl -sL https://raw.githubusercontent.com/TinotendaHlatywayo/KairoCORE/main/deploy/setup-azure-vm.sh | bash -s -- kairocore.me
```

   The script automatically:
   - Adds a **4GB swap file** (essential for the small free-tier VM)
   - Installs **PHP 8.3**, **MariaDB** (light), **Nginx**, **Composer**, **Node.js**
   - Tunes MariaDB & PHP-FPM for low memory
   - Creates the database + secure random password
   - Clones `KairoCORE`, installs deps, builds frontend, runs migrations, caches
   - Configures Nginx for wildcard subdomains
   - Saves your DB credentials to `.env.production.local`

3. Keep the printed **database credentials** safe.

---

## Phase 5 — Enable free SSL

```bash
# still SSH'd into the VM
certbot --nginx -d kairocore.me -d *.kairocore.me
```

- Enter your email, accept the terms.
- SSL **auto-renews forever** and is **free**.

---

## Phase 6 — First tenant + verify

1. Visit **https://kairocore.me** — the Kairo CORE marketing site.
2. Log in to the admin panel: **https://kairocore.me/platform** with your super admin account.
3. On-board your first school, e.g. **Chiwarira Primary**.
4. Test a school subdomain: **https://chiwariraprimary.kairocore.me/workspace**.

---

## Money-saving tips (so $100 lasts)

- Leave **Auto-shutdown** on — the VM only runs during school hours.
- The `B-series` free hours (750/mo) easily cover one always-on 1GB VM or
  a few hours/day on a bigger one.
- Choose the **smallest size** that runs Filament acceptably (start with `B1ms`).
- Monitor usage: Azure Portal → **Cost management**.

---

## Cost summary

- **Phase 0–6**: covered by the free tier + $100 credit → **$0 out of pocket**.
- **Monthly recurring**: $0 while within the 750 free hours (one free B-series VM).
- **Renewal**: the Azure for Students offer renews each year while you're a student.

---

## Troubleshooting

| Problem | Fix |
|---|---|
| `git clone` fails/first load slow | Ensure DNS `@` **and** `*` both point to VM IP, then wait 10–30 min |
| VM size "not available" | Choose a free size available in your region (`B2ats_v2` first, else `B1s`) or change the Region |
| Site slow on 1GB RAM | Confirm the **4GB swap** was added (script step 2) and PHP-FPM set to `ondemand` |
| 502 Bad Gateway | `sudo systemctl restart php8.3-fpm && sudo systemctl reload nginx` |
| Can't reach site at all | Verify ports 80/443 open in the VM's NSG; check `curl -I http://localhost` on the VM |

---

## What's automated vs manual

| Step | Automatic | Manual (you) |
|---|---|---|
| Token rotation | `rotate-gh-token.sh` | Regenerate token in GitHub UI |
| Azure account + VM | — | Portal (Phase 1–2) |
| DNS records | — | Registrar dashboard (Phase 3) |
| Server software + app | `setup-azure-vm.sh` | Run it (Phase 4) |
| SSL | — | `certbot` command (Phase 5) |
| First tenant | — | Admin panel (Phase 6) |
