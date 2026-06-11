<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Manager;
use App\Models\Officer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DepartmentReadAccessTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';

    private Department $activeDepartment;

    private Department $inactiveDepartment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activeDepartment = Department::factory()->create([
            'code' => 'active-dept',
            'name' => 'Active Department',
            'is_active' => true,
        ]);

        $this->inactiveDepartment = Department::factory()->create([
            'code' => 'inactive-dept',
            'name' => 'Inactive Department',
            'is_active' => false,
        ]);
    }

    /**
     * Create an active main manager who may view inactive departments.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function mainManager(array $overrides = []): Manager
    {
        $manager = Manager::create(array_merge([
            'username' => 'read-main-manager',
            'email' => 'read.main.manager@example.com',
            'password' => self::PASSWORD,
        ], $overrides));

        $manager->forceFill(['is_main_manager' => true])->save();

        return $manager->refresh();
    }

    /**
     * Build the request headers for an authenticated actor.
     *
     * @return array<string, string>
     */
    private function authHeaders($actor): array
    {
        return ['Authorization' => 'Bearer '.$actor->createToken('test-token')->plainTextToken];
    }

    /**
     * @return array<int, string>
     */
    private function departmentResourceKeys(): array
    {
        return ['id', 'code', 'name', 'is_active', 'categories_count', 'created_at', 'updated_at'];
    }

    /**
     * @return Collection<int, int>
     */
    private function departmentIdsFromIndexResponse(TestResponse $response): Collection
    {
        return collect($response->json('data'))->pluck('id');
    }

    public function test_unauthenticated_index_returns_only_active_departments(): void
    {
        $response = $this->getJson('/api/departments');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => $this->departmentResourceKeys(),
                ],
            ]);

        $ids = $this->departmentIdsFromIndexResponse($response);

        $this->assertTrue($ids->contains($this->activeDepartment->id));
        $this->assertFalse($ids->contains($this->inactiveDepartment->id));
    }

    public function test_unauthenticated_show_returns_active_department(): void
    {
        $this->getJson("/api/departments/{$this->activeDepartment->id}")
            ->assertOk()
            ->assertJsonStructure($this->departmentResourceKeys())
            ->assertJsonPath('id', $this->activeDepartment->id)
            ->assertJsonPath('code', 'active-dept')
            ->assertJsonPath('name', 'Active Department')
            ->assertJsonPath('is_active', true);
    }

    public function test_unauthenticated_show_returns_404_for_inactive_department(): void
    {
        $this->getJson("/api/departments/{$this->inactiveDepartment->id}")
            ->assertNotFound();
    }

    public function test_main_manager_index_includes_inactive_departments(): void
    {
        $manager = $this->mainManager();

        $response = $this->withHeaders($this->authHeaders($manager))
            ->getJson('/api/departments');

        $response->assertOk();

        $ids = $this->departmentIdsFromIndexResponse($response);

        $this->assertTrue($ids->contains($this->activeDepartment->id));
        $this->assertTrue($ids->contains($this->inactiveDepartment->id));
    }

    public function test_main_manager_show_returns_inactive_department(): void
    {
        $manager = $this->mainManager();

        $this->withHeaders($this->authHeaders($manager))
            ->getJson("/api/departments/{$this->inactiveDepartment->id}")
            ->assertOk()
            ->assertJsonPath('id', $this->inactiveDepartment->id)
            ->assertJsonPath('code', 'inactive-dept')
            ->assertJsonPath('is_active', false);
    }

    public function test_officer_index_returns_only_active_departments(): void
    {
        $officer = Officer::create([
            'username' => 'read-officer',
            'email' => 'read.officer@example.com',
            'password' => self::PASSWORD,
            'badge_number' => 'BOA-READ',
        ]);

        $response = $this->withHeaders($this->authHeaders($officer))
            ->getJson('/api/departments');

        $response->assertOk();

        $ids = $this->departmentIdsFromIndexResponse($response);

        $this->assertTrue($ids->contains($this->activeDepartment->id));
        $this->assertFalse($ids->contains($this->inactiveDepartment->id));
    }

    public function test_unauthenticated_index_does_not_require_auth_header(): void
    {
        $response = $this->getJson('/api/departments');

        $this->assertNotSame(401, $response->status());
        $response->assertOk();
    }
}
