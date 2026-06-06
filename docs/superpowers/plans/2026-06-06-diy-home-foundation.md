# DIY Home Foundation Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the first production-shaped homepage DIY decoration loop: admin draft/publish, public API, Taro renderer, and a practical admin editor.

**Architecture:** Add a new Content/DiyPage domain with versioned JSON schema persistence. Admin APIs manage draft and publish flows; the public API returns only published schema with hydrated product groups. The miniprogram renders published schema through a typed component registry and keeps the existing homepage as fallback.

**Tech Stack:** Hyperf 3.1, PHP 8.2, DDD services, PHPUnit/co-phpunit, Vue 3 + Element Plus + MineAdmin UI, Taro 4 + React + TypeScript + SCSS + Vitest.

---

## File Structure

### Backend

- Create `databases/migrations/2026_06_06_000001_create_diy_page_tables.php`: `diy_pages` and `diy_page_versions`.
- Create `app/Infrastructure/Model/Content/DiyPage.php`: page model.
- Create `app/Infrastructure/Model/Content/DiyPageVersion.php`: version model.
- Create `app/Domain/Content/DiyPage/Enum/DiyPageStatus.php`: page and version status constants.
- Create `app/Domain/Content/DiyPage/Contract/DiyPageDraftInput.php`: draft DTO contract.
- Create `app/Domain/Content/DiyPage/Entity/DiyPageEntity.php`: page aggregate state and publish behavior.
- Create `app/Domain/Content/DiyPage/ValueObject/DiyPageSchemaVo.php`: schema validation and normalization.
- Create `app/Domain/Content/DiyPage/Repository/DiyPageRepository.php`: persistence boundary.
- Create `app/Domain/Content/DiyPage/Service/DomainDiyPageService.php`: draft, publish, reset, query rules.
- Create `app/Application/Admin/Content/AppDiyPageCommandService.php`: admin write orchestration.
- Create `app/Application/Admin/Content/AppDiyPageQueryService.php`: admin read orchestration.
- Create `app/Application/Api/Content/AppApiDiyPageQueryService.php`: public read orchestration and product hydration.
- Create `app/Interface/Admin/Dto/DiyPage/DiyPageDraftDto.php`: admin draft DTO.
- Create `app/Interface/Admin/Request/DiyPage/DiyPageRequest.php`: admin validation.
- Create `app/Interface/Admin/Controller/DiyPage/DiyPageController.php`: admin endpoints.
- Create `app/Interface/Api/Controller/V1/Common/DiyPageController.php`: public endpoint.
- Create `app/Interface/Api/Transformer/DiyPageTransformer.php`: public response shape.
- Test `tests/Unit/Domain/Content/DiyPage/ValueObject/DiyPageSchemaVoTest.php`.
- Test `tests/Unit/Domain/Content/DiyPage/Service/DomainDiyPageServiceTest.php`.
- Test `tests/Unit/Interface/Api/Transformer/DiyPageTransformerTest.php`.

### Miniprogram

- Create `miniprogram/src/services/diy/page.ts`: fetch published DIY page.
- Create `miniprogram/src/components/diy-renderer/types.ts`: schema types.
- Create `miniprogram/src/components/diy-renderer/link.ts`: cross-platform link resolver.
- Create `miniprogram/src/components/diy-renderer/index.tsx`: renderer registry.
- Create `miniprogram/src/components/diy-renderer/index.scss`: renderer layout.
- Create `miniprogram/src/components/diy/Banner/index.tsx` and `index.scss`.
- Create `miniprogram/src/components/diy/QuickNav/index.tsx` and `index.scss`.
- Create `miniprogram/src/components/diy/ImageAd/index.tsx` and `index.scss`.
- Create `miniprogram/src/components/diy/ProductGroup/index.tsx` and `index.scss`.
- Create `miniprogram/src/components/diy/TitleBar/index.tsx` and `index.scss`.
- Create `miniprogram/src/components/diy/Gap/index.tsx` and `index.scss`.
- Create `miniprogram/src/components/diy/Divider/index.tsx` and `index.scss`.
- Modify `miniprogram/src/pages/home/index.tsx`: prefer DIY page when available, otherwise render current homepage.
- Test `miniprogram/src/components/diy-renderer/link.test.ts`.
- Test `miniprogram/src/components/diy-renderer/renderer.test.ts`.

