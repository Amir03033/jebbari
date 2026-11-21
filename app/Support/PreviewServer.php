<?php

namespace App\Support;

use App\Models\Preview;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Runs scripts/preview-deploy.sh. Arguments are a fixed list (never a shell
 * string) and every value goes in through the environment, which starts clean:
 * nothing of Jebbari's own .env (its database above all) reaches the script.
 */
class PreviewServer
{
    /** Variables the script may inherit from the worker; everything else is removed. */
    private const INHERIT = ['PATH', 'HOME', 'USER', 'LOGNAME', 'LANG', 'LC_ALL', 'TMPDIR', 'COMPOSER_HOME', 'npm_config_cache'];

    /** Refuses to run when the preview would share Jebbari's database or is not set up. */
    public function assertConfigured(): void
    {
        if (! config('preview.path')) {
            throw new RuntimeException('PREVIEW_PATH is not set in Jebbari\'s .env.');
        }

        $db = config('preview.db');
        if (! $db['database'] || ! $db['username']) {
            throw new RuntimeException('PREVIEW_DB_DATABASE and PREVIEW_DB_USERNAME must be set in Jebbari\'s .env.');
        }

        $own = config('database.connections.'.config('database.default'));
        if (($own['driver'] ?? null) !== 'sqlite'
            && (string) ($own['database'] ?? '') === (string) $db['database']
            && (string) ($own['host'] ?? '') === (string) $db['host']) {
            throw new RuntimeException('The preview database is Jebbari\'s own database. Use the separate, empty preview database.');
        }
    }

    /** The preview site's .env, from resources/preview/env.template. */
    public function envFile(): string
    {
        $db = config('preview.db');
        $values = [
            'APP_URL' => config('preview.url'),
            'DB_HOST' => $db['host'],
            'DB_PORT' => $db['port'],
            'DB_DATABASE' => $db['database'],
            'DB_USERNAME' => $db['username'],
            'DB_PASSWORD' => $db['password'],
        ];

        return strtr((string) file_get_contents(config('preview.env_template')), collect($values)
            ->mapWithKeys(fn ($value, $key) => ['{{'.$key.'}}' => self::envValue((string) $value)])
            ->all());
    }

    /** A .env value: single-quoted (literal) where possible, otherwise double-quoted and escaped. */
    public static function envValue(string $value): string
    {
        $value = str_replace(["\r", "\n"], '', $value);

        if (! str_contains($value, "'")) {
            return "'".$value."'";
        }

        return '"'.addcslashes($value, '\\"$').'"';
    }

    /**
     * @param  callable(string): void  $output  receives the script's output as it comes
     * @return int the exit code
     */
    public function run(string $mode, ?Preview $preview, callable $output): int
    {
        $env = [
            'PREVIEW_PATH' => (string) config('preview.path'),
            'PREVIEW_PHP' => (string) config('preview.php'),
            'PREVIEW_COMPOSER' => (string) config('preview.composer'),
            'PREVIEW_NPM' => (string) config('preview.npm'),
            'PREVIEW_FRAME_ORIGIN' => $this->origin(),
            'JEBBARI_ROOT' => base_path(),
            'GIT_TERMINAL_PROMPT' => '0',
        ];

        if ($preview) {
            $env += [
                'PREVIEW_REPO' => (string) Preview::normalizeRepoUrl($preview->repo_url),
                'PREVIEW_BRANCH' => $preview->branch,
                'PREVIEW_ACCESS_KEY' => $preview->access_key,
                'PREVIEW_COOKIE' => (string) config('preview.cookie'),
                'PREVIEW_DEV' => $preview->dev_dependencies ? '1' : '0',
                'PREVIEW_ENV' => $this->envFile(),
            ];
        }

        // The GitHub token goes in as a git config header for github.com only: never in the URL or the log.
        if ($token = config('preview.github_token')) {
            $env += [
                'GIT_CONFIG_COUNT' => '1',
                'GIT_CONFIG_KEY_0' => 'http.https://github.com/.extraheader',
                'GIT_CONFIG_VALUE_0' => 'Authorization: Basic '.base64_encode('x-access-token:'.$token),
            ];
        }

        $process = new Process(['bash', config('preview.script'), $mode], base_path(), $this->cleanEnvironment($env), null, config('preview.timeout'));
        $secrets = array_filter([config('preview.db.password'), config('preview.github_token'), $preview?->access_key]);

        $process->run(function ($type, $buffer) use ($output, $secrets) {
            $output(self::mask($buffer, $secrets));
        });

        return (int) $process->getExitCode();
    }

    /** Removes every inherited variable except a few harmless ones, then adds ours. */
    private function cleanEnvironment(array $env): array
    {
        $inherited = array_keys(getenv() + $_ENV + array_filter($_SERVER, 'is_string'));
        $remove = array_fill_keys(array_diff($inherited, self::INHERIT), false);

        return array_merge($remove, $env);
    }

    private function origin(): string
    {
        $parts = parse_url((string) config('app.url'));

        return isset($parts['scheme'], $parts['host'])
            ? $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '')
            : '';
    }

    public static function mask(string $text, array $secrets): string
    {
        foreach ($secrets as $secret) {
            $text = str_replace((string) $secret, '********', $text);
        }

        return $text;
    }
}
