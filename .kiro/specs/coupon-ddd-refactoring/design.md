# Design Document: Coupon DDD Refactoring

## Overview

This design document outlines the refactoring of the Coupon module to align with Domain-Driven Design (DDD) architecture standards established in the project. The refactoring will introduce Contract interfaces, DTO classes, and standardized Entity creation/update patterns while preserving all existing business logic.

The refactoring follows the architectural pattern established in the Seckill module, ensuring consistency across the codebase. The key changes include:

- Introduction of Contract interfaces (CouponInput, CouponUserInput)
- Implementation of DTO classes (CouponDto, CouponUserDto)
- Addition of create() and update() methods to Entity classes
- Refactoring of Entity constructors to be parameterless
- Enhancement of Mapper classes with getNewEntity() methods
- Update of Domain Services to accept Contract interfaces
- Update of CommandServices to work with Contract interfaces
- Addition of toDto() methods to Request classes

## Architecture

### Layer Responsibilities

**Interface Layer (app/Interface/Admin/)**
- Request validation (format, type, length, required, range, enum)
- Request to DTO conversion via toDto() method
- HTTP response formatting

**Application Layer (app/Application/)**
- Transaction management
- Cache clearing
- Domain event publishing
- Orchestration of domain services

**Domain Layer (app/Domain/)**
- Business logic and rules
- Entity lifecycle management
- Domain service operations
- Contract interface definitions

**Infrastructure Layer (app/Infrastructure/)**
- Data persistence via Repositories
- Model to Entity conversion via Mappers
- External service integration

### Data Flow

```
Request → Request::toDto() → DTO (implements Contract) → CommandService → DomainService → Entity → Repository → Database
```

For queries:
```
Database → Repository → Model → Mapper::fromModel() → Entity → QueryService → Controller → Response
```

## Components and Interfaces

### Contract Interfaces

#### CouponInput Interface
Location: `app/Domain/Coupon/Contract/CouponInput.php`

```php
interface CouponInput
{
    public function getId(): int;
    public function getName(): ?string;
    public function getType(): ?string;
    public function getValue(): ?float;
    public function getMinAmount(): ?float;
    public function getTotalQuantity(): ?int;
    public function getPerUserLimit(): ?int;
    public function getStartTime(): ?string;
    public function getEndTime(): ?string;
    public function getStatus(): ?string;
    public function getDescription(): ?string;
}
```

#### CouponUserInput Interface
Location: `app/Domain/Coupon/Contract/CouponUserInput.php`

```php
interface CouponUserInput
{
    public function getId(): int;
    public function getCouponId(): ?int;
    public function getMemberId(): ?int;
    public function getOrderId(): ?int;
    public function getStatus(): ?string;
    public function getReceivedAt(): ?string;
    public function getUsedAt(): ?string;
    public function getExpireAt(): ?string;
}
```

### DTO Classes

#### CouponDto
Location: `app/Interface/Admin/DTO/Coupon/CouponDto.php`

```php
final class CouponDto implements CouponInput
{
    public ?int $id = null;
    public ?string $name = null;
    public ?string $type = null;
    public ?float $value = null;
    public ?float $minAmount = null;
    public ?int $totalQuantity = null;
    public ?int $perUserLimit = null;
    public ?string $startTime = null;
    public ?string $endTime = null;
    public ?string $status = null;
    public ?string $description = null;
    
    // Getter methods implementing CouponInput interface
}
```

#### CouponUserDto
Location: `app/Interface/Admin/DTO/Coupon/CouponUserDto.php`

```php
final class CouponUserDto implements CouponUserInput
{
    public ?int $id = null;
    public ?int $couponId = null;
    public ?int $memberId = null;
    public ?int $orderId = null;
    public ?string $status = null;
    public ?string $receivedAt = null;
    public ?string $usedAt = null;
    public ?string $expireAt = null;
    
    // Getter methods implementing CouponUserInput interface
}
```

### Entity Refactoring

#### CouponEntity Changes

**Constructor:**
```php
public function __construct() {
    // Initialize with defaults
    $this->id = 0;
    $this->usedQuantity = 0;
    $this->status = 'active';
}
```

