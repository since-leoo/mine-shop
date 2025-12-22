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

namespace Plugin\Since\SystemMessage\Model;

use App\Model\Permission\User;
use Carbon\Carbon;
use Hyperf\Collection\Collection;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 模板ID，主键
 * @property string $name 模板名称
 * @property string $title 消息标题模板
 * @property string $content 消息内容模板
 * @property string $type 消息类型
 * @property string $format 内容格式
 * @property array $variables 可用变量列表
 * @property bool $is_active 是否启用
 * @property int $created_by 创建者
 * @property int $updated_by 更新者
 * @property Carbon $created_at 创建时间
 * @property Carbon $updated_at 更新时间
 * @property Carbon $deleted_at 删除时间
 * @property string $remark 备注
 * @property User $creator 创建者
 * @property User $updater 更新者
 * @property Collection|Message[] $messages 使用此模板的消息
 */
class MessageTemplate extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'message_templates';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [
        'name',
        'title',
        'content',
        'type',
        'format',
        'variables',
        'is_active',
        'created_by',
        'updated_by',
        'remark',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = [
        'id' => 'integer',
        'variables' => 'json',
        'is_active' => 'boolean',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * 消息类型常量
     */
    public const TYPE_SYSTEM = 'system';
    public const TYPE_ANNOUNCEMENT = 'announcement';
    public const TYPE_ALERT = 'alert';
    public const TYPE_REMINDER = 'reminder';

    /**
     * 内容格式常量
     */
    public const FORMAT_TEXT = 'text';
    public const FORMAT_HTML = 'html';
    public const FORMAT_MARKDOWN = 'markdown';

    /**
     * 创建者关联
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * 更新者关联
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    /**
     * 使用此模板的消息关联
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'template_id', 'id');
    }

    /**
     * 渲染模板
     */
    public function render(array $variables = []): array
    {
        $title = $this->renderString($this->title, $variables);
        $content = $this->renderString($this->content, $variables);

        return [
            'title' => $title,
            'content' => $content,
        ];
    }

    /**
     * 渲染字符串
     */
    protected function renderString(string $template, array $variables): string
    {
        $pattern = config('system_message.template.variable_pattern', '/\{\{(\w+)\}\}/');
        
        return preg_replace_callback($pattern, function ($matches) use ($variables) {
            $variableName = $matches[1];
            return $variables[$variableName] ?? $matches[0];
        }, $template);
    }

    /**
     * 预览模板
     */
    public function preview(array $variables = []): array
    {
        // 为预览提供默认变量值
        $defaultVariables = $this->getDefaultVariables();
        $mergedVariables = array_merge($defaultVariables, $variables);
        
        return $this->render($mergedVariables);
    }

    /**
     * 获取默认变量值（用于预览）
     */
    protected function getDefaultVariables(): array
    {
        $defaults = [];
        
        if (is_array($this->variables)) {
            foreach ($this->variables as $variable) {
                $defaults[$variable] = "[{$variable}]";
            }
        }
        
        return $defaults;
    }

    /**
     * 验证模板变量
     */
    public function validateVariables(array $variables): array
    {
        $errors = [];
        $requiredVariables = $this->getRequiredVariables();
        
        foreach ($requiredVariables as $variable) {
            if (!isset($variables[$variable]) || $variables[$variable] === '') {
                $errors[] = "缺少必需变量: {$variable}";
            }
        }
        
        return $errors;
    }

    /**
     * 获取模板中的必需变量
     */
    public function getRequiredVariables(): array
    {
        $pattern = config('system_message.template.variable_pattern', '/\{\{(\w+)\}\}/');
        $variables = [];
        
        // 从标题中提取变量
        preg_match_all($pattern, $this->title, $titleMatches);
        if (!empty($titleMatches[1])) {
            $variables = array_merge($variables, $titleMatches[1]);
        }
        
        // 从内容中提取变量
        preg_match_all($pattern, $this->content, $contentMatches);
        if (!empty($contentMatches[1])) {
            $variables = array_merge($variables, $contentMatches[1]);
        }
        
        return array_unique($variables);
    }

    /**
     * 更新模板变量列表
     */
    public function updateVariables(): bool
    {
        $variables = $this->getRequiredVariables();
        return $this->update(['variables' => $variables]);
    }

    /**
     * 检查模板是否可用
     */
    public function isAvailable(): bool
    {
        return $this->is_active && !$this->trashed();
    }

    /**
     * 启用模板
     */
    public function enable(): bool
    {
        return $this->update(['is_active' => true]);
    }

    /**
     * 禁用模板
     */
    public function disable(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * 获取模板使用统计
     */
    public function getUsageStats(): array
    {
        $totalUsage = $this->messages()->count();
        $recentUsage = $this->messages()->where('created_at', '>=', now()->subDays(30))->count();
        
        return [
            'total_usage' => $totalUsage,
            'recent_usage' => $recentUsage,
            'last_used_at' => $this->messages()->latest('created_at')->value('created_at'),
        ];
    }

    /**
     * 获取所有消息类型
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_SYSTEM => '系统消息',
            self::TYPE_ANNOUNCEMENT => '公告',
            self::TYPE_ALERT => '警报',
            self::TYPE_REMINDER => '提醒',
        ];
    }

    /**
     * 获取所有内容格式
     */
    public static function getFormats(): array
    {
        return [
            self::FORMAT_TEXT => '纯文本',
            self::FORMAT_HTML => 'HTML',
            self::FORMAT_MARKDOWN => 'Markdown',
        ];
    }

    /**
     * 作用域：按类型筛选
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * 作用域：按格式筛选
     */
    public function scopeOfFormat($query, string $format)
    {
        return $query->where('format', $format);
    }

    /**
     * 作用域：启用的模板
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 作用域：禁用的模板
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * 作用域：按创建者筛选
     */
    public function scopeByCreator($query, int $creatorId)
    {
        return $query->where('created_by', $creatorId);
    }

    /**
     * 作用域：最近创建的模板
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * 作用域：按名称搜索
     */
    public function scopeSearchByName($query, string $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    /**
     * 获取热门模板
     */
    public static function getPopularTemplates(int $limit = 10): Collection
    {
        return static::withCount('messages')
            ->where('is_active', true)
            ->orderBy('messages_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 复制模板
     */
    public function duplicate(string $newName = null): static
    {
        $newTemplate = $this->replicate();
        $newTemplate->name = $newName ?: $this->name . ' (副本)';
        $newTemplate->is_active = false; // 新复制的模板默认禁用
        $newTemplate->save();
        
        return $newTemplate;
    }
}