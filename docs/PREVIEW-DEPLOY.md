# Preview deploy

Paste a client's GitHub repository URL in the admin, click Deploy, and the site is built on
**https://preview.jebbari.nl**. There is only ever one preview at a time. The client gets a link
to `https://jebbari.nl/review/<token>`. That page shows a "Preview, niet definitief" bar above
the site, with two buttons: **Goedkeuren** (approve) and **Wijzigingen aanvragen** (request
changes). Either click sends you an e-mail.

## How it works

| Part | Where |
|---|---|
| Admin: create, deploy, log, clear | `/admin/previews` (owner only), `app/Filament/Resources/Previews` |
| Model and migration | `app/Models/Preview.php`, `previews` table |
| Build script | `scripts/preview-deploy.sh` (`deploy` or `clear`) |
| Queue jobs | `app/Jobs/DeployPreview.php`, `app/Jobs/ClearPreview.php`, run on the `previews` queue |
| Running the script | `app/Support/PreviewServer.php` |
| Client page | `app/Http/Controllers/PreviewReviewController.php`, `resources/views/pages/review.blade.php` |
| `.env` of the preview site | `resources/preview/env.template` |
| Settings | `config/preview.php` (all values come from Jebbari's `.env`) |

**Creating a preview deploys it right away.** After that it is deployed again by itself when its
branch gets a new commit (see *Automatic updates*), or by hand with the Deploy button. A deploy
does this:

1. Every other preview is marked *Gewist* (cleared), so its client link stops working. Each preview
   has its own access key, so their cookies stop opening the subdomain too. The key of the preview
   itself stays the same, so a client who is looking keeps access after an update.
2. The job is queued. The preview worker (below) starts it within a minute and writes the
   output to the log, which you can follow on the preview's page in the admin.

The script then:

1. clones the repository into a temporary folder next to `httpdocs`
2. runs `composer install --no-dev` (or with dev packages, if you ticked that box, for seeders
   that use Faker)
3. runs `npm ci && npm run build` if there is a `package.json`, then removes `node_modules`
4. empties `httpdocs` (it keeps `.htaccess` and `.well-known`) and moves the build in
5. writes `.env` from the template
6. adds the access check, the 403 page and the noindex headers to `public/.htaccess`, and writes a
   `robots.txt`
7. builds the database (see below) and runs `storage:link`

The old preview stays up until step 4.

### The database: emptied or kept

- **First deploy of a preview:** always `key:generate` and `migrate:fresh --seed`: an empty
  database with the project's seed data.
- **Redeploy of the same preview, with *Database behouden* on (default):** only `migrate` for new
  migrations. Content, accounts and uploads (`storage/app`) stay, and so does the app key, which
  encrypted values such as two-factor secrets need. Publish the pages once in the preview's own
  admin, and they survive every update.
- **Database behouden off,** or when another preview used the database in between, or after
  *Preview wissen*: emptied and seeded again.

Jebbari tracks which preview the database belongs to (`owns_database`). A fresh build that fails
leaves it without an owner, so the next deploy starts clean again. Keeping never mixes two
clients' data.

### Automatic updates

With *Automatisch bijwerken* on (default), the preview worker asks GitHub every minute for the
newest commit on the live preview's branch. If it differs from the last build, the preview is
deployed again, with its database kept. So after you push to the branch, the preview is up to
date within a few minutes. The client's approval is reset, because they have not seen the new
version yet. Nothing is set up per repository; it uses `PREVIEW_GITHUB_TOKEN` for private ones.

A commit whose build failed is not retried; the next push is, or click Deploy. Expired and cleared
previews are not updated.

### The 403 page

Strangers who open preview.jebbari.nl without the cookie get a page of its own
(`resources/preview/403.html`, copied to `public/__jebbari-403.html`), still with status 403: a
closed door with someone peeking back through the keyhole, and a short note that the site is
still being built. It is self-contained (no files from the site, which are behind the same check)
and follows the light or dark setting of the visitor.

**Preview wissen** (clear) empties the subdomain and leaves a plain "Geen preview" page. It also
drops every table in the preview database.

### Access without a password

The subdomain only lets in visitors who have a cookie (`jebbari_preview`), and only jebbari.nl
sets that cookie: when someone opens a valid client link. The cookie is set for `.jebbari.nl`, so
the subdomain receives it. It is HttpOnly and Secure, and it expires with the link. Anyone without
the cookie gets a 403. `robots.txt` stays reachable and forbids everything. Every response also
carries `X-Robots-Tag: noindex, nofollow`.

The preview may only be shown in a frame by jebbari.nl (`frame-ancestors`). The client page
itself allows framing of the preview subdomain and nothing else. It sends no referrer, so the
token in the link never reaches the preview site.

### Safety rules in the code

- **Repository URL:** only `https://github.com/<owner>/<repo>` is accepted. It is stored as
  `https://github.com/<owner>/<repo>.git`.
- **Branch:** only letters, digits, `. _ / -`. It cannot start with `-` and cannot contain `..`.
- **No shell:** the script runs as a fixed argument list. Every value goes in through
  environment variables, and the script quotes them all. `git clone` puts `--` before the URL.
- **Clean environment:** the script starts with nothing from Jebbari's environment, so Jebbari's
  own `APP_KEY` and database can never reach the preview site. The job also refuses to start
  when the preview database is Jebbari's own database.
- **Safe emptying:** before it empties anything, the script checks the target. It must be an
  absolute path without symlinks, it must end in `/preview.<domain>/httpdocs`, and it must not
  contain Jebbari or lie inside it.
- **Secrets stay out of logs:** the database password, the GitHub token and the access key are
  masked in the log. The token goes to git as a header, never in the URL.
- **Client link:** the token is 64 random characters. The buttons are rate limited and protected
  against CSRF. An expired, replaced or cleared link shows a short message (410), not the site.

## Server steps (once)

All of these happen in Plesk and in Jebbari's `.env` on the server.

### 1. Turn off password protection on the subdomain

Websites & Domains > preview.jebbari.nl > **Password-Protected Directories**: remove the
protection. The cookie check replaces it. Basic authentication does not work well inside the
frame: the client would see a login prompt within your page, or none at all.

### 2. Jebbari's `.env`

Add these lines (example values):

```
PREVIEW_PATH=/var/www/vhosts/jebbari.nl/preview.jebbari.nl/httpdocs
PREVIEW_URL=https://preview.jebbari.nl
PREVIEW_COOKIE_DOMAIN=.jebbari.nl

PREVIEW_DB_HOST=127.0.0.1
PREVIEW_DB_PORT=3306
PREVIEW_DB_DATABASE=<the empty preview database>
PREVIEW_DB_USERNAME=<its user>
PREVIEW_DB_PASSWORD=<its password>

PREVIEW_PHP=/opt/plesk/php/8.3/bin/php
PREVIEW_COMPOSER=/usr/lib64/plesk-9.0/composer.phar
PREVIEW_NPM=/opt/plesk/node/22/bin/npm

PREVIEW_GITHUB_TOKEN=github_pat_...
```

- `PREVIEW_PHP` must be the same PHP version the subdomain uses (Websites & Domains >
  preview.jebbari.nl > PHP).
- Find Composer with `ls /usr/lib64/plesk-9.0/composer.phar` or `command -v composer`. Find npm
  with `ls /opt/plesk/node/`. Scheduled tasks have a short `PATH`, so give full paths.
- Give the preview database's user rights on that database only. It must not be able to reach
  Jebbari's database.

Then:

```bash
php artisan migrate --force
```

```bash
php artisan config:cache
```

(`./deploy.sh` runs both.)

### 3. GitHub token for private repositories

On GitHub, go to Settings > Developer settings > **Fine-grained personal access tokens** >
Generate new token:

- **Resource owner:** your account, or the organisation that owns the client repositories
- **Repository access:** the client repositories (or all)
- **Permissions:** Repository permissions > **Contents: Read-only**. Metadata is added
  automatically. Nothing else.
- **Expiration:** for example 90 days. Put a reminder in your calendar to renew it.

Put the token in `PREVIEW_GITHUB_TOKEN`. Public repositories work without it.

A fine-grained token only reaches repositories owned by its resource owner. If a client's
repository lives in the client's own account, ask them to transfer it to you, or to add it to an
organisation you can make a token for. Being a collaborator is not enough for a fine-grained token.

### 4. Queue worker for previews

Builds take minutes. They run on their own queue, which only releases a job to another worker
after 30 minutes, so a build never runs twice. Add a second scheduled task, next to the existing
one, under the domain: Websites & Domains > jebbari.nl > **Scheduled Tasks** > Add Task.

| Field | Value |
|---|---|
| Task type | **Run a PHP script** |
| Script path | `/var/www/vhosts/jebbari.nl/httpdocs/artisan` |
| Use PHP version | **8.4** (Jebbari's version) |
| Arguments | `previews:work` |
| Run | Cron style `* * * * *` (every minute) |
| Notify | Errors only |

`previews:work` first checks the live preview for a new commit (automatic updates), then does
the same as `queue:work previews --queue=previews --stop-when-empty --max-time=55`. A task set
up earlier with those `queue:work` arguments still runs builds, but without automatic updates:
change its arguments to `previews:work`.

Keep the existing image worker as it is: it does not touch the `previews` queue. The job sets its
own timeout (25 minutes) and its own number of tries, so do not add `--timeout` or `--tries`. A
task that starts while a build is running finds nothing to do and stops. A clear job waits until
the build is finished.

Do not create the task under Tools & Settings: those tasks run as root, and the script refuses to
run as root.

### 5. Limit what the preview site can read (`open_basedir`)

preview.jebbari.nl is part of the same subscription as jebbari.nl. So the client's code runs as
the same system user as Jebbari: during the build (Composer scripts, npm, seeders) and on every
page view. Without a limit, a page of the preview site could read Jebbari's `.env`.

Limit the running site to its own folder:

1. Websites & Domains > preview.jebbari.nl > **PHP** (PHP Settings).
2. Find **open_basedir** under the security settings. The Plesk default
   `{WEBSPACEROOT}{/}{:}{TMP}{/}` covers the whole subscription, Jebbari included.
3. Set it to:

   ```
   /var/www/vhosts/jebbari.nl/preview.jebbari.nl/:/tmp/
   ```

   Do not use `{DOCROOT}`: that is only `httpdocs/public`, and Laravel needs `vendor`, `storage`
   and `.env` one level up.
4. Click OK, then deploy a preview and check that it opens.

This limits the site as it runs in the browser. It does not limit the build: Composer, npm and
the seeders run from the queue worker without `open_basedir`. So **only deploy repositories you
built yourself or trust.**

### 6. Static files through Apache

Websites & Domains > preview.jebbari.nl > **Apache & nginx Settings**: leave **Serve static
files directly by nginx** off. Otherwise nginx serves images, CSS and JavaScript without the
cookie check in `.htaccess`. mod_rewrite and mod_headers must be on (Tools & Settings > Apache
Web Server); in Plesk they are on by default.

### 7. Check the folders

As the site user (SSH):

```bash
ls -ld /var/www/vhosts/jebbari.nl/preview.jebbari.nl /var/www/vhosts/jebbari.nl/preview.jebbari.nl/httpdocs
```

Both must be owned by the site's system user. The build folder is created in
`preview.jebbari.nl`, next to `httpdocs`.

### 8. First test

1. Admin > Systeem > **Previews** > Nieuwe preview. Use a public Laravel repository first.
2. Follow the log until the status says *Klaar* (ready).
3. Open **Klantpagina**: the site appears under the bar.
4. Open https://preview.jebbari.nl in a private window: you should get a 403.
5. Click Goedkeuren and check that the e-mail arrives at the contact address from the site
   settings (contact > notify e-mail, otherwise the contact e-mail).

## When a build fails

The log shows the step and the error. Common causes:

| Log says | Fix |
|---|---|
| `Authentication failed` / `could not read Username` | Private repository: set or renew `PREVIEW_GITHUB_TOKEN`, and check its repository access |
| `Class "Faker\Factory" not found` | Edit the preview, tick **Composer dev-pakketten installeren**, deploy again |
| `composer not found` / `npm not found` | Set `PREVIEW_COMPOSER` / `PREVIEW_NPM` to full paths |
| `requires php ^8.4` | Set the subdomain and `PREVIEW_PHP` to the PHP version the project needs |
| `no write access` | Check ownership (step 7); the worker must run as the site user |
| `has no public/ folder` | The repository is not a Laravel-style project; the document root is `httpdocs/public` |
| Status stays *In de wachtrij* | The preview worker (step 4) is not running |

## Not verified

The build environment of this project runs as root and cannot reach Plesk, so
`scripts/preview-deploy.sh` was checked for syntax and tested through the job with a stand-in
script. It has not yet run against a real repository. Run the first test above before you send a
link to a client.