**Create Method:**
```php
public function create(CouponInput $dto): self
{
    $this->name = $dto->getName();
    $this->type = $dto->getType();
    $this->value = $dto->getValue();
    $this->minAmount = $dto->getMinAmount() ?? 0.0;
    $this->totalQuantity = $dto->getTotalQuantity();
    $this->usedQuantity = 0;
    $this->perUserLimit = $dto->getPerUserLimit() ?? 1;
    $this->startTime = $dto->getStartTime();
    $this->endTime = $dto->getEndTime();
    $this->status = $dto->getStatus() ?? 'active';
    $this->description = $dto->getDescription();
    
    // Validate time window
    $this->ensureTimeWindowIsValid();
    
    return $this;
}
```

**Update Method:**
```php
public function update(CouponInput $dto): self
{
    if ($dto->getName() !== null) {
        $this->name = $dto->getName();
    }
    
    if ($dto->getType() !== null) {
        $this->type = $dto->getType();
    }
    
    if ($dto->getValue() !== null) {
        $this->value = $dto->getValue();
    }
    
    if ($dto->getMinAmount() !== null) {
        $this->minAmount = $dto->getMinAmount();
    }
    
    if ($dto->getTotalQuantity() !== null) {
        $this->totalQuantity = $dto->getTotalQuantity();
    }
    
    if ($dto->getPerUserLimit() !== null) {
        $this->perUserLimit = $dto->getPerUserLimit();
    }
    
    if ($dto->getStartTime() !== null) {
        $this->startTime = $dto->getStartTime();
    }
    
    if ($dto->getEndTime() !== null) {
        $this->endTime = $dto->getEndTime();
    }
    
    if ($dto->getStatus() !== null) {
        $this->status = $dto->getStatus();
    }
    
    if ($dto->getDescription() !== null) {
        $this->description = $dto->getDescription();
    }
    
    // Validate time window if times were updated
    if ($dto->getStartTime() !== null || $dto->getEndTime() !== null) {
        $this->ensureTimeWindowIsValid();
    }
    
    return $this;
}
```

**Preserved Business Logic Methods:**
- `defineTimeWindow(?string $startTime, ?string $endTime): self`
- `ensureTimeWindowIsValid(): self`
- `activate(): self`
- `deactivate(): self`
- `toggleStatus(): self`
- `assertActive(): self`
- `assertEffectiveAt(Carbon $now): self`
- `assertAvailableStock(int $availableQuantity, int $requestQuantity): self`
- `canMemberReceive(int $currentCount): bool`
- `resolveExpireAt(?string $customExpireAt, Carbon $now): Carbon`

#### CouponUserEntity Changes

**Constructor:**
```php
public function __construct() {
    $this->id = 0;
    $this->status = 'unused';
}
```

**Create Method:**
```php
public function create(CouponUserInput $dto): self
{
    $this->couponId = $dto->getCouponId();
    $this->memberId = $dto->getMemberId();
    $this->orderId = $dto->getOrderId();
    $this->status = $dto->getStatus() ?? 'unused';
    $this->receivedAt = $dto->getReceivedAt();
    $this->usedAt = $dto->getUsedAt();
    $this->expireAt = $dto->getExpireAt();
    
    return $this;
}
```

**Update Method:**
```php
public function update(CouponUserInput $dto): self
{
    if ($dto->getCouponId() !== null) {
        $this->couponId = $dto->getCouponId();
    }
    
    if ($dto->getMemberId() !== null) {
        $this->memberId = $dto->getMemberId();
    }
    
    if ($dto->getOrderId() !== null) {
        $this->orderId = $dto->getOrderId();
    }
    
    if ($dto->getStatus() !== null) {
        $this->status = $dto->getStatus();
    }
    
    if ($dto->getReceivedAt() !== null) {
        $this->receivedAt = $dto->getReceivedAt();
    }
    
    if ($dto->getUsedAt() !== null) {
        $this->usedAt = $dto->getUsedAt();
    }
    
    if ($dto->getExpireAt() !== null) {
        $this->expireAt = $dto->getExpireAt();
    }
    
    return $this;
}
```

