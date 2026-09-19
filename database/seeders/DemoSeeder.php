<?php

namespace Database\Seeders;

use App\Models\CharityNeed;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    /**
     * Presentation accounts and listings for the hackathon demo.
     */
    public function run(): void
    {
        $password = Hash::make('password');

        $seller = User::updateOrCreate(
            ['email' => 'seller@revalue.test'],
            ['name' => 'Amina Seller', 'password' => $password, 'email_verified_at' => now(), 'role' => User::ROLE_USER]
        );

        $donor = User::updateOrCreate(
            ['email' => 'donor@revalue.test'],
            ['name' => 'Daniel Donor', 'password' => $password, 'email_verified_at' => now(), 'role' => User::ROLE_USER]
        );

        $buyer = User::updateOrCreate(
            ['email' => 'buyer@revalue.test'],
            ['name' => 'Grace Buyer', 'password' => $password, 'email_verified_at' => now(), 'role' => User::ROLE_USER]
        );

        $charityUser = User::updateOrCreate(
            ['email' => 'charity@revalue.test'],
            ['name' => 'Hope Home', 'password' => $password, 'email_verified_at' => now(), 'role' => User::ROLE_USER]
        );

        $pendingUser = User::updateOrCreate(
            ['email' => 'pending-charity@revalue.test'],
            ['name' => 'New Light Trust', 'password' => $password, 'email_verified_at' => now(), 'role' => User::ROLE_USER]
        );

        $recyclerUser = User::updateOrCreate(
            ['email' => 'recycler@revalue.test'],
            ['name' => 'EcoCycle Kenya', 'password' => $password, 'email_verified_at' => now(), 'role' => User::ROLE_USER]
        );

        $admin = User::updateOrCreate(
            ['email' => 'admin@revalue.test'],
            ['name' => 'ReValue Admin', 'password' => $password, 'email_verified_at' => now(), 'role' => User::ROLE_ADMIN]
        );

        $hope = Organization::updateOrCreate(
            ['user_id' => $charityUser->id],
            [
                'type' => Organization::TYPE_CHARITY,
                'name' => "Hope Children's Home",
                'contact_person' => 'Mercy Wanjiku',
                'email' => 'charity@revalue.test',
                'phone' => '0712345678',
                'location' => 'Parklands, Nairobi',
                'registration_details' => 'NGO-2041 / Social Development',
                'website' => 'https://example.org/hope',
                'verification_status' => Organization::STATUS_VERIFIED,
                'review_notes' => 'Documents confirmed for hackathon demo.',
            ]
        );

        Organization::updateOrCreate(
            ['user_id' => $pendingUser->id],
            [
                'type' => Organization::TYPE_CHARITY,
                'name' => 'New Light Community Trust',
                'contact_person' => 'Peter Otieno',
                'email' => 'pending-charity@revalue.test',
                'phone' => '0700111222',
                'location' => 'Kayole, Nairobi',
                'registration_details' => 'Pending registration pack',
                'verification_status' => Organization::STATUS_PENDING,
            ]
        );

        Organization::updateOrCreate(
            ['user_id' => $recyclerUser->id],
            [
                'type' => Organization::TYPE_RECYCLER,
                'name' => 'EcoCycle Kenya',
                'contact_person' => 'Faith Njeri',
                'email' => 'recycler@revalue.test',
                'phone' => '0799887766',
                'location' => 'Industrial Area, Nairobi',
                'registration_details' => 'NEMA waste handler 8821',
                'verification_status' => Organization::STATUS_VERIFIED,
                'review_notes' => 'Verified recycler for e-waste demo.',
            ]
        );

        CharityNeed::query()->where('organization_id', $hope->id)->delete();
        foreach ([
            ['category' => 'mattresses', 'quantity' => 5, 'description' => 'Clean double or single mattresses for the dorms.'],
            ['category' => 'electronics', 'quantity' => 2, 'description' => 'Working laptops for homework club.'],
            ['category' => 'furniture', 'quantity' => 10, 'description' => 'Plastic or wooden chairs for the dining hall.'],
        ] as $need) {
            CharityNeed::create([
                ...$need,
                'organization_id' => $hope->id,
                'status' => CharityNeed::STATUS_OPEN,
            ]);
        }

        $this->listing($seller, Listing::TYPE_SELL, 'Samsung 43" Smart TV', 'Good-condition living room TV. Remote included.', 'electronics', 'good', 10500, Listing::STATUS_AVAILABLE);
        $this->listing($seller, Listing::TYPE_SELL, 'Solid wood dining table', 'Seats six. Minor scratches on one leg.', 'furniture', 'fair', 8500, Listing::STATUS_AVAILABLE);

        $sold = $this->listing($seller, Listing::TYPE_SELL, 'Russell Hobbs microwave', 'Used kitchen microwave, heats evenly.', 'appliances', 'good', 4000, Listing::STATUS_SOLD);

        $this->listing($donor, Listing::TYPE_DONATE, 'Double spring mattress', 'Clean and usable. Moving out of Nairobi.', 'mattresses', 'good', null, Listing::STATUS_AVAILABLE);
        $this->listing($donor, Listing::TYPE_DONATE, 'Set of 6 plastic chairs', 'Stackable chairs from a small office.', 'furniture', 'good', null, Listing::STATUS_AVAILABLE);
        $this->listing($donor, Listing::TYPE_RECYCLE, 'Dead refrigerator', 'Compressor failed. Better as scrap than resale.', 'appliances', 'damaged', null, Listing::STATUS_AVAILABLE);
        $this->listing($donor, Listing::TYPE_RECYCLE, 'Broken laptop for parts', 'Screen cracked, board unknown. E-waste.', 'electronics', 'damaged', null, Listing::STATUS_AVAILABLE);

        Order::updateOrCreate(
            ['listing_id' => $sold->id, 'buyer_id' => $buyer->id],
            [
                'item_price' => 4000,
                'delivery_fee' => 600,
                'service_fee' => 300,
                'total_amount' => 4900,
                'payment_reference' => 'DEMO-PAYSTACK-0001',
                'payment_status' => Order::PAYMENT_PAID,
                'order_status' => Order::STATUS_COMPLETED,
                'pickup_verified_at' => now()->subDays(2),
                'delivery_verified_at' => now()->subDay(),
            ]
        );

        $this->command?->info('Demo accounts (password: password):');
        $this->command?->info('seller@revalue.test, donor@revalue.test, buyer@revalue.test');
        $this->command?->info('charity@revalue.test, pending-charity@revalue.test, recycler@revalue.test');
        $this->command?->info('admin@revalue.test  |  Super admin stays at superadmin@revalue.test');
    }

    private function listing(
        User $user,
        string $type,
        string $title,
        string $description,
        string $category,
        string $condition,
        ?int $price,
        string $status,
    ): Listing {
        return Listing::updateOrCreate(
            ['user_id' => $user->id, 'title' => $title],
            [
                'type' => $type,
                'description' => $description,
                'category' => $category,
                'condition' => $condition,
                'image_path' => null,
                'suggested_price' => $price,
                'final_price' => $price,
                'status' => $status,
            ]
        );
    }
}
