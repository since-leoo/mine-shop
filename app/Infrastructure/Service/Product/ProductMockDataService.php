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

namespace App\Infrastructure\Service\Product;

use App\Domain\Product\Enum\BrandStatus;
use App\Domain\Product\Enum\CategoryStatus;
use App\Domain\Product\Enum\ProductStatus;
use App\Infrastructure\Model\Product\Brand;
use App\Infrastructure\Model\Product\Category;
use App\Infrastructure\Model\Product\Product;
use App\Infrastructure\Model\Product\ProductAttribute;
use App\Infrastructure\Model\Product\ProductGallery;
use App\Infrastructure\Model\Product\ProductSku;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Hyperf\DbConnection\Db;
use Hyperf\Stringable\Str;

class ProductMockDataService
{
    private Generator $faker;

    private array $categorySortTracker = [];

    private int $productSort = 1;

    private array $usedProductNames = [];

    public function __construct()
    {
        $this->faker = FakerFactory::create('zh_CN');
    }

    /**
     * @param array{limit?:int,force?:bool,dry_run?:bool} $options
     */
    public function seed(array $options = []): array
    {
        $this->productSort = 1;
        $this->usedProductNames = [];

        $limit = isset($options['limit']) ? max(0, (int) $options['limit']) : 0;
        $limit = $limit > 0 ? $limit : null;
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $force = (bool) ($options['force'] ?? false);

        if ($dryRun) {
            return [
                'dry_run' => true,
                'categories' => $this->countCategoryNodes($this->categoryTemplates()),
                'brands' => \count($this->brandPresets()),
                'products' => $this->countProductPresets($limit),
            ];
        }

        if ($force) {
            $this->truncateTables();
        }

        return Db::transaction(function () use ($limit) {
            $categorySummary = $this->seedCategories();
            $brandSummary = $this->seedBrands();
            $productSummary = $this->seedProducts($categorySummary['path_map'], $brandSummary['list'], $limit);

            return [
                'dry_run' => false,
                'categories' => $categorySummary['count'],
                'brands' => $brandSummary['count'],
                'products' => $productSummary['products'],
                'skus' => $productSummary['skus'],
                'attributes' => $productSummary['attributes'],
                'gallery' => $productSummary['gallery'],
                'missing_categories' => $productSummary['missing_categories'],
            ];
        });
    }

    private function truncateTables(): void
    {
        $models = [
            ProductAttribute::class,
            ProductGallery::class,
            ProductSku::class,
            Product::class,
            Brand::class,
            Category::class,
        ];

        foreach ($models as $model) {
            try {
                $model::truncate();
            } catch (\Throwable $exception) {
                $model::query()->delete();
            }
        }
    }

    private function seedCategories(): array
    {
        $this->categorySortTracker = [];
        $pathMap = [];
        $all = [];

        $walker = function (array $nodes, int $parentId = 0, int $level = 1, array $parents = []) use (&$walker, &$pathMap, &$all) {
            foreach ($nodes as $node) {
                $path = array_merge($parents, [$node['name']]);
                $sort = $this->nextCategorySort($parentId);
                $category = Category::query()->updateOrCreate(
                    ['parent_id' => $parentId, 'name' => $node['name']],
                    [
                        'icon' => $node['icon'] ?? null,
                        'thumbnail' => $node['thumbnail'] ?? $this->categoryThumbnail($path),
                        'description' => $node['description'] ?? null,
                        'sort' => $sort,
                        'level' => $level,
                        'status' => CategoryStatus::ACTIVE->value,
                    ],
                );
                $all[] = $category;
                $pathKey = implode(' / ', $path);
                $pathMap[$pathKey] = $category;

                if (! empty($node['children'])) {
                    $walker($node['children'], (int) $category->id, $level + 1, $path);
                }
            }
        };

        $walker($this->categoryTemplates());

        return [
            'count' => \count($all),
            'path_map' => $pathMap,
        ];
    }

    private function nextCategorySort(int $parentId): int
    {
        $this->categorySortTracker[$parentId] = ($this->categorySortTracker[$parentId] ?? 0) + 1;
        return $this->categorySortTracker[$parentId];
    }

    private function seedBrands(): array
    {
        $list = [];
        $sort = 1;

        foreach ($this->brandPresets() as $preset) {
            $model = Brand::query()->updateOrCreate(
                ['name' => $preset['name']],
                [
                    'logo' => $preset['logo'],
                    'description' => $preset['description'],
                    'website' => $preset['website'],
                    'sort' => $sort++,
                    'status' => BrandStatus::ACTIVE->value,
                ],
            );
            $list[] = [
                'model' => $model,
                'tags' => $preset['tags'] ?? [],
            ];
        }

        return [
            'list' => $list,
            'count' => \count($list),
        ];
    }

    private function seedProducts(array $categoryMap, array $brandList, ?int $limit): array
    {
        $payload = $this->generateProductPayloads($categoryMap, $brandList, $limit);
        $products = 0;
        $skus = 0;
        $attributes = 0;
        $gallery = 0;

        foreach ($payload['records'] as $record) {
            /** @var Product $product */
            $product = Product::query()->create($record['product']);

            if (! empty($record['skus'])) {
                $product->skus()->createMany($record['skus']);
                $skus += \count($record['skus']);
            }

            if (! empty($record['attributes'])) {
                $product->attributes()->createMany($record['attributes']);
                $attributes += \count($record['attributes']);
            }

            if (! empty($record['gallery'])) {
                $product->gallery()->createMany($record['gallery']);
                $gallery += \count($record['gallery']);
            }

            ++$products;
        }

        return [
            'products' => $products,
            'skus' => $skus,
            'attributes' => $attributes,
            'gallery' => $gallery,
            'missing_categories' => $payload['missing_categories'],
        ];
    }

    private function generateProductPayloads(array $categoryMap, array $brandList, ?int $limit): array
    {
        $records = [];
        $missing = [];

        foreach ($this->productTemplates() as $template) {
            $iterations = max(1, (int) ($template['count'] ?? 1));
            for ($i = 0; $i < $iterations; ++$i) {
                if ($limit !== null && \count($records) >= $limit) {
                    break 2;
                }

                $path = $template['category_path'];
                if (! isset($categoryMap[$path])) {
                    $missing[$path] = true;
                    continue;
                }

                /** @var Category $category */
                $category = $categoryMap[$path];
                $brand = $this->pickBrand($brandList, $template['brand_tags'] ?? []);
                $name = $this->buildProductName($template);
                $slug = Str::slug($name, '-') ?: 'product-' . mb_substr(md5($name), 0, 8);
                [$galleryImages, $galleryRecords] = $this->buildGallery($slug, $template['image_hint'] ?? $template['key'], $template['gallery_count'] ?? 4);
                $attributeRows = $this->buildAttributeRows($template);
                $detail = $this->buildDetailContent($template, $attributeRows);
                $skuResult = $this->buildSkuRecords($template, $template['spec_schema'] ?? [], $galleryImages, $name);
                $virtualRange = $template['virtual_sales'] ?? [800, 2400];
                $virtualSales = $this->faker->numberBetween($virtualRange[0], $virtualRange[1]);
                $realSales = (int) round($virtualSales * ($template['real_sales_ratio'] ?? 0.65));

                $records[] = [
                    'product' => [
                        'category_id' => $category->id,
                        'brand_id' => $brand->id,
                        'name' => $name,
                        'sub_title' => $template['sub_title'] ?? null,
                        'main_image' => $galleryImages[0] ?? null,
                        'gallery_images' => $galleryImages,
                        'description' => $this->buildDescription($template),
                        'detail_content' => $detail,
                        'attributes' => $attributeRows,
                        'min_price' => $skuResult['min'],
                        'max_price' => $skuResult['max'],
                        'virtual_sales' => $virtualSales,
                        'real_sales' => $realSales,
                        'is_recommend' => (bool) ($template['is_recommend'] ?? false),
                        'is_hot' => (bool) ($template['is_hot'] ?? false),
                        'is_new' => (bool) ($template['is_new'] ?? false),
                        'shipping_template_id' => null,
                        'sort' => $this->productSort++,
                        'status' => ProductStatus::ACTIVE->value,
                    ],
                    'skus' => $skuResult['records'],
                    'attributes' => $attributeRows,
                    'gallery' => $galleryRecords,
                ];
            }
        }

        return [
            'records' => $records,
            'missing_categories' => array_keys($missing),
        ];
    }