**Preserved Business Logic Methods:**
- `static issue(int $couponId, int $memberId, string $receivedAt, string $expireAt): self`
- `markExpired(): self`
- `markUsed(?string $usedAt = null, ?int $orderId = null): self`

### Mapper Enhancements

#### CouponMapper

**Add getNewEntity() method:**
```php
public static function getNewEntity(): CouponEntity
{
    return new CouponEntity();
}
```

**Ensure fromModel() is complete:**
```php
public static function fromModel(Coupon $coupon): CouponEntity
{
    $entity = new CouponEntity();
    $entity->setId((int) $coupon->id);
    $entity->setName($coupon->name);
    $entity->setType($coupon->type);
    $entity->setValue($coupon->value !== null ? (float) $coupon->value : null);
    $entity->setMinAmount($coupon->min_amount !== null ? (float) $coupon->min_amount : null);
    $entity->setTotalQuantity($coupon->total_quantity !== null ? (int) $coupon->total_quantity : null);
    $entity->setUsedQuantity($coupon->used_quantity !== null ? (int) $coupon->used_quantity : null);
    $entity->setPerUserLimit($coupon->per_user_limit !== null ? (int) $coupon->per_user_limit : null);
    
    $startTime = $coupon->start_time ? $coupon->start_time->toDateTimeString() : null;
    $endTime = $coupon->end_time ? $coupon->end_time->toDateTimeString() : null;
    $entity->defineTimeWindow($startTime, $endTime);
    
    $entity->setStatus($coupon->status);
    $entity->setDescription($coupon->description);
    
    return $entity;
}
```

#### CouponUserMapper

**Add getNewEntity() method:**
```php
public static function getNewEntity(): CouponUserEntity
{
    return new CouponUserEntity();
}
```

### Request Layer Updates

#### CouponRequest

**Add toDto() method:**
```php
public function toDto(?int $id = null): CouponInput
{
    $params = $this->validated();
    $params['id'] = $id;
    
    // Convert snake_case to camelCase for DTO mapping
    if (isset($params['min_amount'])) {
        $params['minAmount'] = $params['min_amount'];
        unset($params['min_amount']);
    }
    
    if (isset($params['total_quantity'])) {
        $params['totalQuantity'] = $params['total_quantity'];
        unset($params['total_quantity']);
    }
    
    if (isset($params['per_user_limit'])) {
        $params['perUserLimit'] = $params['per_user_limit'];
        unset($params['per_user_limit']);
    }
    
    if (isset($params['start_time'])) {
        $params['startTime'] = $params['start_time'];
        unset($params['start_time']);
    }
    
    if (isset($params['end_time'])) {
        $params['endTime'] = $params['end_time'];
        unset($params['end_time']);
    }
    
    return Mapper::map($params, new CouponDto());
}
```

#### CouponUserRequest

**Add toDto() method:**
```php
public function toDto(?int $id = null): CouponUserInput
{
    $params = $this->validated();
    $params['id'] = $id;
    
    // Convert snake_case to camelCase for DTO mapping
    if (isset($params['coupon_id'])) {
        $params['couponId'] = $params['coupon_id'];
        unset($params['coupon_id']);
    }
    
    if (isset($params['member_id'])) {
        $params['memberId'] = $params['member_id'];
        unset($params['member_id']);
    }
    
    if (isset($params['order_id'])) {
        $params['orderId'] = $params['order_id'];
        unset($params['order_id']);
    }
    
    if (isset($params['received_at'])) {
        $params['receivedAt'] = $params['received_at'];
        unset($params['received_at']);
    }
    
    if (isset($params['used_at'])) {
        $params['usedAt'] = $params['used_at'];
        unset($params['used_at']);
    }
    
    if (isset($params['expire_at'])) {
        $params['expireAt'] = $params['expire_at'];
        unset($params['expire_at']);
    }
    
    return Mapper::map($params, new CouponUserDto());
}
```

### Domain Service Refactoring

#### CouponService

