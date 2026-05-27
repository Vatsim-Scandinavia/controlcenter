# Ratings Admin Page Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a Livewire + Flux v4 admin page at `/admin/ratings` for creating, editing, deleting, and reordering ratings with an area filter.

**Architecture:** A Livewire component (`App\Livewire\Admin\Ratings`) handles all interactive logic and is embedded in a thin Blade wrapper view that extends the existing `layouts.app` layout. Tailwind v3 is added with `prefix: 'tw-'` and `corePlugins.preflight: false` so it coexists with Bootstrap without style conflicts. SortableJS is exposed on `window` from `app.js` and invoked inside Livewire's `@script` block.

**Tech Stack:** Livewire 3, Flux v4 (`livewire/flux`), Tailwind v3, SortableJS, Laravel 12, PHPUnit 11

---

## Files

| Action | Path | Purpose |
|--------|------|---------|
| Modify | `composer.json` | livewire/livewire + livewire/flux |
| Modify | `package.json` | tailwindcss@3, @tailwindcss/forms, autoprefixer, sortablejs |
| Create | `tailwind.config.js` | Tailwind v3 config with prefix + content paths |
| Create | `postcss.config.js` | Wire Tailwind + autoprefixer into Vite's CSS pipeline |
| Create | `resources/css/flux.css` | Tailwind directives entry point |
| Modify | `vite.config.js` | Add `resources/css/flux.css` entry |
| Modify | `resources/js/app.js` | Expose `window.Sortable` |
| Modify | `resources/views/layouts/header.blade.php` | Add `@livewireStyles` + Flux CSS |
| Modify | `resources/views/layouts/app.blade.php` | Add `@livewireScripts` before `</body>` |
| Create | `database/migrations/2026_05_27_000000_add_sort_order_to_ratings_table.php` | Add `sort_order` column |
| Modify | `app/Models/Rating.php` | Add `$fillable` |
| Modify | `database/factories/RatingFactory.php` | Add `sort_order` |
| Modify | `config/roles.php` | Add `manage-ratings` permission |
| Create | `app/Policies/RatingPolicy.php` | Gate on `manage-ratings` |
| Create | `app/Livewire/Admin/Ratings.php` | Livewire component — list, CRUD, reorder, filter |
| Create | `resources/views/livewire/admin/ratings.blade.php` | Component view |
| Create | `resources/views/admin/ratings/index.blade.php` | Wrapper view extending layouts.app |
| Modify | `routes/web.php` | Add `/admin/ratings` route |
| Modify | `resources/views/layouts/sidebar.blade.php` | Add Ratings link |
| Create | `tests/Feature/RatingsAdminTest.php` | Feature tests for policy + component |

---

### Task 1: Install PHP dependencies and update layout

**Files:**
- Modify: `resources/views/layouts/header.blade.php`
- Modify: `resources/views/layouts/app.blade.php`

- [ ] **Step 1: Install Livewire and Flux**

```bash
composer require livewire/livewire livewire/flux:^4.0
```

Expected: Both packages install without errors.

- [ ] **Step 2: Publish Flux assets**

```bash
php artisan flux:install
```

If `flux:install` does not exist, run:

```bash
php artisan vendor:publish --tag=flux-components
```

Expected: Flux publishes its views/assets without errors.

- [ ] **Step 3: Add `@livewireStyles` to header**

In `resources/views/layouts/header.blade.php`, add after the existing `@vite()` call (line 30):

```blade
@vite(['resources/js/theme.js', 'resources/sass/app.scss'])

@livewireStyles
```

- [ ] **Step 4: Add `@livewireScripts` to app layout**

In `resources/views/layouts/app.blade.php`, insert before `</body>` (after `@yield('js')` on line 77):

```blade
    @yield('js')

    @livewireScripts
    </body>
</html>
```

- [ ] **Step 5: Verify the app still loads**

```bash
php artisan serve --port=8080 &
curl -s -o /dev/null -w "%{http_code}" http://localhost:8080/login
kill %1
```

Expected: `302` (redirect to login — app is up).

- [ ] **Step 6: Commit**

```bash
jj describe -m "chore: install Livewire 3 and Flux v4" && jj new
```

---

### Task 2: Install JS dependencies and configure Tailwind v3

**Files:**
- Create: `tailwind.config.js`
- Create: `postcss.config.js`
- Create: `resources/css/flux.css`
- Modify: `vite.config.js`
- Modify: `resources/js/app.js`
- Modify: `resources/views/layouts/header.blade.php`

