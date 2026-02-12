local lockKey = KEYS[1]
local stockKey = KEYS[2]
local quantity = tonumber(ARGV[1])

if quantity > 0 then
    redis.call("INCRBY", stockKey, quantity)
end
redis.call("DEL", lockKey)
return 1
