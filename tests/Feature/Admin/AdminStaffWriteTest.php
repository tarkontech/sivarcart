<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;
use SivarcartCore\SivarcartCore\Admin\AdminPermissions;
use SivarcartCore\SivarcartCore\Models\StoreRole;
use Workbench\App\Models\User;

/**
 * Sign a member of the staff of the store in.
 *
 * @param  list<string>  $permissions
 */
function staffOfTeam(array $permissions = ['*']): User
{
    $user = User::factory()->create();

    $user->assignStoreRole(StoreRole::factory()->create(['permissions' => $permissions]));

    test()->actingAs($user);

    return $user;
}

it('shows the staff of the store', function () {
    staffOfTeam();

    $member = User::factory()->create(['name' => 'Rosa Ramirez']);
    $member->assignStoreRole(StoreRole::factory()->create(['slug' => 'encargada']));

    $this->get('/sivarcart/admin/staff')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Staff/Index')
            ->has('staff.data', 2)
            ->where('staff.data.1.name', 'Rosa Ramirez')
            ->where('staff.data.1.roles.0', 'encargada')
            ->has('roles')
            ->where('canUpdate', true),
        );
});

it('gives a member of the staff the roles the staff chose', function () {
    staffOfTeam();

    $member = User::factory()->create();

    StoreRole::factory()->create(['slug' => 'encargada']);
    StoreRole::factory()->create(['slug' => 'cajera']);

    $this->put('/sivarcart/admin/staff/'.$member->getKey(), [
        'roles' => ['encargada'],
    ])->assertRedirect();

    expect($member->fresh()->storeRoles()->pluck('slug')->all())->toBe(['encargada']);
});

it('takes the roles of the store away from a member of the staff', function () {
    staffOfTeam();

    $member = User::factory()->create();
    $member->assignStoreRole(StoreRole::factory()->create(['slug' => 'encargada']));

    $this->delete('/sivarcart/admin/staff/'.$member->getKey())->assertRedirect();

    expect($member->fresh()->storeRoles()->count())->toBe(0)
        ->and(User::query()->whereKey($member->getKey())->exists())->toBeTrue();
});

it('creates a member of the staff of the store', function () {
    staffOfTeam();

    $role = StoreRole::factory()->create(['slug' => 'encargada']);

    $this->post('/sivarcart/admin/staff', [
        'name' => 'Rosa Ramirez',
        'email' => 'rosa@example.test',
        'password' => 'una-clave-larga',
        'password_confirmation' => 'una-clave-larga',
        'roles' => ['encargada'],
    ])->assertRedirect();

    $member = User::query()->where('email', 'rosa@example.test')->firstOrFail();

    expect($member->name)->toBe('Rosa Ramirez')
        ->and($member->storeRoles()->pluck('slug')->all())->toBe(['encargada']);
});

it('does not create a member of the staff with a short password', function () {
    staffOfTeam();

    $this->post('/sivarcart/admin/staff', [
        'name' => 'Rosa Ramirez',
        'email' => 'rosa@example.test',
        'password' => 'corta',
        'password_confirmation' => 'corta',
    ])->assertSessionHasErrors('password');

    expect(User::query()->where('email', 'rosa@example.test')->exists())->toBeFalse();
});

it('does not write the staff to the staff that cannot', function () {
    staffOfTeam(['staff.view']);

    $member = User::factory()->create();

    $this->put('/sivarcart/admin/staff/'.$member->getKey(), [
        'roles' => ['encargada'],
    ])->assertForbidden();

    $this->post('/sivarcart/admin/staff', [
        'name' => 'Rosa Ramirez',
        'email' => 'rosa@example.test',
        'password' => 'una-clave-larga',
        'password_confirmation' => 'una-clave-larga',
    ])->assertForbidden();

    expect(User::query()->where('email', 'rosa@example.test')->exists())->toBeFalse();
});

