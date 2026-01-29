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

namespace HyperfTests\Feature\Admin;

use App\Domain\Auth\Enum\Status;
use App\Infrastructure\Model\Permission\Menu;
use App\Infrastructure\Model\Permission\Role;
use App\Infrastructure\Model\Permission\User;
use Hyperf\DbConnection\Db;
use Hyperf\Stringable\Str;
use HyperfTests\HttpTestCase;

/**
 * @internal
 */
abstract class ControllerCase extends HttpTestCase
{
    use GetTokenTrait;

    protected User $user;

    protected string $token;

    protected Role $role;

    protected function setUp(): void
    {
        parent::setUp();
        User::truncate();
        Role::truncate();
        Db::table('user_belongs_role')->truncate();
        Db::table('role_belongs_menu')->truncate();
        $this->user = $this->generatorUser();
        $this->token = $this->getToken($this->user);
        $this->role = $this->generatorRole();
        $this->user->roles()->sync($this->role);
    }

    protected function tearDown(): void
    {
        $this->user->forceDelete();
        $this->role->forceDelete();
        parent::tearDown();
    }

    public function addPermissions(string ...$permission): bool
    {
        foreach ($permission as $code) {
            if (Menu::where('name', $code)->exists()) {
                $entity = Menu::where('name', $code)->first();
            } else {
                $entity = Menu::create([
                    'parent_id' => 0,
                    'name' => $code,
                    'code' => Str::random(10),
                    'icon' => Str::random(10),
                    'route' => Str::random(10),
                    'component' => Str::random(10),
                    'redirect' => Str::random(10),
                    'is_hidden' => rand(0, 1),
                    'type' => Str::random(1),
                    'status' => Status::Normal,
                    'sort' => rand(1, 100),
                    'remark' => Str::random(10),
                ]);
            }

            $this->role->menus()->sync($entity, false);
        }
        return true;
    }

    public function hasPermissions(string $code): bool
    {
        return $this->user->roles()->whereHas('menus', static fn ($query) => $query->where('name', $code))->exists();
    }

    public function forAddPermission(string $code): void
    {
        self::assertFalse($this->hasPermissions($code));
        self::assertTrue($this->addPermissions($code));
        self::assertTrue($this->hasPermissions($code));
    }

    public function deletePermissions(string ...$code): bool
    {
        foreach ($code as $item) {
            $entity = Menu::where('name', $item)->first();
            $this->role->menus()->detach($entity);
        }
        return true;
    }

    protected function authHeader(?string $token = null): array
    {
        return ['Authorization' => 'Bearer ' . ($token ?? $this->token)];
    }

    private function generatorRole(): Role
    {
        return Role::create([
            'name' => Str::random(10),
            'code' => Str::random(10),
            'sort' => rand(1, 100),
            'status' => rand(1, 2),
            'remark' => Str::random(),
        ]);
    }
}