    private function pickBrand(array $brandList, array $requiredTags): Brand
    {
        $pool = [];
        foreach ($brandList as $item) {
            if ($requiredTags === [] || array_intersect($requiredTags, $item['tags']) !== []) {
                $pool[] = $item['model'];
            }
        }

        if ($pool === []) {
            $pool = array_map(static fn ($item) => $item['model'], $brandList);
        }

        return $pool[array_rand($pool)];
    }

    private function buildProductName(array $template): string
    {
        $series = $this->faker->randomElement($template['series'] ?? ['Nova']);
        $model = $this->faker->randomElement($template['model_codes'] ?? ['Pro']);
        $name = trim($series . ' ' . $model . ' ' . ($template['display_name'] ?? ''));

        return $this->ensureUniqueName($name);
    }

    private function ensureUniqueName(string $name): string
    {
        $base = $name;
        $index = 1;
        while (isset($this->usedProductNames[$name])) {
            ++$index;
            $name = $base . ' ' . $index;
        }
        $this->usedProductNames[$name] = true;

        return $name;
    }

    /**
     * @return array{0: string[], 1: array<int, array{image_url:string,alt_text:string,sort_order:int,is_primary:bool}>}
     */
    private function buildGallery(string $slug, string $hint, int $count = 4): array
    {
        $images = [];
        $count = max(2, $count);
        for ($i = 1; $i <= $count; ++$i) {
            $seed = rawurlencode(\sprintf('%s-%s-%d', $hint, $slug, $i));
            $images[] = \sprintf('https://picsum.photos/seed/%s/1200/800', $seed);
        }

        $records = [];
        foreach ($images as $index => $url) {
            $records[] = [
                'image_url' => $url,
                'alt_text' => \sprintf('%s 图 %d', $hint, $index + 1),
                'sort_order' => $index + 1,
                'is_primary' => $index === 0,
            ];
        }

        return [$images, $records];
    }

    /**
     * @return array{records: array<int, array<string, mixed>>, min: int, max: int}
     */
    private function buildSkuRecords(array $template, array $specSchema, array $galleryImages, string $productName): array
    {
        $combinations = $this->buildSkuCombinations($specSchema, $template['sku_limit'] ?? null);
        $records = [];
        $prices = [];

        foreach ($combinations as $index => $combo) {
            $salePrice = $this->calculateSalePrice($template['price'] ?? [], $combo);
            $marketDelta = $template['price']['market_delta'] ?? 30000;
            $marketPrice = $salePrice + $marketDelta;
            $costRatio = $template['price']['cost_ratio'] ?? 0.6;
            $costPrice = (int) round($salePrice * $costRatio);
            $stockRange = $template['stock_range'] ?? [40, 160];
            $stock = $this->faker->numberBetween($stockRange[0], $stockRange[1]);
            $warning = max(5, (int) round($stock * 0.12));
            $specValues = [];
            foreach ($combo as $specName => $specValue) {
                $specValues[] = [
                    'name' => $specName,
                    'value' => $specValue,
                ];
            }

            $records[] = [
                'sku_name' => \sprintf('%s · %s', $productName, implode(' / ', array_values($combo))),
                'spec_values' => $specValues,
                'image' => $galleryImages[$index % max(1, \count($galleryImages))] ?? $galleryImages[0] ?? null,
                'cost_price' => $costPrice,
                'market_price' => $marketPrice,
                'sale_price' => $salePrice,
                'stock' => $stock,
                'warning_stock' => $warning,
                'weight' => (float) ($template['weight'] ?? 0.5),
                'status' => ProductSku::STATUS_ACTIVE,
            ];
            $prices[] = $salePrice;
        }

        $min = $prices ? min($prices) : 0;
        $max = $prices ? max($prices) : 0;

        return [
            'records' => $records,
            'min' => $min,
            'max' => $max,
        ];
    }

    private function buildSkuCombinations(array $schema, ?int $limit = null): array
    {
        if ($schema === []) {
            return [['规格' => '标准款']];
        }

        $combinations = [[]];
        foreach ($schema as $name => $values) {
            $values = array_values($values);
            $next = [];
            foreach ($combinations as $combo) {
                foreach ($values as $value) {
                    $next[] = $combo + [$name => $value];
                }
            }
            $combinations = $next;
        }

        if ($limit !== null && $limit > 0) {
            $combinations = \array_slice($combinations, 0, $limit);
        }

        return $combinations;
    }

    private function calculateSalePrice(array $priceConfig, array $combo): int
    {
        $price = $priceConfig['base'] ?? 0;
        $increments = $priceConfig['increments'] ?? [];

        foreach ($combo as $specName => $specValue) {
            if (isset($increments[$specName][$specValue])) {
                $price += $increments[$specName][$specValue];
            }
        }

        return $price;
    }

    private function buildAttributeRows(array $template): array
    {
        $rows = [];
        foreach ($template['attributes'] ?? [] as $name => $value) {
            $rows[] = [
                'attribute_name' => $name,
                'value' => $value,
            ];
        }

        $rows[] = [
            'attribute_name' => '上架时间',
            'value' => \sprintf('2025 Q%d', $this->faker->numberBetween(1, 4)),
        ];
        $rows[] = [
            'attribute_name' => '保修',
            'value' => $template['warranty'] ?? '24 个月全国联保',
        ];
        $rows[] = [
            'attribute_name' => '产地',
            'value' => $this->faker->randomElement(['中国', '越南', '马来西亚', '中国台湾', '德国']),
        ];

        return $rows;
    }

    private function buildDescription(array $template): string
    {
        $audience = $this->faker->randomElement(['创作者', '新婚家庭', '数码发烧友', '精致生活家', '户外玩家', '咖啡爱好者']);
        $base = trim((string) ($template['description'] ?? ''));
        $suffix = \sprintf(' 适合%s使用。', $audience);

        return trim($base . $suffix);
    }

