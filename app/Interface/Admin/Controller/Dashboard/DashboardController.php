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

namespace App\Interface\Admin\Controller\Dashboard;

use App\Application\Admin\Dashboard\AppDashboardQueryService;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;

/**
 * 仪表盘控制器.
 *
 * 提供三个页面的数据接口：
 * - welcome: 商城首页欢迎页（实时数据 + 待办事项 + 近期趋势）
 * - analysis: 数据分析页（销售额/支付额/访问人数/新用户/图表）
 * - report: 多维度统计报表（全量维度数据）
 */
#[Controller(prefix: '/admin/dashboard')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly AppDashboardQueryService $queryService,
    ) {}

    /**
     * 商城首页 — 欢迎页.
     *
     * 实时查询今日数据 + 待处理事项 + 总览 + 近7天趋势 + 热销商品.
     */
    #[GetMapping(path: 'welcome')]
    public function welcome(): Result
    {
        return $this->success($this->queryService->welcome());
    }

    /**
     * 数据分析页.
     *
     * 涵盖：销售额、支付额、总访问人数、新用户人数、转化率、
     * 同比增长、销售趋势图、会员趋势图、支付方式分布、
     * 订单类型分布、商品排行、分类排行.
     *
     * 参数：start_date, end_date（默认近30天）
     */
    #[GetMapping(path: 'analysis')]
    public function analysis(): Result
    {
        $params = $this->getRequestData();
        return $this->success($this->queryService->analysis($params));
    }

    /**
     * 多维度统计报表.
     *
     * 涵盖：销售趋势、会员趋势、商品排行、分类排行、支付方式分布/趋势、
     * 订单类型分布/趋势、会员等级分布、地区销售排行、退款分析、客单价分布.
     *
     * 参数：start_date, end_date（默认近30天）
     */
    #[GetMapping(path: 'report')]
    public function report(): Result
    {
        $params = $this->getRequestData();
        return $this->success($this->queryService->report($params));
    }
}
