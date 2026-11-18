<?php

declare(strict_types=1);

namespace App\Infrastructure\Library\DataPermission\Aspects;

use App\Infrastructure\Library\DataPermission\Attribute\DataScope;
use App\Infrastructure\Library\DataPermission\Context as ScopeContext;
use App\Infrastructure\Library\DataPermission\Factory;
use App\Infrastructure\Library\DataPermission\OwnedTables;
use App\Infrastructure\Library\DataPermission\ScopeType;
use App\Interface\Common\CurrentUser;
use Hyperf\Context\Context;
use Hyperf\Database\Query\Builder;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;

#[Aspect]
final class DataScopeAspect extends AbstractAspect
{
    public const CONTEXT_KEY = 'data_permission';
    private const BUILDING = 'data_permission_building';
    public array $annotations = [DataScope::class];
    public array $classes = [Builder::class . '::runSelect', Builder::class . '::cursor', Builder::class . '::exists', Builder::class . '::update', Builder::class . '::delete'];

    public function __construct(private readonly Factory $factory) {}

    public function process(ProceedingJoinPoint $point)
    {
        $metadata = $point->getAnnotationMetadata();
        $scope = $metadata->method[DataScope::class] ?? $metadata->class[DataScope::class] ?? null;
        if ($scope !== null) {
            $previous = $this->snapshot();
            try {
                Context::set(self::CONTEXT_KEY, true);
                ScopeContext::setDeptColumn($scope->getDeptColumn());
                ScopeContext::setCreatedByColumn($scope->getCreatedByColumn());
                ScopeContext::setScopeType($scope->getScopeType());
                ScopeContext::setOnlyTables($scope->getOnlyTables());
                return $point->process();
            } finally {
                $this->restore($previous);
            }
        }
        $query = $point->getInstance();
        $user = CurrentUser::ctxUser();
        if (! $query instanceof Builder || $user === null || Context::get(self::BUILDING, false) || (! Context::get('data_permission_enabled', false) && ! Context::get(self::CONTEXT_KEY, false))) {
            return $point->process();
        }
        $wheres = $query->wheres;
        $bindings = $query->bindings;
        $unions = $query->unions;
        try {
            Context::set(self::BUILDING, true);
            try {
                $this->constrain($query, $user);
            } finally {
                Context::destroy(self::BUILDING);
            }
            return $point->process();
        } finally {
            $query->wheres = $wheres;
            $query->bindings = $bindings;
            $query->unions = $unions;
        }
    }

    public function constrain(Builder $query, \App\Infrastructure\Model\Permission\User $user): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }
        if (is_string($query->from) && preg_match('/^([a-zA-Z0-9_]+)(?:\s+(?:as\s+)?([a-zA-Z0-9_]+))?$/i', $query->from, $match)) {
            $table = $match[1];
            $alias = $match[2] ?? $table;
            $explicit = Context::get(self::CONTEXT_KEY, false) && in_array($table, ScopeContext::getOnlyTables(), true);
            if ($explicit || in_array($table, OwnedTables::ROOTS, true) || isset(OwnedTables::CHILDREN[$table])) {
                // 将原有 OR 条件整体括起来，数据范围永远与整个查询取交集。
                if ($query->wheres !== []) {
                    $nested = $query->forNestedWhere();
                    $nested->wheres = $query->wheres;
                    $nested->bindings['where'] = $query->bindings['where'];
                    $query->wheres = [];
                    $query->bindings['where'] = [];
                    $query->addNestedWhereQuery($nested);
                }
                $previous = $this->snapshot();
                try {
                    if ($explicit) {
                        if (! str_contains(ScopeContext::getCreatedByColumn(), '.')) {
                            ScopeContext::setCreatedByColumn($alias . '.' . ScopeContext::getCreatedByColumn());
                        }
                        if (! str_contains(ScopeContext::getDeptColumn(), '.')) {
                            ScopeContext::setDeptColumn($alias . '.' . ScopeContext::getDeptColumn());
                        }
                    } else {
                        ScopeContext::setScopeType(ScopeType::CREATED_BY);
                        ScopeContext::setCreatedByColumn($alias . '.created_by');
                    }
                    if (! $explicit && isset(OwnedTables::CHILDREN[$table])) {
                        [$parent, $foreignKey] = OwnedTables::CHILDREN[$table];
                        $query->whereExists(function (Builder $owned) use ($parent, $foreignKey, $alias, $user): void {
                            $owned->selectRaw('1')->from($parent . ' as scope_owner')->whereColumn('scope_owner.id', $alias . '.' . $foreignKey);
                            ScopeContext::setCreatedByColumn('scope_owner.created_by');
                            $this->factory->build($owned, $user);
                        });
                    } else {
                        $this->factory->build($query, $user);
                    }
                } finally {
                    $this->restore($previous);
                }
            }
        }
        foreach ($query->unions ?? [] as $index => $union) {
            $query->unions[$index]['query'] = clone $union['query'];
            $this->constrain($query->unions[$index]['query'], $user);
            // 重建 union 绑定，包含每个分支追加的权限参数。
        }
        if ($query->unions !== null) {
            $query->bindings['union'] = [];
            foreach ($query->unions as $union) {
                $query->addBinding($union['query']->getBindings(), 'union');
            }
        }
    }

    private function snapshot(): array
    {
        return [Context::get(self::CONTEXT_KEY), ScopeContext::getDeptColumn(), ScopeContext::getCreatedByColumn(), ScopeContext::getScopeType(), ScopeContext::getOnlyTables()];
    }

    private function restore(array $state): void
    {
        $state[0] === null ? Context::destroy(self::CONTEXT_KEY) : Context::set(self::CONTEXT_KEY, $state[0]);
        ScopeContext::setDeptColumn($state[1]);
        ScopeContext::setCreatedByColumn($state[2]);
        ScopeContext::setScopeType($state[3]);
        ScopeContext::setOnlyTables($state[4]);
    }
}