### Web Admin

- Create `web/src/modules/mall/api/diyPage.ts`: admin API client.
- Create `web/src/modules/mall/views/diy/home/index.vue`: editor shell.
- Create `web/src/modules/mall/views/diy/schema/types.ts`: schema types.
- Create `web/src/modules/mall/views/diy/schema/componentRegistry.ts`: component defaults and form metadata.
- Create `web/src/modules/mall/views/diy/components/ComponentLibrary.vue`: addable components.
- Create `web/src/modules/mall/views/diy/components/PhonePreview.vue`: schema preview and selection.
- Create `web/src/modules/mall/views/diy/components/PropertyPanel.vue`: active component editor.
- Create `web/src/modules/mall/views/diy/components/renderers/BannerPreview.vue`.
- Create `web/src/modules/mall/views/diy/components/renderers/QuickNavPreview.vue`.
- Create `web/src/modules/mall/views/diy/components/renderers/ImageAdPreview.vue`.
- Create `web/src/modules/mall/views/diy/components/renderers/ProductGroupPreview.vue`.
- Create `web/src/modules/mall/views/diy/components/renderers/TitleBarPreview.vue`.
- Create `web/src/modules/mall/views/diy/components/renderers/GapPreview.vue`.
- Create `web/src/modules/mall/views/diy/components/renderers/DividerPreview.vue`.

---

## Chunk 1: Backend Schema Foundation

### Task 1: Migration and Models

**Files:**
- Create: `databases/migrations/2026_06_06_000001_create_diy_page_tables.php`
- Create: `app/Infrastructure/Model/Content/DiyPage.php`
- Create: `app/Infrastructure/Model/Content/DiyPageVersion.php`

- [ ] **Step 1: Write migration**

Create `diy_pages` with `page_key`, `title`, `platform`, `status`, `published_version_id`, author fields, timestamps, soft deletes. Create `diy_page_versions` with `page_id`, `version_no`, `status`, `schema`, `published_at`, author fields, timestamps.

- [ ] **Step 2: Add model classes**

`DiyPage` uses `SoftDeletes`, table `diy_pages`, fillable fields, casts, and `versions()` / `publishedVersion()` relations.

`DiyPageVersion` uses table `diy_page_versions`, fillable fields, casts `schema` to array and `published_at` to datetime, and `page()` relation.

- [ ] **Step 3: Run migration test group**

Run: `composer test -- --group=migrations`

Expected: migrations pass or existing repository migration group behavior remains unchanged.

- [ ] **Step 4: Commit**

```bash
git add databases/migrations/2026_06_06_000001_create_diy_page_tables.php app/Infrastructure/Model/Content/DiyPage.php app/Infrastructure/Model/Content/DiyPageVersion.php
git commit -m "feat(diy): add page version tables"
```

### Task 2: Schema Value Object

**Files:**
- Create: `app/Domain/Content/DiyPage/ValueObject/DiyPageSchemaVo.php`
- Create: `app/Domain/Content/DiyPage/Enum/DiyPageStatus.php`
- Test: `tests/Unit/Domain/Content/DiyPage/ValueObject/DiyPageSchemaVoTest.php`

- [ ] **Step 1: Write failing schema tests**

Cover:

- Valid minimal home schema passes.
- Unknown component throws `DomainException`.
- Duplicate component IDs throw `DomainException`.
- Too many components throw `DomainException`.
- Disabled components are removed by `publishedPayload()`.
- Product group limit is capped or rejected at max 50.

- [ ] **Step 2: Run tests to verify failure**

