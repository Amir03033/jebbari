# Deploying jebbari.nl

One script does everything: `deploy.sh` in the project root.

- First install: `./deploy.sh --first you@example.com`
- Every update after that: `./deploy.sh`

The steps below assume a Plesk VPS with the domain `jebbari.nl`. Paths are Plesk's defaults; adjust them if yours differ.

---

## What the server needs

| | Needed | Where in Plesk |
|---|---|---|
| PHP | **8.4 or newer**, with `gd` (WebP), `intl`, `mbstring`, `pdo_mysql`, `fileinfo`, `zip`, `exif`, `curl`, OPcache | Websites & Domains > jebbari.nl > PHP Settings |
| PHP limits | `memory_limit` 512M, `upload_max_filesize` 12M, `post_max_size` 16M, `max_execution_time` 60 | same page |
| Database | MySQL 8 or MariaDB 10.6+, utf8mb4 | Databases > Add Database |
| Node.js | 20.19 or newer (only for building CSS/JS) | Extensions > Node.js |
| Composer | 2.x | Usually present; otherwise `curl -sS https://getcomposer.org/installer \| php` |
| SSH | A shell for the domain's system user | Websites & Domains > jebbari.nl > Hosting & DNS > Hosting settings > SSH access: `/bin/bash` |
| HTTPS | Let's Encrypt certificate | SSL/TLS Certificates |

On Plesk the command-line `php` is often an older version than the website's. Use the full path to PHP 8.4: `/opt/plesk/php/8.4/bin/php`. Node from the Plesk extension usually lives in `/opt/plesk/node/22/bin/`.

---

## First install (once)

### 1. Get the code onto the server

Do everything as the **site's system user**, never as root. Files that root creates in `storage/` cannot be written by the website afterwards, and the site breaks with "Permission denied". `deploy.sh` refuses to run as root.

- Find the user name in Plesk: Websites & Domains > jebbari.nl > Hosting & DNS > **Hosting settings** > System user (for example `jebbari_nl`).
- Give it a shell on the same page: SSH access: `/bin/bash` (not "chrooted": the chrooted shell has no PHP 8.4 or Node).
- Log in as that user: `ssh jebbari_nl@your-server`. Already logged in as root? Switch with `su - jebbari_nl -s /bin/bash`.

The user's home is `/var/www/vhosts/jebbari.nl`. Then:

```bash
cd /var/www/vhosts/jebbari.nl
git clone --branch claude/new-project-approach-7lh5u0 https://github.com/Amir03033/jebbari.git jebbari
cd jebbari
```

The repository is private, so GitHub will ask for a login. Use a fine-grained personal access token (read-only, this repository only) as the password, or add a deploy key. Plesk's **Git** extension can also do the clone; point it at `/var/www/vhosts/jebbari.nl/jebbari` with deploy mode "manual".

### 2. Point the website at `public/`

Websites & Domains > jebbari.nl > **Hosting & DNS > Hosting settings > Document root**: set it to `jebbari/public`. Save.

Only `public/` may be reachable from the web. Never point the document root at the project folder itself.

### 3. Create the database and `.env`

Create an empty MySQL database and user in Plesk. Then:

```bash
cp .env.example .env
nano .env
```

Change these lines (keep the rest as is):

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://jebbari.nl
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_user
DB_PASSWORD=your_password

SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=your.smtp.host
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="hallo@jebbari.nl"
MAIL_FROM_NAME="Jebbari"
```

Leave `APP_KEY=` empty: the script generates it.

### 4. Run the script

```bash
chmod +x deploy.sh
PHP=/opt/plesk/php/8.4/bin/php NPM=/opt/plesk/node/22/bin/npm ./deploy.sh --first you@example.com
```

It asks once for the admin password (at least 12 characters). It then:

1. checks PHP 8.4, the extensions, Composer, Node and `.env`
2. installs PHP packages (`composer install --no-dev`)
3. generates `APP_KEY`
4. builds CSS and JavaScript (`npm ci`, `npm run build`)
5. creates the tables (`migrate`)
6. fills the CMS with the starting content **as drafts**: pages, services, ServerScope, the proposed texts and the agreed About text (`/nl/over-mij`)
7. creates your owner account
8. links uploads (`storage:link`), publishes the admin assets, caches config, routes and views

If it stops, it tells you why in red (including when you run it as root). Fix that and run it again; it is safe to repeat.

**Right after this: back up `.env`** somewhere safe (password manager). Without `APP_KEY` nobody can pass the two-factor login and the admin is locked.

### 5. Queue worker (image processing)

Uploaded images are resized in the background by `scripts/queue-worker.php`. It processes what is waiting and stops; a scheduled task starts it every minute.

Create the task **under the domain**, so it runs as the site's system user and not as root: Websites & Domains > jebbari.nl > **Scheduled Tasks** > Add Task.

| Field | Value |
|---|---|
| Task type | **Run a PHP script** |
| Script path | `/var/www/vhosts/jebbari.nl/jebbari/scripts/queue-worker.php` (Plesk may show the start of the path for you; then type `jebbari/scripts/queue-worker.php`) |
| Use PHP version | **8.4** |
| Arguments | leave empty |
| Run | Cron style `* * * * *` (every minute) |
| Notify | Errors only |

Click **Run Now** once: the output should end with `Worker STOPPED Queue empty`.

Do not create it under Tools & Settings > Scheduled Tasks: those run as root.

Optional, monthly: a "Run a command" task under the same domain with `cd ~/jebbari && /opt/plesk/php/8.4/bin/php artisan activitylog:clean` (applies the 24-month audit retention).

### 6. Log in and publish

1. Open **https://jebbari.nl/admin**, log in, and set up the authenticator app. Store the recovery codes safely.
2. **Pages**: review each draft and click Publish. Start with Home, Over mij/About, Work, Services, Blog, Contact. Pages that still contain `[[ OWNER: ... ]]` cannot be published until you fill those in (the privacy statement and terms, for example).
3. **Site settings**: contact e-mail, booking link, company name, KvK number, social links (add LinkedIn and GitHub here to show them in the footer).
4. **Projects**: publish ServerScope and add the others.

Until a page is published, its URL returns 404. That is by design: nothing goes live by accident.

---

## Every update

As the site user (not root):

```bash
cd /var/www/vhosts/jebbari.nl/jebbari
PHP=/opt/plesk/php/8.4/bin/php NPM=/opt/plesk/node/22/bin/npm ./deploy.sh
```

It puts the site in maintenance mode (visitors see a calm 503 page that refreshes itself), pulls the latest code, installs packages, rebuilds assets, runs new migrations, refreshes caches and brings the site back. It does **not** touch your content. If a step fails, the site stays in maintenance mode and the script says so; fix the cause and run it again, or bring the site back with `php artisan up`.

Tip: add an alias so you only type `deploy`:

```bash
echo "alias deploy='cd /var/www/vhosts/jebbari.nl/jebbari && PHP=/opt/plesk/php/8.4/bin/php NPM=/opt/plesk/node/22/bin/npm ./deploy.sh'" >> ~/.bashrc
```

---

## Check after going live

```bash
curl -sI https://jebbari.nl/ | head -3                                     # 302 to /nl or /en
curl -s https://jebbari.nl/nl/over-mij | grep -o "Amir Jebbari is de oprichter[^<]*"
curl -s https://jebbari.nl/robots.txt | head -5
curl -s https://jebbari.nl/llms.txt
```

Then follow `docs/ai-check.md`: Google Search Console and Bing Webmaster Tools (submit `https://jebbari.nl/sitemap.xml`, request indexing of `/nl/over-mij` and `/en/about`), the Rich Results Test, and check that Cloudflare or Plesk does not block AI bots.

---

## What to expect from jebbari.nl now

**Design**: the premium redesign with the cobalt (blue) accent, Geist type, light theme by default and a dark theme the visitor can switch on. A command menu (Ctrl K), page transitions and quiet motion that switches off for visitors who prefer reduced motion.

**Pages (Dutch and English)**: Home, Werk/Work with case studies, Diensten/Services with packages, Over mij/About, Blog, Contact (form stored in the admin inbox and e-mailed to you), FAQ, Now, Uses, CV, privacy and terms. Each appears once you publish it.

**About Amir**: `/nl/over-mij` and `/en/about` open with "Amir Jebbari" as the heading and the sentence "Amir Jebbari is de oprichter van Jebbari, gevestigd in Amsterdam en Zwolle." Sections: who I am, what I do, projects (ServerScope, barbercuts.nl, amirs.life), how I work, contact. No education and no Iran Digital Lifeline anywhere. The old `/nl/over-ons` redirects there.

**Address**: the footer and the search engine data show one address: Professor W.H. Keesomlaan 12, 1183 DJ Amstelveen, The Netherlands. No VAT number anywhere.

**For search engines and AI**: every page carries structured data linking Jebbari, the website and Amir Jebbari (founder, with LinkedIn and amirs.life as his profiles). Blog posts are credited to Amir. `robots.txt` explicitly welcomes Google, Bing, ChatGPT, Claude, Perplexity and Apple crawlers and keeps `/admin` out. `/llms.txt` gives AI assistants a short factual summary and the list of live pages. `/sitemap.xml` lists every published page in both languages.

**Admin (`/admin`)**: Filament CMS with two-factor login. You edit pages with blocks, projects, services, posts, testimonials and media in Dutch and English side by side. Every change is a draft until you publish; you can preview drafts. Inbox for contact form messages, audit log, users and roles, and a small cookieless page-view counter. No cookie banner is needed: there is no tracking.

**Privacy and speed**: no third-party requests, no tracking cookies, strict security headers, about 28 KB of JavaScript (gzipped).

**Results with AI assistants**: assistants with web search (Perplexity, ChatGPT search, Gemini) can pick up the new information within weeks of indexing. Models without web search only change after their next training round, which can take months. Re-run the questions in `docs/ai-check.md` every four weeks.

**Still needs you**: publish the pages, fill the `[[ OWNER: ... ]]` fields (privacy, terms), contact e-mail, KvK number, a GitHub URL, real project screenshots, three article topics, and the texts for LinkedIn, GitHub and your other sites (`docs/AI-VISIBILITY.md`, `docs/TODO-AMIR.md`).

---

## Fixing "Permission denied" (if something was run as root)

From root, give the files back to the site user (replace `jebbari_nl` with yours; `psacln` is Plesk's group for site users):

```bash
chown -R jebbari_nl:psacln /var/www/vhosts/jebbari.nl/jebbari
```

Then run `./deploy.sh` again as the site user.