- [ ] **Step 1: Install npm packages**

```bash
npm install -D tailwindcss@3 @tailwindcss/forms autoprefixer sortablejs
```

Expected: Packages added to `node_modules` and `package.json`.

- [ ] **Step 2: Create `tailwind.config.js`**

```js
/** @type {import('tailwindcss').Config} */
export default {
    prefix: 'tw-',
    content: [
        './resources/views/livewire/**/*.blade.php',
        './app/Livewire/**/*.php',
        './vendor/livewire/flux/stubs/**/*.blade.php',
    ],
    corePlugins: {
        preflight: false,
    },
    theme: {
        extend: {},
    },
    plugins: [
        require('@tailwindcss/forms'),
    ],
};
```

- [ ] **Step 3: Create `postcss.config.js`**

```js
export default {
    plugins: {
        tailwindcss: {},
        autoprefixer: {},
    },
};
```

- [ ] **Step 4: Create `resources/css/flux.css`**

```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

- [ ] **Step 5: Add `resources/css/flux.css` to Vite inputs**

In `vite.config.js`, add the CSS file to the `input` array:

```js
laravel({
    input: [
        "/resources/sass/app.scss",
        "/resources/js/app.js",
        "/resources/css/flux.css",   // ← add this line
        "/resources/js/theme.js",
        "/resources/js/vue.js",
        "/resources/js/easymde.js",
        "/resources/sass/easymde.scss",
        "/resources/js/chart.js",
        "/resources/js/flatpickr.js",
        "/resources/sass/flatpickr.scss",
        "/resources/js/bootstrap-table.js",
        "/resources/sass/bootstrap-table.scss",
    ],
    refresh: true,
}),
```

- [ ] **Step 6: Include `flux.css` in the header**

In `resources/views/layouts/header.blade.php`, update the `@vite()` call to include the new CSS file:

```blade
@vite(['resources/js/theme.js', 'resources/sass/app.scss', 'resources/css/flux.css'])

@livewireStyles
```

- [ ] **Step 7: Expose SortableJS on `window`**

In `resources/js/app.js`, add at the top:

```js
import Sortable from 'sortablejs';
window.Sortable = Sortable;
```

- [ ] **Step 8: Build assets and verify**

```bash
npm run build 2>&1 | tail -20
```

Expected: Build completes without errors. Output includes `flux.css` in the manifest.

- [ ] **Step 9: Commit**

```bash
jj describe -m "chore: add Tailwind v3 + SortableJS" && jj new
```

---

### Task 3: Add `sort_order` to ratings table + update model and factory

**Files:**
- Create: `database/migrations/2026_05_27_000000_add_sort_order_to_ratings_table.php`
- Modify: `app/Models/Rating.php`
- Modify: `database/factories/RatingFactory.php`

- [ ] **Step 1: Create the migration**

```bash
php artisan make:migration add_sort_order_to_ratings_table
```

Replace the generated file's content with:

```php
<?php

use App\Models\Rating;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('endorsement_type');
        });

        Rating::orderBy('id')->each(function (Rating $rating, int $index) {
            $rating->updateQuietly(['sort_order' => $index + 1]);
        });
    }

    public function down(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
```

- [ ] **Step 2: Run the migration**

```bash
php artisan migrate
```

Expected: `Migrating: ...add_sort_order_to_ratings_table` → `Migrated`.

- [ ] **Step 3: Update `Rating` model with `$fillable`**

Replace the contents of `app/Models/Rating.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'vatsim_rating',
        'endorsement_type',
        'sort_order',
    ];

    public function trainings()
    {
        return $this->belongsToMany(Training::class);
    }

    public function endorsements()
    {
        return $this->belongsToMany(Endorsement::class);
    }

    public function areas()
    {
        return $this->belongsToMany(Area::class)->withPivot('required_vatsim_rating', 'allow_bundling', 'hour_requirement', 'queue_length_low', 'queue_length_high');
    }

    public function requiredByPositions()
    {
        return $this->hasMany(Position::class, 'required_facility_rating_id');
    }
}
```

- [ ] **Step 4: Update `RatingFactory` to include `sort_order`**

Replace `database/factories/RatingFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Rating;
use Illuminate\Database\Eloquent\Factories\Factory;

