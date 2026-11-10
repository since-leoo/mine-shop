#!/usr/bin/env bash
set -euo pipefail

# Starts private socket-only services. Never reads application .env or existing databases.
project_dir="$(cd "$(dirname "$0")/.." && pwd)"
php_bin="${PHP_BIN:-php}"
mysql_bin="${MYSQLD_BIN:-}"
redis_bin="${REDIS_SERVER_BIN:-}"
if [[ -z "$mysql_bin" ]]; then
  mysql_bin="$(command -v mysqld || true)"
fi
if [[ -z "$mysql_bin" ]]; then
  for candidate in /opt/local/lib/mysql8/bin/mysqld /opt/homebrew/opt/mysql/bin/mysqld /usr/local/opt/mysql/bin/mysqld; do
    if [[ -x "$candidate" ]]; then mysql_bin="$candidate"; break; fi
  done
fi
if [[ -z "$redis_bin" ]]; then redis_bin="$(command -v redis-server || true)"; fi
if [[ -z "$redis_bin" && -x /opt/local/bin/redis-server ]]; then redis_bin=/opt/local/bin/redis-server; fi
if [[ ! -x "$mysql_bin" || ! -x "$redis_bin" ]]; then
  echo 'Install MySQL and Redis, or set MYSQLD_BIN and REDIS_SERVER_BIN to their executable paths.' >&2
  exit 1
fi

test_run_dir="$(mktemp -d /tmp/mine-shop-tests.XXXXXX)"
mysql_pid=''
redis_pid=''
cleanup() {
  if [[ -n "$redis_pid" ]]; then kill "$redis_pid" 2>/dev/null || true; wait "$redis_pid" 2>/dev/null || true; fi
  if [[ -n "$mysql_pid" ]]; then kill "$mysql_pid" 2>/dev/null || true; wait "$mysql_pid" 2>/dev/null || true; fi
  # Only the datadir created by this invocation is removed; retain diagnostics and JUnit reports.
  rm -rf -- "$test_run_dir/mysql"
  echo "Test reports and service logs: $test_run_dir"
}
trap cleanup EXIT
trap 'exit 130' INT TERM
mkdir "$test_run_dir/mysql"
"$mysql_bin" --no-defaults --initialize-insecure --user="$(id -un)" --datadir="$test_run_dir/mysql" > "$test_run_dir/mysql-initialize.log" 2>&1
"$mysql_bin" --no-defaults --user="$(id -un)" --datadir="$test_run_dir/mysql" --socket="$test_run_dir/mysql.sock" --pid-file="$test_run_dir/mysql.pid" --log-error="$test_run_dir/mysql.log" --skip-networking --mysqlx=0 --innodb-buffer-pool-size=64M > "$test_run_dir/mysql-stdout.log" 2>&1 &
mysql_pid=$!
"$redis_bin" --port 0 --unixsocket "$test_run_dir/redis.sock" --save '' --appendonly no --dir "$test_run_dir" > "$test_run_dir/redis.log" 2>&1 &
redis_pid=$!
export MINE_SHOP_TEST_MYSQL_SOCKET="$test_run_dir/mysql.sock"
export MINE_SHOP_TEST_REDIS_SOCKET="$test_run_dir/redis.sock"
ready=false
for ((attempt=0; attempt<100; attempt++)); do
  if "$php_bin" -r 'try { new PDO("mysql:unix_socket=".getenv("MINE_SHOP_TEST_MYSQL_SOCKET"), "root", ""); $r = new Redis(); $r->connect(getenv("MINE_SHOP_TEST_REDIS_SOCKET")); exit($r->ping() ? 0 : 1); } catch (Throwable) { exit(1); }' 2>/dev/null; then ready=true; break; fi
  sleep 0.1
done
if [[ "$ready" != true ]]; then echo "Private services did not start; inspect $test_run_dir" >&2; exit 1; fi
cd "$project_dir"
"$php_bin" vendor/bin/phpunit -c phpunit.unit.xml --log-junit "$test_run_dir/unit.xml"
"$php_bin" vendor/bin/phpunit -c phpunit.integration.xml --log-junit "$test_run_dir/integration.xml"
