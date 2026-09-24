<?php
declare(strict_types=1);
namespace Plugin\WecomScrm\Model;
use Hyperf\DbConnection\Model\Model;
final class WecomGroupChat extends Model { protected ?string $table = 'wecom_group_chats'; protected array $fillable = ['chat_id', 'name', 'owner', 'member_count', 'status', 'raw_data']; protected array $casts = ['raw_data' => 'array']; }
