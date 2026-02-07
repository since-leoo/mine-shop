# Implementation Plan: Coupon DDD Refactoring

## Overview

This implementation plan refactors the Coupon module to follow Domain-Driven Design (DDD) architecture standards. The refactoring introduces Contract interfaces, DTO classes, and standardized Entity patterns while preserving all existing business logic. The implementation follows the architectural pattern established in the Seckill module.

## Tasks

- [x] 1. Create Contract Interfaces
  - Create `app/Domain/Coupon/Contract/CouponInput.php` interface with all required getter methods
  - Create `app/Domain/Coupon/Contract/CouponUserInput.php` interface with all required getter methods
  - _Requirements: 1.1, 1.2, 1.3, 1.4_

- [ ] 2. Create DTO Classes
  - [x] 2.1 Create CouponDto class
    - Create `app/Interface/Admin/DTO/Coupon/CouponDto.php` implementing CouponInput
    - Add public nullable properties: id, name, type, value, minAmount, totalQuantity, perUserLimit, startTime, endTime, status, description
    - Implement all getter methods from CouponInput interface
    - _Requirements: 2.1, 2.2, 2.3_
  
  - [x] 2.2 Create CouponUserDto class
    - Create `app/Interface/Admin/DTO/Coupon/CouponUserDto.php` implementing CouponUserInput
    - Add public nullable properties: id, couponId, memberId, orderId, status, receivedAt, usedAt, expireAt
    - Implement all getter methods from CouponUserInput interface
    - _Requirements: 2.4, 2.5, 2.6_

- [ ] 3. Refactor CouponEntity
  - [x] 3.1 Update constructor to be parameterless
    - Change constructor to `public function __construct()` with no parameters
    - Initialize properties with defaults: id=0, usedQuantity=0, status='active', others=null
    - _Requirements: 6.1, 6.2_
  
  - [x] 3.2 Add create() method
    - Add `public function create(CouponInput $dto): self` method
    - Initialize all properties from DTO (name, type, value, minAmount, totalQuantity, perUserLimit, startTime, endTime, description)
    - Set usedQuantity=0 and status='active' as defaults
    - Call ensureTimeWindowIsValid() to validate time window
    - Return $this for method chaining
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_
  
  - [x] 3.3 Add update() method
    - Add `public function update(CouponInput $dto): self` method
    - Only update properties that are non-null in the DTO
    - Validate time window if startTime or endTime are updated
    - Return $this for method chaining
    - _Requirements: 5.1, 5.2, 5.3_
  
  - [ ]* 3.4 Write property tests for CouponEntity
    - **Property 1: Entity creation initializes all DTO properties**
    - **Validates: Requirements 4.2**
    - **Property 2: Entity creation sets default values**
    - **Validates: Requirements 4.3, 4.4, 16.1**
    - **Property 3: Entity creation returns self for chaining**
    - **Validates: Requirements 4.5**
    - **Property 6: Entity update only modifies non-null DTO properties**
    - **Validates: Requirements 5.2**
    - **Property 7: Entity update returns self for chaining**
    - **Validates: Requirements 5.3**

- [ ] 4. Refactor CouponUserEntity
  - [x] 4.1 Update constructor to be parameterless
    - Change constructor to `public function __construct()` with no parameters
    - Initialize properties with defaults: id=0, status='unused', others=null
    - _Requirements: 6.3, 6.4_
  
  - [x] 4.2 Add create() method
    - Add `public function create(CouponUserInput $dto): self` method
    - Initialize all properties from DTO
    - Set status='unused' as default if not provided
    - Return $this for method chaining
    - _Requirements: 4.6, 4.7, 4.8_
  
  - [x] 4.3 Add update() method
    - Add `public function update(CouponUserInput $dto): self` method
    - Only update properties that are non-null in the DTO
    - Return $this for method chaining
    - _Requirements: 5.4, 5.5, 5.6_
  
  - [ ]* 4.4 Write property tests for CouponUserEntity
    - **Property 4: CouponUser entity creation initializes properties**
    - **Validates: Requirements 4.7**
    - **Property 5: CouponUser entity creation returns self**
    - **Validates: Requirements 4.8**
    - **Property 8: CouponUser entity update only modifies non-null properties**
    - **Validates: Requirements 5.5**
    - **Property 9: CouponUser entity update returns self**
    - **Validates: Requirements 5.6**

