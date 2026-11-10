<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */

namespace Plugin\Distribution\Application\Registration;

use App\Domain\Member\Event\MemberRegistered;
use Plugin\Distribution\Domain\ValueObject\RegistrationSourceVo;

/** 仅在一次注册用例内存活，通过协程 Context 隔离请求。 */
final class RegistrationScope
{
    /** @var array<int, array> */
    public array $bindings = [];

    /** @var array<int, MemberRegistered> */
    public array $events = [];

    public function __construct(public readonly RegistrationSourceVo $source) {}
}