**Update create() method:**
```php
public function create(CouponInput $dto): bool
{
    $entity = CouponMapper::getNewEntity();
    $entity->create($dto);
    
    return (bool) $this->repository->createFromEntity($entity);
}
```

**Update update() method:**
```php
public function update(CouponInput $dto): bool
{
    $entity = $this->getEntity($dto->getId());
    $entity->update($dto);
    
    return $this->repository->updateFromEntity($entity);
}
```

**Ensure getEntity() exists:**
```php
public function getEntity(int $id): CouponEntity
{
    $coupon = $this->findById($id);
    if (!$coupon) {
        throw new BusinessException(ResultCode::FORBIDDEN, '优惠券不存在');
    }
    
    return CouponMapper::fromModel($coupon);
}
```

#### CouponUserService

**Update issue() method signature (keep implementation):**
The issue() method already creates entities properly using `CouponUserEntity::issue()`, so it doesn't need major changes. However, we should ensure it follows the pattern.

### CommandService Refactoring

#### CouponCommandService

**Update create() method:**
```php
public function create(CouponInput $dto): bool
{
    return $this->couponService->create($dto);
}
```

**Update update() method:**
```php
public function update(CouponInput $dto): bool
{
    // Verify coupon exists
    $this->queryService->find($dto->getId());
    
    return $this->couponService->update($dto);
}
```

**Update toggleStatus() method:**
```php
public function toggleStatus(int $id): bool
{
    $entity = $this->couponService->getEntity($id);
    return $this->couponService->toggleStatus($entity);
}
```

## Data Models

### Coupon Entity Properties

| Property | Type | Description | Default |
|----------|------|-------------|---------|
| id | int | Primary key | 0 |
| name | ?string | Coupon name | null |
| type | ?string | Type: 'fixed' or 'percent' | null |
| value | ?float | Discount value | null |
| minAmount | ?float | Minimum order amount | null |
| totalQuantity | ?int | Total available quantity | null |
| usedQuantity | ?int | Number of coupons used | 0 |
| perUserLimit | ?int | Max coupons per user | null |
| startTime | ?string | Start of validity period | null |
| endTime | ?string | End of validity period | null |
| status | ?string | Status: 'active' or 'inactive' | 'active' |
| description | ?string | Coupon description | null |

### CouponUser Entity Properties

| Property | Type | Description | Default |
|----------|------|-------------|---------|
| id | int | Primary key | 0 |
| couponId | ?int | Foreign key to coupon | null |
| memberId | ?int | Foreign key to member | null |
| orderId | ?int | Foreign key to order (when used) | null |
| status | ?string | Status: 'unused', 'used', 'expired' | 'unused' |
| receivedAt | ?string | Timestamp when received | null |
| usedAt | ?string | Timestamp when used | null |
| expireAt | ?string | Expiration timestamp | null |

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*


### Property Reflection

After analyzing all acceptance criteria, I've identified the following testable properties and their relationships:

**Redundancies Identified:**
- Properties 4.3 and 16.1 both test that status is set to 'active' on creation → Combine into one property
- Properties 13.1 and 13.2 both test time window validation → Combine into one property
- Properties 10.7 and 13.1 both test time window exception → Already combined above
- Properties 18.1, 18.2, 18.3 can be combined into one comprehensive serialization property for CouponEntity
- Properties 18.4, 18.5, 18.6 can be combined into one comprehensive serialization property for CouponUserEntity

**Properties to Implement:**

1. **Entity Creation Properties** (4.2, 4.3, 4.4, 4.5, 4.7, 4.8)
2. **Entity Update with Dirty Tracking** (5.2, 5.3, 5.5, 5.6)
3. **Constructor Initialization** (6.2, 6.4) - Examples, not properties
4. **Mapper Conversion** (7.3, 7.6)
5. **Time Window Validation** (10.7, 13.1, 13.2 combined)
6. **Stock Validation** (10.8, 14.1, 14.2, 14.3)
7. **Request Validation** (11.2, 11.3, 11.4)
8. **Status Transitions** (11.8, 16.2, 16.3, 16.4, 16.5, 16.6)
9. **Per-User Limits** (15.1, 15.2, 15.3, 15.4)
10. **Expiration Logic** (17.1, 17.2, 17.3, 17.4)
11. **Serialization** (18.1-18.6 combined)

