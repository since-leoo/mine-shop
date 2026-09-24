<?php
declare(strict_types=1);
namespace Plugin\WecomScrm\Model;
use Hyperf\DbConnection\Model\Model;
final class WecomBinding extends Model
{
    protected ?string $table = 'wecom_user_bindings';
    protected array $fillable = ['user_id', 'wecom_userid', 'external_userid', 'unionid', 'source'];
}
