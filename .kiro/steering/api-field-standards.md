# API 字段规范与 Request 验证标准

## 字段命名权威

- 后端 API 接口字段为唯一权威标准，使用 snake_case 命名
- 小程序端（shopProgramMini）必须适配后端字段名，不允许后端做 camelCase 兼容映射
- Transformer 输出字段使用 camelCase 是为了前端展示，但 Request 入参必须是后端标准字段名
- 如果发现小程序端发送的字段名与后端不一致，修改小程序端代码适配后端，而不是后端做兼容

## Request 验证规则

- 每个写操作的 Request 必须严格验证所有字段
- `required` 字段不能用 `nullable` 替代，必填就是必填
- 字段类型必须明确声明：`string`、`integer`、`boolean`、`numeric`、`array` 等
- 字符串字段必须加 `max` 长度限制，与数据库字段长度对齐
- 数值字段必须加 `min`/`max` 范围限制
- 枚举字段必须用 `in:value1,value2` 限定可选值
- 禁止 `required_without` 引用自身字段（如 `'name' => 'required_without:name'`），这是无效规则
- 不要为了兼容前端的多种字段名而在验证规则中同时接受多个字段名

## DTO toDto() 方法

- `Request::toDto()` 直接从 `$this->validated()` 映射，不做字段名转换
- 如果前端字段名与后端不一致，应该修改前端，而不是在 toDto() 中做归一化