it('writes the permissions of a role of the store', function () {
    staffOfTeam();

    $role = StoreRole::factory()->create(['slug' => 'encargada', 'permissions' => ['catalog.view']]);

    $this->put('/sivarcart/admin/roles/'.$role->slug, [
        'name' => $role->name,
        'permissions' => ['catalog.view', 'catalog.update'],
    ])->assertRedirect();

    expect($role->fresh()->permissions)->toBe(['catalog.view', 'catalog.update']);
});

it('keeps the wildcard of a role that the staff did not touch', function () {
    staffOfTeam();

    $role = StoreRole::factory()->create(['slug' => 'duena', 'permissions' => ['*']]);

    $this->put('/sivarcart/admin/roles/duena', [
        'name' => $role->name,
        'permissions' => AdminPermissions::all(),
    ])->assertRedirect();

    expect($role->fresh()->permissions)->toBe(['*']);
});

it('takes the wildcard away when the staff unchecks one of its permissions', function () {
    staffOfTeam();

    $role = StoreRole::factory()->create(['slug' => 'duena', 'permissions' => ['*']]);

    $permissions = AdminPermissions::all();

    $this->put('/sivarcart/admin/roles/duena', [
        'name' => $role->name,
        'permissions' => array_values(array_filter(
            $permissions,
            fn (string $permission): bool => $permission !== 'catalog.update',
        )),
    ])->assertRedirect();

    expect($role->fresh()->permissions)->not->toContain('*')
        ->and($role->fresh()->permissions)->toContain('catalog.view');
});

it('keeps the group of a role that the staff did not touch', function () {
    staffOfTeam();

    $role = StoreRole::factory()->create(['slug' => 'encargada', 'permissions' => ['catalog.*']]);

    $this->put('/sivarcart/admin/roles/encargada', [
        'name' => $role->name,
        'permissions' => ['catalog.view', 'catalog.update'],
    ])->assertRedirect();

    expect($role->fresh()->permissions)->toBe(['catalog.*']);
});

it('tells the panel which permissions a role grants', function () {
    staffOfTeam();

    StoreRole::factory()->create(['slug' => 'duena', 'permissions' => ['*']]);
    StoreRole::factory()->create(['slug' => 'encargada', 'permissions' => ['catalog.*']]);

    $this->get('/sivarcart/admin/roles')
        ->assertInertia(fn (Assert $page) => $page
            ->where('roles', function (Collection $roles): bool {
                $bySlug = [];

                foreach ($roles as $role) {
                    $bySlug[$role['slug']] = $role;
                }

                expect($bySlug['duena']['granted'])->toBe(AdminPermissions::all())
                    ->and($bySlug['encargada']['granted'])->toBe(['catalog.view', 'catalog.update']);

                return true;
            }),
        );
});

it('does not remove a role that the package brought', function () {
    staffOfTeam();

    $role = StoreRole::factory()->system()->create(['slug' => 'owner']);

    $this->delete('/sivarcart/admin/roles/owner')->assertRedirect();

    expect(StoreRole::query()->where('slug', 'owner')->exists())->toBeTrue();
});

it('removes a role of the store that the package did not bring', function () {
    staffOfTeam();

    $role = StoreRole::factory()->create(['slug' => 'encargada']);

    $this->delete('/sivarcart/admin/roles/encargada')->assertRedirect();

    expect(StoreRole::query()->where('slug', 'encargada')->exists())->toBeFalse();
});

it('does not write the roles to the staff that cannot', function () {
    staffOfTeam(['roles.view']);

    $role = StoreRole::factory()->create(['slug' => 'encargada', 'permissions' => []]);

    $this->put('/sivarcart/admin/roles/encargada', [
        'name' => $role->name,
        'permissions' => ['*'],
    ])->assertForbidden();

    expect($role->fresh()->permissions)->toBe([]);
});

it('shows the vocabulary of the permissions of the store in the roles screen', function () {
    staffOfTeam();

    $this->get('/sivarcart/admin/roles')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('permissions')
            ->where('permissions.0', 'settings.view')
            ->where('canUpdate', true),
        );
});
