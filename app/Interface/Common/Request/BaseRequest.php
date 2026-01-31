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

namespace App\Interface\Common\Request;

use App\Interface\Common\Request\Traits\NoAuthorizeTrait;
use Hyperf\HttpServer\Router\Dispatched;
use Hyperf\Validation\Request\FormRequest;

abstract class BaseRequest extends FormRequest
{
    use NoAuthorizeTrait;

    public function rules(): array
    {
        $method = $this->getActionMethod();
        $ruleMethod = $method . 'Rules';

        if (method_exists($this, $ruleMethod)) {
            return $this->{$ruleMethod}();
        }

        return [];
    }

    public function attributes(): array
    {
        $method = $this->getActionMethod();
        $attributeMethod = $method . 'Attributes';

        if (method_exists($this, $attributeMethod)) {
            return $this->{$attributeMethod}();
        }

        return $this->getCommonAttributes();
    }

    public function messages(): array
    {
        $method = $this->getActionMethod();
        $messageMethod = $method . 'Messages';

        if (method_exists($this, $messageMethod)) {
            return $this->{$messageMethod}();
        }

        return $this->getCommonMessages();
    }

    public function validated(): array
    {
        $validated = parent::validated();

        $method = $this->getActionMethod();
        $processMethod = 'process' . ucfirst($method) . 'Data';

        if (method_exists($this, $processMethod)) {
            return $this->{$processMethod}($validated);
        }

        return $validated;
    }

    protected function getActionMethod(): string
    {
        /**
         * @var null|Dispatched $dispatch
         */
        $dispatch = $this->getAttribute(Dispatched::class);
        $callback = $dispatch?->handler?->callback;

        if (\is_array($callback) && \count($callback) === 2) {
            return $callback[1];
        }

        if (\is_string($callback)) {
            if (str_contains($callback, '@')) {
                return explode('@', $callback)[1] ?? 'index';
            }
            if (str_contains($callback, '::')) {
                return explode('::', $callback)[1] ?? 'index';
            }
        }

        return 'index';
    }

    protected function getCommonAttributes(): array
    {
        return [];
    }

    protected function getCommonMessages(): array
    {
        return [];
    }
}
