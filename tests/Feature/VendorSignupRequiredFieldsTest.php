<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Colin, Nov 19 2026: vendors were finishing /become-a-vendor with the
 * Business Info step empty. On the full signup form (signupForm=full)
 * every section is required; the join.peptidemap.com invitation form
 * posts to the same endpoint without the flag and keeps optional rules.
 */
class VendorSignupRequiredFieldsTest extends TestCase
{
    use RefreshDatabase;

    private const STEP3 = [
        'productCount', 'tagline', 'companyDescription', 'paymentMethods',
        'shippingInformation', 'returnPolicy', 'manufacturingNotes',
        'independentTestingNotes', 'businessHoursJson', 'usps', 'logoFile',
    ];

    public function test_full_signup_form_requires_every_business_info_section(): void
    {
        $this->post('/become-a-vendor', ['signupForm' => 'full'])
            ->assertSessionHasErrors(self::STEP3);
    }

    public function test_full_signup_form_rejects_one_word_answers_and_all_closed_hours(): void
    {
        $this->post('/become-a-vendor', [
            'signupForm' => 'full',
            'productCount' => '1-10',
            'tagline' => 'x',
            'companyDescription' => 'We sell peptides.',
            'paymentMethods' => ['Credit Card'],
            'shippingInformation' => 'n/a',
            'returnPolicy' => 'None',
            'manufacturingNotes' => 'USA',
            'independentTestingNotes' => 'Yes',
            'businessHoursJson' => ['timezone' => 'America/New_York', 'mon' => null, 'sat' => null],
            'usps' => ['us_based'],
        ])
            ->assertSessionHasErrors([
                'tagline', 'companyDescription', 'shippingInformation', 'returnPolicy',
                'manufacturingNotes', 'independentTestingNotes', 'businessHoursJson',
            ])
            ->assertSessionDoesntHaveErrors(['productCount', 'paymentMethods', 'usps']);
    }

    public function test_one_open_day_is_enough_for_business_hours(): void
    {
        $this->post('/become-a-vendor', [
            'signupForm' => 'full',
            'businessHoursJson' => ['timezone' => 'America/New_York', 'mon' => ['open' => '09:00', 'close' => '17:00']],
        ])
            ->assertSessionDoesntHaveErrors(['businessHoursJson']);
    }

    public function test_invitation_form_without_the_flag_keeps_business_info_optional(): void
    {
        $this->post('/become-a-vendor', ['companyDescription' => 'Short.'])
            ->assertSessionDoesntHaveErrors(self::STEP3);
    }
}
