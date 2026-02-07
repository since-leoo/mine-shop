# Requirements Document

## Introduction

This document specifies the requirements for refactoring the Coupon module to follow Domain-Driven Design (DDD) architecture standards established in the project. The refactoring will align the Coupon module with the architectural patterns used in the Seckill module, ensuring consistency, maintainability, and adherence to DDD principles.

The refactoring focuses on introducing Contract interfaces, DTO classes, proper Entity creation/update methods, and ensuring clear separation of concerns across layers while preserving all existing business logic and validation rules.

## Glossary

- **Coupon_System**: The system responsible for managing coupon lifecycle, issuance, and usage
- **Entity**: Domain object with business logic and identity
- **DTO** (Data Transfer Object): Object that carries data between layers, implementing Contract interfaces
- **Contract**: Interface defining input data structure for domain operations
- **Mapper**: Component responsible for converting between Models and Entities
- **Request**: HTTP request validation and transformation layer
- **CommandService**: Application service managing transactions and orchestrating domain operations
- **DomainService**: Service containing domain logic that doesn't naturally fit within a single Entity
- **Repository**: Data access layer for persisting and retrieving domain objects
- **Coupon**: Promotional discount with time window, quantity limits, and usage rules
- **CouponUser**: Record of a coupon issued to a specific member
- **Time_Window**: Period during which a coupon is valid (start_time to end_time)
- **Stock**: Available quantity of coupons (total_quantity - used_quantity)

## Requirements

### Requirement 1: Contract Interface Creation

**User Story:** As a developer, I want Contract interfaces for Coupon operations, so that the domain layer depends on abstractions rather than concrete implementations.

#### Acceptance Criteria

1. THE Coupon_System SHALL define a CouponInput interface in app/Domain/Coupon/Contract/
2. THE CouponInput interface SHALL declare methods: getId(), getName(), getType(), getValue(), getMinAmount(), getTotalQuantity(), getPerUserLimit(), getStartTime(), getEndTime(), getStatus(), getDescription()
3. THE Coupon_System SHALL define a CouponUserInput interface in app/Domain/Coupon/Contract/
4. THE CouponUserInput interface SHALL declare methods: getId(), getCouponId(), getMemberId(), getOrderId(), getStatus(), getReceivedAt(), getUsedAt(), getExpireAt()

### Requirement 2: DTO Implementation

**User Story:** As a developer, I want DTO classes that implement Contract interfaces, so that data can flow from the interface layer to the domain layer in a type-safe manner.

#### Acceptance Criteria

1. THE Coupon_System SHALL define a CouponDto class in app/Interface/Admin/DTO/Coupon/
2. THE CouponDto SHALL implement the CouponInput interface
3. THE CouponDto SHALL contain public nullable properties matching all interface methods
4. THE Coupon_System SHALL define a CouponUserDto class in app/Interface/Admin/DTO/Coupon/
5. THE CouponUserDto SHALL implement the CouponUserInput interface
6. THE CouponUserDto SHALL contain public nullable properties matching all interface methods

### Requirement 3: Request Layer Enhancement

**User Story:** As a developer, I want Request classes to convert validated input into DTOs, so that controllers receive type-safe data objects.

#### Acceptance Criteria

1. WHEN CouponRequest is validated, THE Coupon_System SHALL provide a toDto() method that returns CouponInput
2. THE CouponRequest::toDto() method SHALL accept an optional id parameter
3. THE CouponRequest::toDto() method SHALL map validated data to CouponDto using Hyperf\DTO\Mapper
4. WHEN CouponUserRequest is validated, THE Coupon_System SHALL provide a toDto() method that returns CouponUserInput
5. THE CouponUserRequest::toDto() method SHALL accept an optional id parameter
6. THE CouponUserRequest::toDto() method SHALL map validated data to CouponUserDto using Hyperf\DTO\Mapper

### Requirement 4: Entity Creation Pattern

**User Story:** As a developer, I want Entities to have create() methods that accept Contract interfaces, so that entity creation follows DDD patterns.

#### Acceptance Criteria

1. THE CouponEntity SHALL provide a create(CouponInput $dto) method
2. WHEN create() is called, THE CouponEntity SHALL initialize all properties from the DTO
3. WHEN create() is called, THE CouponEntity SHALL set status to 'active' by default
4. WHEN create() is called, THE CouponEntity SHALL set used_quantity to 0
5. THE CouponEntity SHALL return itself from create() for method chaining
6. THE CouponUserEntity SHALL provide a create(CouponUserInput $dto) method
7. WHEN CouponUserEntity::create() is called, THE CouponUserEntity SHALL initialize all properties from the DTO
8. THE CouponUserEntity SHALL return itself from create() for method chaining

### Requirement 5: Entity Update Pattern

**User Story:** As a developer, I want Entities to have update() methods that accept Contract interfaces, so that entity updates follow DDD patterns with dirty tracking.

#### Acceptance Criteria

