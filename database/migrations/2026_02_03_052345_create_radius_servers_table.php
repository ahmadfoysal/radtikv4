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
        Schema::create('radius_servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('host')->nullable(); // Nullable until Linode creates it
            $table->integer('auth_port')->default(1812);
            $table->integer('acct_port')->default(1813);
            $table->text('secret');
            $table->integer('timeout')->default(5);
            $table->integer('retries')->default(3);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            
            // SSH Configuration
            $table->integer('ssh_port')->default(22);
            $table->string('ssh_username')->default('root');
            $table->text('ssh_password')->nullable(); // Encrypted
            $table->text('ssh_private_key')->nullable(); // Encrypted, for key-based auth
            
            // Linode Integration
            $table->string('linode_node_id')->nullable(); // Linode instance ID
            $table->string('linode_region')->default('us-east'); // us-east, us-west, eu-west, etc.
            $table->string('linode_plan')->default('g6-nanode-1'); // Linode plan type
            $table->string('linode_image')->default('linode/ubuntu22.04'); // OS image
            $table->string('linode_label')->nullable(); // Label in Linode dashboard
            $table->ipAddress('linode_ipv4')->nullable(); // IPv4 address from Linode
            $table->string('linode_ipv6')->nullable(); // IPv6 address from Linode
            
            // Installation & Status
            $table->enum('installation_status', [
                'pending',      // Waiting to create
                'creating',     // Creating Linode node
                'installing',   // Installing FreeRADIUS
                'completed',    // Ready to use
                'failed',       // Installation failed
                'error'         // Error occurred
            ])->default('pending');
            $table->text('installation_log')->nullable(); // Installation logs
            $table->timestamp('installed_at')->nullable(); // When installation completed
            
            // Auto-provisioning flag
            $table->boolean('auto_provision')->default(true); // Auto-create on Linode
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('radius_servers');
    }
};