Run: `vendor/bin/co-phpunit --prepend tests/bootstrap.php tests/Unit/Domain/Content/DiyPage/ValueObject/DiyPageSchemaVoTest.php`

Expected: FAIL because classes do not exist.

- [ ] **Step 3: Implement value object**

Implement:

- `fromArray(array $schema, string $pageKey): self`
- `toArray(): array`
- `publishedPayload(): array`
- component type whitelist
- link type whitelist
- per-component validation limits

Use `\DomainException` for schema business rule failures.

- [ ] **Step 4: Run tests**

Run: `vendor/bin/co-phpunit --prepend tests/bootstrap.php tests/Unit/Domain/Content/DiyPage/ValueObject/DiyPageSchemaVoTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Domain/Content/DiyPage/ValueObject/DiyPageSchemaVo.php app/Domain/Content/DiyPage/Enum/DiyPageStatus.php tests/Unit/Domain/Content/DiyPage/ValueObject/DiyPageSchemaVoTest.php
git commit -m "feat(diy): validate page schema"
```

### Task 3: Domain Service Draft and Publish

**Files:**
- Create: `app/Domain/Content/DiyPage/Contract/DiyPageDraftInput.php`
- Create: `app/Domain/Content/DiyPage/Entity/DiyPageEntity.php`
- Create: `app/Domain/Content/DiyPage/Repository/DiyPageRepository.php`
- Create: `app/Domain/Content/DiyPage/Service/DomainDiyPageService.php`
- Test: `tests/Unit/Domain/Content/DiyPage/Service/DomainDiyPageServiceTest.php`

- [ ] **Step 1: Write failing service tests with mocked repository**

Cover:

- `saveDraft()` creates page when missing and creates/updates draft version.
- `publish()` archives old published version and marks draft as published.
- `getPublished()` returns normalized published payload.
- `resetDraft()` creates default home schema draft.

- [ ] **Step 2: Run tests to verify failure**

Run: `vendor/bin/co-phpunit --prepend tests/bootstrap.php tests/Unit/Domain/Content/DiyPage/Service/DomainDiyPageServiceTest.php`

Expected: FAIL because service classes do not exist.

- [ ] **Step 3: Implement repository methods**

Required methods:

- `findByPageKey(string $pageKey, string $platform = 'all'): ?DiyPage`
- `createPage(array $data): DiyPage`
- `findDraftVersion(int $pageId): ?DiyPageVersion`
- `storeDraft(int $pageId, array $schema, ?int $operatorId): DiyPageVersion`
- `nextVersionNo(int $pageId): int`
- `publishVersion(DiyPage $page, DiyPageVersion $version, ?int $operatorId): DiyPageVersion`
- `findPublishedByPageKey(string $pageKey, string $platform = 'all'): ?DiyPageVersion`

- [ ] **Step 4: Implement domain service**

Service enforces schema with `DiyPageSchemaVo`, default home schema, and status transitions.

- [ ] **Step 5: Run tests**

