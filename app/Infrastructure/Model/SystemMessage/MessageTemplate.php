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

namespace App\Infrastructure\Model\SystemMessage;

use App\Infrastructure\Model\Permission\User;
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
 */
class MessageTemplate extends Model
{
    use SoftDeletes;

    public const TYPE_SYSTEM = 'system';

    public const TYPE_ANNOUNCEMENT = 'announcement';

    public const TYPE_ALERT = 'alert';

    public const TYPE_REMINDER = 'reminder';

    public const FORMAT_TEXT = 'text';

    public const FORMAT_HTML = 'html';

    public const FORMAT_MARKDOWN = 'markdown';

    protected ?string $table = 'message_templates';

    protected array $fillable = [
        'name', 'title', 'content', 'type', 'format',
        'variables', 'is_active', 'created_by', 'updated_by', 'remark',
    ];

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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'template_id', 'id');
    }

    public function render(array $variables = []): array
    {
        $title = $this->renderString($this->title, $variables);
        $content = $this->renderString($this->content, $variables);
        return ['title' => $title, 'content' => $content];
    }

    public function preview(array $variables = []): array
    {
        $defaultVariables = $this->getDefaultVariables();
        $mergedVariables = array_merge($defaultVariables, $variables);
        return $this->render($mergedVariables);
    }

    public function validateVariables(array $variables): array
    {
        $errors = [];
        $requiredVariables = $this->getRequiredVariables();
        foreach ($requiredVariables as $variable) {
            if (! isset($variables[$variable]) || $variables[$variable] === '') {
                $errors[] = "缺少必需变量: {$variable}";
            }
        }
        return $errors;
    }

    public function getRequiredVariables(): array
    {
        $pattern = config('system_message.template.variable_pattern', '/\{\{(\w+)\}\}/');
        $variables = [];
        preg_match_all($pattern, $this->title, $titleMatches);
        if (! empty($titleMatches[1])) {
            $variables = array_merge($variables, $titleMatches[1]);
        }
        preg_match_all($pattern, $this->content, $contentMatches);
        if (! empty($contentMatches[1])) {
            $variables = array_merge($variables, $contentMatches[1]);
        }
        return array_unique($variables);
    }

    public function updateVariables(): bool
    {
        $variables = $this->getRequiredVariables();
        return $this->update(['variables' => $variables]);
    }

    public function isAvailable(): bool
    {
        return $this->is_active && ! $this->trashed();
    }

    public function enable(): bool
    {
        return $this->update(['is_active' => true]);
    }

    public function disable(): bool
    {
        return $this->update(['is_active' => false]);
    }

    public function getUsageStats(): array
    {
        $totalUsage = $this->messages()->count();
        $recentUsage = $this->messages()->where('created_at', '>=', Carbon::now()->subDays(30))->count();
        return [
            'total_usage' => $totalUsage,
            'recent_usage' => $recentUsage,
            'last_used_at' => $this->messages()->latest('created_at')->value('created_at'),
        ];
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_SYSTEM => '系统消息',
            self::TYPE_ANNOUNCEMENT => '公告',
            self::TYPE_ALERT => '警报',
            self::TYPE_REMINDER => '提醒',
        ];
    }

    public static function getFormats(): array
    {
        return [
            self::FORMAT_TEXT => '纯文本',
            self::FORMAT_HTML => 'HTML',
            self::FORMAT_MARKDOWN => 'Markdown',
        ];
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOfFormat($query, string $format)
    {
        return $query->where('format', $format);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeByCreator($query, int $creatorId)
    {
        return $query->where('created_by', $creatorId);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    public function scopeSearchByName($query, string $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    public static function getPopularTemplates(int $limit = 10): Collection
    {
        return static::withCount('messages')
            ->where('is_active', true)
            ->orderBy('messages_count', 'desc')
            ->limit($limit)
            ->get();
    }

    public function duplicate(?string $newName = null): static
    {
        $newTemplate = $this->replicate();
        $newTemplate->name = $newName ?: $this->name . ' (副本)';
        $newTemplate->is_active = false;
        $newTemplate->save();
        return $newTemplate;
    }

    protected function renderString(string $template, array $variables): string
    {
        $pattern = config('system_message.template.variable_pattern', '/\{\{(\w+)\}\}/');
        return preg_replace_callback($pattern, static function ($matches) use ($variables) {
            $variableName = $matches[1];
            return $variables[$variableName] ?? $matches[0];
        }, $template);
    }

    protected function getDefaultVariables(): array
    {
        $defaults = [];
        if (\is_array($this->variables)) {
            foreach ($this->variables as $variable) {
                $defaults[$variable] = "[{$variable}]";
            }
        }
        return $defaults;
    }
}