class RatingFactory extends Factory
{
    protected $model = Rating::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->lexify('??-???'),
            'description' => $this->faker->sentence(6),
            'vatsim_rating' => $this->faker->numberBetween(2, 5),
            'endorsement_type' => null,
            'sort_order' => $this->faker->unique()->numberBetween(1, 1000),
        ];
    }
}
```

- [ ] **Step 5: Run existing tests to confirm no regressions**

```bash
php artisan test
```

Expected: All previously-passing tests continue to pass.

- [ ] **Step 6: Commit**

```bash
jj describe -m "feat(ratings): add sort_order column and update model" && jj new
```

---

### Task 4: Add `manage-ratings` permission and `RatingPolicy` (TDD)

**Files:**
- Create: `tests/Feature/RatingsAdminTest.php`
- Modify: `config/roles.php`
- Create: `app/Policies/RatingPolicy.php`

- [ ] **Step 1: Write the failing policy tests**

Create `tests/Feature/RatingsAdminTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RatingsAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->admin->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        $this->user = User::factory()->create();
    }

    #[Test]
    public function admin_can_view_any_rating()
    {
        $this->assertTrue($this->admin->can('viewAny', Rating::class));
    }

    #[Test]
    public function non_admin_cannot_view_any_rating()
    {
        $this->assertFalse($this->user->can('viewAny', Rating::class));
    }

    #[Test]
    public function admin_can_create_rating()
    {
        $this->assertTrue($this->admin->can('create', Rating::class));
    }

    #[Test]
    public function admin_can_update_rating()
    {
        $rating = Rating::factory()->create();
        $this->assertTrue($this->admin->can('update', $rating));
    }

    #[Test]
    public function admin_can_delete_rating()
    {
        $rating = Rating::factory()->create();
        $this->assertTrue($this->admin->can('delete', $rating));
    }

    #[Test]
    public function non_admin_cannot_create_rating()
    {
        $this->assertFalse($this->user->can('create', Rating::class));
    }
}
```

- [ ] **Step 2: Run tests to confirm they fail**

```bash
php artisan test tests/Feature/RatingsAdminTest.php
```

Expected: 6 tests fail — policy does not exist yet.

- [ ] **Step 3: Add `manage-ratings` to `config/roles.php`**

In the `matrix` section of `config/roles.php`, add after the `manage-positions` line:

```php
// Infrastructure
'manage-positions' => ['admin', 'moderator', 'nav-editor'],
'manage-ratings' => ['admin'],
```

- [ ] **Step 4: Create `app/Policies/RatingPolicy.php`**

```php
<?php

namespace App\Policies;

