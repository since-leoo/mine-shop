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

use App\Infrastructure\Model\Permission\Menu;
use App\Infrastructure\Model\Permission\Role;
use App\Interface\Common\ResultCode;
use Hyperf\Database\Model\ModelNotFoundException;
use Hyperf\Stringable\Str;
use HyperfTests\Feature\Admin\ControllerCase;

/**
 * @internal
 * @coversNothing
 */
final class RoleControllerTest extends ControllerCase
{
    public function testPageList(): void
    {
        $result = $this->get('/admin/role/list');
        self::assertSame($result['code'], ResultCode::UNAUTHORIZED->value);
        $result = $this->get('/admin/role/list', [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::FORBIDDEN->value);
        $this->forAddPermission('permission:role:index');
        $result = $this->get('/admin/role/list', [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);
        $this->deletePermissions('permission:role:index');
        $result = $this->get('/admin/role/list', [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::FORBIDDEN->value);
    }

    public function testCreate(): void
    {
        $attributes = [
            'name',
            'code',
            'sort',
            'status',
            'remark',
        ];
        $result = $this->post('/admin/role');
        self::assertSame($result['code'], ResultCode::UNAUTHORIZED->value);
        $this->forAddPermission('permission:role:save');
        foreach ($attributes as $attribute) {
            $result = $this->post('/admin/role', [$attribute => ''], $this->authHeader());
            self::assertSame($result['code'], ResultCode::UNPROCESSABLE_ENTITY->value);
        }
        $result = $this->post('/admin/role', [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::UNPROCESSABLE_ENTITY->value);
        $this->deletePermissions('permission:role:save');
        self::assertFalse($this->hasPermissions('permission:role:save'));
        $fill = [
            'name' => Str::random(10),
            'code' => Str::random(10),
            'sort' => rand(1, 100),
            'status' => rand(1, 2),
            'remark' => Str::random(),
        ];
        $result = $this->post('/admin/role', $fill, $this->authHeader());
        self::assertSame($result['code'], ResultCode::FORBIDDEN->value);
        $this->forAddPermission('permission:role:save');
        $result = $this->post('/admin/role', $fill, $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);
        $this->deletePermissions('permission:role:save');
        $result = $this->post('/admin/role', $fill, $this->authHeader());
        self::assertSame($result['code'], ResultCode::FORBIDDEN->value);
        $oldCode = $fill['code'];
        $fill['code'] = Str::random(10);
        $result = $this->post('/admin/role', $fill, $this->authHeader());
        self::assertSame($result['code'], ResultCode::FORBIDDEN->value);
        $entity = Role::query()->where('code', $oldCode)->first();
        self::assertNotNull($entity);
        self::assertSame($entity->name, $fill['name']);
        self::assertSame($entity->sort, $fill['sort']);
        self::assertSame($entity->status->value, $fill['status']);
        self::assertSame($entity->remark, $fill['remark']);
        self::assertSame($entity->code, $oldCode);
        $entity->forceDelete();
    }

    public function testSave(): void
    {
        $entity = Role::create([
            'name' => Str::random(10),
            'code' => Str::random(10),
            'sort' => rand(1, 100),
            'status' => rand(1, 2),
            'remark' => Str::random(),
        ]);
        $result = $this->put('/admin/role/' . $entity->id);
        self::assertSame($result['code'], ResultCode::UNAUTHORIZED->value);
        $this->forAddPermission('permission:role:update');
        $result = $this->put('/admin/role/' . $entity->id, [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::UNPROCESSABLE_ENTITY->value);
        $this->deletePermissions('permission:role:update');
        self::assertFalse($this->hasPermissions('permission:role:update'));
        $fill = [
            'name' => Str::random(10),
            'code' => Str::random(10),
            'sort' => rand(1, 100),
            'status' => rand(1, 2),
            'remark' => Str::random(),
        ];
        $result = $this->put('/admin/role/' . $entity->id, $fill, $this->authHeader());
        self::assertSame($result['code'], ResultCode::FORBIDDEN->value);
        $this->forAddPermission('permission:role:update');
        $result = $this->put('/admin/role/' . $entity->id, $fill, $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);
        $this->deletePermissions('permission:role:update');
        $result = $this->put('/admin/role/' . $entity->id, $fill, $this->authHeader());
        self::assertSame($result['code'], ResultCode::FORBIDDEN->value);
        $entity->refresh();
        self::assertSame($entity->name, $fill['name']);
        self::assertSame($entity->sort, $fill['sort']);
        self::assertSame($entity->status->value, $fill['status']);
        self::assertSame($entity->remark, $fill['remark']);
        $entity->forceDelete();
    }

    public function testDelete(): void
    {
        $entity = Role::create([
            'name' => Str::random(10),
            'code' => Str::random(10),
            'sort' => rand(1, 100),
            'status' => rand(1, 2),
            'remark' => Str::random(),
        ]);
        $result = $this->delete('/admin/role');
        self::assertSame($result['code'], ResultCode::UNAUTHORIZED->value);
        $result = $this->delete('/admin/role', [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::FORBIDDEN->value);
        $this->forAddPermission('permission:role:delete');
        $result = $this->delete('/admin/role', [$entity->id], $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);
        $this->deletePermissions('permission:role:delete');
        $result = $this->delete('/admin/role', [$entity->id], $this->authHeader());
        self::assertSame($result['code'], ResultCode::FORBIDDEN->value);
        $this->expectException(ModelNotFoundException::class);
        $entity->refresh();
    }

    public function testBatchGrantPermissionsForRole(): void
    {
        $menus = [
            Menu::create([
                'parent_id' => 0,
                'name' => Str::random(10),

                'icon' => Str::random(10),
                'route' => Str::random(10),
                'component' => Str::random(10),
                'redirect' => Str::random(10),
                'is_hidden' => rand(1, 2),
                'type' => Str::random(1),
                'status' => rand(1, 2),
                'sort' => rand(1, 100),
                'remark' => Str::random(10),
            ]),
            Menu::create([
                'parent_id' => 0,
                'name' => Str::random(10),

                'icon' => Str::random(10),
                'route' => Str::random(10),
                'component' => Str::random(10),
                'redirect' => Str::random(10),
                'is_hidden' => rand(1, 2),
                'type' => Str::random(1),
                'status' => rand(1, 2),
                'sort' => rand(1, 100),
                'remark' => Str::random(10),
            ]),
            Menu::create([
                'parent_id' => 0,
                'name' => Str::random(10),

                'icon' => Str::random(10),
                'route' => Str::random(10),
                'component' => Str::random(10),
                'redirect' => Str::random(10),
                'is_hidden' => rand(1, 2),
                'type' => Str::random(1),
                'status' => rand(1, 2),
                'sort' => rand(1, 100),
                'remark' => Str::random(10),
            ]),
        ];
        $names = array_column($menus, 'name');
        $role = Role::create([
            'name' => Str::random(10),
            'code' => Str::random(10),
            'sort' => rand(1, 100),
            'status' => rand(1, 2),
            'remark' => Str::random(),
        ]);
        $uri = '/admin/role/' . $role->id . '/permissions';
        $result = $this->put($uri);
        self::assertSame($result['code'], ResultCode::UNAUTHORIZED->value);
        $result = $this->put($uri, [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::FORBIDDEN->value);
        $result = $this->put($uri, ['permissions' => $names], $this->authHeader());
        self::assertSame($result['code'], ResultCode::FORBIDDEN->value);
        $this->forAddPermission('permission:role:setMenu');
        $this->forAddPermission('permission:role:getMenu');
        $result = $this->put($uri, ['permissions' => $names], $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);
        $result = $this->get('/admin/role/' . $role->id . '/permissions', [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);
        $role->forceDelete();
        Menu::query()->whereIn('name', $names)->forceDelete();
    }
}
