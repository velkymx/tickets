<?php

namespace App\SystemHealth;

use Illuminate\Support\Facades\Process;

class Requirements
{
    public const MIN_PHP = '8.4.0';

    public const MIN_NODE = '24.0.0';

    public const EXTENSIONS = [
        'ctype', 'curl', 'dom', 'fileinfo', 'filter', 'hash',
        'mbstring', 'openssl', 'pdo', 'session', 'tokenizer', 'xml',
    ];

    public const DRIVER_EXTENSIONS = [
        'sqlite' => 'pdo_sqlite',
        'mysql' => 'pdo_mysql',
        'mariadb' => 'pdo_mysql',
        'pgsql' => 'pdo_pgsql',
    ];

    /**
     * @return list<RequirementResult>
     */
    public static function check(?string $driver = null, bool $withBuildTools = false): array
    {
        $results = [new RequirementResult('PHP >= 8.4', version_compare(PHP_VERSION, self::MIN_PHP, '>='), 'found '.PHP_VERSION)];

        foreach (self::EXTENSIONS as $ext) {
            $loaded = extension_loaded($ext);
            $results[] = new RequirementResult("PHP extension: {$ext}", $loaded, $loaded ? 'loaded' : 'not loaded');
        }

        if ($driver !== null) {
            $results[] = self::checkDriver($driver);
        }

        if ($withBuildTools) {
            array_push($results, ...self::checkBuildTools());
        }

        return $results;
    }

    private static function checkDriver(string $driver): RequirementResult
    {
        $ext = self::DRIVER_EXTENSIONS[$driver] ?? null;

        if ($ext === null) {
            return new RequirementResult("PHP extension for {$driver}", false, 'unsupported driver');
        }

        $loaded = extension_loaded($ext);

        return new RequirementResult("PHP extension: {$ext}", $loaded, $loaded ? "required for {$driver}" : "required for {$driver}, not loaded");
    }

    /**
     * @return list<RequirementResult>
     */
    private static function checkBuildTools(): array
    {
        $node = Process::run(['node', '--version']);
        $nodeVersion = ltrim(trim($node->output()), 'v');
        $nodeOk = $node->successful() && version_compare($nodeVersion, self::MIN_NODE, '>=');

        $npm = Process::run(['npm', '--version']);

        return [
            new RequirementResult('Node.js >= 24', $nodeOk, $nodeVersion !== '' ? $nodeVersion : 'not found'),
            new RequirementResult('npm', $npm->successful(), $npm->successful() ? 'found' : 'not found'),
        ];
    }
}
