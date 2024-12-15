<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\RoleMaster;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $manager;
    private $employee;
    private $company;

    protected function setUp(): void
    {
        parent::setUp();

        $roleMasterManager = RoleMaster::create(['name' => 'Manager', 'slug' => 'manager', 'created_by' => 0]);
        $roleMasterEmployee = RoleMaster::create(['name' => 'Employee', 'slug' => 'employee', 'created_by' => 0]);

        $this->company = Company::create(['name' => 'Test Company', 'email' => 'company@test.com', 'phone' => '123456789', 'created_by' => 0]);

        $this->admin = User::create([
            'name' => 'Admin Test',
            'created_by' => 0,
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'role_master_id' => 0,
            'company_id' => $this->company->id,
        ]);

        $this->manager = User::create([
            'name' => 'Manager Test',
            'created_by' => 0,
            'email' => 'manager@test.com',
            'role_master_id' => $roleMasterManager->id,
            'company_id' => $this->company->id,
            'password' => Hash::make('password123'),
        ]);

        $this->employee = User::create([
            'name' => 'Employee Test',
            'created_by' => 0,
            'email' => 'employee@test.com',
            'role_master_id' => $roleMasterEmployee->id,
            'company_id' => $this->company->id,
            'password' => Hash::make('password123'),
        ]);
    }

    private function authenticateUser($user)
    {
        return JWTAuth::fromUser($user);
    }

    public function testIndex()
    {
        $token = $this->authenticateUser($this->manager);

        $response = $this->getJson('/api/users', [
            'Authorization' => "Bearer $token"
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                    'data' => [
                        '*' => ['id', 'name', 'email', 'address', 'company_name', 'role_master', 'created_by', 'created_at'],
                    ],
                 ]);
    }

    public function testCreateUserAsManager()
    {
        $token = $this->authenticateUser($this->manager);

        $response = $this->postJson('/api/users', [
            'name' => 'New Employee',
            'email' => 'newemployee@test.com',
            'phone' => '123456789',
            'address' => 'New Address',
            'company_id' => $this->company->id,
        ], [
            'Authorization' => "Bearer $token"
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Employee created successfully.']);
    }

    public function testCreateUserAsEmployee()
    {
        $token = $this->authenticateUser($this->employee);

        $response = $this->postJson('/api/users', [
            'name' => 'Unauthorized Employee',
            'email' => 'unauthorizedemployee@test.com',
            'phone' => '987654321',
            'address' => 'Unauthorized Address',
            'company_id' => $this->company->id,
        ], [
            'Authorization' => "Bearer $token"
        ]);

        $response->assertStatus(500)
                 ->assertJson(['message' => "You don't have permission on this action"]);
    }

    public function testUpdateUserAsManager()
    {
        $token = $this->authenticateUser($this->manager);

        $userToUpdate = User::create([
            'name' => 'Admin Test',
            'email' => 'adminupdate1@test.com',
            'password' => bcrypt('123456'),
            'created_by' => 0,
            'company_id' => $this->company->id,
            'role_master_id' => RoleMaster::where('slug', 'employee')->first()->id,
        ]);

        $response = $this->putJson("/api/users/{$userToUpdate->id}", [
            'name' => 'Updated Name',
            'email' => 'updatedemail@test.com',
            'phone' => '111222333',
            'address' => 'Updated Address',
        ], [
            'Authorization' => "Bearer $token"
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Employee updated successfully.']);
    }

    public function testUpdateUserAsEmployee()
    {
        $token = $this->authenticateUser($this->employee);

        $userToUpdate = User::create([
            'name' => 'Admin Test',
            'email' => 'adminupdate@test.com',
            'password' => bcrypt('123456'),
            'created_by' => 0,
            'company_id' => $this->company->id,
            'role_master_id' => RoleMaster::where('slug', 'employee')->first()->id,
        ]);

        $response = $this->putJson("/api/users/{$userToUpdate->id}", [
            'name' => 'Unauthorized Update',
            'email' => 'unauthorizedupdate@test.com',
            'phone' => '000000000',
            'address' => 'Unauthorized Address',
        ], [
            'Authorization' => "Bearer $token"
        ]);

        $response->assertStatus(500)
                 ->assertJson(['message' => "You don't have permission on this action"]);
    }

    public function testDetailUser()
    {
        $token = $this->authenticateUser($this->manager);

        $response = $this->getJson('/api/users/' . $this->employee->id, [
            'Authorization' => "Bearer $token"
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'id', 'name', 'email', 'address', 'company_id', 'role_master_id', 'created_at',
                 ]);
    }

    public function testDeleteUserAsManager()
    {
        $token = $this->authenticateUser($this->manager);

        $userToDelete = User::create([
            'name' => 'Admin Test',
            'email' => 'admindelete1@test.com',
            'password' => bcrypt('123456'),
            'created_by' => 0,
            'company_id' => $this->company->id,
            'role_master_id' => RoleMaster::where('slug', 'employee')->first()->id,
        ]);

        $response = $this->deleteJson("/api/users/{$userToDelete->id}", [], [
            'Authorization' => "Bearer $token"
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'User deleted successfully.']);
    }

    public function testDeleteUserAsEmployee()
    {
        $token = $this->authenticateUser($this->employee);

        $userToDelete = User::create([
            'name' => 'Admin Test',
            'email' => 'admindelete@test.com',
            'password' => bcrypt('123456'),
            'created_by' => 0,
            'company_id' => $this->company->id,
            'role_master_id' => RoleMaster::where('slug', 'employee')->first()->id,
        ]);

        $response = $this->deleteJson("/api/users/{$userToDelete->id}", [], [
            'Authorization' => "Bearer $token"
        ]);

        $response->assertStatus(500)
                 ->assertJson(['message' => "You don't have permission on this action"]);
    }
}
