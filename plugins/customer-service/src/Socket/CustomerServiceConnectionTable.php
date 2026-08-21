<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Socket;

use Swoole\Table;

final class CustomerServiceConnectionTable
{
    public function __construct(private readonly Table $table) {}

    public static function create(): Table
    {
        $table = new Table(65536);
        $table->column('principal_type', Table::TYPE_STRING, 16);
        $table->column('principal_id', Table::TYPE_INT, 8);
        $table->column('admin_user_id', Table::TYPE_INT, 8);
        $table->column('connected_at', Table::TYPE_INT, 8);
        if (! $table->create()) {
            throw new \RuntimeException('客服连接表创建失败');
        }
        return $table;
    }

    public function set(string $fd, array $data): void
    {
        $this->table->set($fd, $data);
    }

    public function get(string $fd): ?array
    {
        $row = $this->table->get($fd);
        return $row === false ? null : $row;
    }

    public function del(string $fd): void
    {
        $this->table->del($fd);
    }

    /** @return iterable<string, array<string, int|string>> */
    public function all(): iterable
    {
        foreach ($this->table as $fd => $connection) {
            yield (string) $fd => $connection;
        }
    }
}
