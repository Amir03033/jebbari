# Live uptime on case studies

For a published project with a link and **Live meten** switched on (project form,
"Overzicht"), the site requests that address every 5 minutes through the same
SSRF-safe fetcher as the website check (10 s timeout, 256 KB). 2xx and 3xx count as up.

The case study shows "Live gemeten": online now (or "not reachable now", only after
two failed checks in a row), uptime and average response time over the last 30 days,
and how many measurements that is. Nothing appears until there are at least 24
measurements (about 2 hours). Measurements older than 90 days are deleted.

Labelled honestly as measured "by jebbari.nl itself". ServerScope could become the
source later; that needs its API and a key.

Code: `App\Uptime\Monitor`, `App\Models\UptimeCheck`, `php artisan uptime:check`
(scheduled every 5 minutes; without a scheduler, queued at most every 10 minutes after a visit).
