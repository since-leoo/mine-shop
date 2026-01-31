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

namespace Plugin\Since\SystemMessage\Facade {
    if (! class_exists(SystemMessage::class, false)) {
        class SystemMessage
        {
            public static function sendToUser(int $userId, string $title, string $content): void {}

            public static function sendToAll(string $title, string $content): void {}
        }
    }
}

namespace Plugin\Wechat\Interfaces {
    if (! interface_exists(MiniAppInterface::class, false)) {
        interface MiniAppInterface
        {
            /**
             * @return array<string, mixed>
             */
            public function silentAuthorize(string $code): array;

            /**
             * @return array<string, mixed>
             */
            public function performSilentLogin(string $code, string $encryptedData, string $iv): array;

            /**
             * @return array<string, mixed>
             */
            public function getPhoneNumber(string $code): array;
        }
    }
}
