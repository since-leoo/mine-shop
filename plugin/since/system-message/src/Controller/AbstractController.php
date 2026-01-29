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

namespace Plugin\Since\SystemMessage\Controller;

use App\Interface\Common\Controller\AbstractController as BaseController;
use App\Interface\Common\CurrentUser;
use Hyperf\HttpServer\Contract\RequestInterface;

abstract class AbstractController extends BaseController
{
    protected RequestInterface $request;

    protected CurrentUser $currentUser;

    public function __construct(CurrentUser $currentUser, RequestInterface $request)
    {
        $this->currentUser = $currentUser;
        $this->request = $request;
    }
}