- [x] 5. Checkpoint - Verify Entity refactoring
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 6. Update Mapper Classes
  - [x] 6.1 Update CouponMapper
    - Add `public static function getNewEntity(): CouponEntity` method that returns `new CouponEntity()`
    - Verify fromModel() properly converts all Model properties to Entity
    - Update fromModel() to use new parameterless constructor
    - _Requirements: 7.1, 7.2, 7.3_
  
  - [x] 6.2 Update CouponUserMapper
    - Add `public static function getNewEntity(): CouponUserEntity` method that returns `new CouponUserEntity()`
    - Verify fromModel() properly converts all Model properties to Entity
    - Update fromModel() to use new parameterless constructor
    - _Requirements: 7.4, 7.5, 7.6_
  
  - [ ]* 6.3 Write property tests for Mappers
    - **Property 10: Mapper converts all Model properties to Entity**
    - **Validates: Requirements 7.3**
    - **Property 11: CouponUser Mapper converts all properties**
    - **Validates: Requirements 7.6**

- [ ] 7. Add toDto() Methods to Request Classes
  - [x] 7.1 Update CouponRequest
    - Add `public function toDto(?int $id = null): CouponInput` method
    - Map validated data to CouponDto using Hyperf\DTO\Mapper
    - Convert snake_case keys to camelCase (min_amount → minAmount, total_quantity → totalQuantity, etc.)
    - _Requirements: 3.1, 3.2, 3.3_
  
  - [x] 7.2 Update CouponUserRequest
    - Add `public function toDto(?int $id = null): CouponUserInput` method
    - Map validated data to CouponUserDto using Hyperf\DTO\Mapper
    - Convert snake_case keys to camelCase (coupon_id → couponId, member_id → memberId, etc.)
    - _Requirements: 3.4, 3.5, 3.6_

- [ ] 8. Refactor CouponService
  - [x] 8.1 Update create() method signature
    - Change signature to `public function create(CouponInput $dto): bool`
    - Use `CouponMapper::getNewEntity()` to create new entity
    - Call `$entity->create($dto)` to initialize entity
    - Pass entity to repository for persistence
    - _Requirements: 8.1, 8.2, 8.3_
  
  - [x] 8.2 Update update() method signature
    - Change signature to `public function update(CouponInput $dto): bool`
    - Use `$this->getEntity($dto->getId())` to retrieve existing entity
    - Call `$entity->update($dto)` to modify entity
    - Pass entity to repository for persistence
    - _Requirements: 8.4, 8.5, 8.6_
  
  - [x] 8.3 Verify getEntity() method exists
    - Ensure `public function getEntity(int $id): CouponEntity` method exists
    - Method should retrieve model and convert to entity using mapper
    - _Requirements: 8.7_

- [ ] 9. Refactor CouponUserService
  - [x] 9.1 Review and update service methods
    - Verify issue() method creates entities properly
    - Ensure all methods follow DDD patterns
    - _Requirements: 8.8_

- [ ] 10. Refactor CouponCommandService
  - [x] 10.1 Update create() method
    - Change signature to `public function create(CouponInput $dto): bool`
    - Pass CouponInput directly to CouponService::create()
    - _Requirements: 9.1, 9.2_
  
  - [x] 10.2 Update update() method
    - Change signature to `public function update(CouponInput $dto): bool`
    - Verify coupon exists using queryService
    - Pass CouponInput directly to CouponService::update()
    - _Requirements: 9.3, 9.4_
  
  - [x] 10.3 Update toggleStatus() method
    - Change signature to accept int $id instead of Entity
    - Retrieve entity using service
    - Pass entity to service for status toggle
    - _Requirements: 9.1, 9.2_

- [ ] 11. Refactor CouponUserCommandService
  - [x] 11.1 Review and update command service methods
    - Ensure methods follow the same pattern as CouponCommandService
    - _Requirements: 9.5_

