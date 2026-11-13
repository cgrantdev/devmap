<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\User;
use App\Models\VendorSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Coupon boosts ("Limited-time promotion" on the vendor edit page).
 *
 * Colin, Nov 14 2026: Certified Pep had no coupon of its own. A WEEKEND35
 * boost became its standard code and never came off, because the saved
 * previous % and code were null, and null meant "nothing to restore".
 */
class CouponBoostRevertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        // Friday 13 Nov 2026, noon in New York (EST, UTC-5).
        $this->travelTo(Carbon::parse('2026-11-13 17:00:00', 'UTC'));
    }

    public function test_boost_on_a_vendor_without_a_coupon_runs_until_the_et_end_then_leaves_no_coupon(): void
    {
        $vs = $this->vendor('certified-pep', null, null);

        $vs->applyCouponBoost(35, null, $this->et('2026-11-15 23:59'), 'WEEKEND35');

        $vs = $vs->fresh();
        $this->assertSame('WEEKEND35', $vs->coupon_code);
        $this->assertEquals(35, (float) $vs->coupon_discount_percent);
        $this->assertTrue($vs->couponBoostActive());
        // 11:59 PM EST on Sunday is 04:59 UTC on Monday.
        $this->assertSame('2026-11-16 04:59:00', $vs->coupon_boost_expires_at->toDateTimeString());

        // 11 PM Sunday in New York: still running.
        $this->travelTo(Carbon::parse('2026-11-16 04:00:00', 'UTC'));
        $this->artisan('coupons:revert-expired-boosts')->assertSuccessful();
        $this->assertSame('WEEKEND35', $vs->fresh()->coupon_code);

        $this->travelTo(Carbon::parse('2026-11-16 05:00:00', 'UTC'));
        $this->artisan('coupons:revert-expired-boosts')->assertSuccessful();

        $vs = $vs->fresh();
        $this->assertNull($vs->coupon_code);
        $this->assertNull($vs->coupon_discount_percent);
        $this->assertNull($vs->coupon_boost_code);
        $this->assertNull($vs->coupon_boost_expires_at);
        $this->assertFalse($vs->couponBoostActive());
    }

    public function test_boost_with_a_code_puts_the_vendors_own_code_and_percent_back(): void
    {
        $vs = $this->vendor('amino-club', 'PMAP', 10);

        $vs->applyCouponBoost(30, null, $this->et('2026-11-14 23:59'), 'FLASH30');

        $this->assertSame('FLASH30', $vs->fresh()->coupon_code);
        $this->assertEquals(30, (float) $vs->fresh()->coupon_discount_percent);

        $this->travelTo(Carbon::parse('2026-11-15 06:00:00', 'UTC'));
        $this->artisan('coupons:revert-expired-boosts')->assertSuccessful();

        $vs = $vs->fresh();
        $this->assertSame('PMAP', $vs->coupon_code);
        $this->assertEquals(10, (float) $vs->coupon_discount_percent);
    }

    public function test_boost_without_a_code_leaves_the_code_alone_and_puts_the_percent_back(): void
    {
        $vs = $this->vendor('hydro-research', 'PMAP', 10);

        $vs->applyCouponBoost(25, null, $this->et('2026-11-14 23:59'));

        $this->assertSame('PMAP', $vs->fresh()->coupon_code);
        $this->assertEquals(25, (float) $vs->fresh()->coupon_discount_percent);

        $this->travelTo(Carbon::parse('2026-11-15 06:00:00', 'UTC'));
        $this->artisan('coupons:revert-expired-boosts')->assertSuccessful();

        $vs = $vs->fresh();
        $this->assertSame('PMAP', $vs->coupon_code);
        $this->assertEquals(10, (float) $vs->coupon_discount_percent);
    }

    public function test_reapplying_a_running_boost_still_restores_the_vendors_own_coupon(): void
    {
        $vs = $this->vendor('amino-club', 'PMAP', 10);

        $vs->applyCouponBoost(30, null, $this->et('2026-11-14 23:59'), 'FLASH30');
        $vs->fresh()->applyCouponBoost(40, null, $this->et('2026-11-15 23:59'), 'FLASH40');

        $this->assertSame('FLASH40', $vs->fresh()->coupon_code);

        $this->travelTo(Carbon::parse('2026-11-16 05:00:00', 'UTC'));
        $this->artisan('coupons:revert-expired-boosts')->assertSuccessful();

        $vs = $vs->fresh();
        $this->assertSame('PMAP', $vs->coupon_code);
        $this->assertEquals(10, (float) $vs->coupon_discount_percent);
    }

    public function test_scheduled_boost_on_a_vendor_without_a_coupon_starts_and_ends_on_time(): void
    {
        $vs = $this->vendor('certified-pep', null, null);

        $vs->applyCouponBoost(40, $this->et('2026-11-14 09:00'), $this->et('2026-11-15 23:59'), 'SAT40');

        $vs = $vs->fresh();
        $this->assertTrue($vs->couponBoostScheduled());
        $this->assertNull($vs->coupon_code);
        $this->assertNull($vs->coupon_discount_percent);

        // 9:05 AM Saturday in New York.
        $this->travelTo(Carbon::parse('2026-11-14 14:05:00', 'UTC'));
        $this->artisan('coupons:activate-scheduled-boosts')->assertSuccessful();

        $vs = $vs->fresh();
        $this->assertTrue($vs->couponBoostActive());
        $this->assertSame('SAT40', $vs->coupon_code);
        $this->assertEquals(40, (float) $vs->coupon_discount_percent);

        $this->travelTo(Carbon::parse('2026-11-16 05:00:00', 'UTC'));
        $this->artisan('coupons:revert-expired-boosts')->assertSuccessful();

        $vs = $vs->fresh();
        $this->assertNull($vs->coupon_code);
        $this->assertNull($vs->coupon_discount_percent);
    }

    public function test_deals_page_flags_a_running_boost_on_a_vendor_without_a_coupon(): void
    {
        $vs = $this->vendor('certified-pep', null, null);
        $vs->applyCouponBoost(35, null, $this->et('2026-11-15 23:59'), 'WEEKEND35');

        $deals = collect($this->get('/deals')->assertOk()->viewData('page')['props']['deals']);
        $row = $deals->firstWhere('slug', 'certified-pep');

        $this->assertNotNull($row);
        $this->assertSame('WEEKEND35', $row['code']);
        $this->assertTrue($row['is_boosted']);
        $this->assertNotNull($row['boost_ends_at']);
    }

    public function test_cancel_drops_a_scheduled_boost_and_leaves_the_coupon_alone(): void
    {
        $vs = $this->vendor('amino-club', 'PMAP', 10);
        $vs->applyCouponBoost(35, $this->et('2026-11-14 09:00'), $this->et('2026-11-15 23:59'), 'SAT35');

        $this->actingAs($this->admin())
            ->delete('/admin/vendors/'.$vs->brand_id.'/coupon-boost')
            ->assertSessionHas('flash_success', 'Scheduled boost cancelled.');

        $vs = $vs->fresh();
        $this->assertNull($vs->coupon_boost_expires_at);
        $this->assertNull($vs->coupon_boost_starts_at);
        $this->assertSame('PMAP', $vs->coupon_code);
        $this->assertEquals(10, (float) $vs->coupon_discount_percent);

        // Nothing left for the start job to swap in.
        $this->travelTo(Carbon::parse('2026-11-14 14:05:00', 'UTC'));
        $this->artisan('coupons:activate-scheduled-boosts')->assertSuccessful();
        $this->assertSame('PMAP', $vs->fresh()->coupon_code);
    }

    public function test_cancel_on_a_vendor_without_a_coupon_removes_the_boost_code(): void
    {
        $vs = $this->vendor('certified-pep', null, null);
        $vs->applyCouponBoost(35, null, $this->et('2026-11-15 23:59'), 'WEEKEND35');

        $this->actingAs($this->admin())
            ->delete('/admin/vendors/'.$vs->brand_id.'/coupon-boost')
            ->assertSessionHas('flash_success');

        $vs = $vs->fresh();
        $this->assertNull($vs->coupon_code);
        $this->assertNull($vs->coupon_discount_percent);
        $this->assertFalse($vs->couponBoostInFlight());
    }

    public function test_boost_launched_from_the_admin_ends_at_the_et_time_typed(): void
    {
        $vs = $this->vendor('certified-pep', null, null);

        $this->actingAs($this->admin())
            ->post('/admin/vendors/'.$vs->brand_id.'/coupon-boost', [
                'boost_percent' => 35,
                'expires_at' => '2026-11-15T23:59',
                'boost_code' => 'weekend35',
            ])
            ->assertSessionHasNoErrors();

        $vs = $vs->fresh();
        $this->assertSame('WEEKEND35', $vs->coupon_code);
        $this->assertSame('2026-11-16 04:59:00', $vs->coupon_boost_expires_at->toDateTimeString());
    }

    public function test_admin_accepts_an_end_time_a_few_hours_out_in_et(): void
    {
        $vs = $this->vendor('amino-club', 'PMAP', 10);

        // 3 PM in New York is still ahead at noon, though 15:00 UTC is not.
        $this->actingAs($this->admin())
            ->post('/admin/vendors/'.$vs->brand_id.'/coupon-boost', [
                'boost_percent' => 20,
                'expires_at' => '2026-11-13T15:00',
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue($vs->fresh()->couponBoostActive());
    }

    public function test_revert_job_clears_an_old_stuck_boost_nobody_changed(): void
    {
        // What the old code left behind: no saved previous % or code.
        $vs = $this->vendor('certified-pep', 'WEEKEND35', 35, [
            'coupon_boost_percent' => 35,
            'coupon_boost_code' => 'WEEKEND35',
            'coupon_boost_expires_at' => '2026-11-09 04:59:00',
        ]);

        $this->artisan('coupons:revert-expired-boosts')->assertSuccessful();

        $vs = $vs->fresh();
        $this->assertNull($vs->coupon_code);
        $this->assertNull($vs->coupon_discount_percent);
        $this->assertNull($vs->coupon_boost_expires_at);
    }

    public function test_revert_job_keeps_a_coupon_set_by_hand_after_an_old_stuck_boost(): void
    {
        $vs = $this->vendor('amino-club', 'PMAP', 15, [
            'coupon_boost_percent' => 35,
            'coupon_boost_code' => 'WEEKEND35',
            'coupon_boost_expires_at' => '2026-11-09 04:59:00',
        ]);

        $this->artisan('coupons:revert-expired-boosts')->assertSuccessful();

        $vs = $vs->fresh();
        $this->assertSame('PMAP', $vs->coupon_code);
        $this->assertEquals(15, (float) $vs->coupon_discount_percent);
        $this->assertNull($vs->coupon_boost_expires_at);
    }

    public function test_admin_vendor_form_can_clear_the_coupon_code(): void
    {
        $vs = $this->vendor('certified-pep', 'WEEKEND35', 35);

        $this->actingAs($this->admin())
            ->post('/admin/vendors/'.$vs->brand_id, [
                'name' => 'Certified Pep',
                'coupon_code' => '',
                'coupon_discount_percent' => '',
            ])
            ->assertSessionHasNoErrors();

        $vs = $vs->fresh();
        $this->assertNull($vs->coupon_code);
        $this->assertNull($vs->coupon_discount_percent);
    }

    private function vendor(string $slug, ?string $code, $percent, array $extra = []): VendorSetting
    {
        $brand = Brand::create([
            'name' => ucwords(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'is_active' => true,
        ]);

        return VendorSetting::create([
            'brand_id' => $brand->id,
            'approval_status' => 'approved',
            'status' => 1,
            'coupon_code' => $code,
            'coupon_discount_percent' => $percent,
        ] + $extra);
    }

    private function et(string $time): \DateTimeImmutable
    {
        return new \DateTimeImmutable($time, new \DateTimeZone('America/New_York'));
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'QA Admin',
            'email' => 'qa-boost-admin@example.test',
            'password' => bcrypt('x'),
            'role' => 'admin',
        ]);
    }
}
