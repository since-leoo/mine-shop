<?php
declare(strict_types=1);
namespace Plugin\WecomScrm\Model;
use Hyperf\DbConnection\Model\Model;
final class WecomGroupSop extends Model
{
    protected ?string $table = 'wecom_group_sops';
    protected array $fillable = ['name', 'trigger', 'conditions', 'actions', 'enabled'];
    protected array $casts = ['conditions' => 'array', 'actions' => 'array', 'enabled' => 'boolean'];
}
