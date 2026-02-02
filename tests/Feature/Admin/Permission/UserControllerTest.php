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

namespace HyperfTests\Feature\Admin\Permission;

use App\Domain\Auth\Enum\Status;
use App\Domain\Auth\Enum\Type;
use App\Infrastructure\Model\Permission\Role;
use App\Infrastructure\Model\Permission\User;
use App\Interface\Common\ResultCode;
use Hyperf\Collection\Arr;
use Hyperf\Database\Model\ModelNotFoundException;
use Hyperf\Stringable\Str;
use HyperfTests\Feature\Admin\ControllerCase;

/**
 * @internal
 * @coversNothing
 */
final class UserControllerTest extends ControllerCase
{
    public function testPageList(): void
    {
        $noTokenResult = $this->get('/admin/user/list');
        self::assertSame(Arr::get($noTokenResult, 'code'), ResultCode::UNAUTHORIZED->value);

        $result = $this->get('/admin/user/list', [], $this->authHeader());
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        self::assertFalse($this->hasPermissions('permission:user:index'));
        self::assertTrue($this->addPermissions('permission:user:index'));
        self::assertTrue($this->hasPermissions('permission:user:index'));
        $result = $this->get('/admin/user/list', [], $this->authHeader());
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertSame(Arr::get($result, 'data.total'), User::count());
        $this->deletePermissions('permission:user:index');
        $result = $this->get('/admin/user/list', [], $this->authHeader());
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
    }

    public function testCreate(): void
    {
        $attributes = [
            'username',
            'user_type',
            'nickname',
        ];
        $this->forAddPermission('permission:user:save');
        foreach ($attributes as $attribute) {
            $result = $this->post('/admin/user', [$attribute => ''], $this->authHeader());
            self::assertSame(Arr::get($result, 'code'), ResultCode::UNPROCESSABLE_ENTITY->value);
        }
        $this->deletePermissions('permission:user:save');
        self::assertFalse($this->hasPermissions('permission:user:save'));
        $fillAttributes = [
            'username' => Str::random(),
            'user_type' => 100,
            'nickname' => Str::random(),
            'password' => Str::random(12),
        ];
        $result = $this->post('/admin/user', $fillAttributes);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        $result = $this->post('/admin/user', $fillAttributes, $this->authHeader());
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        self::assertFalse($this->hasPermissions('permission:user:save'));
        self::assertTrue($this->addPermissions('permission:user:save'));
        self::assertTrue($this->hasPermissions('permission:user:save'));
        $result = $this->post('/admin/user', $fillAttributes, $this->authHeader());
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertIsString($this->getToken(User::query()->where('username', $fillAttributes['username'])->first()));
        User::query()->where('username', $fillAttributes['username'])->forceDelete();
        $fillAttributes = [
            'username' => Str::random(),
            'user_type' => 100,
            'nickname' => Str::random(),
            'password' => Str::random(12),
            'phone' => Str::random(8),
            'email' => Str::random(10) . '@qq.com',
            'avatar' => 'https://www.baidu.com',
            'signed' => 'test',
            'status' => 1,
            'backend_setting' => ['test'],
            'remark' => 'test',
        ];
        $result = $this->post('/admin/user', $fillAttributes, $this->authHeader());
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertIsString($this->getToken(User::query()->where('username', $fillAttributes['username'])->first()));
        User::query()->where('username', $fillAttributes['username'])->forceDelete();
    }

    public function testDelete(): void
    {
        $user = User::create([
            'username' => Str::random(),
            'user_type' => 100,
            'nickname' => Str::random(),
        ]);
        $result = $this->delete('/admin/user', [], $this->authHeader());
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        self::assertFalse($this->hasPermissions('permission:user:delete'));
        self::assertTrue($this->addPermissions('permission:user:delete'));
        self::assertTrue($this->hasPermissions('permission:user:delete'));
        $result = $this->delete('/admin/user', [$user->getKey()], $this->authHeader());
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        $this->expectException(ModelNotFoundException::class);
        $user->refresh();
    }

