<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\VendorCertificationClaim;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CgmpVendorReportedLabelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_cgmp_filter_keeps_its_query_key_and_drops_the_verified_label(): void
    {
        $reported = Brand::create([
            'name' => 'Reported CGMP Vendor',
            'slug' => 'reported-cgmp-vendor',
            'is_active' => true,
        ]);
        Brand::create([
            'name' => 'Unclaimed Vendor',
            'slug' => 'unclaimed-vendor',
            'is_active' => true,
        ]);
        VendorCertificationClaim::create([
            'brand_id' => $reported->id,
            'type' => VendorCertificationClaim::TYPE_CGMP,
            'status' => VendorCertificationClaim::STATUS_APPROVED,
        ]);

        $filtered = $this->get('/vendors?verified=cgmp');
        $filtered->assertOk();
        $filtered->assertSee('Reported CGMP Vendor', false);
        $filtered->assertDontSee('Unclaimed Vendor', false);
        $filtered->assertSee('cGMP (vendor-reported)', false);
        $filtered->assertDontSee('cGMP Verified', false);
        $filtered->assertDontSee('Peptidemap-verified: cGMP', false);

        $this->get('/brands?verified=cgmp')
            ->assertOk()
            ->assertSee('cGMP (vendor-reported)', false)
            ->assertDontSee('cGMP Verified', false);

        foreach ([
            'resources/js/Pages/Frontend/Brands.vue',
            'resources/js/Pages/Frontend/CompareCompound.vue',
            'resources/js/Pages/Frontend/Products.vue',
            'resources/js/Pages/Frontend/BrandProducts.vue',
            'resources/js/Pages/Admin/VendorEdit.vue',
            'app/Models/VendorCertificationClaim.php',
        ] as $path) {
            $this->assertStringNotContainsString(
                'cGMP Verified',
                file_get_contents(base_path($path)),
                $path
            );
        }

        $this->assertSame('cgmp', VendorCertificationClaim::TYPE_CGMP);
        $this->assertSame('cGMP (vendor-reported)', VendorCertificationClaim::TYPE_LABELS[VendorCertificationClaim::TYPE_CGMP]);
    }
}
