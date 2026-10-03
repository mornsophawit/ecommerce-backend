<?php

namespace Database\Seeders;

use App\Models\Status;
use App\Models\User;
use Illuminate\Database\Seeder;

class StatusesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Dynamic fallback checking to prevent crashes if user 1 does not exist yet
        $adminId = User::where('role', 'super_admin')->first()?->id ?? 1;

        // 1. Order Management Pipeline Statuses
        $orderStatuses = [
            ['value' => 'pending', 'name' => 'Order Pending', 'name_kh' => 'កំពុងរង់ចាំ'],
            ['value' => 'processing', 'name' => 'Order Processing', 'name_kh' => 'កំពុងដំណើរការ'],
            ['value' => 'completed', 'name' => 'Order Completed', 'name_kh' => 'ការបញ្ជាទិញជោគជ័យ'],
            ['value' => 'cancelled', 'name' => 'Order Cancelled', 'name_kh' => 'ត្រូវបានបោះបង់'],
        ];

        foreach ($orderStatuses as $status) {
            Status::firstOrCreate(
                ['type' => 'order', 'value' => $status['value']],
                [
                    'name' => $status['name'],
                    'name_kh' => $status['name_kh'],
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ]
            );
        }

        // 2. Financial Transaction Payment Statuses
        $paymentStatuses = [
            ['value' => 'pending', 'name' => 'Payment Pending', 'name_kh' => 'កំពុងរង់ចាំប្រាក់'],
            ['value' => 'paid', 'name' => 'Payment Paid', 'name_kh' => 'បានបង់ប្រាក់'],
            ['value' => 'failed', 'name' => 'Payment Failed', 'name_kh' => 'ការបង់ប្រាក់បរាជ័យ'],
        ];

        foreach ($paymentStatuses as $status) {
            Status::firstOrCreate(
                ['type' => 'payment', 'value' => $status['value']],
                [
                    'name' => $status['name'],
                    'name_kh' => $status['name_kh'],
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ]
            );
        }

        // 3. Dispute Resolution Refund Statuses (Added for full Test coverage)
        $refundStatuses = [
            ['value' => 'pending', 'name' => 'Refund Pending', 'name_kh' => 'កំពុងពិនិត្យសំណើ'],
            ['value' => 'approved', 'name' => 'Refund Approved', 'name_kh' => 'សំណើត្រូវបានយល់ព្រម'],
            ['value' => 'rejected', 'name' => 'Refund Rejected', 'name_kh' => 'សំណើត្រូវបានបដិសេធ'],
        ];

        foreach ($refundStatuses as $status) {
            Status::firstOrCreate(
                ['type' => 'refund', 'value' => $status['value']],
                [
                    'name' => $status['name'],
                    'name_kh' => $status['name_kh'],
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ]
            );
        }
    }
}