    private function buildDetailContent(array $template, array $attributes): string
    {
        $points = $template['detail_points'] ?? [];
        $html = '<h3>核心亮点</h3><ul>';
        foreach ($points as $point) {
            $html .= '<li>' . htmlspecialchars((string) $point, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '</li>';
        }
        $html .= '</ul><h3>参数概览</h3><ul>';
        foreach ($attributes as $attribute) {
            $html .= \sprintf(
                '<li>%s：%s</li>',
                htmlspecialchars((string) ($attribute['attribute_name'] ?? ''), \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars((string) ($attribute['value'] ?? ''), \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8'),
            );
        }
        $html .= '</ul>';
        $html .= '<p>' . htmlspecialchars((string) ($template['description'] ?? ''), \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '</p>';

        return $html;
    }

    private function countCategoryNodes(array $templates): int
    {
        $count = 0;
        foreach ($templates as $node) {
            ++$count;
            if (! empty($node['children'])) {
                $count += $this->countCategoryNodes($node['children']);
            }
        }

        return $count;
    }

    private function countProductPresets(?int $limit): int
    {
        $count = 0;
        foreach ($this->productTemplates() as $template) {
            $batch = max(1, (int) ($template['count'] ?? 1));
            if ($limit !== null) {
                $remaining = $limit - $count;
                if ($remaining <= 0) {
                    break;
                }
                $batch = min($batch, $remaining);
            }
            $count += $batch;
        }

        return $count;
    }

    /**
     * 生成稳定的分类缩略图链接（基于路径种子）.
     *
     * @param string[] $path
     */
    private function categoryThumbnail(array $path): string
    {
        $seed = Str::slug(implode('-', $path), '-') ?: md5(implode('|', $path));
        return \sprintf('https://picsum.photos/seed/mine-shop-%s/640/640', rawurlencode($seed));
    }

    private function categoryTemplates(): array
    {
        return [
            [
                'name' => '数码电器',
                'icon' => 'ph:device-mobile',
                'description' => '覆盖手机、电脑、影音以及智能家居的核心品类',
                'children' => [
                    [
                        'name' => '手机通讯',
                        'icon' => 'ph:device-mobile-camera',
                        'description' => '旗舰直板、折叠屏与穿戴设备',
                        'children' => [
                            [
                                'name' => '旗舰手机',
                                'icon' => 'ph:cell-signal-full',
                                'description' => '高性能旗舰直板机型',
                            ],
                            [
                                'name' => '折叠屏与穿戴',
                                'icon' => 'ph:device-mobile',
                                'description' => '横向/竖向折叠屏与高端穿戴产品',
                            ],
                        ],
                    ],
                    [
                        'name' => '电脑办公',
                        'icon' => 'ph:laptop',
                        'description' => '轻薄本、创作者本与桌面办公设备',
                        'children' => [
                            [
                                'name' => '轻薄本',
                                'icon' => 'ph:laptop',
                                'description' => '移动办公轻薄笔记本',
                            ],
                            [
                                'name' => '创作者本',
                                'icon' => 'ph:monitor-play',
                                'description' => '高性能图形创作笔记本',
                            ],
                        ],
                    ],
                    [
                        'name' => '影音娱乐',
                        'icon' => 'ph:headphones',
                        'description' => '耳机、音箱与沉浸影音设备',
                        'children' => [
                            [
                                'name' => '降噪耳机',
                                'icon' => 'ph:headphones',
                                'description' => '全场景主动降噪耳机',
                            ],
                            [
                                'name' => '智能音箱',
                                'icon' => 'ph:speaker-high',
                                'description' => '语音助手与全屋播放中心',
                            ],
                        ],
                    ],
                    [
                        'name' => '智能生活',
                        'icon' => 'ph:watch',
                        'description' => '智能穿戴与家居 IoT 设备',
                        'children' => [
                            [
                                'name' => '智能手表',
                                'icon' => 'ph:watch',
                                'description' => '运动健康智能手表',
                            ],
                            [
                                'name' => '智能家居',
                                'icon' => 'ph:house-line',
                                'description' => '传感、安防与自动化控制套件',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => '家居生活',
                'icon' => 'ph:house',
                'description' => '围绕厨房、清洁、空气与软装的品质家居',
                'children' => [
                    [
                        'name' => '厨房电器',
                        'icon' => 'ph:fork-knife',
                        'description' => '健康冰箱与料理电器',
                        'children' => [
                            [
                                'name' => '健康冰箱',
                                'icon' => 'ph:snowflake',
                                'description' => '多温区保鲜冰箱',
                            ],
                            [
                                'name' => '厨房料理',
                                'icon' => 'ph:knife',
                                'description' => '空气炸锅与烹饪机',
                            ],
                        ],
                    ],
                    [
                        'name' => '清洁电器',
                        'icon' => 'ph:broom',
                        'description' => '扫地机器人、洗地机等清洁设备',
                        'children' => [
                            [
                                'name' => '扫地机器人',
                                'icon' => 'ph:robot',
                                'description' => '扫拖一体智能清洁设备',
                            ],
                            [
                                'name' => '洗地机',
                                'icon' => 'ph:basket',
                                'description' => '吸拖洗一体地面清洁',
                            ],
                        ],
                    ],
                    [
                        'name' => '家具软装',
                        'icon' => 'ph:armchair',
                        'description' => '沙发、床垫与软装灵感',
                        'children' => [
                            [
                                'name' => '沙发',
                                'icon' => 'ph:armchair',
                                'description' => '模块化与北欧风沙发',
                            ],
                            [
                                'name' => '床垫',
                                'icon' => 'ph:wave-sine',
                                'description' => '乳胶与混合型床垫',
                            ],
                        ],
                    ],
                    [
                        'name' => '空气水健康',
                        'icon' => 'ph:wind',
                        'description' => '空气净化与舒缓香薰',
                        'children' => [
                            [
                                'name' => '空气净化',
                                'icon' => 'ph:wind',
                                'description' => '全屋净化器与新风系统',
                            ],
                            [
                                'name' => '香薰加湿',
                                'icon' => 'ph:drop',
                                'description' => '香薰机与超声波加湿',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => '潮流生活',
                'icon' => 'ph:flame',
                'description' => '运动户外、美妆个护与精品饮食',
                'children' => [
                    [
                        'name' => '运动户外',
                        'icon' => 'ph:mountains',
                        'description' => '跑步、骑行与旅行装备用品',
                        'children' => [
                            [
                                'name' => '跑步装备',
                                'icon' => 'ph:running',
                                'description' => '跑鞋、运动配件',
                            ],
                            [
                                'name' => '骑行装备',
                                'icon' => 'ph:bicycle',
                                'description' => '整车与骑行组件',
                            ],
                            [
                                'name' => '户外旅行',
                                'icon' => 'ph:suitcase',
                                'description' => '背包与轻量旅行装备',
                            ],
                        ],
                    ],
                    [
                        'name' => '美妆个护',
                        'icon' => 'ph:sparkle',
                        'description' => '护肤套装与美容仪',
                        'children' => [
                            [
                                'name' => '护肤套装',
                                'icon' => 'ph:sparkle',
                                'description' => '护肤精华、面霜组合',
                            ],
                            [
                                'name' => '个护电器',
                                'icon' => 'ph:drop-half-bottom',
                                'description' => '美容仪、护理仪器',
                            ],
                        ],
                    ],
                    [
                        'name' => '咖啡茶饮',
                        'icon' => 'ph:coffee',
                        'description' => '精品咖啡豆与手冲器具',
                        'children' => [
                            [
                                'name' => '精品咖啡',
                                'icon' => 'ph:coffee',
                                'description' => '产地精品咖啡豆与挂耳',
                            ],
                            [
                                'name' => '手冲器具',
                                'icon' => 'ph:drop-half',
                                'description' => '磨豆机与冲煮器材',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function brandPresets(): array
    {
        return [
            [
                'name' => 'Nebula Mobile',
                'description' => '聚焦旗舰手机与穿戴生态的科技品牌',
                'logo' => 'https://api.dicebear.com/7.x/shapes/svg?seed=NebulaMobile&backgroundColor=0f172a&scale=110',
                'website' => 'https://nebula-mobile.example.com',
                'tags' => ['digital', 'phone', 'wearable'],
            ],
            [
                'name' => 'Aurora Labs',
                'description' => '致力于高性能计算与全屋智能的研发团队',
                'logo' => 'https://api.dicebear.com/7.x/shapes/svg?seed=AuroraLabs&backgroundColor=312e81&scale=110',
                'website' => 'https://aurora-labs.example.com',
                'tags' => ['digital', 'laptop', 'smart-home'],
            ],
            [
                'name' => 'Vantage Audio',
                'description' => '沉浸式声学体验与专业监听耳机制造商',
                'logo' => 'https://api.dicebear.com/7.x/shapes/svg?seed=VantageAudio&backgroundColor=1d2538&scale=110',
                'website' => 'https://vantage-audio.example.com',
                'tags' => ['audio', 'digital'],
            ],
            [
                'name' => 'PulseGear',
                'description' => '运动穿戴与健康数据平台品牌',
                'logo' => 'https://api.dicebear.com/7.x/shapes/svg?seed=PulseGear&backgroundColor=0f766e&scale=110',
                'website' => 'https://pulsegear.example.com',
                'tags' => ['wearable', 'sports', 'digital'],
            ],
            [
                'name' => 'AeroHome',
                'description' => '提供智能家居与空气环境解决方案',
                'logo' => 'https://api.dicebear.com/7.x/shapes/svg?seed=AeroHome&backgroundColor=2563eb&scale=110',
                'website' => 'https://aerohome.example.com',
                'tags' => ['smart-home', 'appliance'],
            ],
            [
                'name' => 'PureLiving',
                'description' => '高端空气净化与水健康品牌',
                'logo' => 'https://api.dicebear.com/7.x/shapes/svg?seed=PureLiving&backgroundColor=059669&scale=110',
                'website' => 'https://pureliving.example.com',
                'tags' => ['home', 'air', 'appliance'],
            ],
            [
                'name' => 'TerraNest',
                'description' => '自然主义家具与模块化沙发设计工作室',
                'logo' => 'https://api.dicebear.com/7.x/shapes/svg?seed=TerraNest&backgroundColor=78350f&scale=110',
                'website' => 'https://terranest.example.com',
                'tags' => ['furniture', 'home'],
            ],
            [
                'name' => 'CloudNine Bedding',
                'description' => '专注床垫与睡眠系统的设计品牌',
                'logo' => 'https://api.dicebear.com/7.x/shapes/svg?seed=CloudNine&backgroundColor=1d4ed8&scale=110',
                'website' => 'https://cloudnine-bedding.example.com',
                'tags' => ['bedding', 'home'],
            ],
            [
                'name' => 'Hearthly Kitchen',
                'description' => '打造厨房电器与餐厨生活方式',
                'logo' => 'https://api.dicebear.com/7.x/shapes/svg?seed=Hearthly&backgroundColor=b45309&scale=110',
                'website' => 'https://hearthly.example.com',
                'tags' => ['kitchen', 'appliance'],
            ],
            [
                'name' => 'StrideWorks',
                'description' => '跑步科技与碳板鞋研发团队',
                'logo' => 'https://api.dicebear.com/7.x/shapes/svg?seed=StrideWorks&backgroundColor=be123c&scale=110',
                'website' => 'https://strideworks.example.com',
                'tags' => ['sports', 'running'],
            ],
            [
                'name' => 'PeakTrail Outfitters',
                'description' => '山系旅行与功能背包品牌',
                'logo' => 'https://api.dicebear.com/7.x/shapes/svg?seed=PeakTrail&backgroundColor=14532d&scale=110',
                'website' => 'https://peaktrail.example.com',
                'tags' => ['outdoor', 'travel'],
            ],
            [
                'name' => 'Lumina Beauty Lab',
                'description' => '以植物活性为核心的护肤品牌',
                'logo' => 'https://api.dicebear.com/7.x/shapes/svg?seed=LuminaBeauty&backgroundColor=7e22ce&scale=110',
                'website' => 'https://lumina-beauty.example.com',
                'tags' => ['beauty', 'personal-care'],
            ],
            [
                'name' => 'CraftRoast Studio',
                'description' => '精品咖啡烘焙与冲煮实验室',
                'logo' => 'https://api.dicebear.com/7.x/shapes/svg?seed=CraftRoast&backgroundColor=92400e&scale=110',
                'website' => 'https://craftroast.example.com',
                'tags' => ['coffee', 'gourmet'],
            ],
            [
                'name' => 'DriftCycle Works',
                'description' => '碳纤维整车与骑行配件制造商',
                'logo' => 'https://api.dicebear.com/7.x/shapes/svg?seed=DriftCycle&backgroundColor=1e293b&scale=110',
                'website' => 'https://driftcycle.example.com',
                'tags' => ['cycling', 'sports'],
            ],
            [
                'name' => 'Mistify Studio',
                'description' => '香薰与加湿体验的生活方式品牌',
                'logo' => 'https://api.dicebear.com/7.x/shapes/svg?seed=Mistify&backgroundColor=f97316&scale=110',
                'website' => 'https://mistify.example.com',
                'tags' => ['air', 'aroma', 'home'],
            ],
        ];
    }

    private function productTemplates(): array
    {
        return [
            [
                'key' => 'flagship_phone',
                'category_path' => '数码电器 / 手机通讯 / 旗舰手机',
                'display_name' => '5G 智能手机',
                'series' => ['星轨', '凌云', '曜影', '极昼', '逐光', '天穹'],
                'model_codes' => ['Pro 12', 'Max 20', 'Ultra', 'Pro+ Eco'],
                'sub_title' => '第二代骁龙8 | 2K 120Hz 柔性屏 | 120W 闪充',
                'description' => '面向追求影像与性能平衡的旗舰用户，轻薄手感与全天续航兼备。',
                'detail_points' => [
                    '自研计算摄影引擎，夜景与人像细节更纯净',
                    '航天铝中框搭配微曲玻璃，整机仅 189g',
                    'AI 动态性能调度，电竞级散热保持稳定帧率',
                ],
                'attributes' => [
                    '芯片' => '第二代骁龙8 + LPDDR5X',
                    '屏幕' => '6.73" LTPO AMOLED 1-120Hz',
                    '快充' => '120W 双电芯 + 50W 无线',
                    '系统' => 'NebulaOS 2.0',
                ],
                'spec_schema' => [
                    '颜色' => ['曜石黑', '星际蓝', '冰川白'],
                    '存储' => ['12GB+256GB', '16GB+512GB'],
                ],
                'price' => [
                    'base' => 449900,
                    'market_delta' => 50000,
                    'cost_ratio' => 0.61,
                    'increments' => [
                        '颜色' => ['曜石黑' => 0, '星际蓝' => 15000, '冰川白' => 20000],
                        '存储' => ['12GB+256GB' => 0, '16GB+512GB' => 90000],
                    ],
                ],
                'brand_tags' => ['phone', 'digital'],
                'weight' => 0.43,
                'stock_range' => [90, 220],
                'virtual_sales' => [5200, 9800],
                'real_sales_ratio' => 0.72,
                'is_recommend' => true,
                'is_hot' => true,
                'is_new' => true,
                'count' => 3,
                'sku_limit' => 4,
                'image_hint' => 'phone',
                'warranty' => '24 个月全国联保',
            ],
            [
                'key' => 'foldable_device',
                'category_path' => '数码电器 / 手机通讯 / 折叠屏与穿戴',
                'display_name' => '折叠屏旗舰',
                'series' => ['灵犀', '曜折', '星舷'],
                'model_codes' => ['Fold X', 'Fold Air', 'Flex 2'],
                'sub_title' => '无缝水滴铰链 | 2K 柔性内屏 | 66W 闪充',
                'description' => '针对商务与轻量化折叠需求打造，横竖双形态自由切换。',
                'detail_points' => [
                    '新一代水滴铰链支持 50 万次折叠寿命',
                    '航空纤维背板，机身薄至 10.2mm',
                    'AI 分屏协同，会议与创作效率倍增',
                ],
                'attributes' => [
                    '内屏尺寸' => '7.9" 2K 柔性屏',
                    '外屏比例' => '21:9 微曲外屏',
                    '铰链材质' => '航空钛合金 + 高分子纤维',
                ],
                'spec_schema' => [
                    '颜色' => ['晨曦金', '青山黛', '曜夜黑'],
                    '形态' => ['横向折叠', '竖向折叠'],
                ],
                'price' => [
                    'base' => 699900,
                    'market_delta' => 80000,
                    'cost_ratio' => 0.58,
                    'increments' => [
                        '颜色' => ['晨曦金' => 30000, '青山黛' => 20000, '曜夜黑' => 0],
                        '形态' => ['横向折叠' => 0, '竖向折叠' => 60000],
                    ],
                ],
                'brand_tags' => ['phone', 'wearable'],
                'weight' => 0.26,
                'stock_range' => [40, 120],
                'virtual_sales' => [2600, 6200],
                'real_sales_ratio' => 0.66,
                'is_recommend' => true,
                'is_hot' => true,
                'is_new' => true,
                'count' => 2,
                'sku_limit' => 4,
                'image_hint' => 'foldable',
                'warranty' => '30 个月长铰链质保',
            ],
            [
                'key' => 'lightweight_laptop',
                'category_path' => '数码电器 / 电脑办公 / 轻薄本',
                'display_name' => '超轻薄本',
                'series' => ['皓月', '星驰', '流光', '凌锋'],
                'model_codes' => ['Air 14', 'Air 16', 'Go 13'],
                'sub_title' => '全金属 1.2kg | 16:10 OLED | 70Wh 大电池',
                'description' => '为移动办公与差旅人群设计，轻薄机身内置长续航与高色域屏幕。',
                'detail_points' => [
                    '1.15kg 机身支持 180° 摊平',
                    '2.8K OLED 原彩校准，ΔE<1',
                    '全功能雷电 4 + 40Gbps 扩展坞',
                ],
                'attributes' => [
                    '处理器' => 'Intel Core Ultra 7 155H',
                    '屏幕' => '14.5" 2.8K OLED 500nits',
                    '续航' => '70Wh 电池，轻度使用 18 小时',
                ],
                'spec_schema' => [
                    '内存' => ['16GB', '32GB'],
                    '硬盘' => ['1TB SSD', '2TB SSD'],
                ],
                'price' => [
                    'base' => 629900,
                    'market_delta' => 70000,
                    'cost_ratio' => 0.59,
                    'increments' => [
                        '内存' => ['16GB' => 0, '32GB' => 70000],
                        '硬盘' => ['1TB SSD' => 0, '2TB SSD' => 90000],
                    ],
                ],
                'brand_tags' => ['laptop', 'digital'],
                'weight' => 1.35,
                'stock_range' => [60, 180],
                'virtual_sales' => [3100, 6800],
                'real_sales_ratio' => 0.7,
                'is_recommend' => true,
                'is_hot' => true,
                'is_new' => false,
                'count' => 2,
                'sku_limit' => 4,
                'image_hint' => 'laptop',
                'warranty' => '24 个月整机 + 1 年上门',
            ],
            [
                'key' => 'creator_laptop',
                'category_path' => '数码电器 / 电脑办公 / 创作者本',
                'display_name' => '创作本',
                'series' => ['玄铁', '云锦', '逐影'],
                'model_codes' => ['Studio 16', 'Studio 14'],
                'sub_title' => 'RTX 4070 移动独显 | MiniLED 原彩屏',
                'description' => '针对设计、后期与 3D 创作者调校，提供稳定的 GPU 性能与校色认证。',
                'detail_points' => [
                    '16" 4K MiniLED，1000nits 峰值亮度',
                    'Creator Dial 旋钮支持 Adobe 自定义',
                    '双风道 8 热管，满载功耗 160W 稳定输出',
                ],
                'attributes' => [
                    'GPU' => 'NVIDIA RTX 4070 Laptop',
                    '接口' => '双雷电4 + SD Express',
                    '屏幕色域' => '99% DCI-P3 ΔE<1',
                ],
                'spec_schema' => [
                    '显卡' => ['RTX 4060', 'RTX 4070'],
                    '硬盘' => ['1TB SSD', '2TB SSD'],
                ],
                'price' => [
                    'base' => 1099900,
                    'market_delta' => 120000,
                    'cost_ratio' => 0.57,
                    'increments' => [
                        '显卡' => ['RTX 4060' => 0, 'RTX 4070' => 180000],
                        '硬盘' => ['1TB SSD' => 0, '2TB SSD' => 120000],
                    ],
                ],
                'brand_tags' => ['laptop', 'digital'],
                'weight' => 2.3,
                'stock_range' => [30, 90],
                'virtual_sales' => [1500, 3600],
                'real_sales_ratio' => 0.6,
                'is_recommend' => true,
                'is_hot' => true,
                'is_new' => false,
                'count' => 1,
                'sku_limit' => 3,
                'image_hint' => 'creator-laptop',
                'warranty' => '24 个月整机 + 5×8 小时在线支持',
            ],
            [
                'key' => 'noise_cancel_headphones',
                'category_path' => '数码电器 / 影音娱乐 / 降噪耳机',
                'display_name' => '降噪耳机',
                'series' => ['澎湃', '灵澈', '曜声'],
                'model_codes' => ['Flow Pro', 'Silence Max', 'NC 2'],
                'sub_title' => '旗舰主动降噪 52dB | 空气导管舒压设计',
                'description' => '全天候出行与通勤佩戴体验，兼顾 Hi-Res 音质与多设备协同。',
                'detail_points' => [
                    '四麦阵列 + 自适应 ANC，噪声识别更精确',
                    '53mm 涂石墨烯振膜，低频有力',
                    '双蓝牙装置，电脑与手机无缝切换',
                ],
                'attributes' => [
                    '续航' => '开启降噪 35 小时',
                    '编码' => 'LDAC / LHDC / AAC',
                    '重量' => '280g 轻量头梁',
                ],
                'spec_schema' => [
                    '颜色' => ['曜岩黑', '雾霜白', '绚光铜'],
                    '耳垫材质' => ['蛋白皮', '亲肤织物'],
                ],
                'price' => [
                    'base' => 189900,
                    'market_delta' => 30000,
                    'cost_ratio' => 0.55,
                    'increments' => [
                        '颜色' => ['曜岩黑' => 0, '雾霜白' => 8000, '绚光铜' => 12000],
                        '耳垫材质' => ['蛋白皮' => 0, '亲肤织物' => 5000],
                    ],
                ],
                'brand_tags' => ['audio'],
                'weight' => 0.32,
                'stock_range' => [120, 280],
                'virtual_sales' => [2800, 7200],
                'real_sales_ratio' => 0.78,
                'is_recommend' => true,
                'is_hot' => true,
                'is_new' => false,
                'count' => 2,
                'sku_limit' => 5,
                'image_hint' => 'headphones',
                'warranty' => '18 个月音频延保',
            ],
            [
                'key' => 'smart_speaker',
                'category_path' => '数码电器 / 影音娱乐 / 智能音箱',
                'display_name' => '智能音箱',
                'series' => ['灵韵', '声塔'],
                'model_codes' => ['Sound Pro', 'Sound Beam'],
                'sub_title' => '全屋语音 + 空间音频 | 双频 Wi-Fi Mesh',
                'description' => '集成声学与 IoT 中枢于一体，支持 Matter 联动与多房间同步播放。',
                'detail_points' => [
                    '声学透光织物，环绕 8 扬声器矩阵',
                    '空间建模自动校准 EQ，客厅如影院',
                    '双芯片设计，语音控制独立低延迟',
                ],
                'attributes' => [
                    '协议' => 'AirPlay2 / Chromecast / Matter',
                    '麦克风' => '6 阵列远场拾音',
                    '功率' => '140W 峰值输出',
                ],
                'spec_schema' => [
                    '配色' => ['雾白', '夜航灰'],
                    '声道' => ['2.1 声道', '3D 全景声'],
                ],
                'price' => [
                    'base' => 169900,
                    'market_delta' => 32000,
                    'cost_ratio' => 0.5,
                    'increments' => [
                        '配色' => ['雾白' => 0, '夜航灰' => 8000],
                        '声道' => ['2.1 声道' => 0, '3D 全景声' => 60000],
                    ],
                ],
                'brand_tags' => ['audio', 'smart-home'],
                'weight' => 2.4,
                'stock_range' => [80, 220],
                'virtual_sales' => [1800, 4900],
                'real_sales_ratio' => 0.73,
                'is_recommend' => true,
                'is_hot' => false,
                'is_new' => true,
                'count' => 1,
                'sku_limit' => 3,
                'image_hint' => 'speaker',
                'warranty' => '24 个月整机',
            ],
            [
                'key' => 'smart_watch',
                'category_path' => '数码电器 / 智能生活 / 智能手表',
                'display_name' => '旗舰智能手表',
                'series' => ['羽光', '曜环', '脉动'],
                'model_codes' => ['Pulse', 'Runner', 'Pro'],
                'sub_title' => '双频 GPS | 睡眠呼吸监测 | eSIM 独立通话',
                'description' => '融合运动表现与健康管理，支持百种运动与离线地图导航。',
                'detail_points' => [
                    '蓝宝石镜面与钛合金表圈，100 米防水',
                    '全链路 AI 体能评估，训练建议实时反馈',
                    '独立 eSIM + Wi-Fi，离线音乐与支付都能用',
                ],
                'attributes' => [
                    '续航' => '14 天典型使用，开启 GPS 36 小时',
                    '表盘' => '1.43" AMOLED 1000nits',
                    '健康监测' => 'ECG + 血氧 + 呼吸率',
                ],
                'spec_schema' => [
                    '颜色' => ['曜夜黑', '光纱银', '赤茶金'],
                    '表带' => ['氟橡胶', '编织', '金属链'],
                ],
                'price' => [
                    'base' => 239900,
                    'market_delta' => 28000,
                    'cost_ratio' => 0.54,
                    'increments' => [
                        '颜色' => ['曜夜黑' => 0, '光纱银' => 12000, '赤茶金' => 18000],
                        '表带' => ['氟橡胶' => 0, '编织' => 9000, '金属链' => 32000],
                    ],
                ],
                'brand_tags' => ['wearable', 'digital'],
                'weight' => 0.15,
                'stock_range' => [90, 240],
                'virtual_sales' => [2400, 5600],
                'real_sales_ratio' => 0.74,
                'is_recommend' => true,
                'is_hot' => true,
                'is_new' => true,
                'count' => 2,
                'sku_limit' => 5,
                'image_hint' => 'watch',
                'warranty' => '24 个月主机 + 12 个月配件',
            ],
            [
                'key' => 'smart_home_kit',
                'category_path' => '数码电器 / 智能生活 / 智能家居',
                'display_name' => '智能家居套装',
                'series' => ['云岚', '星宿'],
                'model_codes' => ['Sense Kit', 'Guardian Kit'],
                'sub_title' => 'Matter 双模网关 | 多传感联动 | 本地自动化',
                'description' => '集门窗、温湿度、动静态传感于一体的全屋安防入门套装。',
                'detail_points' => [
                    '支持线程 + Zigbee 三模网关，离线也能联动',
                    '多场景模板，3 分钟完成入户、睡眠等模式设置',
                    '支持 HomeKit / 小程序 / FastAPI 同步控制',
                ],
                'attributes' => [
                    '套装内容' => '网关 + 2 门窗 + 2 温湿度 + 1 动态 + 1 漏水',
                    '协议' => 'Matter / Thread / Zigbee 3.0',
                    '自动化' => '本地脚本 + 云端备份',
                ],
                'spec_schema' => [
                    '网关' => ['Zigbee 3.0', 'Matter 双模'],
                    '传感器数量' => ['6 件套', '10 件套'],
                ],
                'price' => [
                    'base' => 299900,
                    'market_delta' => 40000,
                    'cost_ratio' => 0.52,
                    'increments' => [
                        '网关' => ['Zigbee 3.0' => 0, 'Matter 双模' => 50000],
                        '传感器数量' => ['6 件套' => 0, '10 件套' => 70000],
                    ],
                ],
                'brand_tags' => ['smart-home'],
                'weight' => 2.1,
                'stock_range' => [50, 150],
                'virtual_sales' => [1200, 3300],
                'real_sales_ratio' => 0.63,
                'is_recommend' => true,
                'is_hot' => false,
                'is_new' => true,
                'count' => 1,
                'sku_limit' => 3,
                'image_hint' => 'sensor',
                'warranty' => '24 个月主机 + 12 个月传感器',
            ],
            [
                'key' => 'smart_fridge',
                'category_path' => '家居生活 / 厨房电器 / 健康冰箱',
                'display_name' => '多门冰箱',
                'series' => ['晨牧', '云岭'],
                'model_codes' => ['Fresh 520', 'Pure 468'],
                'sub_title' => '零嵌对开门 | 分子保鲜 | AI 菜谱',
                'description' => '升级食材管理体验，提供独立母婴、果蔬与珍品区，支持语音联动烹饪。',
                'detail_points' => [
                    '零嵌深度 600mm，橱柜齐平更美观',
                    '双循环 + 离子杀菌，食材 7 天依旧鲜嫩',
                    '15.6" 屏幕智能菜谱，自动推算保质期',
                ],
                'attributes' => [
                    '容积' => '518L 四区独立变温',
                    '能效' => '一级能效，日耗电 0.85 度',
                    '压缩机' => '全直流变频',
                ],
                'spec_schema' => [
                    '颜色' => ['晨雾银', '曜夜黑'],
                    '容积' => ['428L', '518L'],
                ],
                'price' => [
                    'base' => 699900,
                    'market_delta' => 90000,
                    'cost_ratio' => 0.56,
                    'increments' => [
                        '颜色' => ['晨雾银' => 0, '曜夜黑' => 30000],
                        '容积' => ['428L' => 0, '518L' => 120000],
                    ],
                ],
                'brand_tags' => ['kitchen', 'appliance'],
                'weight' => 82.0,
                'stock_range' => [20, 60],
                'virtual_sales' => [900, 2200],
                'real_sales_ratio' => 0.58,
                'is_recommend' => true,
                'is_hot' => false,
                'is_new' => false,
                'count' => 1,
                'sku_limit' => 3,
                'image_hint' => 'fridge',
                'warranty' => '整机 3 年 + 压缩机 10 年',
            ],
            [
                'key' => 'air_fryer',
                'category_path' => '家居生活 / 厨房电器 / 厨房料理',
                'display_name' => '多功能空气炸锅',
                'series' => ['星灶', '云火'],
                'model_codes' => ['Chef 6', 'Chef 4'],
                'sub_title' => '双区同步烹饪 | 精准控温 40-220℃',
                'description' => '兼具空气炸、蒸烤与低温发酵模式，一机搞定健康餐桌。',
                'detail_points' => [
                    '双托盘独立控温，主菜与配菜同出锅',
                    '洞洞盘风道，油脂滤出率 85%',
                    'App 云菜谱，每周更新低卡菜单',
                ],
                'attributes' => [
                    '容量' => '6L + 4L 双腔',
                    '涂层' => 'FDA 认证陶瓷涂层',
                    '清洁' => '全拆洗支持洗碗机',
                ],
                'spec_schema' => [
                    '颜色' => ['砂岩白', '玄铁黑'],
                    '容量' => ['4L', '6L'],
                ],
                'price' => [
                    'base' => 79900,
                    'market_delta' => 18000,
                    'cost_ratio' => 0.52,
                    'increments' => [
                        '颜色' => ['砂岩白' => 0, '玄铁黑' => 5000],
                        '容量' => ['4L' => 0, '6L' => 20000],
                    ],
                ],
                'brand_tags' => ['kitchen', 'appliance'],
                'weight' => 7.2,
                'stock_range' => [120, 260],
                'virtual_sales' => [1800, 5200],
                'real_sales_ratio' => 0.81,
                'is_recommend' => true,
                'is_hot' => true,
                'is_new' => false,
                'count' => 1,
                'sku_limit' => 3,
                'image_hint' => 'air-fryer',
                'warranty' => '24 个月主机保修',
            ],
            [
                'key' => 'robot_vacuum',
                'category_path' => '家居生活 / 清洁电器 / 扫地机器人',
                'display_name' => '扫拖机器人',
                'series' => ['云岚 Sweep', '极昼 Sweep'],
                'model_codes' => ['2 Pro', 'X Ultra'],
                'sub_title' => '激光雷达导航 | 自动集尘洗拖一体基站',
                'description' => '适配复杂户型的高阶扫拖机，支持自清洁拖布与自动补水。',
                'detail_points' => [
                    '每秒 3000 次避障测距，轻松绕过电线',
                    '65℃ 热风烘干拖布，杜绝异味',
                    'App 绘制 3D 户型，可设置禁区与多楼层',
                ],
                'attributes' => [
                    '吸力' => '6000Pa 峰值',
                    '基站功能' => '集尘 + 洗拖 + 补水 + 烘干',
                    '电池' => '5200mAh，单次 200㎡',
                ],
                'spec_schema' => [
                    '基站' => ['自动集尘', '洗拖一体'],
                    '配色' => ['陶瓷白', '星夜灰'],
                ],
                'price' => [
                    'base' => 429900,
                    'market_delta' => 60000,
                    'cost_ratio' => 0.58,
                    'increments' => [
                        '基站' => ['自动集尘' => 0, '洗拖一体' => 120000],
                        '配色' => ['陶瓷白' => 0, '星夜灰' => 20000],
                    ],
                ],
                'brand_tags' => ['appliance', 'smart-home'],
                'weight' => 4.5,
                'stock_range' => [70, 180],
                'virtual_sales' => [2200, 5400],
                'real_sales_ratio' => 0.69,
                'is_recommend' => true,
                'is_hot' => true,
                'is_new' => true,
                'count' => 2,
                'sku_limit' => 4,
                'image_hint' => 'robot',
                'warranty' => '整机 2 年 + 基站 3 年',
            ],
            [
                'key' => 'sofa',
                'category_path' => '家居生活 / 家具软装 / 沙发',
                'display_name' => '模块化沙发',
                'series' => ['栖木', '沐澜'],
                'model_codes' => ['Cloud 3', 'Cloud 4'],
                'sub_title' => '模块随心组合 | 意大利科技布',
                'description' => '北欧线条搭配超柔科技布，支持可拆洗与模块扩展。',
                'detail_points' => [
                    '高回弹羽绒填充，坐感包裹',
                    '磁吸靠包，自由调节支撑',
                    '模块 5 分钟拆装，适配不同户型',
                ],
                'attributes' => [
                    '主材' => '欧洲进口落叶松框架',
                    '面料' => '第三代防污科技布',
                    '坐垫' => '高密度海绵 + 羽绒',
                ],
                'spec_schema' => [
                    '颜色' => ['云雾灰', '暮蓝'],
                    '尺寸' => ['三人位', '四人位+贵妃'],
                ],
                'price' => [
                    'base' => 899900,
                    'market_delta' => 150000,
                    'cost_ratio' => 0.52,
                    'increments' => [
                        '颜色' => ['云雾灰' => 0, '暮蓝' => 40000],
                        '尺寸' => ['三人位' => 0, '四人位+贵妃' => 200000],
                    ],
                ],
                'brand_tags' => ['furniture'],
                'weight' => 48.0,
                'stock_range' => [15, 40],
                'virtual_sales' => [400, 1100],
                'real_sales_ratio' => 0.55,
                'is_recommend' => true,
                'is_hot' => false,
                'is_new' => false,
                'count' => 1,
                'sku_limit' => 3,
                'image_hint' => 'sofa',
                'warranty' => '框架 10 年质保',
            ],
            [
                'key' => 'mattress',
                'category_path' => '家居生活 / 家具软装 / 床垫',
                'display_name' => '乳胶床垫',
                'series' => ['松澜', '若水'],
                'model_codes' => ['Balance', 'Breeze'],
                'sub_title' => '三区分压 | 进口乳胶 | 防螨抑菌',
                'description' => '睡眠实验室参与打样，兼顾支撑与包裹感的混合型床垫。',
                'detail_points' => [
                    '三层分区支撑，肩部柔软、腰臀承托',
                    '新西兰羊毛套层，全年透气',
                    '抗菌防螨率 99%，可拆洗外罩',
                ],
                'attributes' => [
                    '厚度' => '28cm',
                    '材质' => '6cm 泰国乳胶 + 独立袋弹簧',
                    '面料' => 'CoolTouch 冰爽面料',
                ],
                'spec_schema' => [
                    '尺寸' => ['1.5m', '1.8m'],
                    '硬度' => ['软适中', '偏硬'],
                ],
                'price' => [
                    'base' => 569900,
                    'market_delta' => 70000,
                    'cost_ratio' => 0.5,
                    'increments' => [
                        '尺寸' => ['1.5m' => 0, '1.8m' => 60000],
                        '硬度' => ['软适中' => 0, '偏硬' => 30000],
                    ],
                ],
                'brand_tags' => ['bedding'],
                'weight' => 32.0,
                'stock_range' => [25, 80],
                'virtual_sales' => [600, 1800],
                'real_sales_ratio' => 0.6,
                'is_recommend' => true,
                'is_hot' => true,
                'is_new' => false,
                'count' => 1,
                'sku_limit' => 4,
                'image_hint' => 'mattress',
                'warranty' => '15 年弹簧塌陷保修',
            ],
            [
                'key' => 'air_purifier',
                'category_path' => '家居生活 / 空气水健康 / 空气净化',
                'display_name' => '智能空气净化器',
                'series' => ['沐野', '涟漪'],
                'model_codes' => ['Pure 70', 'Pure 100'],
                'sub_title' => '五重滤芯 | VOC 监测 | PM1.0 精准显示',
                'description' => '为中大型客厅打造的旗舰净化器，内置 CO₂ 与 VOC 监测模块。',
                'detail_points' => [
                    'CADR 720m³/h，15 分钟净化 40㎡ 空间',
                    '全彩触控屏 + App 双端控制',
                    '滤芯芯片记录寿命，扫码即换',
                ],
                'attributes' => [
                    '滤芯寿命' => '4000 小时',
                    '噪音' => '睡眠模式 18dB',
                    '功能' => '紫外杀菌 + 甲醛分解',
                ],
                'spec_schema' => [
                    '滤芯' => ['HEPA13', '双重碳滤'],
                    '适用面积' => ['70㎡', '100㎡'],
                ],
                'price' => [
                    'base' => 259900,
                    'market_delta' => 35000,
                    'cost_ratio' => 0.53,
                    'increments' => [
                        '滤芯' => ['HEPA13' => 0, '双重碳滤' => 40000],
                        '适用面积' => ['70㎡' => 0, '100㎡' => 60000],
                    ],
                ],
                'brand_tags' => ['air'],
                'weight' => 9.5,
                'stock_range' => [60, 190],
                'virtual_sales' => [1600, 4200],
                'real_sales_ratio' => 0.67,
                'is_recommend' => true,
                'is_hot' => false,
                'is_new' => true,
                'count' => 1,
                'sku_limit' => 4,
                'image_hint' => 'air-purifier',
                'warranty' => '主机 3 年保修',
            ],
            [
                'key' => 'aroma_humidifier',
                'category_path' => '家居生活 / 空气水健康 / 香薰加湿',
                'display_name' => '香薰加湿器',
                'series' => ['暖澜', '雾岛'],
                'model_codes' => ['Aroma 2', 'Aroma 3'],
                'sub_title' => '超声波细雾 | 恒湿感应 | 氛围灯',
                'description' => '兼具香薰与加湿功能，适合卧室与办公桌面的静谧氛围。',
                'detail_points' => [
                    '纳米级雾化片，细雾不打湿桌面',
                    '恒湿模式自动控制在 55%',
                    '双层精油仓，支持定时混香',
                ],
                'attributes' => [
                    '水箱' => '3.5L 上加水设计',
                    '噪音' => '低至 25dB',
                    '功能' => '7 色氛围灯 + 智能定时',
                ],
                'spec_schema' => [
                    '颜色' => ['陶土橙', '云母白'],
                    '水箱容量' => ['2L', '3.5L'],
                ],
                'price' => [
                    'base' => 129900,
                    'market_delta' => 20000,
                    'cost_ratio' => 0.5,
                    'increments' => [
                        '颜色' => ['陶土橙' => 0, '云母白' => 8000],
                        '水箱容量' => ['2L' => 0, '3.5L' => 18000],
                    ],
                ],
                'brand_tags' => ['aroma', 'air', 'home'],
                'weight' => 1.8,
                'stock_range' => [80, 200],
                'virtual_sales' => [900, 2600],
                'real_sales_ratio' => 0.71,
                'is_recommend' => true,
                'is_hot' => true,
                'is_new' => true,
                'count' => 1,
                'sku_limit' => 3,
                'image_hint' => 'humidifier',
                'warranty' => '12 个月主机',
            ],
            [
                'key' => 'running_shoes',
                'category_path' => '潮流生活 / 运动户外 / 跑步装备',
                'display_name' => '碳板跑鞋',
                'series' => ['凌动', '逐风', '曜速'],
                'model_codes' => ['Pace 2', 'Carbon 1'],
                'sub_title' => '全掌碳板 | 超临界发泡中底 | 4mm 落差',
                'description' => '为马拉松 PB 与日常速度训练打造的轻量跑鞋。',
                'detail_points' => [
                    '第三代碳板回弹 85%',
                    '鞋面采用单层工程网布，透气轻盈',
                    '后跟稳定片+防滑大底，雨天也稳',
                ],
                'attributes' => [
                    '重量' => '205g (42 码)',
                    '中底' => 'Peba 超临界发泡',
                    '用途' => '全马竞速/速度训练',
                ],
                'spec_schema' => [
                    '颜色' => ['霓虹橙', '银河白', '天际蓝'],
                    '尺码' => ['39', '40', '41', '42', '43', '44'],
                ],
                'price' => [
                    'base' => 139900,
                    'market_delta' => 20000,
                    'cost_ratio' => 0.48,
                    'increments' => [
                        '颜色' => ['霓虹橙' => 0, '银河白' => 4000, '天际蓝' => 6000],
                        '尺码' => ['39' => 0, '40' => 0, '41' => 2000, '42' => 2000, '43' => 3000, '44' => 3000],
                    ],
                ],
                'brand_tags' => ['sports', 'running'],
                'weight' => 0.85,
                'stock_range' => [100, 320],
                'virtual_sales' => [2800, 7600],
                'real_sales_ratio' => 0.83,
                'is_recommend' => true,
                'is_hot' => true,
                'is_new' => false,
                'count' => 2,
                'sku_limit' => 6,
                'image_hint' => 'running-shoes',
                'warranty' => '90 天鞋面质量保证',
            ],
            [
                'key' => 'cycling_bike',
                'category_path' => '潮流生活 / 运动户外 / 骑行装备',
                'display_name' => '碳纤维公路车',
                'series' => ['逐日', '风切'],
                'model_codes' => ['Carbon SL', 'Carbon CL'],
                'sub_title' => 'Toray T1100 碳纤维 | Aero 设计 | 电子变速',
                'description' => '面向进阶骑士的轻量碳纤维整车，兼容竞速与长途骑行。',
                'detail_points' => [
                    '整车风阻降低 12%，平路更快',
                    '整合式车把与内走线，外观简洁',
                    '提供 Bike-Fit 数据服务，线上定制',
                ],
                'attributes' => [
                    '车重' => '7.1kg（M 码）',
                    '车轮' => '50mm 碳刀轮组',
                    '保修' => '终身车架质保',
                ],
                'spec_schema' => [
                    '车架' => ['碳纤维轻量', '碳纤维爬坡'],
                    '套件' => ['105 Di2', 'Ultegra Di2'],
                ],
                'price' => [
                    'base' => 2280000,
                    'market_delta' => 200000,
                    'cost_ratio' => 0.46,
                    'increments' => [
                        '车架' => ['碳纤维轻量' => 0, '碳纤维爬坡' => 250000],
                        '套件' => ['105 Di2' => 0, 'Ultegra Di2' => 480000],
                    ],
                ],
                'brand_tags' => ['cycling', 'sports'],
                'weight' => 7.5,
                'stock_range' => [8, 30],
                'virtual_sales' => [120, 420],
                'real_sales_ratio' => 0.52,
                'is_recommend' => true,
                'is_hot' => false,
                'is_new' => true,
                'count' => 1,
                'sku_limit' => 3,
                'image_hint' => 'bike',
                'warranty' => '终身车架 + 2 年组件',
            ],
            [
                'key' => 'travel_pack',
                'category_path' => '潮流生活 / 运动户外 / 户外旅行',
                'display_name' => '功能旅行背包',
                'series' => ['山岚', '原野'],
                'model_codes' => ['Trek 28', 'Trek 38'],
                'sub_title' => '模块化仓位 | X-Pac 面料 | 快取相机夹层',
                'description' => '满足周边徒步与城市通勤的双场景背包，兼顾摄影装备。',
                'detail_points' => [
                    '主仓 180° 展开，内置磁吸小包',
                    '背板支撑系统减负 20%',
                    '底部隐藏雨罩 + 行李杆固定',
                ],
                'attributes' => [
                    '面料' => 'X-Pac VX21 防雨',
                    '重量' => '1.4kg',
                    '承重' => '18kg',
                ],
                'spec_schema' => [
                    '颜色' => ['岩石灰', '苔藓绿'],
                    '容量' => ['28L', '38L'],
                ],
                'price' => [
                    'base' => 139900,
                    'market_delta' => 22000,
                    'cost_ratio' => 0.5,
                    'increments' => [
                        '颜色' => ['岩石灰' => 0, '苔藓绿' => 6000],
                        '容量' => ['28L' => 0, '38L' => 26000],
                    ],
                ],
                'brand_tags' => ['outdoor', 'travel'],
                'weight' => 1.4,
                'stock_range' => [70, 210],
                'virtual_sales' => [900, 2600],
                'real_sales_ratio' => 0.68,
                'is_recommend' => true,
                'is_hot' => false,
                'is_new' => false,
                'count' => 1,
                'sku_limit' => 4,
                'image_hint' => 'backpack',
                'warranty' => '5 年结构保修',
            ],
            [
                'key' => 'skincare_set',
                'category_path' => '潮流生活 / 美妆个护 / 护肤套装',
                'display_name' => '修护护肤套装',
                'series' => ['晨雾', '澄光'],
                'model_codes' => ['Bloom', 'Revive'],
                'sub_title' => '多肽修护科技 | 早晚双管 | 无酒精配方',
                'description' => '针对都市干敏肌，兼顾修护、提亮与抗氧化。',
                'detail_points' => [
                    '核心成分含有 6 种专利多肽',
                    '稳定型 VC 与神经酰胺协同',
                    'EWG 全绿评分，孕妇可用',
                ],
                'attributes' => [
                    '套装内容' => '洁面 + 精华 + 面霜 + 眼霜',
                    '肤感' => '轻乳霜质地，易吸收',
                    '香调' => '白茶青草调',
                ],
                'spec_schema' => [
                    '肤质' => ['干皮', '混合', '油皮'],
                    '规格' => ['标准装', '豪华礼盒'],
                ],
                'price' => [
                    'base' => 159900,
                    'market_delta' => 26000,
                    'cost_ratio' => 0.48,
                    'increments' => [
                        '肤质' => ['干皮' => 0, '混合' => 4000, '油皮' => 8000],
                        '规格' => ['标准装' => 0, '豪华礼盒' => 30000],
                    ],
                ],
                'brand_tags' => ['beauty'],
                'weight' => 0.9,
                'stock_range' => [90, 260],
                'virtual_sales' => [2200, 5600],
                'real_sales_ratio' => 0.76,
                'is_recommend' => true,
                'is_hot' => true,
                'is_new' => true,
                'count' => 2,
                'sku_limit' => 4,
                'image_hint' => 'skincare',
                'warranty' => '开封后 12 个月内最佳',
            ],
            [
                'key' => 'coffee_beans',
                'category_path' => '潮流生活 / 咖啡茶饮 / 精品咖啡',
                'display_name' => '精品咖啡豆',
                'series' => ['烘香', '赤道', '云栖'],
                'model_codes' => ['Roaster Pro', 'Origin 01'],
                'sub_title' => '直采庄园 | 水洗 & 日晒 | SCA 87+',
                'description' => '来自埃塞与哥伦比亚的拼配豆，适配手冲与意式。',
                'detail_points' => [
                    '产区信息透明，杯测分 87+',
                    '烘焙曲线全程记录，风味稳定',
                    '氮气充氛包装，锁鲜 12 个月',
                ],
                'attributes' => [
                    '烘焙度' => '多段浅中度',
                    '风味' => '柑橘、可可、焦糖',
                    '处理法' => '水洗 + 日晒',
                ],
                'spec_schema' => [
                    '烘焙度' => ['浅烘', '中烘', '中深烘'],
                    '包装规格' => ['250g', '500g'],
                ],
                'price' => [
                    'base' => 12900,
                    'market_delta' => 4000,
                    'cost_ratio' => 0.42,
                    'increments' => [
                        '烘焙度' => ['浅烘' => 0, '中烘' => 1000, '中深烘' => 1500],
                        '包装规格' => ['250g' => 0, '500g' => 6000],
                    ],
                ],
                'brand_tags' => ['coffee'],
                'weight' => 0.65,
                'stock_range' => [120, 400],
                'virtual_sales' => [2600, 6400],
                'real_sales_ratio' => 0.88,
                'is_recommend' => true,
                'is_hot' => true,
                'is_new' => false,
                'count' => 2,
                'sku_limit' => 5,
                'image_hint' => 'coffee',
                'warranty' => '最佳赏味期 12 个月',
            ],
        ];
    }
}