1. THE CouponEntity SHALL provide an update(CouponInput $dto) method
2. WHEN update() is called, THE CouponEntity SHALL only update properties that are non-null in the DTO
3. THE CouponEntity SHALL return itself from update() for method chaining
4. THE CouponUserEntity SHALL provide an update(CouponUserInput $dto) method
5. WHEN CouponUserEntity::update() is called, THE CouponUserEntity SHALL only update properties that are non-null in the DTO
6. THE CouponUserEntity SHALL return itself from update() for method chaining

### Requirement 6: Entity Constructor Refactoring

**User Story:** As a developer, I want Entities to use public parameterless constructors, so that entity instantiation follows the established DDD pattern.

#### Acceptance Criteria

1. THE CouponEntity SHALL have a public constructor with no parameters
2. THE CouponEntity SHALL initialize all properties to default values (0 for int, null for nullable types)
3. THE CouponUserEntity SHALL have a public constructor with no parameters
4. THE CouponUserEntity SHALL initialize all properties to default values (0 for int, null for nullable types)

### Requirement 7: Mapper Enhancement

**User Story:** As a developer, I want Mappers to provide getNewEntity() methods, so that entity creation is centralized and consistent.

#### Acceptance Criteria

1. THE CouponMapper SHALL provide a static getNewEntity() method
2. THE CouponMapper::getNewEntity() SHALL return a new CouponEntity instance
3. THE CouponMapper::fromModel() method SHALL properly convert all Model properties to Entity properties
4. THE CouponUserMapper SHALL provide a static getNewEntity() method
5. THE CouponUserMapper::getNewEntity() SHALL return a new CouponUserEntity instance
6. THE CouponUserMapper::fromModel() method SHALL properly convert all Model properties to Entity properties

### Requirement 8: Domain Service Refactoring

**User Story:** As a developer, I want Domain Services to accept Contract interfaces instead of Entities, so that services depend on abstractions.

#### Acceptance Criteria

1. THE CouponService::create() method SHALL accept CouponInput instead of CouponEntity
2. WHEN CouponService::create() is called, THE Coupon_System SHALL use CouponMapper::getNewEntity() to create a new entity
3. WHEN CouponService::create() is called, THE Coupon_System SHALL call entity.create(dto) to initialize the entity
4. THE CouponService::update() method SHALL accept CouponInput instead of CouponEntity
5. WHEN CouponService::update() is called, THE Coupon_System SHALL retrieve the existing entity using getEntity()
6. WHEN CouponService::update() is called, THE Coupon_System SHALL call entity.update(dto) to modify the entity
7. THE CouponService SHALL provide a getEntity(int $id) method that returns CouponEntity
8. THE CouponUserService SHALL follow the same pattern for create() and update() methods

### Requirement 9: CommandService Refactoring

**User Story:** As a developer, I want CommandServices to accept Contract interfaces, so that the application layer is decoupled from concrete implementations.

#### Acceptance Criteria

1. THE CouponCommandService::create() method SHALL accept CouponInput instead of CouponEntity
2. THE CouponCommandService::create() method SHALL pass the CouponInput to CouponService::create()
3. THE CouponCommandService::update() method SHALL accept CouponInput instead of CouponEntity
4. THE CouponCommandService::update() method SHALL pass the CouponInput to CouponService::update()
5. THE CouponUserCommandService SHALL follow the same pattern for its methods

### Requirement 10: Business Logic Preservation

**User Story:** As a developer, I want all existing business logic to remain functional after refactoring, so that no functionality is lost.

#### Acceptance Criteria

1. THE CouponEntity SHALL preserve the defineTimeWindow() method for time validation
2. THE CouponEntity SHALL preserve the ensureTimeWindowIsValid() method
3. THE CouponEntity SHALL preserve the activate(), deactivate(), and toggleStatus() methods
4. THE CouponEntity SHALL preserve the assertActive(), assertEffectiveAt(), and assertAvailableStock() methods
5. THE CouponEntity SHALL preserve the canMemberReceive() and resolveExpireAt() methods
6. THE CouponUserEntity SHALL preserve the issue(), markUsed(), and markExpired() static/instance methods
7. WHEN time window validation fails, THE Coupon_System SHALL throw InvalidArgumentException with message "优惠券生效时间不合法"
8. WHEN stock validation fails, THE Coupon_System SHALL throw RuntimeException with message "优惠券库存不足"

### Requirement 11: Validation Responsibility Separation

**User Story:** As a developer, I want clear separation between Request validation and Entity validation, so that each layer has well-defined responsibilities.

#### Acceptance Criteria

1. THE CouponRequest SHALL validate format, length, type, required fields, range, enum values, and timestamps
2. THE CouponRequest SHALL validate that end_time is after start_time
3. THE CouponRequest SHALL validate that value is numeric and >= 0.01
4. THE CouponRequest SHALL validate that total_quantity is integer and >= 1
5. THE CouponEntity SHALL validate business rules including time window logic, status transitions, and stock checks
6. THE CouponEntity SHALL NOT duplicate format or type validations already performed by Request layer
7. THE CouponUserRequest SHALL validate format, type, and enum values for status
8. THE CouponUserEntity SHALL validate business rules for status transitions (unused to used/expired)

