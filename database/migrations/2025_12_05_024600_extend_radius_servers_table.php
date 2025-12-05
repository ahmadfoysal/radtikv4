<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('radius_servers', function (Blueprint $table) {
            // Provider / node info
            $table->string('provider')->nullable()->after('user_id');
            $table->string('provider_server_id')->nullable()->after('provider');
            $table->string('region')->nullable()->after('provider_server_id');
            $table->string('plan')->nullable()->after('region');
            $table->string('status')->nullable()->after('plan'); // provisioning, running, stopped, error
            $table->timestamp('provisioned_at')->nullable()->after('status');
            $table->timestamp('last_sync_at')->nullable()->after('provisioned_at');
            $table->text('last_error')->nullable()->after('last_sync_at');

            // SSH access
            $table->string('ssh_username')->nullable()->after('last_error');
            $table->string('ssh_auth_type')->nullable()->after('ssh_username'); // password | key
            $table->text('ssh_password')->nullable()->after('ssh_auth_type');
            $table->string('ssh_key_name')->nullable()->after('ssh_password'); // optional identifier/path

            // RADIUS connectivity (file-based, NOT DB)
            $table->unsignedSmallInteger('radius_auth_port')->default(1812)->after('ssh_key_name');
            $table->unsignedSmallInteger('radius_acct_port')->default(1813)->after('radius_auth_port');
            $table->text('radius_secret')->nullable()->after('radius_acct_port');

            // Subscription package (store JSON/array)
            $table->json('package')->nullable()->after('radius_secret');

            // Billing / behavior flags
            $table->boolean('auto_renew')->default(false)->after('package');

            // Monitoring fields
            $table->timestamp('last_health_check_at')->nullable()->after('auto_renew');
            $table->string('last_health_status')->nullable()->after('last_health_check_at'); // healthy | unreachable | degraded
            $table->text('last_health_message')->nullable()->after('last_health_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('radius_servers', function (Blueprint $table) {
            $table->dropColumn([
                'provider',
                'provider_server_id',
                'region',
                'plan',
                'status',
                'provisioned_at',
                'last_sync_at',
                'last_error',
                'ssh_username',
                'ssh_auth_type',
                'ssh_password',
                'ssh_key_name',
                'radius_auth_port',
                'radius_acct_port',
                'radius_secret',
                'package',
                'auto_renew',
                'last_health_check_at',
                'last_health_status',
                'last_health_message',
            ]);
        });
    }
};