Run: `vendor/bin/co-phpunit --prepend tests/bootstrap.php tests/Unit/Domain/Content/DiyPage/Service/DomainDiyPageServiceTest.php`

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Domain/Content/DiyPage tests/Unit/Domain/Content/DiyPage/Service/DomainDiyPageServiceTest.php
git commit -m "feat(diy): add draft publish domain service"
```

---

## Chunk 2: Backend Admin and Public APIs

### Task 4: Admin DTO, Request, Services, Controller

**Files:**
- Create: `app/Application/Admin/Content/AppDiyPageCommandService.php`
- Create: `app/Application/Admin/Content/AppDiyPageQueryService.php`
- Create: `app/Interface/Admin/Dto/DiyPage/DiyPageDraftDto.php`
- Create: `app/Interface/Admin/Request/DiyPage/DiyPageRequest.php`
- Create: `app/Interface/Admin/Controller/DiyPage/DiyPageController.php`

- [ ] **Step 1: Write request and DTO**

`DiyPageRequest` validates:

- `title`: nullable string max 100
- `schema`: required array
- `schema.version`: required integer min 1
- `schema.page`: required array
- `schema.components`: required array max 50

`DiyPageDraftDto` implements `DiyPageDraftInput`.

- [ ] **Step 2: Implement application services**

Use `Db::transaction()` in command service for `saveDraft()`, `publish()`, and `resetDraft()`.

- [ ] **Step 3: Implement controller**

Routes:

- `GET /admin/diy/pages/{pageKey}`
- `PUT /admin/diy/pages/{pageKey}/draft`
- `POST /admin/diy/pages/{pageKey}/publish`
- `POST /admin/diy/pages/{pageKey}/reset`

Use access token, permission, and operation middleware matching existing admin controllers. Permission codes:

- `mall:diy:read`
- `mall:diy:update`
- `mall:diy:publish`

- [ ] **Step 4: Run PHP syntax check**

Run: `php -l app/Interface/Admin/Controller/DiyPage/DiyPageController.php`

Expected: `No syntax errors detected`.

- [ ] **Step 5: Commit**

```bash
git add app/Application/Admin/Content app/Interface/Admin/Dto/DiyPage app/Interface/Admin/Request/DiyPage app/Interface/Admin/Controller/DiyPage
git commit -m "feat(diy): add admin page APIs"
```

### Task 5: Public API and Transformer

**Files:**
- Create: `app/Application/Api/Content/AppApiDiyPageQueryService.php`
- Create: `app/Interface/Api/Controller/V1/Common/DiyPageController.php`
- Create: `app/Interface/Api/Transformer/DiyPageTransformer.php`
- Test: `tests/Unit/Interface/Api/Transformer/DiyPageTransformerTest.php`

- [ ] **Step 1: Write failing transformer tests**

Cover:

- Empty payload returns `page => null`, `components => []`, `publishedAt => null`.
- Disabled components do not appear.
- Published payload uses camelCase `publishedAt`.

- [ ] **Step 2: Run tests to verify failure**

Run: `vendor/bin/co-phpunit --prepend tests/bootstrap.php tests/Unit/Interface/Api/Transformer/DiyPageTransformerTest.php`

Expected: FAIL because transformer does not exist.

- [ ] **Step 3: Implement API query service**

Use `DomainDiyPageService::getPublished($pageKey)`. Hydrate `product-group` components by calling `AppApiProductQueryService`:

- `manual`: query product IDs in component order.
- `recommend`: `page(['is_recommend' => true, 'status' => 'active'], 1, limit)`.
- `hot`: `page(['is_hot' => true, 'status' => 'active'], 1, limit)`.
- `new`: `page(['is_new' => true, 'status' => 'active'], 1, limit)`.

- [ ] **Step 4: Implement public controller**

Route: `GET /api/v1/diy/pages/{pageKey}` with `ApiSignatureMiddleware`.

- [ ] **Step 5: Run tests**

Run: `vendor/bin/co-phpunit --prepend tests/bootstrap.php tests/Unit/Interface/Api/Transformer/DiyPageTransformerTest.php`

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Application/Api/Content app/Interface/Api/Controller/V1/Common/DiyPageController.php app/Interface/Api/Transformer/DiyPageTransformer.php tests/Unit/Interface/Api/Transformer/DiyPageTransformerTest.php
git commit -m "feat(diy): expose published page API"
```

---

## Chunk 3: Miniprogram Renderer

### Task 6: DIY Fetch Service and Types

**Files:**
- Create: `miniprogram/src/services/diy/page.ts`
- Create: `miniprogram/src/components/diy-renderer/types.ts`

- [ ] **Step 1: Add schema types**

Define `DiyPagePayload`, `DiyComponent`, `DiyLink`, and typed data shapes for the first seven component types.

- [ ] **Step 2: Add fetch service**

