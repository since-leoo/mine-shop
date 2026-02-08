-- 原子扣减库存 Lua 脚本
-- KEYS[1] = stock hash key (e.g. product:stock or seckill:stock:123)
-- ARGV = [sku_id_1, quantity_1, sku_id_2, quantity_2, ...]
-- 返回: 1=成功, 0=库存不足

-- 第一轮：检查所有 SKU 库存是否充足
for i = 1, #ARGV, 2 do
    local field = ARGV[i]
    local quantity = tonumber(ARGV[i + 1])
    local current = tonumber(redis.call('HGET', KEYS[1], field) or 0)
    if current < quantity then
        return 0
    end
end

-- 第二轮：全部扣减
for i = 1, #ARGV, 2 do
    local field = ARGV[i]
    local quantity = tonumber(ARGV[i + 1])
    redis.call('HINCRBY', KEYS[1], field, -quantity)
end

return 1