    public function testSave(): void
    {
        $user = User::create([
            'username' => Str::random(),
            'user_type' => 100,
            'nickname' => Str::random(),
        ]);
        $result = $this->put('/admin/user/' . $user->id);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        $this->forAddPermission('permission:user:update');
        $result = $this->put('/admin/user/' . $user->id, [], $this->authHeader());
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNPROCESSABLE_ENTITY->value);
        $this->deletePermissions('permission:user:update');
        self::assertFalse($this->hasPermissions('permission:user:update'));
        $fillAttributes = [
            'username' => Str::random(),
            'user_type' => 100,
            'nickname' => Str::random(),
            'phone' => Str::random(8),
            'email' => Str::random(10) . '@qq.com',
            'avatar' => 'https://www.baidu.com',
            'signed' => 'test',
            'status' => 1,
            'backend_setting' => ['testxx'],
            'remark' => 'test',
        ];
        $result = $this->put('/admin/user/' . $user->id, $fillAttributes);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        $result = $this->put('/admin/user/' . $user->id, $fillAttributes, $this->authHeader());
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        self::assertFalse($this->hasPermissions('permission:user:update'));
        self::assertTrue($this->addPermissions('permission:user:update'));
        self::assertTrue($this->hasPermissions('permission:user:update'));
        $result = $this->put('/admin/user/' . $user->id, $fillAttributes, $this->authHeader());
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        $user->refresh();
        self::assertSame($user->username, $fillAttributes['username']);
        self::assertSame($user->user_type, Type::from($fillAttributes['user_type']));
        self::assertSame($user->nickname, $fillAttributes['nickname']);
        self::assertSame($user->phone, $fillAttributes['phone']);
        self::assertSame($user->email, $fillAttributes['email']);
        self::assertSame($user->avatar, $fillAttributes['avatar']);
        self::assertSame($user->signed, $fillAttributes['signed']);
        self::assertSame($user->status, Status::from($fillAttributes['status']));
        self::assertSame($user->backend_setting, $fillAttributes['backend_setting']);
        self::assertSame($user->remark, $fillAttributes['remark']);
        $user->forceDelete();
    }

    public function testUpdateInfo(): void
    {
        $user = $this->user;
        $result = $this->put('/admin/user');
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        $this->forAddPermission('permission:user:update');
        $result = $this->put('/admin/user', [], $this->authHeader());
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNPROCESSABLE_ENTITY->value);
        $this->deletePermissions('permission:user:update');
        self::assertFalse($this->hasPermissions('permission:user:update'));
        $fillAttributes = [
            'username' => Str::random(),
            'user_type' => 100,
            'nickname' => Str::random(),
            'phone' => Str::random(8),
            'email' => Str::random(10) . '@qq.com',
            'avatar' => 'https://www.baidu.com',
            'signed' => 'test',
            'status' => 1,
            'backend_setting' => ['testxx'],
            'remark' => 'test',
        ];
        $result = $this->put('/admin/user', $fillAttributes);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        $result = $this->put('/admin/user', $fillAttributes, $this->authHeader());
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        self::assertFalse($this->hasPermissions('permission:user:update'));
        self::assertTrue($this->addPermissions('permission:user:update'));
        self::assertTrue($this->hasPermissions('permission:user:update'));

        $result = $this->put('/admin/user', $fillAttributes, $this->authHeader());
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        $user->refresh();
        self::assertSame($user->username, $fillAttributes['username']);
        self::assertSame($user->user_type, Type::from($fillAttributes['user_type']));
        self::assertSame($user->nickname, $fillAttributes['nickname']);
        self::assertSame($user->phone, $fillAttributes['phone']);
        self::assertSame($user->email, $fillAttributes['email']);
        self::assertSame($user->avatar, $fillAttributes['avatar']);
        self::assertSame($user->signed, $fillAttributes['signed']);
        self::assertSame($user->status, Status::from($fillAttributes['status']));
        self::assertSame($user->backend_setting, $fillAttributes['backend_setting']);
        self::assertSame($user->remark, $fillAttributes['remark']);
        $user->forceDelete();
    }

    public function testResetPassword(): void
    {
        $user = $this->user;
        $oldPassword = $user->password;
        $result = $this->put('/admin/user/password');
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        $result = $this->put('/admin/user/password', [], $this->authHeader());
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        self::assertFalse($this->hasPermissions('permission:user:password'));
        self::assertTrue($this->addPermissions('permission:user:password'));
        self::assertTrue($this->hasPermissions('permission:user:password'));

        $result = $this->put('/admin/user/password', [], $this->authHeader());
        self::assertSame(Arr::get($result, 'code'), ResultCode::FAIL->value);
        $result = $this->put('/admin/user/password', ['id' => $user->id], $this->authHeader());
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        $user->refresh();
        self::assertNotSame($oldPassword, $user->password);
    }

    public function testBatchGrantRolesForUser(): void
    {
        $user = $this->user;
        $uri = '/admin/user/' . $user->id . '/roles';
        $roles = [
            Role::create([
                'name' => Str::random(22),
                'code' => Str::random(22),
                'sort' => rand(1, 100),
                'status' => Status::from(rand(1, 2)),
                'remark' => Str::random(),
            ]),
            Role::create([
                'name' => Str::random(22),
                'code' => Str::random(22),
                'sort' => rand(1, 100),
                'status' => Status::from(rand(1, 2)),
                'remark' => Str::random(),
            ]),
            Role::create([
                'name' => Str::random(22),
                'code' => Str::random(22),
                'sort' => rand(1, 100),
                'status' => Status::from(rand(1, 2)),
                'remark' => Str::random(),
            ]),
        ];
        $roles[] = $this->role;
        $roleIds = array_map(static fn ($role) => $role->id, $roles);
        $roleCodes = array_map(static fn ($role) => $role->code, $roles);
        $result = $this->put($uri, ['role_codes' => $roleCodes]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        $result = $this->put($uri, ['role_codes' => $roleCodes], $this->authHeader());
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        self::assertFalse($this->hasPermissions('permission:user:setRole'));
        self::assertTrue($this->addPermissions('permission:user:setRole'));
        self::assertTrue($this->hasPermissions('permission:user:setRole'));
        $result = $this->put($uri, ['role_codes' => $roleCodes], $this->authHeader());
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);

        self::assertFalse($this->hasPermissions('permission:user:getRole'));
        self::assertTrue($this->addPermissions('permission:user:getRole'));
        self::assertTrue($this->hasPermissions('permission:user:getRole'));

        $result = $this->get($uri, [], $this->authHeader());

        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertSame(\count(Arr::get($result, 'data')), \count($roles));
        $resultCodes = array_column(Arr::get($result, 'data'), 'code');
        foreach ($roles as $role) {
            self::assertContains($role->code, $resultCodes);
        }
        $user->refresh();
    }
}
