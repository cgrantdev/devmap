<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
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

    public function test_public_copy_is_guarded_in_source_files(): void
    {
        $tooltip = "b.type === 'cgmp' ? 'cGMP (vendor-reported)' : `\${b.label} (vendor-submitted)`";

        foreach ([
            'resources/js/Pages/Frontend/Brands.vue',
            'resources/js/Pages/Frontend/BrandProducts.vue',
        ] as $path) {
            $source = file_get_contents(base_path($path));
            $this->assertStringNotContainsString('Peptidemap-verified', $source, $path);
            $this->assertStringContainsString($tooltip, $source, $path);
        }

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

        $vendorCopy = file_get_contents(base_path('resources/js/Pages/Vendor/Certifications.vue'));
        $this->assertStringContainsString('cGMP (vendor-reported) or 7+ Tested badge', $vendorCopy);
        $this->assertStringContainsString('Approved on {{ formatDate(claims[type].verified_at) }}', $vendorCopy);
        $this->assertStringNotContainsString('verified badge', $vendorCopy);
        $this->assertStringNotContainsString('Verified on', $vendorCopy);

        $discord = file_get_contents(base_path('app/Http/Controllers/Admin/CertificationsController.php'));
        $this->assertStringContainsString('badge approved:', $discord);
        $this->assertStringNotContainsString('verified:', $discord);

        $this->assertSame('cgmp', VendorCertificationClaim::TYPE_CGMP);
        $this->assertSame('cGMP (vendor-reported)', VendorCertificationClaim::TYPE_LABELS[VendorCertificationClaim::TYPE_CGMP]);
    }

    public function test_verified_query_keeps_only_approved_claims(): void
    {
        $category = ProductCategory::create([
            'name' => 'Retatrutide',
            'slug' => 'retatrutide',
            'is_active' => true,
        ]);

        $approved = $this->vendor('Approved Cgmp Vendor', 'approved-cgmp-vendor', $category);
        $both = $this->vendor('Both Badges Vendor', 'both-badges-vendor', $category);
        $pending = $this->vendor('Pending Cgmp Vendor', 'pending-cgmp-vendor', $category);
        $rejected = $this->vendor('Rejected Cgmp Vendor', 'rejected-cgmp-vendor', $category);
        $testingOnly = $this->vendor('Testing Only Vendor', 'testing-only-vendor', $category);

        $this->claim($approved, VendorCertificationClaim::TYPE_CGMP, VendorCertificationClaim::STATUS_APPROVED);
        $this->claim($both, VendorCertificationClaim::TYPE_CGMP, VendorCertificationClaim::STATUS_APPROVED);
        $this->claim($both, VendorCertificationClaim::TYPE_TESTING_7X, VendorCertificationClaim::STATUS_APPROVED);
        $this->claim($pending, VendorCertificationClaim::TYPE_CGMP, VendorCertificationClaim::STATUS_PENDING);
        $this->claim($rejected, VendorCertificationClaim::TYPE_CGMP, VendorCertificationClaim::STATUS_REJECTED);
        $this->claim($testingOnly, VendorCertificationClaim::TYPE_TESTING_7X, VendorCertificationClaim::STATUS_APPROVED);

        $cgmp = $this->get('/vendors?verified=cgmp');
        $cgmp->assertOk();
        $cgmp->assertSee('Approved Cgmp Vendor', false);
        $cgmp->assertSee('Both Badges Vendor', false);
        $cgmp->assertSee('cGMP (vendor-reported)', false);
        $cgmp->assertDontSee('Pending Cgmp Vendor', false);
        $cgmp->assertDontSee('Rejected Cgmp Vendor', false);
        $cgmp->assertDontSee('Testing Only Vendor', false);

        // Schema and FAQ copy stay on the full catalog. The filtered table
        // is compound.products.
        $this->assertCompareVendors('/compare/retatrutide?verified=cgmp', [
            'Approved Cgmp Vendor',
            'Both Badges Vendor',
        ], [
            'Pending Cgmp Vendor',
            'Rejected Cgmp Vendor',
            'Testing Only Vendor',
        ]);

        $bothOnly = $this->get('/vendors?verified=cgmp,testing_7x');
        $bothOnly->assertOk();
        $bothOnly->assertSee('Both Badges Vendor', false);
        $bothOnly->assertDontSee('Approved Cgmp Vendor', false);
        $bothOnly->assertDontSee('Pending Cgmp Vendor', false);
        $bothOnly->assertDontSee('Rejected Cgmp Vendor', false);
        $bothOnly->assertDontSee('Testing Only Vendor', false);

        $this->assertCompareVendors('/compare/retatrutide?verified=cgmp,testing_7x', [
            'Both Badges Vendor',
        ], [
            'Approved Cgmp Vendor',
            'Pending Cgmp Vendor',
            'Rejected Cgmp Vendor',
            'Testing Only Vendor',
        ]);
    }

    /**
     * @param  list<string>  $present
     * @param  list<string>  $absent
     */
    private function assertCompareVendors(string $url, array $present, array $absent): void
    {
        $this->get($url)->assertOk()->assertInertia(fn ($page) => $page
            ->where('compound.products', function ($products) use ($present, $absent) {
                $names = collect($products)->pluck('brand_name')->all();
                foreach ($present as $name) {
                    $this->assertContains($name, $names);
                }
                foreach ($absent as $name) {
                    $this->assertNotContains($name, $names);
                }

                return true;
            })
        );
    }

    private function vendor(string $name, string $slug, ProductCategory $category): Brand
    {
        $brand = Brand::create([
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
        ]);
        Product::create([
            'name' => $name.' 10mg',
            'slug' => $slug.'-10mg',
            'brand_id' => $brand->id,
            'product_category_id' => $category->id,
            'price' => 40,
            'status' => 'active',
            'hidden' => false,
        ]);

        return $brand;
    }

    private function claim(Brand $brand, string $type, string $status): void
    {
        VendorCertificationClaim::create([
            'brand_id' => $brand->id,
            'type' => $type,
            'status' => $status,
        ]);
    }
}
