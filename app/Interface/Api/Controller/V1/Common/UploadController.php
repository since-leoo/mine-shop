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

namespace App\Interface\Api\Controller\V1\Common;

use App\Application\Admin\Infrastructure\AppAttachmentCommandService;

use App\Infrastructure\Error\AttachmentError;
use App\Interface\Api\Middleware\ApiSignatureMiddleware;
use App\Interface\Api\Middleware\TokenMiddleware;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\CurrentMember;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Symfony\Component\Finder\SplFileInfo;

#[Controller(prefix: '/api/v1/upload')]
#[Middleware(ApiSignatureMiddleware::class)]
#[Middleware(TokenMiddleware::class)]
final class UploadController extends AbstractController
{
    public function __construct(
        private readonly AppAttachmentCommandService $attachmentService,
        private readonly CurrentMember $currentMember,
    ) {}

    /**
     * 小程序文件上传.
     */
    #[PostMapping(path: 'image')]
    public function image(RequestInterface $request): Result
    {
        $uploadFile = $request->file('file');
        if (! $uploadFile || ! $uploadFile->isValid()) {
            throw AttachmentError::FILE_REQUIRED->invalid();
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (! \in_array($uploadFile->getMimeType(), $allowedMimes, true)) {
            throw AttachmentError::IMAGE_FORMAT_UNSUPPORTED->invalid();
        }

        $maxSize = 5 * 1024 * 1024;
        if ($uploadFile->getSize() > $maxSize) {
            throw AttachmentError::IMAGE_TOO_LARGE->invalid();
        }

        $newTmpPath = sys_get_temp_dir() . '/' . uniqid() . '.' . $uploadFile->getExtension();
        $uploadFile->moveTo($newTmpPath);
        $splFileInfo = new SplFileInfo($newTmpPath, '', '');

        $entity = $this->attachmentService->upload($splFileInfo, $uploadFile, $this->currentMember->id());

        return $this->success([
            'url' => $entity->getUrl(),
        ], '上传成功');
    }
}
