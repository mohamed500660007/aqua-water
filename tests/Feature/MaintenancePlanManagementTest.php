<?php

namespace Tests\Feature;

use App\Domain\Maintenance\Models\MaintenancePlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenancePlanManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_page_shows_seven_candles_with_their_fixed_intervals(): void
    {
        $this->get(route('maintenance.plan'))
            ->assertOk()
            ->assertDontSee('name="candles', false)
            ->assertSeeInOrder([
                'الشمعة الأولى', 'كل شهر',
                'الشمعة الثانية', 'كل 4 شهور',
                'الشمعة الثالثة', 'كل 6 شهور',
                'الشمعة الرابعة', 'كل سنة',
                'الشمعة الخامسة', 'كل سنة',
                'الشمعة السادسة', 'كل سنة',
                'الشمعة السابعة', 'كل سنة',
            ]);
    }

    public function test_the_single_plan_exists_after_migrating(): void
    {
        $this->assertSame(1, MaintenancePlan::count());
        $this->assertNotNull(MaintenancePlan::current());
    }
}
