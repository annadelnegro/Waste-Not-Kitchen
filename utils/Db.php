<?php
declare(strict_types=1);

/**
 * Minimal PDO database helper for MAMP.
 *
 * - Prefers MAMP's UNIX socket for reliability
 * - Falls back to host/port
 * - Reads optional settings from project .env (if present)
 * - Sensible defaults: utf8mb4, exceptions, real prepared statements
 */
final class Db
{
	/** @var \PDO|null */
	private static $pdo = null;

	/**
	 * Get a shared PDO connection.
	 * Usage: $pdo = Db::conn();
	 */
	public static function conn(): \PDO
	{
		if (self::$pdo instanceof \PDO) {
			return self::$pdo;
		}

		self::loadEnvIfPresent();

		$dbName   = getenv('DB_NAME') ?: 'waste_not_kitchen';
		$dbUser   = getenv('DB_USER') ?: 'root';
		$dbPass   = getenv('DB_PASS') ?: 'root';
		$dbHost   = getenv('DB_HOST') ?: '127.0.0.1';
		$dbPort   = getenv('DB_PORT') ?: '8889';
		$dbSocket = getenv('DB_SOCKET') ?: '/Applications/MAMP/tmp/mysql/mysql.sock';

		// Prefer the MAMP socket if it exists; otherwise use host/port
		if (is_string($dbSocket) && @file_exists($dbSocket)) {
			$dsn = "mysql:unix_socket={$dbSocket};dbname={$dbName};charset=utf8mb4";
		} else {
			$dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
		}

		$options = [
			\PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
			\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
			\PDO::ATTR_EMULATE_PREPARES   => false,
		];

		self::$pdo = new \PDO($dsn, $dbUser, $dbPass, $options);
		return self::$pdo;
	}

	/**
	 * Lightweight .env loader (no dependencies):
	 * Parses key=value lines from the project .env and populates getenv/$_ENV if not already set.
	 */
	private static function loadEnvIfPresent(): void
	{
		$root = dirname(__DIR__); // utils/ -> project root
		$envPath = $root . DIRECTORY_SEPARATOR . '.env';
		if (!is_file($envPath) || !is_readable($envPath)) {
			return;
		}

		$lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		if ($lines === false) {
			return;
		}

		foreach ($lines as $line) {
			$line = trim($line);
			if ($line === '' || $line[0] === '#') {
				continue;
			}
			// Allow KEY="value with spaces" and KEY=value
			if (!strpos($line, '=')) {
				continue;
			}
			[$key, $value] = array_map('trim', explode('=', $line, 2));
			$value = trim($value, "\"' ");
			if ($key === '') {
				continue;
			}
			// Don't overwrite existing env
			if (getenv($key) === false) {
				putenv("{$key}={$value}");
				$_ENV[$key] = $value;
			}
		}
	}
}