### Correctness Properties

Property 1: Entity creation initializes all DTO properties
*For any* valid CouponInput DTO, when create() is called on a new CouponEntity, all non-null properties from the DTO should be reflected in the entity's properties
**Validates: Requirements 4.2**

Property 2: Entity creation sets default values
*For any* CouponInput DTO, when create() is called on a new CouponEntity, the entity should have status='active' and used_quantity=0 regardless of DTO values
**Validates: Requirements 4.3, 4.4, 16.1**

Property 3: Entity creation returns self for chaining
*For any* valid CouponInput DTO, when create() is called on a CouponEntity, the return value should be the same entity instance (enabling method chaining)
**Validates: Requirements 4.5**

Property 4: CouponUser entity creation initializes properties
*For any* valid CouponUserInput DTO, when create() is called on a new CouponUserEntity, all non-null properties from the DTO should be reflected in the entity's properties
**Validates: Requirements 4.7**

Property 5: CouponUser entity creation returns self
*For any* valid CouponUserInput DTO, when create() is called on a CouponUserEntity, the return value should be the same entity instance
**Validates: Requirements 4.8**

Property 6: Entity update only modifies non-null DTO properties
*For any* existing CouponEntity with initial values and any CouponInput DTO with some null properties, when update() is called, only the non-null DTO properties should change in the entity, and null DTO properties should leave entity properties unchanged
**Validates: Requirements 5.2**

Property 7: Entity update returns self for chaining
*For any* CouponEntity and any CouponInput DTO, when update() is called, the return value should be the same entity instance
**Validates: Requirements 5.3**

Property 8: CouponUser entity update only modifies non-null properties
*For any* existing CouponUserEntity with initial values and any CouponUserInput DTO with some null properties, when update() is called, only the non-null DTO properties should change
**Validates: Requirements 5.5**

Property 9: CouponUser entity update returns self
*For any* CouponUserEntity and any CouponUserInput DTO, when update() is called, the return value should be the same entity instance
**Validates: Requirements 5.6**

Property 10: Mapper converts all Model properties to Entity
*For any* Coupon Model with valid data, when CouponMapper::fromModel() is called, all Model properties should be correctly transferred to the resulting CouponEntity with proper type conversions
**Validates: Requirements 7.3**

Property 11: CouponUser Mapper converts all properties
*For any* CouponUser Model with valid data, when CouponUserMapper::fromModel() is called, all Model properties should be correctly transferred to the resulting CouponUserEntity
**Validates: Requirements 7.6**

Property 12: Time window validation rejects invalid ranges
*For any* time window where start_time >= end_time, when defineTimeWindow() or ensureTimeWindowIsValid() is called, the system should throw InvalidArgumentException with message "优惠券生效时间不合法"
**Validates: Requirements 10.7, 13.1, 13.2**

Property 13: Stock validation rejects insufficient inventory
*For any* coupon with available stock less than requested quantity, when assertAvailableStock() is called, the system should throw RuntimeException with message "优惠券库存不足"
**Validates: Requirements 10.8, 14.2**

Property 14: Stock calculation is correct
*For any* coupon, the available stock should always equal (total_quantity - issued_count)
**Validates: Requirements 14.1**

Property 15: Using a coupon increments used_quantity
*For any* coupon, when a user coupon is marked as used, the coupon's used_quantity should increase by 1
**Validates: Requirements 14.3**

Property 16: Toggle status switches between active and inactive
*For any* CouponEntity with status 'active' or 'inactive', when toggleStatus() is called, the status should switch to the opposite value
**Validates: Requirements 16.2**

Property 17: User coupon issuance sets status to unused
*For any* valid coupon and member, when a user coupon is issued, the resulting CouponUserEntity should have status='unused'
**Validates: Requirements 16.3**

Property 18: MarkUsed rejects non-unused coupons
*For any* CouponUserEntity with status other than 'unused', when markUsed() is called, the system should throw RuntimeException
**Validates: Requirements 16.4**