use App\Models\Rating;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RatingPolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('manage-ratings');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('manage-ratings');
    }

    public function update(User $user, Rating $rating): bool
    {
        return $user->hasPermission('manage-ratings');
    }

    public function delete(User $user, Rating $rating): bool
    {
        return $user->hasPermission('manage-ratings');
    }
}
```

- [ ] **Step 5: Run the policy tests**

```bash
php artisan test tests/Feature/RatingsAdminTest.php
```

Expected: All 6 tests pass.

- [ ] **Step 6: Commit**

```bash
jj describe -m "feat(ratings): add manage-ratings permission and RatingPolicy" && jj new
```

---

### Task 5: Livewire Ratings component (TDD)

**Files:**
- Modify: `tests/Feature/RatingsAdminTest.php`
- Create: `app/Livewire/Admin/Ratings.php`
- Create: `resources/views/livewire/admin/ratings.blade.php`

- [ ] **Step 1: Add Livewire component tests to `RatingsAdminTest.php`**

Append these test methods to `tests/Feature/RatingsAdminTest.php` (inside the class, after existing methods):

```php
    #[Test]
    public function admin_can_load_ratings_component()
    {
        \Livewire\Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\Ratings::class)
            ->assertOk();
    }

    #[Test]
    public function non_admin_is_forbidden_from_ratings_component()
    {
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        \Livewire\Livewire::actingAs($this->user)
            ->test(\App\Livewire\Admin\Ratings::class);
    }

    #[Test]
    public function admin_can_create_a_rating()
    {
        \Livewire\Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\Ratings::class)
            ->call('openCreate')
            ->assertSet('showModal', true)
            ->set('name', 'S1')
            ->set('description', 'Student Pilot 1')
            ->set('vatsim_rating', 1)
            ->call('save')
            ->assertSet('showModal', false)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('ratings', ['name' => 'S1', 'description' => 'Student Pilot 1']);
    }

    #[Test]
    public function admin_can_edit_a_rating()
    {
        $rating = Rating::factory()->create(['name' => 'OldName']);

        \Livewire\Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\Ratings::class)
            ->call('openEdit', $rating->id)
            ->assertSet('editing', $rating->id)
            ->assertSet('name', 'OldName')
            ->set('name', 'NewName')
            ->call('save')
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('ratings', ['id' => $rating->id, 'name' => 'NewName']);
    }

    #[Test]
    public function admin_can_delete_a_rating()
    {
        $rating = Rating::factory()->create();

        \Livewire\Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\Ratings::class)
            ->call('delete', $rating->id);

        $this->assertDatabaseMissing('ratings', ['id' => $rating->id]);
    }

    #[Test]
    public function reorder_updates_sort_order()
    {
        $r1 = Rating::factory()->create(['sort_order' => 1]);
        $r2 = Rating::factory()->create(['sort_order' => 2]);

        \Livewire\Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\Ratings::class)
            ->call('reorder', [$r2->id, $r1->id]);

        $this->assertDatabaseHas('ratings', ['id' => $r2->id, 'sort_order' => 1]);
        $this->assertDatabaseHas('ratings', ['id' => $r1->id, 'sort_order' => 2]);
    }

    #[Test]
    public function area_filter_scopes_ratings()
    {
        $area = Area::factory()->create();
        $inArea = Rating::factory()->create();
        $inArea->areas()->attach($area->id, [
            'required_vatsim_rating' => 1,
            'allow_bundling' => false,
            'hour_requirement' => 0,
            'queue_length_low' => 0,
            'queue_length_high' => 0,
        ]);
        $notInArea = Rating::factory()->create();

        $component = \Livewire\Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\Ratings::class)
            ->set('selectedArea', $area->id);

        $ids = $component->viewData('this')->ratings->pluck('id')->toArray();
        $this->assertContains($inArea->id, $ids);
        $this->assertNotContains($notInArea->id, $ids);
    }

    #[Test]
    public function save_validates_name_required()
    {
        \Livewire\Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\Ratings::class)
            ->call('openCreate')
            ->set('name', '')
            ->set('description', 'Some description')
            ->call('save')
            ->assertHasErrors(['name' => 'required']);
    }
```

- [ ] **Step 2: Run tests to confirm they all fail**

```bash
php artisan test tests/Feature/RatingsAdminTest.php
```

Expected: New Livewire tests fail (class not found). Policy tests still pass.

- [ ] **Step 3: Create `app/Livewire/Admin/Ratings.php`**

```php
<?php

namespace App\Livewire\Admin;

