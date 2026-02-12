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

namespace App\Interface\Common\Controller;

use App\Interface\Common\CurrentUser;
use Hyperf\HttpServer\Contract\RequestInterface;

abstract class SystemMessageAbstractController extends AbstractController
{
    protected RequestInterface $request;

    protected CurrentUser $currentUser;

    public function __construct(CurrentUser $currentUser, RequestInterface $request)
    {
        $this->currentUser = $currentUser;
        $this->request = $request;
    }
}
