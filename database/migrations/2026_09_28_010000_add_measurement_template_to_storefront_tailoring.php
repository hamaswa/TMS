<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('storefront_tailoring_services', 'measurement_template_id')) {
            Schema::table('storefront_tailoring_services', function (Blueprint $table) {
                $table->foreignId('measurement_template_id')
                    ->nullable()
                    ->after('storefront_id')
                    ->constrained('measurement_templates')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('storefront_inquiries', 'measurement_template_id')) {
            Schema::table('storefront_inquiries', function (Blueprint $table) {
                $table->foreignId('measurement_template_id')
                    ->nullable()
                    ->after('tailoring_service_id')
                    ->constrained('measurement_templates')
                    ->nullOnDelete();
            });
        }

        $services = DB::table('storefront_tailoring_services as services')
            ->join('storefronts', 'storefronts.id', '=', 'services.storefront_id')
            ->join('businesses', 'businesses.id', '=', 'storefronts.business_id')
            ->select('services.id', 'services.name', 'businesses.owner_user_id')
            ->get();

        foreach ($services as $service) {
            $templateQuery = DB::table('measurement_templates')
                ->where('user_id', $service->owner_user_id)
                ->where('is_active', true);

            $template = null;
            if (str_contains($service->name, 'ویسٹ')) {
                $template = (clone $templateQuery)->where('name', 'like', '%ویسٹ%')->first();
            } elseif (str_contains($service->name, 'بچ')) {
                $template = (clone $templateQuery)->where('name', 'like', '%بچ%')->first();
            } elseif (str_contains($service->name, 'شلوار') || str_contains($service->name, 'سوٹ')) {
                $template = (clone $templateQuery)->where('is_default', true)->first();
            }

            if ($template) {
                DB::table('storefront_tailoring_services')
                    ->where('id', $service->id)
                    ->update(['measurement_template_id' => $template->id]);
            }
        }

        DB::table('storefront_inquiries')
            ->whereNull('measurement_template_id')
            ->whereIn('status', ['new', 'contacted'])
            ->whereNotNull('tailoring_service_id')
            ->orderBy('id')
            ->eachById(function ($inquiries) {
                foreach ($inquiries as $inquiry) {
                    $templateId = DB::table('storefront_tailoring_services')
                        ->where('id', $inquiry->tailoring_service_id)
                        ->value('measurement_template_id');
                    if ($templateId) {
                        DB::table('storefront_inquiries')
                            ->where('id', $inquiry->id)
                            ->update(['measurement_template_id' => $templateId]);
                    }
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('storefront_inquiries', 'measurement_template_id')) {
            Schema::table('storefront_inquiries', function (Blueprint $table) {
                $table->dropConstrainedForeignId('measurement_template_id');
            });
        }
        if (Schema::hasColumn('storefront_tailoring_services', 'measurement_template_id')) {
            Schema::table('storefront_tailoring_services', function (Blueprint $table) {
                $table->dropConstrainedForeignId('measurement_template_id');
            });
        }
    }
};