use App\Models\Area;
use App\Models\Rating;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Ratings extends Component
{
    public ?int $selectedArea = null;
    public bool $showModal = false;
    public ?int $editing = null;

    public string $name = '';
    public string $description = '';
    public ?int $vatsim_rating = null;
    public ?string $endorsement_type = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Rating::class);
    }

    #[Computed]
    public function ratings()
    {
        $query = Rating::orderBy('sort_order');

        if ($this->selectedArea) {
            $query->whereHas('areas', fn ($q) => $q->where('areas.id', $this->selectedArea));
        }

        return $query->get();
    }

    #[Computed]
    public function areas()
    {
        return Area::orderBy('name')->get();
    }

    public function openCreate(): void
    {
        $this->reset(['name', 'description', 'vatsim_rating', 'endorsement_type', 'editing']);
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $rating = Rating::findOrFail($id);
        $this->editing = $id;
        $this->name = $rating->name;
        $this->description = $rating->description;
        $this->vatsim_rating = $rating->vatsim_rating;
        $this->endorsement_type = $rating->endorsement_type;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name'             => 'required|string|max:16',
            'description'      => 'required|string|max:100',
            'vatsim_rating'    => 'nullable|integer',
            'endorsement_type' => 'nullable|string|max:255',
        ]);

        if ($this->editing) {
            Rating::findOrFail($this->editing)->update([
                'name'             => $this->name,
                'description'      => $this->description,
                'vatsim_rating'    => $this->vatsim_rating,
                'endorsement_type' => $this->endorsement_type,
            ]);
        } else {
            Rating::create([
                'name'             => $this->name,
                'description'      => $this->description,
                'vatsim_rating'    => $this->vatsim_rating,
                'endorsement_type' => $this->endorsement_type,
                'sort_order'       => (Rating::max('sort_order') ?? 0) + 1,
            ]);
        }

        $this->showModal = false;
        unset($this->ratings);
    }

    public function delete(int $id): void
    {
        Rating::findOrFail($id)->delete();
        unset($this->ratings);
    }

    public function reorder(array $ids): void
    {
        foreach ($ids as $order => $id) {
            Rating::where('id', $id)->update(['sort_order' => $order + 1]);
        }
        unset($this->ratings);
    }

    public function render()
    {
        return view('livewire.admin.ratings');
    }
}
```

- [ ] **Step 4: Create `resources/views/livewire/admin/ratings.blade.php`**

First, create the directory:

```bash
mkdir -p resources/views/livewire/admin
```

Then create the file:

```blade
<div>
    {{-- Toolbar --}}
    <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
        <flux:select wire:model.live="selectedArea" class="tw-w-48">
            <option value="">All areas</option>
            @foreach($this->areas as $area)
                <option value="{{ $area->id }}">{{ $area->name }}</option>
            @endforeach
        </flux:select>

        <flux:button wire:click="openCreate" variant="primary">
            Create Rating
        </flux:button>
    </div>

    {{-- Table --}}
    <div class="tw-bg-white dark:tw-bg-gray-800 tw-rounded-lg tw-shadow tw-overflow-hidden">
        <table class="tw-w-full tw-text-sm">
            <thead class="tw-bg-gray-50 dark:tw-bg-gray-700 tw-text-gray-600 dark:tw-text-gray-300 tw-uppercase tw-text-xs">
                <tr>
                    <th class="tw-w-10 tw-px-4 tw-py-3"></th>
                    <th class="tw-px-4 tw-py-3 tw-text-left">Name</th>
                    <th class="tw-px-4 tw-py-3 tw-text-left">Description</th>
                    <th class="tw-px-4 tw-py-3 tw-text-left">VATSIM Rating</th>
                    <th class="tw-px-4 tw-py-3 tw-text-left">Endorsement Type</th>
                    <th class="tw-px-4 tw-py-3"></th>
                </tr>
            </thead>
            <tbody id="ratings-tbody" wire:ignore.self>
                @foreach($this->ratings as $rating)
                    <tr data-id="{{ $rating->id }}" class="tw-border-t tw-border-gray-100 dark:tw-border-gray-700 hover:tw-bg-gray-50 dark:hover:tw-bg-gray-750">
                        <td class="tw-px-4 tw-py-3 tw-text-gray-400 tw-cursor-grab drag-handle">
                            <svg class="tw-w-4 tw-h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M9 3h2v2H9zm4 0h2v2h-2zM9 7h2v2H9zm4 0h2v2h-2zM9 11h2v2H9zm4 0h2v2h-2zM9 15h2v2H9zm4 0h2v2h-2zM9 19h2v2H9zm4 0h2v2h-2z"/>
                            </svg>
                        </td>
                        <td class="tw-px-4 tw-py-3 tw-font-medium tw-text-gray-900 dark:tw-text-gray-100">{{ $rating->name }}</td>
                        <td class="tw-px-4 tw-py-3 tw-text-gray-600 dark:tw-text-gray-400">{{ $rating->description }}</td>
                        <td class="tw-px-4 tw-py-3">
                            @if($rating->vatsim_rating === null)
                                <span class="tw-inline-flex tw-items-center tw-px-2 tw-py-0.5 tw-rounded tw-text-xs tw-font-medium tw-bg-purple-100 tw-text-purple-800">Endorsement</span>
                            @else
                                <span class="tw-text-gray-700 dark:tw-text-gray-300">{{ $rating->vatsim_rating }}</span>
                            @endif
                        </td>
                        <td class="tw-px-4 tw-py-3 tw-text-gray-600 dark:tw-text-gray-400">{{ $rating->endorsement_type ?? '—' }}</td>
                        <td class="tw-px-4 tw-py-3 tw-text-right tw-space-x-2">
                            <flux:button wire:click="openEdit({{ $rating->id }})" size="sm" variant="ghost">Edit</flux:button>
                            <flux:button
                                wire:click="delete({{ $rating->id }})"
                                wire:confirm="Delete '{{ $rating->name }}'? This cannot be undone."
                                size="sm"
                                variant="danger"
                            >Delete</flux:button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if($this->ratings->isEmpty())
            <div class="tw-px-4 tw-py-8 tw-text-center tw-text-gray-400 dark:tw-text-gray-500">
                No ratings found.
            </div>
        @endif
    </div>

    {{-- Create / Edit Modal --}}
    <flux:modal wire:model="showModal" class="tw-max-w-lg tw-w-full">
        <flux:modal.header>
            <flux:heading>{{ $editing ? 'Edit Rating' : 'Create Rating' }}</flux:heading>
        </flux:modal.header>

        <flux:modal.body>
            <div class="tw-space-y-4">
                <div>
                    <flux:label for="name">Name <span class="tw-text-red-500">*</span></flux:label>
                    <flux:input id="name" wire:model="name" maxlength="16" placeholder="e.g. S1" />
                    <flux:error name="name" />
                </div>
                <div>
                    <flux:label for="description">Description <span class="tw-text-red-500">*</span></flux:label>
                    <flux:input id="description" wire:model="description" maxlength="100" placeholder="e.g. Student Pilot" />
                    <flux:error name="description" />
                </div>
                <div>
                    <flux:label for="vatsim_rating">VATSIM Rating</flux:label>
                    <flux:input id="vatsim_rating" wire:model="vatsim_rating" type="number" placeholder="Leave blank for Endorsement" />
                    <flux:error name="vatsim_rating" />
                </div>
                <div>
                    <flux:label for="endorsement_type">Endorsement Type</flux:label>
                    <flux:input id="endorsement_type" wire:model="endorsement_type" />
                    <flux:error name="endorsement_type" />
                </div>
            </div>
        </flux:modal.body>

        <flux:modal.footer>
            <flux:button wire:click="$set('showModal', false)" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="save" variant="primary">
                {{ $editing ? 'Update' : 'Create' }}
            </flux:button>
        </flux:modal.footer>
    </flux:modal>

    @script
    <script>
        new Sortable(document.getElementById('ratings-tbody'), {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function () {
                const ids = [...document.querySelectorAll('#ratings-tbody tr')].map(
                    tr => parseInt(tr.dataset.id)
                );
                $wire.reorder(ids);
            },
        });
    </script>
    @endscript
