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

namespace App\Application\Admin\Infrastructure;

use App\Domain\Infrastructure\Attachment\Entity\AttachmentEntity;
use App\Domain\Infrastructure\Attachment\Repository\AttachmentRepository;
use App\Infrastructure\Abstract\IService;
use Mine\Upload\UploadInterface;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Finder\SplFileInfo;

/**
 * 附件命令服务：处理所有写操作.
 */
final class AppAttachmentCommandService extends IService
{
    public function __construct(
        public readonly AttachmentRepository $repository,
        private readonly AppAttachmentQueryService $queryService,
        private readonly UploadInterface $upload
    ) {}

    public function upload(SplFileInfo $fileInfo, UploadedFileInterface $uploadedFile, int $userId): AttachmentEntity
    {
        $fileHash = md5_file($fileInfo->getRealPath());
        $exists = $this->queryService->findByHash($fileHash);
        if ($exists) {
            return $exists;
        }

        $upload = $this->upload->upload($fileInfo);

        $entity = (new AttachmentEntity())
            ->setCreatedBy($userId)
            ->setOriginName($uploadedFile->getClientFilename())
            ->setStorageMode($upload->getStorageMode())
            ->setObjectName($upload->getObjectName())
            ->setMimeType($upload->getMimeType())
            ->setStoragePath($upload->getStoragePath())
            ->setHash($fileHash)
            ->setSuffix($upload->getSuffix())
            ->setSizeByte($upload->getSizeByte())
            ->setSizeInfo((string) $upload->getSizeInfo())
            ->setUrl($upload->getUrl());

        return $this->repository->save($entity);
    }

    public function delete(int $id): bool
    {
        $attachment = $this->queryService->find($id);
        $attachment || throw new \InvalidArgumentException(trans('attachment.attachment_not_exist'));
        return $this->repository->deleteByIds([$id]) > 0;
    }
}