Property 19: MarkExpired rejects non-unused coupons
*For any* CouponUserEntity with status other than 'unused', when markExpired() is called, the system should throw RuntimeException
**Validates: Requirements 16.5**

Property 20: MarkUsed updates status and timestamp
*For any* CouponUserEntity with status='unused', when markUsed() is called, the entity should have status='used' and used_at should be set to a valid timestamp
**Validates: Requirements 16.6**

Property 21: Status transition validation
*For any* CouponUserEntity, status transitions should only be allowed from 'unused' to 'used' or 'unused' to 'expired', and any other transition should be rejected
**Validates: Requirements 11.8**

Property 22: Per-user limit enforcement
*For any* coupon with per_user_limit set and any member, when the member's current coupon count equals or exceeds per_user_limit, canMemberReceive() should return false
**Validates: Requirements 15.1, 15.4**

Property 23: Batch issuance respects per-user limits
*For any* batch issuance operation, members who have reached their per_user_limit should be skipped and not receive additional coupons
**Validates: Requirements 15.2**

Property 24: Null per-user limit allows unlimited coupons
*For any* coupon with per_user_limit=null and any member with any current coupon count, canMemberReceive() should return true
**Validates: Requirements 15.3**

Property 25: Expiration uses earlier of custom or coupon end time
*For any* coupon with end_time and any custom expireAt, when resolveExpireAt() is called, the result should be the earlier of the two timestamps
**Validates: Requirements 17.1**

Property 26: Expiration defaults to coupon end time
*For any* coupon with end_time and no custom expireAt, when resolveExpireAt() is called, the result should equal the coupon's end_time
**Validates: Requirements 17.2**

Property 27: Expiration defaults to 30 days when no times provided
*For any* coupon without end_time and no custom expireAt, when resolveExpireAt() is called, the result should be 30 days from the current time
**Validates: Requirements 17.3**

Property 28: Expiration rejects past dates
*For any* calculated expireAt that is before the current time, when resolveExpireAt() is called, the system should throw InvalidArgumentException
**Validates: Requirements 17.4**

Property 29: CouponEntity serialization is complete and correct
*For any* CouponEntity, when toArray() is called, the result should contain snake_case keys matching database columns (name, type, value, min_amount, total_quantity, used_quantity, per_user_limit, start_time, end_time, status, description), filter out null values, and have correct value types
**Validates: Requirements 18.1, 18.2, 18.3**

Property 30: CouponUserEntity serialization is complete and correct
*For any* CouponUserEntity, when toArray() is called, the result should contain snake_case keys matching database columns (coupon_id, member_id, order_id, status, received_at, used_at, expire_at), filter out null values, and have correct value types
**Validates: Requirements 18.4, 18.5, 18.6**

## Error Handling

### Exception Types and Scenarios

**InvalidArgumentException:**
- Thrown when time window validation fails (start_time >= end_time)
- Thrown when calculated expireAt is before current time
- Thrown when no valid member IDs are provided for issuance
- Message: "优惠券生效时间不合法" for time window errors
- Message: "过期时间无效" for expiration date errors
- Message: "没有可用的会员ID" for member validation errors

**RuntimeException:**
- Thrown when stock validation fails (insufficient inventory)
- Thrown when attempting to use a non-unused coupon
- Thrown when attempting to expire a non-unused coupon
- Thrown when coupon is not active during issuance
- Thrown when coupon is not effective at current time
- Message: "优惠券库存不足" for stock errors
- Message: "仅未使用的优惠券可以置为已使用" for invalid use attempts
- Message: "仅未使用的优惠券可以置为过期" for invalid expiration attempts
- Message: "优惠券未启用" for inactive coupon errors
- Message: "优惠券未开始" or "优惠券已过期" for time-based errors

**BusinessException:**
- Thrown when coupon or user coupon is not found
- Message: "优惠券不存在" for missing coupons
- Message: "用户优惠券不存在" for missing user coupons
- ResultCode: ResultCode::FORBIDDEN or ResultCode::NOT_FOUND

### Error Handling Strategy

