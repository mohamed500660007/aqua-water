<?php

namespace Tests\Feature;

use App\Domain\Customers\Models\Customer;
use App\Support\Lookups;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_root_opens_the_home_page(): void
    {
        $this->get('/dashboard')
            ->assertOk()
            ->assertViewIs('pages.home')
            ->assertSee('مرحبًا بكِ');
    }

    public function test_home_page_links_to_every_dashboard_section(): void
    {
        $response = $this->get(route('home'))->assertOk();

        foreach (Lookups::navItems() as $item) {
            $response->assertSee(route($item['route']), false)->assertSee($item['label']);
        }
    }

    public function test_home_page_greets_the_accounts_manager_without_counters(): void
    {
        Customer::factory()->count(3)->create();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('مرحبًا بكِ يا أستاذة')
            ->assertSee('هبة')
            ->assertSee('مطاردة البيت')
            ->assertSee('toonChase(', false)
            ->assertSee(asset('favicon.svg'), false)
            ->assertDontSee('3 عميل');
    }
}
