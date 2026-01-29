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

namespace Plugin\Since\SystemMessage\Controller\User;

use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Result;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use Plugin\Since\SystemMessage\Controller\AbstractController;
use Plugin\Since\SystemMessage\Request\UpdatePreferenceRequest;
use Plugin\Since\SystemMessage\Service\NotificationService;

#[Controller(prefix: 'plugin/api/system-message/preference')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
class PreferenceController extends AbstractController
{
    #[Inject]
    protected NotificationService $notificationService;

    /**
     * 获取用户通知偏好设置.
     */
    #[GetMapping('index')]
    public function index(): Result
    {
        $userId = $this->currentUser->id();

        try {
            $preference = $this->notificationService->getUserPreference($userId);

            // 如果用户没有偏好设置，返回默认值
            if (! $preference) {
                $preference = $this->notificationService->getDefaultPreferences();
                $preference['user_id'] = $userId;
            }

            return $this->success($preference);
        } catch (\Throwable $e) {
            return $this->error('获取偏好设置失败：' . $e->getMessage());
        }
    }

    /**
     * 更新用户通知偏好设置.
     */
    #[PutMapping('update')]
    public function update(UpdatePreferenceRequest $request): Result
    {
        $userId = $this->currentUser->id();
        $data = $request->validated();

        try {
            $preference = $this->notificationService->updateUserPreference($userId, $data);

            return $this->success($preference, '偏好设置更新成功');
        } catch (\Throwable $e) {
            return $this->error('更新偏好设置失败：' . $e->getMessage());
        }
    }

    /**
     * 重置用户通知偏好设置为默认值
     */
    #[PostMapping('reset')]
    public function reset(): Result
    {
        $userId = $this->currentUser->id();

        try {
            $preference = $this->notificationService->resetUserPreference($userId);

            return $this->success($preference, '偏好设置已重置为默认值');
        } catch (\Throwable $e) {
            return $this->error('重置偏好设置失败：' . $e->getMessage());
        }
    }

    /**
     * 获取默认通知偏好设置.
     */
    #[GetMapping('defaults')]
    public function getDefaults(): Result
    {
        try {
            $defaults = $this->notificationService->getDefaultPreferences();

            return $this->success($defaults);
        } catch (\Throwable $e) {
            return $this->error('获取默认设置失败：' . $e->getMessage());
        }
    }

    /**
     * 更新渠道偏好设置.
     */
    #[PutMapping('updateChannels')]
    public function updateChannelPreferences(): Result
    {
        $userId = $this->currentUser->id();
        $channels = $this->request->input('channels', []);

        if (empty($channels) || ! \is_array($channels)) {
            return $this->error('渠道设置不能为空');
        }

        try {
            $result = $this->notificationService->updateChannelPreferences($userId, $channels);

            return $this->success($result, '渠道偏好设置更新成功');
        } catch (\Throwable $e) {
            return $this->error('更新渠道偏好失败：' . $e->getMessage());
        }
    }

    /**
     * 更新消息类型偏好设置.
     */
    #[PutMapping('updateTypes')]
    public function updateTypePreferences(): Result
    {
        $userId = $this->currentUser->id();
        $types = $this->request->input('types', []);

        if (empty($types) || ! \is_array($types)) {
            return $this->error('消息类型设置不能为空');
        }

        try {
            $result = $this->notificationService->updateTypePreferences($userId, $types);

            return $this->success($result, '消息类型偏好设置更新成功');
        } catch (\Throwable $e) {
            return $this->error('更新消息类型偏好失败：' . $e->getMessage());
        }
    }

    /**
     * 设置免打扰时间.
     */
    #[PutMapping('setDoNotDisturbTime')]
    public function setDoNotDisturbTime(): Result
    {
        $userId = $this->currentUser->id();
        $startTime = $this->request->input('start_time');
        $endTime = $this->request->input('end_time');
        $enabled = (bool) $this->request->input('enabled', true);

        if (! $startTime || ! $endTime) {
            return $this->error('开始时间和结束时间不能为空');
        }

        try {
            $result = $this->notificationService->setDoNotDisturbTime($userId, $startTime, $endTime, $enabled);

            return $this->success($result, '免打扰时间设置成功');
        } catch (\Throwable $e) {
            return $this->error('设置免打扰时间失败：' . $e->getMessage());
        }
    }

    /**
     * 启用/禁用免打扰.
     */
    #[PutMapping('toggleDoNotDisturb')]
    public function toggleDoNotDisturb(): Result
    {
        $userId = $this->currentUser->id();
        $enabled = (bool) $this->request->input('enabled');

        try {
            $result = $this->notificationService->toggleDoNotDisturb($userId, $enabled);

            return $this->success($result, $enabled ? '免打扰已启用' : '免打扰已禁用');
        } catch (\Throwable $e) {
            return $this->error('切换免打扰状态失败：' . $e->getMessage());
        }
    }

    /**
     * 设置最小优先级.
     */
    #[PutMapping('setMinPriority')]
    public function setMinPriority(): Result
    {
        $userId = $this->currentUser->id();
        $priority = (int) $this->request->input('priority');

        if ($priority < 1 || $priority > 5) {
            return $this->error('优先级必须在1-5之间');
        }

        try {
            $result = $this->notificationService->setMinPriority($userId, $priority);

            return $this->success($result, '最小优先级设置成功');
        } catch (\Throwable $e) {
            return $this->error('设置最小优先级失败：' . $e->getMessage());
        }
    }

    /**
     * 检查是否在免打扰时间内.
     */
    #[GetMapping('checkDoNotDisturb')]
    public function checkDoNotDisturb(): Result
    {
        $userId = $this->currentUser->id();

        try {
            $isActive = $this->notificationService->isDoNotDisturbActive($userId);

            return $this->success(['is_active' => $isActive]);
        } catch (\Throwable $e) {
            return $this->error('检查免打扰状态失败：' . $e->getMessage());
        }
    }
}