`fetchDiyPage(pageKey: string)` calls `/api/v1/diy/pages/${pageKey}` through existing `request()`.

- [ ] **Step 3: Run TypeScript check**

Run: `cd miniprogram && npm run test -- --runInBand`

Expected: existing tests pass or unrelated environment issue is documented.

- [ ] **Step 4: Commit**

```bash
git add miniprogram/src/services/diy/page.ts miniprogram/src/components/diy-renderer/types.ts
git commit -m "feat(miniprogram): add diy page service types"
```

### Task 7: Link Resolver and Renderer

**Files:**
- Create: `miniprogram/src/components/diy-renderer/link.ts`
- Create: `miniprogram/src/components/diy-renderer/index.tsx`
- Create: `miniprogram/src/components/diy-renderer/index.scss`
- Test: `miniprogram/src/components/diy-renderer/link.test.ts`
- Test: `miniprogram/src/components/diy-renderer/renderer.test.ts`

- [ ] **Step 1: Write failing tests**

Test:

- `page` links navigate to exact page path.
- `product` links resolve to goods detail path.
- unknown link type is ignored.
- unknown component type is skipped.

- [ ] **Step 2: Run tests to verify failure**

Run: `cd miniprogram && npm run test -- src/components/diy-renderer`

Expected: FAIL because renderer files do not exist.

- [ ] **Step 3: Implement link resolver**

Use Taro navigation and avoid DOM APIs. Keep H5/weapp logic behind existing platform helpers if needed.

- [ ] **Step 4: Implement renderer registry**

Map:

- `banner`
- `quick-nav`
- `image-ad`
- `product-group`
- `title-bar`
- `gap`
- `divider`

Skip unknown or disabled components.

- [ ] **Step 5: Run tests**

