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

namespace App\Application\Admin\Catalog\Dto;

use App\Domain\Catalog\Product\Repository\ProductRepository;
use Plugin\ExportCenter\Annotation\ExportColumn;
use Plugin\ExportCenter\Annotation\ExportSheet;

#[ExportSheet(
    name: '商品列表',
    description: '商品导出',
    dataProvider: [ProductRepository::class, 'getExportData'],
)]
class ProductExportDto
{
    #[ExportColumn(title: '商品编码', order: 1, width: 18)]
    public string $product_code;

    #[ExportColumn(title: '商品名称', order: 2, width: 30)]
    public string $name;

    #[ExportColumn(title: '副标题', order: 3, width: 30, field: 'sub_title')]
    public string $sub_title;

    #[ExportColumn(title: '分类', order: 4, width: 14, field: 'category.name', default: '-')]
    public string $category_name;

    #[ExportColumn(title: '品牌', order: 5, width: 14, field: 'brand.name', default: '-')]
    public string $brand_name;

    #[ExportColumn(title: '最低价(元)', order: 6, type: 'float', width: 12, format: '#,##0.00', divisor: 100)]
    public float $min_price;

    #[ExportColumn(title: '最高价(元)', order: 7, type: 'float', width: 12, format: '#,##0.00', divisor: 100)]
    public float $max_price;

    #[ExportColumn(title: '虚拟销量', order: 8, type: 'int', width: 10)]
    public int $virtual_sales;

    #[ExportColumn(title: '实际销量', order: 9, type: 'int', width: 10)]
    public int $real_sales;

    #[ExportColumn(title: '推荐', order: 10, width: 8, type: 'boolean')]
    public string $is_recommend;

    #[ExportColumn(title: '热门', order: 11, width: 8, type: 'boolean')]
    public string $is_hot;

    #[ExportColumn(title: '新品', order: 12, width: 8, type: 'boolean')]
    public string $is_new;

    #[ExportColumn(title: '状态', order: 13, width: 10, enum: ['draft' => '草稿', 'active' => '上架', 'inactive' => '下架', 'sold_out' => '售罄'])]
    public string $status;

    #[ExportColumn(title: '排序', order: 14, type: 'int', width: 8)]
    public int $sort;

    #[ExportColumn(title: '创建时间', order: 15, width: 20)]
    public string $created_at;
}
