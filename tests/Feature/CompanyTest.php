<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\RoleMaster;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;


    private function authenticateUser()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => bcrypt('password123'),
            'role_master_id' => 0,
            'created_by' => 1
        ]);

    
        $token = JWTAuth::fromUser($user);

        return $token;
    }

    /** @test */
    public function it_returns_companies_list()
    {
        $token = $this->authenticateUser();

    
        $company = Company::create([
            'name' => 'Test Company',
            'email' => 'company@example.com',
            'phone' => '123456789',
            'created_by' => 1
        ]);

    
        $response = $this->getJson('/api/companies', [
            'Authorization' => "Bearer $token"
        ]);

    
        $response->assertStatus(200)
                 ->assertJsonStructure([
                    'data' => [
                        '*' => ['id', 'name', 'email', 'phone', 'created_by', 'created_at'],
                    ]
                 ]);
    }

    /** @test */
    public function it_creates_a_company()
    {
        $token = $this->authenticateUser();

    
        $roleMaster = RoleMaster::create([
            'name' => 'Manager',
            'slug' => 'manager',
            'created_by' => 1
        ]);

    
        $data = [
            'name' => 'New Company',
            'email' => 'newcompany@example.com',
            'phone' => '987654321',
            'created_by' => 1
        ];

    
        $response = $this->postJson('/api/companies', $data, [
            'Authorization' => "Bearer $token"
        ]);

    
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Company created successfully.']);
    }

    /** @test */
    public function it_deletes_a_company()
    {
        $token = $this->authenticateUser();

    
        $company = Company::create([
            'name' => 'Test Company',
            'email' => 'company@example.com',
            'phone' => '123456789',
            'created_by' => 1
        ]);

    
        $response = $this->deleteJson("/api/companies/{$company->id}", [], [
            'Authorization' => "Bearer $token"
        ]);

    
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Company deleted successfully.']);
    }

    /** @test */
    public function it_returns_company_detail()
    {
        $token = $this->authenticateUser();

    
        $company = Company::create([
            'name' => 'Test Company',
            'email' => 'company@example.com',
            'phone' => '123456789',
            'created_by' => 1
        ]);

    
        $response = $this->getJson("/api/companies/{$company->id}", [
            'Authorization' => "Bearer $token"
        ]);

    
        $response->assertStatus(200)
                 ->assertJson([
                     'id' => $company->id,
                     'name' => $company->name,
                     'email' => $company->email,
                     'phone' => $company->phone,
                     'created_by' => $company->created_by,
                     'created_at' => $company->created_at->toISOString(),
                 ]);
    }
}