</div>
```

- [ ] **Step 5: Run the Livewire component tests**

```bash
php artisan test tests/Feature/RatingsAdminTest.php
```

Expected: All 12 tests pass.

- [ ] **Step 6: Run the full test suite**

```bash
php artisan test
```

Expected: All previously-passing tests continue to pass.

- [ ] **Step 7: Commit**

```bash
jj describe -m "feat(ratings): add Livewire Ratings admin component" && jj new
```

---

### Task 6: Route, wrapper view, and sidebar link

**Files:**
- Create: `resources/views/admin/ratings/index.blade.php`
- Modify: `routes/web.php`
- Modify: `resources/views/layouts/sidebar.blade.php`

- [ ] **Step 1: Create the wrapper view**

```bash
mkdir -p resources/views/admin/ratings
```

Create `resources/views/admin/ratings/index.blade.php`:

```blade
@extends('layouts.app')

@section('title', 'Ratings')

@section('content')
    @livewire('admin.ratings')
@endsection
```

- [ ] **Step 2: Add the route**

In `routes/web.php`, add inside the existing authenticated middleware group alongside the other admin routes (near the positions routes):

```php
Route::get('/admin/ratings', function () {
    abort_unless(auth()->user()->can('viewAny', \App\Models\Rating::class), 403);
    return view('admin.ratings.index');
})->name('ratings.index');
```

- [ ] **Step 3: Add the sidebar link**

In `resources/views/layouts/sidebar.blade.php`, add the Ratings link after the Positions link:

```blade
    @can('viewAny', App\Models\Position::class)
        <a class="collapse-item" href="{{ route('positions.index') }}">Positions</a>
    @endcan
    @can('viewAny', App\Models\Rating::class)
        <a class="collapse-item" href="{{ route('ratings.index') }}">Ratings</a>
    @endcan
```

- [ ] **Step 4: Verify the page loads**

```bash
php artisan test --filter=RatingsAdminTest
```

Expected: All 12 tests pass.

- [ ] **Step 5: Run the full test suite**

```bash
php artisan test
```

Expected: All tests pass (3 incomplete activity-log tests remain incomplete).

- [ ] **Step 6: Commit**

```bash
jj describe -m "feat(ratings): add route, view, and sidebar link" && jj new
```