Run: `cd miniprogram && npm run test -- src/components/diy-renderer`

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add miniprogram/src/components/diy-renderer
git commit -m "feat(miniprogram): add diy renderer"
```

### Task 8: Basic Taro Components

**Files:**
- Create: `miniprogram/src/components/diy/Banner/index.tsx`
- Create: `miniprogram/src/components/diy/Banner/index.scss`
- Create: `miniprogram/src/components/diy/QuickNav/index.tsx`
- Create: `miniprogram/src/components/diy/QuickNav/index.scss`
- Create: `miniprogram/src/components/diy/ImageAd/index.tsx`
- Create: `miniprogram/src/components/diy/ImageAd/index.scss`
- Create: `miniprogram/src/components/diy/ProductGroup/index.tsx`
- Create: `miniprogram/src/components/diy/ProductGroup/index.scss`
- Create: `miniprogram/src/components/diy/TitleBar/index.tsx`
- Create: `miniprogram/src/components/diy/TitleBar/index.scss`
- Create: `miniprogram/src/components/diy/Gap/index.tsx`
- Create: `miniprogram/src/components/diy/Gap/index.scss`
- Create: `miniprogram/src/components/diy/Divider/index.tsx`
- Create: `miniprogram/src/components/diy/Divider/index.scss`

- [ ] **Step 1: Implement visual components**

Use Taro `View`, `Text`, `Image`, `Swiper`, `SwiperItem`. Reuse `GoodsList` and `GoodsCard` for `product-group`.

- [ ] **Step 2: Add empty-state guards**

Return `null` for empty image lists, empty nav lists, and empty product groups.

- [ ] **Step 3: Run miniprogram tests**

Run: `cd miniprogram && npm run test`

Expected: PASS.

- [ ] **Step 4: Commit**

```bash
git add miniprogram/src/components/diy
git commit -m "feat(miniprogram): add diy base components"
```

### Task 9: Homepage Fallback Integration

**Files:**
- Modify: `miniprogram/src/pages/home/index.tsx`

- [ ] **Step 1: Add DIY state**

Add state for `diyPage`, `diyLoading`, and `useDiyPage`.

- [ ] **Step 2: Load DIY page before fallback**

Call `fetchDiyPage('home')` in the existing load flow. If `components.length > 0`, render `DiyRenderer`; otherwise run existing `fetchHome()` path.

- [ ] **Step 3: Keep current homepage unchanged as fallback**

Do not delete existing `HomeH5View` or miniprogram view branches in this task.

- [ ] **Step 4: Run builds**

Run:

```bash
cd miniprogram
npm run build:weapp
npm run build:h5
```

Expected: both builds pass.

- [ ] **Step 5: Commit**

```bash
git add miniprogram/src/pages/home/index.tsx
git commit -m "feat(miniprogram): render diy homepage with fallback"
```

---

## Chunk 4: Web Admin Editor

### Task 10: Admin API Client and Schema Registry

**Files:**
- Create: `web/src/modules/mall/api/diyPage.ts`
- Create: `web/src/modules/mall/views/diy/schema/types.ts`
- Create: `web/src/modules/mall/views/diy/schema/componentRegistry.ts`

- [ ] **Step 1: Add API client**

Functions:

- `getDiyPage(pageKey: string)`
- `saveDiyDraft(pageKey: string, data)`
- `publishDiyPage(pageKey: string)`
- `resetDiyDraft(pageKey: string)`

- [ ] **Step 2: Add schema types**

Mirror miniprogram schema types, using web-friendly TypeScript names.

- [ ] **Step 3: Add component registry**

Define default data for each component and form metadata for `PropertyPanel`.

- [ ] **Step 4: Run type check**

Run: `cd web && npm run lint:tsc`

Expected: PASS or unrelated existing type errors documented.

- [ ] **Step 5: Commit**

```bash
git add web/src/modules/mall/api/diyPage.ts web/src/modules/mall/views/diy/schema
git commit -m "feat(web): add diy editor schema client"
```

### Task 11: Editor Shell and Component Panels

**Files:**
- Create: `web/src/modules/mall/views/diy/home/index.vue`
- Create: `web/src/modules/mall/views/diy/components/ComponentLibrary.vue`
- Create: `web/src/modules/mall/views/diy/components/PhonePreview.vue`
- Create: `web/src/modules/mall/views/diy/components/PropertyPanel.vue`

- [ ] **Step 1: Implement editor shell**

Use a dense operational layout:

- top toolbar
- left component library
- center phone preview
- right property panel

Avoid marketing-style cards; keep controls practical and compact.

- [ ] **Step 2: Implement component operations**

Support add, select, move up, move down, copy, delete, enable/disable.

- [ ] **Step 3: Implement draft and publish actions**

Use `useMessage()` for success and error feedback. Save the complete schema.

- [ ] **Step 4: Run type check**

Run: `cd web && npm run lint:tsc`

Expected: PASS or unrelated existing type errors documented.

- [ ] **Step 5: Commit**

```bash
git add web/src/modules/mall/views/diy/home/index.vue web/src/modules/mall/views/diy/components/ComponentLibrary.vue web/src/modules/mall/views/diy/components/PhonePreview.vue web/src/modules/mall/views/diy/components/PropertyPanel.vue
git commit -m "feat(web): add diy homepage editor"
```

### Task 12: Preview Renderers

**Files:**
- Create: `web/src/modules/mall/views/diy/components/renderers/BannerPreview.vue`
- Create: `web/src/modules/mall/views/diy/components/renderers/QuickNavPreview.vue`
- Create: `web/src/modules/mall/views/diy/components/renderers/ImageAdPreview.vue`
- Create: `web/src/modules/mall/views/diy/components/renderers/ProductGroupPreview.vue`
- Create: `web/src/modules/mall/views/diy/components/renderers/TitleBarPreview.vue`
- Create: `web/src/modules/mall/views/diy/components/renderers/GapPreview.vue`
- Create: `web/src/modules/mall/views/diy/components/renderers/DividerPreview.vue`

- [ ] **Step 1: Add preview components**

Render stable approximations of miniprogram components in a 375px phone preview.

- [ ] **Step 2: Ensure text fits**

Use fixed preview widths, line clamps, and no viewport-based font scaling.

- [ ] **Step 3: Run web build or type check**

Run: `cd web && npm run lint:tsc`

Expected: PASS or unrelated existing type errors documented.

- [ ] **Step 4: Commit**

```bash
git add web/src/modules/mall/views/diy/components/renderers
git commit -m "feat(web): add diy preview renderers"
```

### Task 13: Menu Entry and Permissions

**Files:**
- Modify: relevant menu seeder or create a new seeder, depending on current menu seeding pattern.
- Possibly modify: locale files if visible menu labels require translation.

- [ ] **Step 1: Inspect current menu seeders**

Use `databases/seeders/menu_*.php` to match the project pattern.

- [ ] **Step 2: Add menu entry**

Add menu for `商城管理 / 页面装修 / 首页装修`, component path `mall/views/diy/home/index`.

- [ ] **Step 3: Add permissions**

Permission codes:

- `mall:diy:read`
- `mall:diy:update`
- `mall:diy:publish`

- [ ] **Step 4: Commit**

```bash
git add databases/seeders
git commit -m "feat(diy): add admin menu entry"
```

---

## Chunk 5: Verification

### Task 14: Backend Verification

**Files:**
- No new files unless fixes are needed.

- [ ] **Step 1: Run targeted PHP tests**

Run:

```bash
vendor/bin/co-phpunit --prepend tests/bootstrap.php tests/Unit/Domain/Content/DiyPage
vendor/bin/co-phpunit --prepend tests/bootstrap.php tests/Unit/Interface/Api/Transformer/DiyPageTransformerTest.php
```

Expected: PASS.

- [ ] **Step 2: Run static analysis if practical**

Run: `composer analyse`

Expected: PASS or documented unrelated baseline issue.

- [ ] **Step 3: Commit fixes if needed**

```bash
git add <fixed files>
git commit -m "fix(diy): address backend verification issues"
```

### Task 15: Frontend Verification

**Files:**
- No new files unless fixes are needed.

- [ ] **Step 1: Run miniprogram checks**

Run:

```bash
cd miniprogram
npm run test
npm run build:weapp
npm run build:h5
```

Expected: PASS.

- [ ] **Step 2: Run web checks**

Run:

```bash
cd web
npm run lint:tsc
npm run build
```

Expected: PASS.

- [ ] **Step 3: Commit fixes if needed**

```bash
git add <fixed files>
git commit -m "fix(diy): address frontend verification issues"
```

### Task 16: Manual Smoke Test

**Files:**
- No new files unless fixes are needed.

- [ ] **Step 1: Start backend**

Run: `composer dev`

Expected: Hyperf starts without container errors.

- [ ] **Step 2: Start web admin**

Run: `cd web && npm run dev`

Expected: Vite gives a local URL.

- [ ] **Step 3: Exercise admin flow**

In browser:

- open homepage DIY editor
- add banner
- add quick nav
- add product group
- save draft
- publish

- [ ] **Step 4: Exercise public flow**

Call `GET /api/v1/diy/pages/home` with required API signature headers. Confirm it returns published schema.

- [ ] **Step 5: Exercise miniprogram H5**

Run: `cd miniprogram && npm run dev:h5`

Open generated H5 app and confirm homepage renders DIY page.

- [ ] **Step 6: Commit smoke fixes if needed**

```bash
git add <fixed files>
git commit -m "fix(diy): address smoke test issues"
```

---

## Notes for Execution

- Do not modify `DomainApiOrderCommandService`; it is unrelated to DIY decoration.
- Preserve existing `/api/v1/home` behavior until the new DIY homepage is verified.
- Do not delete the current miniprogram homepage layout; keep it as fallback in the first release.
- Use `apply_patch` for manual edits.
- Keep commits narrow and do not include unrelated dirty worktree changes.
