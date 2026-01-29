local lockKey = KEYS[1]
local stockKey = KEYS[2]
local quantity = tonumber(ARGV[1])
local lockTtl = tonumber(ARGV[2])

if redis.call("SET", lockKey, "1", "NX", "EX", lockTtl) == false then
    return -1
end

local currentStock = tonumber(redis.call("GET", stockKey) or 0)
if currentStock < quantity then
    redis.call("DEL", lockKey)
    return 0
end

redis.call("DECRBY", stockKey, quantity)
return 1