- [x] 12. Checkpoint - Verify Service Layer refactoring
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 13. Write Business Logic Property Tests
  - [ ]* 13.1 Write time window validation tests
    - **Property 12: Time window validation rejects invalid ranges**
    - **Validates: Requirements 10.7, 13.1, 13.2**
  
  - [ ]* 13.2 Write stock management tests
    - **Property 13: Stock validation rejects insufficient inventory**
    - **Validates: Requirements 10.8, 14.2**
    - **Property 14: Stock calculation is correct**
    - **Validates: Requirements 14.1**
    - **Property 15: Using a coupon increments used_quantity**
    - **Validates: Requirements 14.3**
  
  - [ ]* 13.3 Write status transition tests
    - **Property 16: Toggle status switches between active and inactive**
    - **Validates: Requirements 16.2**
    - **Property 17: User coupon issuance sets status to unused**
    - **Validates: Requirements 16.3**
    - **Property 18: MarkUsed rejects non-unused coupons**
    - **Validates: Requirements 16.4**
    - **Property 19: MarkExpired rejects non-unused coupons**
    - **Validates: Requirements 16.5**
    - **Property 20: MarkUsed updates status and timestamp**
    - **Validates: Requirements 16.6**
    - **Property 21: Status transition validation**
    - **Validates: Requirements 11.8**
  
  - [ ]* 13.4 Write per-user limit tests
    - **Property 22: Per-user limit enforcement**
    - **Validates: Requirements 15.1, 15.4**
    - **Property 23: Batch issuance respects per-user limits**
    - **Validates: Requirements 15.2**
    - **Property 24: Null per-user limit allows unlimited coupons**
    - **Validates: Requirements 15.3**
  
  - [ ]* 13.5 Write expiration logic tests
    - **Property 25: Expiration uses earlier of custom or coupon end time**
    - **Validates: Requirements 17.1**
    - **Property 26: Expiration defaults to coupon end time**
    - **Validates: Requirements 17.2**
    - **Property 27: Expiration defaults to 30 days when no times provided**
    - **Validates: Requirements 17.3**
    - **Property 28: Expiration rejects past dates**
    - **Validates: Requirements 17.4**
  
  - [ ]* 13.6 Write serialization tests
    - **Property 29: CouponEntity serialization is complete and correct**
    - **Validates: Requirements 18.1, 18.2, 18.3**
    - **Property 30: CouponUserEntity serialization is complete and correct**
    - **Validates: Requirements 18.4, 18.5, 18.6**

- [ ] 14. Write Unit Tests for Edge Cases
  - [ ]* 14.1 Write CouponEntity unit tests
    - Test constructor initialization with default values
    - Test create() with minimal required fields
    - Test create() with all fields populated
    - Test update() with partial DTO
    - Test time window edge cases (same timestamp, 1 second difference)
    - Test null handling in various methods
    - _Requirements: 4.2, 4.3, 4.4, 5.2, 13.1_
  
  - [ ]* 14.2 Write CouponUserEntity unit tests
    - Test constructor initialization
    - Test create() with various DTOs
    - Test update() with partial DTO
    - Test issue() static method
    - Test markUsed() and markExpired() methods
    - _Requirements: 4.7, 5.5, 16.3, 16.4, 16.5, 16.6_
  
  - [ ]* 14.3 Write Request validation unit tests
    - Test CouponRequest validation rules
    - Test CouponUserRequest validation rules
    - Test toDto() conversion with various inputs
    - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.7_
  
  - [ ]* 14.4 Write integration tests
    - Test full flow: Request → DTO → CommandService → DomainService → Entity → Repository
    - Test error handling across layers
    - Test transaction rollback scenarios
    - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5, 12.6_

- [ ] 15. Update Controllers (if needed)
  - [x] 15.1 Review CouponController
    - Verify create() method uses request.toDto()
    - Verify update() method uses request.toDto(id)
    - Verify methods return empty arrays after create/update
    - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5, 12.6_
  
  - [x] 15.2 Review CouponUserController
    - Verify methods follow the same pattern
    - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5, 12.6_

- [x] 16. Final Checkpoint - Complete Testing
  - Run all unit tests and property tests
  - Verify all business logic is preserved
  - Verify no regressions in existing functionality
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties across many generated inputs
- Unit tests validate specific examples and edge cases
- The refactoring preserves all existing business logic while improving architecture
- All existing methods (activate, deactivate, toggleStatus, assertActive, etc.) are preserved
- The implementation follows the Seckill module pattern exactly