1. **Validation Errors**: Caught at Request layer with appropriate HTTP status codes (422 Unprocessable Entity)
2. **Business Rule Violations**: Thrown as domain exceptions with descriptive messages
3. **Not Found Errors**: Thrown as BusinessException with appropriate result codes
4. **Transaction Rollback**: CommandServices should handle transaction rollback on any exception

## Testing Strategy

### Dual Testing Approach

The testing strategy employs both unit tests and property-based tests to ensure comprehensive coverage:

**Unit Tests:**
- Test specific examples of entity creation and updates
- Test edge cases like empty strings, boundary values, null handling
- Test error conditions with specific invalid inputs
- Test integration between layers (Request → DTO → Entity)
- Test specific business scenarios (e.g., issuing to specific members)

**Property-Based Tests:**
- Test universal properties across randomly generated inputs
- Verify entity creation/update behavior with varied DTOs
- Validate time window logic with random date combinations
- Test stock management with random quantities
- Verify status transitions with random state combinations
- Test serialization with random entity states

### Property-Based Testing Configuration

**Library**: Use `phpunit-quickcheck` or similar PHP property-based testing library
**Iterations**: Minimum 100 iterations per property test
**Generators**: Create custom generators for:
- CouponInput DTOs with valid/invalid data
- CouponUserInput DTOs with valid/invalid data
- Time ranges (valid and invalid)
- Stock quantities
- Member IDs and counts

**Test Tagging**: Each property test must include a comment referencing the design property:
```php
/**
 * Feature: coupon-ddd-refactoring, Property 6: Entity update only modifies non-null DTO properties
 */
public function testEntityUpdateOnlyModifiesNonNullProperties(): void
{
    // Property-based test implementation
}
```

### Test Organization

```
tests/
├── Unit/
│   ├── Domain/
│   │   ├── Coupon/
│   │   │   ├── Entity/
│   │   │   │   ├── CouponEntityTest.php
│   │   │   │   └── CouponUserEntityTest.php
│   │   │   ├── Mapper/
│   │   │   │   ├── CouponMapperTest.php
│   │   │   │   └── CouponUserMapperTest.php
│   │   │   └── Service/
│   │   │       ├── CouponServiceTest.php
│   │   │       └── CouponUserServiceTest.php
│   ├── Interface/
│   │   └── Admin/
│   │       ├── DTO/
│   │       │   └── Coupon/
│   │       │       ├── CouponDtoTest.php
│   │       │       └── CouponUserDtoTest.php
│   │       └── Request/
│   │           └── Coupon/
│   │               ├── CouponRequestTest.php
│   │               └── CouponUserRequestTest.php
│   └── Application/
│       └── Commad/
│           ├── CouponCommandServiceTest.php
│           └── CouponUserCommandServiceTest.php
└── Property/
    └── Domain/
        └── Coupon/
            ├── CouponEntityPropertiesTest.php
            └── CouponUserEntityPropertiesTest.php
```

### Key Test Scenarios

**Entity Creation Tests:**
- Create entity with complete DTO
- Create entity with minimal required fields
- Verify default values are set correctly
- Verify method chaining works

**Entity Update Tests:**
- Update with partial DTO (some null fields)
- Update with complete DTO
- Verify unchanged fields remain unchanged
- Verify method chaining works

**Time Window Tests:**
- Valid time windows (start < end)
- Invalid time windows (start >= end)
- Null time values
- Edge cases (same timestamp, 1 second difference)

**Stock Management Tests:**
- Sufficient stock scenarios
- Insufficient stock scenarios
- Zero stock scenarios
- Concurrent issuance scenarios

**Status Transition Tests:**
- Valid transitions (unused → used, unused → expired)
- Invalid transitions (used → unused, expired → used)
- Toggle between active/inactive

**Expiration Logic Tests:**
- Custom expireAt earlier than end_time
- Custom expireAt later than end_time
- No custom expireAt (use end_time)
- No end_time (use 30-day default)
- Past expireAt (should reject)

**Serialization Tests:**
- Entity with all fields populated
- Entity with some null fields
- Verify snake_case conversion
- Verify null filtering
