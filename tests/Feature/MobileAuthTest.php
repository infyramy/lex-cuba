<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileAuthTest extends TestCase
{
    use RefreshDatabase;

    // ── Registration ─────────────────────────────────────────────────────────

    public function test_member_can_register(): void
    {
        $response = $this->postJson('/api/mobile/register', [
            'name' => 'Test Member',
            'email' => 'member@test.com',
            'password' => 'secret123',
            'gender' => 'male',
            'phone' => '+60123456789',
            'institution' => 'Test University',
            'work_study_status' => 'studying',
            'country' => 'Malaysia',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email', 'status'],
                    'token',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'member@test.com',
            'user_type' => 'member',
            'status' => 'active',
        ]);
    }

    public function test_register_validation_fails_with_missing_fields(): void
    {
        $response = $this->postJson('/api/mobile/register', []);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_register_fails_with_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'taken@test.com',
            'user_type' => 'member',
        ]);

        $response = $this->postJson('/api/mobile/register', [
            'name' => 'Another Member',
            'email' => 'taken@test.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    // ── Login ────────────────────────────────────────────────────────────────

    public function test_member_can_login(): void
    {
        User::factory()->create([
            'email' => 'member@test.com',
            'password' => bcrypt('secret123'),
            'user_type' => 'member',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/mobile/login', [
            'email' => 'member@test.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token',
                ],
            ]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'member@test.com',
            'password' => bcrypt('secret123'),
            'user_type' => 'member',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/mobile/login', [
            'email' => 'member@test.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('error.code', 'INVALID_CREDENTIALS');
    }

    public function test_suspended_member_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'suspended@test.com',
            'password' => bcrypt('secret123'),
            'user_type' => 'member',
            'status' => 'suspended',
        ]);

        $response = $this->postJson('/api/mobile/login', [
            'email' => 'suspended@test.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('error.code', 'ACCOUNT_SUSPENDED');
    }

    // ── Me (Profile) ─────────────────────────────────────────────────────────

    public function test_authenticated_member_can_get_profile(): void
    {
        $user = User::factory()->create([
            'user_type' => 'member',
            'status' => 'active',
        ]);

        $token = $user->createToken('mobile')->plainTextToken;

        $response = $this->getJson('/api/mobile/me', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email'],
                ],
            ]);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/mobile/me');

        $response->assertStatus(401)
            ->assertJsonPath('error.code', 'UNAUTHORIZED');
    }

    // ── Logout ───────────────────────────────────────────────────────────────

    public function test_member_can_logout(): void
    {
        $user = User::factory()->create([
            'user_type' => 'member',
            'status' => 'active',
        ]);

        $token = $user->createToken('mobile')->plainTextToken;

        $response = $this->postJson('/api/mobile/logout', [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.success', true);

        // Token should be revoked — verify in database
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    // ── Token Refresh ────────────────────────────────────────────────────────

    public function test_member_can_refresh_token(): void
    {
        $user = User::factory()->create([
            'user_type' => 'member',
            'status' => 'active',
        ]);

        $token = $user->createToken('mobile')->plainTextToken;

        $response = $this->postJson('/api/mobile/refresh-token', [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['token']]);

        // Old token should be deleted, new one created — still 1 token total
        $this->assertDatabaseCount('personal_access_tokens', 1);

        // New token string should be different from old one
        $newToken = $response->json('data.token');
        $this->assertNotEquals($token, $newToken);
    }

    // ── Revoke All ───────────────────────────────────────────────────────────

    public function test_member_can_revoke_all_tokens(): void
    {
        $user = User::factory()->create([
            'user_type' => 'member',
            'status' => 'active',
        ]);

        $user->createToken('mobile');
        $token2 = $user->createToken('mobile')->plainTextToken;

        // Should have 2 tokens before revoking
        $this->assertDatabaseCount('personal_access_tokens', 2);

        $response = $this->postJson('/api/mobile/revoke-all', [], [
            'Authorization' => 'Bearer ' . $token2,
        ]);

        $response->assertStatus(200);

        // All tokens should be revoked
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