### Requirement 12: Controller Integration

**User Story:** As a developer, I want Controllers to use the new DTO pattern, so that the entire request flow follows DDD architecture.

#### Acceptance Criteria

1. WHEN a create request is received, THE Controller SHALL call request.toDto() to get CouponInput
2. WHEN a create request is received, THE Controller SHALL pass CouponInput to CommandService::create()
3. WHEN a create request is received, THE Controller SHALL return an empty array (not query database)
4. WHEN an update request is received, THE Controller SHALL call request.toDto(id) to get CouponInput
5. WHEN an update request is received, THE Controller SHALL pass CouponInput to CommandService::update()
6. WHEN an update request is received, THE Controller SHALL return an empty array (not query database)

### Requirement 13: Time Window Validation

**User Story:** As a system administrator, I want time window validation to ensure coupons have valid effective periods, so that coupons cannot be created with invalid time ranges.

#### Acceptance Criteria

1. WHEN start_time is greater than or equal to end_time, THE Coupon_System SHALL reject the time window
2. WHEN defineTimeWindow() is called with invalid times, THE Coupon_System SHALL throw InvalidArgumentException
3. WHEN a coupon is created, THE Coupon_System SHALL validate the time window before persistence
4. WHEN a coupon is updated, THE Coupon_System SHALL validate the time window before persistence

### Requirement 14: Stock Management

**User Story:** As a system administrator, I want stock validation to prevent over-issuance of coupons, so that the system maintains accurate inventory.

#### Acceptance Criteria

1. WHEN issuing coupons, THE Coupon_System SHALL calculate available stock as (total_quantity - issued_count)
2. WHEN available stock is less than requested quantity, THE Coupon_System SHALL reject the issuance
3. WHEN a coupon is used, THE Coupon_System SHALL increment used_quantity
4. THE Coupon_System SHALL provide syncUsage() method to recalculate used_quantity from actual usage records

### Requirement 15: Per-User Limit Enforcement

**User Story:** As a system administrator, I want per-user limit enforcement, so that members cannot receive more coupons than allowed.

#### Acceptance Criteria

1. WHEN per_user_limit is set, THE Coupon_System SHALL check member's current coupon count before issuance
2. WHEN a member has reached their limit, THE Coupon_System SHALL skip that member during batch issuance
3. WHEN per_user_limit is null, THE Coupon_System SHALL allow unlimited coupons per member
4. THE CouponEntity::canMemberReceive() method SHALL return true if member can receive more coupons

### Requirement 16: Status Transition Rules

**User Story:** As a system administrator, I want controlled status transitions, so that coupons and user coupons maintain valid states.

#### Acceptance Criteria

1. WHEN a coupon is created, THE Coupon_System SHALL set status to 'active'
2. WHEN toggleStatus() is called, THE Coupon_System SHALL switch between 'active' and 'inactive'
3. WHEN a user coupon is issued, THE Coupon_System SHALL set status to 'unused'
4. WHEN markUsed() is called on a non-unused coupon, THE Coupon_System SHALL throw RuntimeException
5. WHEN markExpired() is called on a non-unused coupon, THE Coupon_System SHALL throw RuntimeException
6. WHEN markUsed() succeeds, THE Coupon_System SHALL set status to 'used' and record used_at timestamp

### Requirement 17: Expiration Logic

**User Story:** As a system administrator, I want flexible expiration logic, so that coupons can have custom or default expiration dates.

#### Acceptance Criteria

1. WHEN issuing a coupon with custom expireAt, THE Coupon_System SHALL use the earlier of custom expireAt or coupon end_time
2. WHEN issuing a coupon without custom expireAt, THE Coupon_System SHALL use coupon end_time as expireAt
3. WHEN neither custom expireAt nor end_time exists, THE Coupon_System SHALL set expireAt to 30 days from now
4. WHEN calculated expireAt is before current time, THE Coupon_System SHALL throw InvalidArgumentException
5. THE CouponEntity::resolveExpireAt() method SHALL implement this logic

### Requirement 18: Data Persistence

**User Story:** As a developer, I want Entity::toArray() methods to provide data for persistence, so that entities can be saved to the database.

#### Acceptance Criteria

1. THE CouponEntity::toArray() SHALL return an array with snake_case keys matching database columns
2. THE CouponEntity::toArray() SHALL filter out null values
3. THE CouponEntity::toArray() SHALL include: name, type, value, min_amount, total_quantity, used_quantity, per_user_limit, start_time, end_time, status, description
4. THE CouponUserEntity::toArray() SHALL return an array with snake_case keys matching database columns
5. THE CouponUserEntity::toArray() SHALL filter out null values
6. THE CouponUserEntity::toArray() SHALL include: coupon_id, member_id, order_id, status, received_at, used_at, expire_at
