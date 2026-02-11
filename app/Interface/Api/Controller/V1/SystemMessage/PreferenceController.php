<?php

declare(strict_types=1);

namespace App\Interface\Api\Controller\V1\SystemMessage;

use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Result;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use App\Interface\Common\Controller\SystemMessageAbstractController;
use App\Interface\Admin\Request\SystemMessage\UpdatePreferenceRequest;
use App\Domain\Infrastructure\SystemMessage\Service\NotificationService;

#[Controller(prefix: 'api/v1/system-message/preference')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
class PreferenceController extends SystemMessageAbstractController
{
    #[Inject]
    protected NotificationService $notificationService;

    #[GetMapping('index')]
    public function index(): Result
    {
        $userId = $this->currentUser->id();
        try {
            $preference = $this->notificationService->getUserPreference($userId);
            if (! $preference) { $preference = $this->notificationService->getDefaultPreferences(); $preference['user_id'] = $userId; }
            return $this->success($preference);
        } catch (\Throwable $e) { return $this->error('获取偏好设置失败：' . $e->getMessage()); }
    }

    #[PutMapping('update')]
    public function update(UpdatePreferenceRequest $request): Result
    {
        $userId = $this->currentUser->id();
        try { return $this->success($this->notificationService->updateUserPreference($userId, $request->validated()), '偏好设置更新成功'); } catch (\Throwable $e) { return $this->error('更新偏好设置失败：' . $e->getMessage()); }
    }

    #[PostMapping('reset')]
    public function reset(): Result
    {
        $userId = $this->currentUser->id();
        try { return $this->success($this->notificationService->resetUserPreference($userId), '偏好设置已重置为默认值'); } catch (\Throwable $e) { return $this->error('重置偏好设置失败：' . $e->getMessage()); }
    }

    #[GetMapping('defaults')]
    public function getDefaults(): Result
    {
        try { return $this->success($this->notificationService->getDefaultPreferences()); } catch (\Throwable $e) { return $this->error('获取默认设置失败：' . $e->getMessage()); }
    }

    #[PutMapping('updateChannels')]
    public function updateChannelPreferences(): Result
    {
        $userId = $this->currentUser->id();
        $channels = $this->request->input('channels', []);
        if (empty($channels) || ! \is_array($channels)) { return $this->error('渠道设置不能为空'); }
        try { return $this->success($this->notificationService->updateChannelPreferences($userId, $channels), '渠道偏好设置更新成功'); } catch (\Throwable $e) { return $this->error('更新渠道偏好失败：' . $e->getMessage()); }
    }

    #[PutMapping('updateTypes')]
    public function updateTypePreferences(): Result
    {
        $userId = $this->currentUser->id();
        $types = $this->request->input('types', []);
        if (empty($types) || ! \is_array($types)) { return $this->error('消息类型设置不能为空'); }
        try { return $this->success($this->notificationService->updateTypePreferences($userId, $types), '消息类型偏好设置更新成功'); } catch (\Throwable $e) { return $this->error('更新消息类型偏好失败：' . $e->getMessage()); }
    }

    #[PutMapping('setDoNotDisturbTime')]
    public function setDoNotDisturbTime(): Result
    {
        $userId = $this->currentUser->id();
        $startTime = $this->request->input('start_time');
        $endTime = $this->request->input('end_time');
        $enabled = (bool) $this->request->input('enabled', true);
        if (! $startTime || ! $endTime) { return $this->error('开始时间和结束时间不能为空'); }
        try { return $this->success($this->notificationService->setDoNotDisturbTime($userId, $startTime, $endTime, $enabled), '免打扰时间设置成功'); } catch (\Throwable $e) { return $this->error('设置免打扰时间失败：' . $e->getMessage()); }
    }

    #[PutMapping('toggleDoNotDisturb')]
    public function toggleDoNotDisturb(): Result
    {
        $userId = $this->currentUser->id();
        $enabled = (bool) $this->request->input('enabled');
        try { return $this->success($this->notificationService->toggleDoNotDisturb($userId, $enabled), $enabled ? '免打扰已启用' : '免打扰已禁用'); } catch (\Throwable $e) { return $this->error('切换免打扰状态失败：' . $e->getMessage()); }
    }

    #[PutMapping('setMinPriority')]
    public function setMinPriority(): Result
    {
        $userId = $this->currentUser->id();
        $priority = (int) $this->request->input('priority');
        if ($priority < 1 || $priority > 5) { return $this->error('优先级必须在1-5之间'); }
        try { return $this->success($this->notificationService->setMinPriority($userId, $priority), '最小优先级设置成功'); } catch (\Throwable $e) { return $this->error('设置最小优先级失败：' . $e->getMessage()); }
    }

    #[GetMapping('checkDoNotDisturb')]
    public function checkDoNotDisturb(): Result
    {
        $userId = $this->currentUser->id();
        try { return $this->success(['is_active' => $this->notificationService->isDoNotDisturbActive($userId)]); } catch (\Throwable $e) { return $this->error('检查免打扰状态失败：' . $e->getMessage()); }
    }
}
