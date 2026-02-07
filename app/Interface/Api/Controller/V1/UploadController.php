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

namespace App\Interface\Api\Controller\V1;

use App\Application\Commad\AppAttachmentCommandService;
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
            throw new \InvalidArgumentException('请选择要上传的文件');
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (! \in_array($uploadFile->getMimeType(), $allowedMimes, true)) {
            throw new \InvalidArgumentException('仅支持 jpg/png/gif/webp 格式图片');
        }

        $maxSize = 5 * 1024 * 1024;
        if ($uploadFile->getSize() > $maxSize) {
            throw new \InvalidArgumentException('图片大小不能超过 5MB');
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
