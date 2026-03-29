<?php

namespace Tests\Unit\Modules\ServiceManagement\Actions;

use App\Modules\ServiceManagement\Actions\DeleteDatabase;
use App\Modules\ServiceManagement\Models\DatabaseInstance;
use App\Modules\ServiceManagement\Models\ServiceBinding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class DeleteDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_an_unbound_database_instance(): void
    {
        $database = DatabaseInstance::factory()->create();

        (new DeleteDatabase)->execute($database);

        $this->assertDatabaseMissing('database_instances', ['id' => $database->id]);
    }

    public function test_it_refuses_to_delete_a_bound_database_instance(): void
    {
        $database = DatabaseInstance::factory()->create();
        ServiceBinding::factory()->create(['database_instance_id' => $database->id]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot delete a database instance while it is bound to an environment.');

        (new DeleteDatabase)->execute($database);
    }
}
