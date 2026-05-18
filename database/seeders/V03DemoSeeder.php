<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\OpsFortress\Access\Models\UserBusinessAccess;
use App\Domain\OpsFortress\Access\Models\UserWorkplaceAccess;
use App\Domain\OpsFortress\Accounts\Models\AccountBusiness;
use App\Domain\OpsFortress\Accounts\Models\CustomerAccount;
use App\Domain\OpsFortress\BusinessEntities\Models\BusinessEntity;
use App\Domain\OpsFortress\BusinessEntities\Models\BusinessIdentifier;
use App\Domain\OpsFortress\Industries\Models\BusinessIndustry;
use App\Domain\OpsFortress\Industries\Models\Industry;
use App\Domain\OpsFortress\Lookups\Models\BusinessIdentifierType;
use App\Domain\OpsFortress\Lookups\Models\Country;
use App\Domain\OpsFortress\Occupations\Models\Occupation;
use App\Domain\OpsFortress\Workplaces\Models\Workplace;
use App\Domain\OpsFortress\Workplaces\Models\WorkplaceEnvironment;
use App\Domain\Shared\Context\AccountContext;
use App\Domain\Whs\Swms\Models\PrestartQuestion;
use App\Domain\Whs\Swms\Models\SwmsActivityStep;
use App\Domain\Whs\Swms\Models\SwmsVersion;
use App\Domain\Whs\Swms\Models\WorkplaceTaskSetting;
use App\Domain\Whs\Tasks\Models\Task;
use App\Domain\Whs\Tasks\Models\TaskIndustryAccess;
use App\Domain\Whs\Tasks\Models\TaskOccupationAccess;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class V03DemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $country = Country::query()->updateOrCreate(
                ['iso_alpha2' => 'AU'],
                [
                    'iso_alpha3' => 'AUS',
                    'numeric_code' => '036',
                    'name' => 'Australia',
                    'official_name' => 'Commonwealth of Australia',
                    'default_currency_code' => 'AUD',
                    'active' => true,
                ],
            );

            $abnType = BusinessIdentifierType::query()->updateOrCreate(
                ['country_id' => $country->id, 'identifier_code' => 'ABN'],
                [
                    'name' => 'Australian Business Number',
                    'description' => 'Australian national business identifier.',
                    'format_hint' => '11 digits',
                    'validation_regex' => '^[0-9]{11}$',
                    'active' => true,
                ],
            );

            BusinessIdentifierType::query()->updateOrCreate(
                ['country_id' => $country->id, 'identifier_code' => 'ACN'],
                [
                    'name' => 'Australian Company Number',
                    'description' => 'Australian company identifier.',
                    'format_hint' => '9 digits',
                    'validation_regex' => '^[0-9]{9}$',
                    'active' => true,
                ],
            );

            $account = CustomerAccount::query()->updateOrCreate(
                ['slug' => 'acme-construction'],
                [
                    'name' => 'Acme Construction',
                    'legal_name' => 'Acme Construction Pty Ltd',
                    'status' => 'active',
                    'timezone' => 'Australia/Melbourne',
                    'locale' => 'en-AU',
                    'billing_email' => 'billing@acme.test',
                ],
            );

            app(AccountContext::class)->runAs(
                accountId: $account->id,
                callback: function () use ($account, $country, $abnType): void {
                    $business = BusinessEntity::query()->updateOrCreate(
                        ['legal_name' => 'Acme Construction Pty Ltd', 'country_id' => $country->id],
                        [
                            'trading_name' => 'Acme Construction',
                            'business_type' => 'company',
                            'entity_status' => 'active',
                            'primary_email' => 'ops@acme.test',
                            'primary_phone' => '+61 3 9000 0000',
                            'registered_address' => [
                                'line1' => '100 Demo Street',
                                'suburb' => 'Melbourne',
                                'state' => 'VIC',
                                'postcode' => '3000',
                            ],
                        ],
                    );

                    AccountBusiness::query()->updateOrCreate(
                        ['account_id' => $account->id, 'business_entity_id' => $business->id],
                        [
                            'relationship_type' => 'owned',
                            'is_primary' => true,
                            'starts_at' => now(),
                        ],
                    );

                    BusinessIdentifier::query()->updateOrCreate(
                        [
                            'identifier_type_id' => $abnType->id,
                            'normalised_identifier_value' => '51824753556',
                        ],
                        [
                            'business_entity_id' => $business->id,
                            'identifier_value' => '51 824 753 556',
                            'status' => 'active',
                            'verified_at' => now(),
                        ],
                    );

                    $workplace = Workplace::query()->updateOrCreate(
                        ['business_entity_id' => $business->id, 'code' => 'MEL-CBD'],
                        [
                            'account_id' => $account->id,
                            'country_id' => $country->id,
                            'name' => 'Melbourne CBD Site',
                            'workplace_type' => 'construction_site',
                            'status' => 'active',
                            'street_address' => '200 Site Road',
                            'suburb' => 'Melbourne',
                            'city' => 'Melbourne',
                            'state_region' => 'VIC',
                            'postal_code' => '3000',
                            'latitude' => -37.8136000,
                            'longitude' => 144.9631000,
                            'geofence_radius_meters' => 150,
                        ],
                    );

                    WorkplaceEnvironment::query()->updateOrCreate(
                        ['workplace_id' => $workplace->id, 'environment_code' => 'outdoor'],
                        [
                            'name' => 'Outdoor construction area',
                            'description' => 'Open site areas with mobile plant and weather exposure.',
                            'is_active' => true,
                        ],
                    );

                    $admin = User::query()->updateOrCreate(
                        ['email' => 'admin@acme.test'],
                        [
                            'account_id' => $account->id,
                            'home_business_entity_id' => $business->id,
                            'first_name' => 'Alex',
                            'last_name' => 'Admin',
                            'name' => 'Alex Admin',
                            'mobile' => '+61 400 000 001',
                            'employee_code' => 'ACME-ADMIN',
                            'status' => 'active',
                            'person_type' => 'employee',
                            'timezone' => 'Australia/Melbourne',
                            'locale' => 'en-AU',
                            'email_verified_at' => now(),
                            'password' => Hash::make('password'),
                        ],
                    );

                    UserBusinessAccess::query()->updateOrCreate(
                        [
                            'account_id' => $account->id,
                            'business_entity_id' => $business->id,
                            'user_id' => $admin->id,
                        ],
                        [
                            'permission_role' => 'admin',
                            'access_status' => 'active',
                            'starts_at' => now(),
                            'granted_by_user_id' => $admin->id,
                        ],
                    );

                    UserWorkplaceAccess::query()->updateOrCreate(
                        [
                            'account_id' => $account->id,
                            'workplace_id' => $workplace->id,
                            'user_id' => $admin->id,
                        ],
                        [
                            'business_entity_id' => $business->id,
                            'permission_role' => 'admin',
                            'access_status' => 'active',
                            'starts_at' => now(),
                            'granted_by_user_id' => $admin->id,
                        ],
                    );

                    $industry = Industry::query()->updateOrCreate(
                        ['industry_candidate_key' => 'construction|general'],
                        [
                            'external_industry_id' => 'IND-DEMO-001',
                            'industry_group' => 'Construction',
                            'industry_sub_group' => 'General construction',
                            'industry_leaf' => 'General construction',
                            'active_status' => true,
                        ],
                    );

                    $occupation = Occupation::query()->updateOrCreate(
                        ['occupation_candidate_key' => 'construction|general_worker'],
                        [
                            'external_occupation_id' => 'OCC-DEMO-001',
                            'occupation_group' => 'Construction support',
                            'occupation_sub_group' => 'General site work',
                            'occupation_leaf' => 'General Construction Worker',
                            'active_status' => true,
                        ],
                    );

                    BusinessIndustry::query()->updateOrCreate(
                        ['business_entity_id' => $business->id, 'industry_id' => $industry->id],
                        ['account_id' => $account->id],
                    );

                    $task = Task::query()->updateOrCreate(
                        ['external_task_id' => 'TASK-DEMO-001'],
                        [
                            'task_name' => 'Daily Site Inspection',
                            'task_title' => 'Daily Site Inspection',
                            'document_type' => 'SWMS',
                            'trade_industry' => 'General construction',
                            'task_group' => 'Construction',
                            'task_sub_group' => 'General',
                            'task_leaf' => 'Daily Site Inspection',
                            'task_candidate_key' => 'construction|general|daily_site_inspection',
                            'active_status' => true,
                        ],
                    );

                    TaskIndustryAccess::query()->updateOrCreate(
                        ['task_id' => $task->id, 'industry_id' => $industry->id],
                        [
                            'swms_view_access' => 'full',
                            'pre_start_access' => 'full',
                            'post_task_access' => 'full',
                            'training_access' => 'full',
                            'menu_visibility' => 'full',
                            'active_status' => true,
                        ],
                    );

                    TaskOccupationAccess::query()->updateOrCreate(
                        ['task_id' => $task->id, 'occupation_id' => $occupation->id],
                        [
                            'swms_view_access' => 'full',
                            'pre_start_access' => 'full',
                            'post_task_access' => 'full',
                            'training_access' => 'full',
                            'menu_visibility' => 'full',
                            'active_status' => true,
                        ],
                    );

                    $swms = SwmsVersion::query()->updateOrCreate(
                        ['task_id' => $task->id, 'external_swms_version_id' => 'demo-v1'],
                        [
                            'version_label' => 'v1',
                            'status' => 'published',
                            'full_swms_content' => [
                                'title' => 'Daily Site Inspection',
                                'steps' => ['Check site conditions', 'Confirm controls are in place'],
                            ],
                            'source_file_name' => 'dev-seed',
                            'approved_by_user_id' => $admin->id,
                            'approved_at' => now(),
                            'published_at' => now(),
                        ],
                    );

                    SwmsActivityStep::query()->updateOrCreate(
                        ['swms_version_id' => $swms->id, 'step_number' => 1],
                        [
                            'title' => 'Check site conditions',
                            'instruction' => 'Inspect access paths, exclusion zones, and weather exposure before work starts.',
                            'hazards' => ['slips_trips', 'mobile_plant'],
                            'controls' => ['barricades', 'prestart_briefing'],
                            'required_ppe' => ['hard_hat', 'hi_vis', 'safety_boots'],
                            'minimum_read_seconds' => 10,
                        ],
                    );

                    PrestartQuestion::query()->updateOrCreate(
                        ['task_id' => $task->id, 'question_number' => 1],
                        [
                            'prompt' => 'Are exclusion zones and access paths safe for work today?',
                            'question_type' => 'yes_no',
                            'is_required' => true,
                            'is_critical_failure' => true,
                            'expected_answer' => 'yes',
                        ],
                    );

                    WorkplaceTaskSetting::query()->updateOrCreate(
                        ['workplace_id' => $workplace->id, 'task_id' => $task->id],
                        [
                            'account_id' => $account->id,
                            'business_entity_id' => $business->id,
                            'active_swms_version_id' => $swms->id,
                            'prestart_frequency' => 'daily',
                            'posttask_frequency' => 'off',
                            'minimum_read_seconds' => 10,
                            'configured_by_user_id' => $admin->id,
                            'configured_at' => now(),
                        ],
                    );
                },
                businessEntityId: null,
                workplaceId: null,
            );
        });
    }
}
