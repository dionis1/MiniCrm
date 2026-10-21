<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CrmMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_crm_tables_and_legacy_company_columns_exist(): void
    {
        foreach (['leads', 'crm_tasks', 'crm_activities', 'companies', 'employees'] as $name) {
            $this->assertTrue(Schema::hasTable($name), $name);
        }

        $this->assertTrue(Schema::hasColumns('companies', ['user_id', 'status', 'tags']));
        $this->assertTrue(Schema::hasColumns('employees', ['user_id', 'position']));
    }
}
