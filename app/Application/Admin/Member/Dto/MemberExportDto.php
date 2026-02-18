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

namespace App\Application\Admin\Member\Dto;

use App\Domain\Member\Repository\MemberRepository;
use Plugin\ExportCenter\Annotation\ExportColumn;
use Plugin\ExportCenter\Annotation\ExportSheet;

#[ExportSheet(
    name: '会员列表',
    description: '会员导出',
    dataProvider: [MemberRepository::class, 'getExportData'],
)]
class MemberExportDto
{
    #[ExportColumn(title: 'ID', order: 1, type: 'int', width: 8)]
    public int $id;

    #[ExportColumn(title: '昵称', order: 2, width: 16, default: '-')]
    public string $nickname;

    #[ExportColumn(title: '手机号', order: 3, width: 14, default: '-')]
    public string $phone;

    #[ExportColumn(title: '性别', order: 4, width: 8, field: 'gender', enum: ['unknown' => '未知', 'male' => '男', 'female' => '女'], default: '未知')]
    public string $gender;

    #[ExportColumn(title: '等级', order: 5, width: 12, enum: ['bronze' => '青铜会员', 'silver' => '白银会员', 'gold' => '黄金会员', 'diamond' => '钻石会员'])]
    public string $level;

    #[ExportColumn(title: '成长值', order: 6, type: 'int', width: 10)]
    public int $growth_value;

    #[ExportColumn(title: '订单数', order: 7, type: 'int', width: 10)]
    public int $total_orders;

    #[ExportColumn(title: '消费总额(元)', order: 8, type: 'float', width: 14, format: '#,##0.00', divisor: 100)]
    public float $total_amount;

    #[ExportColumn(title: '状态', order: 9, width: 10, enum: ['active' => '正常', 'inactive' => '未激活', 'banned' => '已封禁'])]
    public string $status;

    #[ExportColumn(title: '来源', order: 10, width: 12, enum: ['wechat' => '微信公众号', 'mini_program' => '小程序', 'h5' => 'H5 活动页', 'admin' => '后台导入'])]
    public string $source;

    #[ExportColumn(title: '省份', order: 11, width: 10)]
    public string $province;

    #[ExportColumn(title: '城市', order: 12, width: 10)]
    public string $city;

    #[ExportColumn(title: '最后登录', order: 13, width: 20, default: '-')]
    public string $last_login_at;

    #[ExportColumn(title: '邀请码', order: 14, width: 12)]
    public string $invite_code;

    #[ExportColumn(title: '注册时间', order: 15, width: 20)]
    public string $created_at;
}
