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

use App\Domain\Catalog\Brand\Enum\BrandStatus;
use App\Domain\Catalog\Category\Enum\CategoryStatus;
use App\Domain\Catalog\Product\Enum\ProductStatus;
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
            $images[] = $this->petImageUrl($hint, 1200, 800, sprintf('%s-%s-%d', $hint, $slug, $i));
        }

        $records = [];
        foreach ($images as $index => $url) {
            $records[] = [
                'image_url' => $url,
                'alt_text' => sprintf('%s %s %d', $hint, '图片', $index + 1),
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
        $hint = end($path) ?: 'pet';
        return $this->petImageUrl((string) $hint, 640, 640, implode('-', $path));
    }

    private function petImageUrl(string $hint, int $width = 640, int $height = 640, string $seed = ''): string
    {
        $keywords = $this->petImageKeywords($hint);
        $lock = abs(crc32(($seed ?: $hint) . '|' . $keywords)) % 1000 + 1;

        return sprintf('https://loremflickr.com/%d/%d/%s?lock=%d', $width, $height, $keywords, $lock);
    }

    private function petImageKeywords(string $hint): string
    {
        $value = Str::lower($hint);
        $map = [
            '猫粮' => 'cat,pet,food',
            '狗粮' => 'dog,pet,food',
            '主粮' => 'pet,food',
            '冻干' => 'cat,pet,food',
            '罐头' => 'pet,food',
            '零食' => 'dog,pet,treat',
            '猫砂' => 'cat,litter,pet',
            '清洁' => 'pet,grooming',
            '洗护' => 'pet,grooming',
            '玩具' => 'pet,toy',
            '牵引' => 'dog,leash,pet',
            '出行' => 'pet,travel',
            '航空箱' => 'pet,carrier',
            '背包' => 'pet,carrier',
            '窝' => 'pet,bed',
            '猫爬架' => 'cat,pet,home',
            '饮水' => 'pet,bowl',
            '喂食' => 'pet,bowl',
            '保健' => 'pet,health',
            '驱虫' => 'pet,health',
            'cat-kibble' => 'cat,pet,food',
            'dog-kibble' => 'dog,pet,food',
            'cat-litter' => 'cat,litter,pet',
            'pet-grooming' => 'pet,grooming',
            'pet-bed' => 'pet,bed',
            'pet-carrier' => 'pet,carrier',
            'dog-leash' => 'dog,leash,pet',
            'pet-toy' => 'pet,toy',
            'pet-health' => 'pet,health',
        ];

        foreach ($map as $keyword => $result) {
            if (str_contains($value, Str::lower($keyword))) {
                return $result;
            }
        }

        return 'pet,cat,dog';
    }

    private function categoryTemplates(): array
    {
        return [
            [
                'name' => '猫咪用品',
                'icon' => 'ph:cat',
                'description' => '覆盖猫粮、猫砂、玩具和猫咪生活方式用品。',
                'children' => [
                    [
                        'name' => '猫粮主食',
                        'icon' => 'ph:bowl-food',
                        'description' => '幼猫、成猫、冻干双拼与功能粮。',
                        'children' => [
                            ['name' => '幼猫粮', 'icon' => 'ph:paw-print', 'description' => '高蛋白、易消化、适合幼猫成长阶段。'],
                            ['name' => '成猫粮', 'icon' => 'ph:paw-print-fill', 'description' => '日常主食、功能营养与冻干配方。'],
                        ],
                    ],
                    [
                        'name' => '猫砂清洁',
                        'icon' => 'ph:sparkle',
                        'description' => '猫砂、除臭、猫砂盆和清洁周边。',
                        'children' => [
                            ['name' => '混合猫砂', 'icon' => 'ph:drop', 'description' => '豆腐砂、膨润土和活性炭混合方案。'],
                            ['name' => '猫砂盆配件', 'icon' => 'ph:archive-box', 'description' => '猫砂盆、铲子、除臭盒与垫子。'],
                        ],
                    ],
                    [
                        'name' => '猫咪玩具',
                        'icon' => 'ph:ball',
                        'description' => '逗猫棒、互动球和解闷玩具。',
                        'children' => [
                            ['name' => '互动玩具', 'icon' => 'ph:game-controller', 'description' => '能量释放与陪伴互动玩具。'],
                            ['name' => '猫爬架窝垫', 'icon' => 'ph:armchair', 'description' => '猫爬架、睡垫和午睡小窝。'],
                        ],
                    ],
                ],
            ],
            [
                'name' => '狗狗用品',
                'icon' => 'ph:dog',
                'description' => '覆盖狗粮、牵引出行、训练互动与日常护理。',
                'children' => [
                    [
                        'name' => '狗粮主食',
                        'icon' => 'ph:bowl-food',
                        'description' => '小型犬、中大型犬和鲜肉粮。',
                        'children' => [
                            ['name' => '小型犬主粮', 'icon' => 'ph:bone', 'description' => '颗粒适配小型犬，适口性更高。'],
                            ['name' => '中大型犬主粮', 'icon' => 'ph:bone-fill', 'description' => '大颗粒、关节友好型配方。'],
                        ],
                    ],
                    [
                        'name' => '牵引出行',
                        'icon' => 'ph:person-simple-walk',
                        'description' => '胸背、牵引绳、外出包和拾便工具。',
                        'children' => [
                            ['name' => '胸背牵引', 'icon' => 'ph:person-simple-walk', 'description' => '舒适胸背与反光牵引绳。'],
                            ['name' => '外出随行', 'icon' => 'ph:backpack', 'description' => '水壶、拾便袋、车载垫和折叠碗。'],
                        ],
                    ],
                ],
            ],
            [
                'name' => '出行洗护',
                'icon' => 'ph:car',
                'description' => '适合外出、洗澡、美容和季节护理场景。',
                'children' => [
                    [
                        'name' => '宠物背包航空箱',
                        'icon' => 'ph:suitcase-rolling',
                        'description' => '猫包、狗包、航空箱和推车。',
                        'children' => [
                            ['name' => '航空箱', 'icon' => 'ph:package', 'description' => '稳固通风、可登机和短途出行。'],
                            ['name' => '外出背包', 'icon' => 'ph:backpack', 'description' => '高颜值、透气视窗、轻露营友好。'],
                        ],
                    ],
                    [
                        'name' => '洗护美容',
                        'icon' => 'ph:bathtub',
                        'description' => '宠物香波、护理喷雾、梳毛和修甲工具。',
                        'children' => [
                            ['name' => '香波护毛', 'icon' => 'ph:drop-half', 'description' => '低刺激、留香自然、敏感背可用。'],
                            ['name' => '梳毛修甲', 'icon' => 'ph:scissors', 'description' => '梳毛梳、指甲剪、除毛刷。'],
                        ],
                    ],
                ],
            ],
            [
                'name' => '健康养护',
                'icon' => 'ph:first-aid-kit',
                'description' => '营养保健、驱虫护理和居家健康辅助。',
                'children' => [
                    [
                        'name' => '营养保健',
                        'icon' => 'ph:heartbeat',
                        'description' => '关节、肠胃、毛发和免疫支持。',
                        'children' => [
                            ['name' => '关节营养', 'icon' => 'ph:activity', 'description' => '软骨素、鱼油和老龄犬猫养护。'],
                            ['name' => '肠胃调理', 'icon' => 'ph:flask', 'description' => '益生菌、化毛和日常肠胃护理。'],
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
                'name' => 'PawJoy',
                'description' => '专注猫狗主粮与零食研发的宠物食品品牌。',
                'logo' => 'https://api.dicebear.com/7.x/shapes/svg?seed=PawJoy&backgroundColor=f08a5d&scale=110',
                'website' => 'https://pawjoy.example.com',
                'tags' => ['cat-food', 'dog-food', 'pet-food'],
            ],
            [
                'name' => 'WhiskerCare',
                'description' => '面向猫咪家庭的清洁、护理和居家用品品牌。',
                'logo' => 'https://api.dicebear.com/7.x/shapes/svg?seed=WhiskerCare&backgroundColor=8ecdb5&scale=110',
                'website' => 'https://whiskercare.example.com',
                'tags' => ['cat', 'cat-litter', 'pet-grooming', 'pet-bed'],
            ],
            [
                'name' => 'BuddyTail',
                'description' => '狗狗出行、牵引与互动玩具设计品牌。',
                'logo' => 'https://api.dicebear.com/7.x/shapes/svg?seed=BuddyTail&backgroundColor=a9d6f5&scale=110',
                'website' => 'https://buddytail.example.com',
                'tags' => ['dog', 'dog-leash', 'pet-toy', 'pet-travel'],
            ],
            [
                'name' => 'NoseLab',
                'description' => '专注宠物营养保健、肠胃护理和驱虫辅助。',
                'logo' => 'https://api.dicebear.com/7.x/shapes/svg?seed=NoseLab&backgroundColor=d9b26f&scale=110',
                'website' => 'https://noselab.example.com',
                'tags' => ['pet-health', 'pet-food'],
            ],
            [
                'name' => 'PetVoyage',
                'description' => '覆盖宠物航空箱、外出背包和旅居生活用品。',
                'logo' => 'https://api.dicebear.com/7.x/shapes/svg?seed=PetVoyage&backgroundColor=5ea488&scale=110',
                'website' => 'https://petvoyage.example.com',
                'tags' => ['pet-carrier', 'pet-travel', 'dog-leash'],
            ],
            [
                'name' => 'MiaoMiao Home',
                'description' => '偏居家审美的猫爬架、窝垫与饮水用品品牌。',
                'logo' => 'https://api.dicebear.com/7.x/shapes/svg?seed=MiaoMiaoHome&backgroundColor=f6b48e&scale=110',
                'website' => 'https://miaomiao-home.example.com',
                'tags' => ['cat', 'pet-bed', 'cat-litter'],
            ],
        ];
    }


    private function productTemplates(): array
    {
        return [
            [
                'key' => 'freeze_dried_cat_food',
                'category_path' => '猫咪用品 / 猫粮主食 / 成猫粮',
                'display_name' => '冻干双拼全价猫粮',
                'series' => ['森林厨房', '深海鲜宴', '轻盈肠胃'],
                'model_codes' => ['1.5kg', '2kg'],
                'sub_title' => '高蛋白冻干 | 呵护肠胃 | 挑嘴猫适口配方',
                'description' => '适合成猫日常喂养，兼顾肉含量、适口性与肠胃友好。',
                'detail_points' => [
                    '主打鲜鸡肉与鱼肉双蛋白结构，适口性更好。',
                    '添加益生元与膳食纤维，帮助日常肠胃调理。',
                    '颗粒大小适中，适合室内成猫长期食用。',
                ],
                'attributes' => [
                    '适用阶段' => '成猫通用',
                    '核心卖点' => '冻干双拼 + 高鲜肉含量',
                    '包装形式' => '自封袋锁鲜包装',
                ],
                'spec_schema' => [
                    '口味' => ['鸡肉三文鱼', '鸭肉鳕鱼', '兔肉火鸡'],
                    '规格' => ['1.5kg', '4lb'],
                ],
                'price' => [
                    'base' => 10900,
                    'market_delta' => 3000,
                    'cost_ratio' => 0.52,
                    'increments' => [
                        '口味' => ['鸡肉三文鱼' => 0, '鸭肉鳕鱼' => 300, '兔肉火鸡' => 500],
                        '规格' => ['1.5kg' => 0, '4lb' => 2000],
                    ],
                ],
                'brand_tags' => ['cat-food', 'pet-food'],
                'weight' => 1.6,
                'stock_range' => [120, 380],
                'virtual_sales' => [2200, 6800],
                'real_sales_ratio' => 0.78,
                'is_recommend' => true,
                'is_hot' => true,
                'is_new' => true,
                'count' => 2,
                'sku_limit' => 4,
                'image_hint' => 'cat-kibble',
                'warranty' => '未拆封保质期 18 个月',
            ],
            [
                'key' => 'active_carb_litter',
                'category_path' => '猫咪用品 / 猫砂清洁 / 混合猫砂',
                'display_name' => '活性炭混合猫砂',
                'series' => ['净味森林', '云朵轻砂'],
                'model_codes' => ['6L', '12L'],
                'sub_title' => '除臭锁味 | 低尘结团 | 适合多猫家庭',
                'description' => '适合追求低尘、强结团和日常除臭体验的猫家庭。',
                'detail_points' => [
                    '豆腐砂与膨润土复配，结团稳定不易散。',
                    '活性炭颗粒帮助吸附异味，适合封闭空间。',
                    '低尘配方更友好，换砂与铲砂更轻松。',
                ],
                'attributes' => [
                    '香型' => '原味 / 绿茶味',
                    '适用宠物' => '猫咪通用',
                    '清洁建议' => '每日铲砂，7-10 天彻底换新',
                ],
                'spec_schema' => [
                    '香型' => ['原味', '绿茶净味'],
                    '容量' => ['6L', '12L'],
                ],
                'price' => [
                    'base' => 2900,
                    'market_delta' => 1000,
                    'cost_ratio' => 0.46,
                    'increments' => [
                        '香型' => ['原味' => 0, '绿茶净味' => 200],
                        '容量' => ['6L' => 0, '12L' => 2200],
                    ],
                ],
                'brand_tags' => ['cat-litter', 'cat'],
                'weight' => 3.2,
                'stock_range' => [180, 420],
                'virtual_sales' => [1800, 5200],
                'real_sales_ratio' => 0.82,
                'is_recommend' => true,
                'is_hot' => true,
                'is_new' => false,
                'count' => 2,
                'sku_limit' => 4,
                'image_hint' => 'cat-litter',
                'warranty' => '请置于阴凉干燥处保存',
            ],
            [
                'key' => 'dog_harness',
                'category_path' => '狗狗用品 / 牵引出行 / 胸背牵引',
                'display_name' => '轻量反光胸背牵引套装',
                'series' => ['城市遛弯', '山野出行'],
                'model_codes' => ['S', 'M', 'L'],
                'sub_title' => '舒适贴合 | 夜间反光 | 胸背牵引一体',
                'description' => '适合城市遛狗、夜间散步和轻徒步场景。',
                'detail_points' => [
                    '胸背受力更均匀，减少颈部压力。',
                    '反光织带提升夜间散步安全性。',
                    '颜色活泼，适合做季节活动主视觉。',
                ],
                'attributes' => [
                    '材质' => '透气网布 + 加厚织带',
                    '适用场景' => '日常遛狗 / 短途旅行',
                    '特色' => '可调节胸围，穿脱便捷',
                ],
                'spec_schema' => [
                    '尺寸' => ['S', 'M', 'L'],
                    '颜色' => ['奶茶橘', '薄荷绿', '海盐蓝'],
                ],
                'price' => [
                    'base' => 4900,
                    'market_delta' => 1800,
                    'cost_ratio' => 0.47,
                    'increments' => [
                        '尺寸' => ['S' => 0, 'M' => 300, 'L' => 600],
                        '颜色' => ['奶茶橘' => 0, '薄荷绿' => 200, '海盐蓝' => 200],
                    ],
                ],
                'brand_tags' => ['dog-leash', 'pet-travel', 'dog'],
                'weight' => 0.35,
                'stock_range' => [100, 280],
                'virtual_sales' => [1500, 4200],
                'real_sales_ratio' => 0.77,
                'is_recommend' => true,
                'is_hot' => false,
                'is_new' => true,
                'count' => 2,
                'sku_limit' => 5,
                'image_hint' => 'dog-leash',
                'warranty' => '牵引织带一年内非人为断裂可换新',
            ],
            [
                'key' => 'pet_carrier',
                'category_path' => '出行洗护 / 宠物背包航空箱 / 外出背包',
                'display_name' => '透气视窗宠物外出背包',
                'series' => ['云朵舱', '小森林'],
                'model_codes' => ['标准款', '升级扩容款'],
                'sub_title' => '高颜值透气视窗 | 短途出行友好 | 轻露营搭子',
                'description' => '适合猫咪、小型犬日常通勤、看诊和短途旅行。',
                'detail_points' => [
                    '大面积透气视窗，宠物更安心。',
                    '背负减压设计，适合长时间携带。',
                    '适合搭配外出水壶、尿垫等场景购。',
                ],
                'attributes' => [
                    '适合宠物' => '猫咪 / 8kg 内小型犬',
                    '材质' => '防泼水面料 + 透气网布',
                    '场景' => '看诊、地铁通勤、短途出游',
                ],
                'spec_schema' => [
                    '款式' => ['标准款', '升级扩容款'],
                    '颜色' => ['米杏色', '薄荷绿'],
                ],
                'price' => [
                    'base' => 15900,
                    'market_delta' => 5000,
                    'cost_ratio' => 0.49,
                    'increments' => [
                        '款式' => ['标准款' => 0, '升级扩容款' => 3000],
                        '颜色' => ['米杏色' => 0, '薄荷绿' => 500],
                    ],
                ],
                'brand_tags' => ['pet-carrier', 'pet-travel'],
                'weight' => 1.8,
                'stock_range' => [60, 160],
                'virtual_sales' => [900, 2800],
                'real_sales_ratio' => 0.72,
                'is_recommend' => true,
                'is_hot' => false,
                'is_new' => true,
                'count' => 1,
                'sku_limit' => 4,
                'image_hint' => 'pet-carrier',
                'warranty' => '箱包类商品一年内结构问题可售后',
            ],
        ];
    }

}
