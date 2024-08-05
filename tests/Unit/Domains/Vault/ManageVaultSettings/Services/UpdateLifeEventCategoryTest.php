<?php

namespace Tests\Unit\Domains\Vault\ManageVaultSettings\Services;

use App\Domains\Vault\ManageVaultSettings\Services\UpdateLifeEventCategory;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\LifeEventCategory;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateLifeEventCategoryTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_category(): void
    {
        $ross = $this->createAdministrator();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_EDIT, $vault);
        $category = LifeEventCategory::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $this->executeService($ross, $ross->account, $vault, $category);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateLifeEventCategory())->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = Account::factory()->create();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_EDIT, $vault);
        $category = LifeEventCategory::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $this->executeService($ross, $account, $vault, $category);
    }

    /** @test */
    public function it_fails_if_category_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_EDIT, $vault);
        $category = LifeEventCategory::factory()->create();
        $this->executeService($ross, $ross->account, $vault, $category);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_account(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_VIEW, $vault);
        $category = LifeEventCategory::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $this->executeService($ross, $ross->account, $vault, $category);
    }

    private function executeService(User $author, Account $account, Vault $vault, LifeEventCategory $category): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'vault_id' => $vault->id,
            'life_event_category_id' => $category->id,
            'label' => 'type name',
            'can_be_deleted' => true,
        ];

        $category = (new UpdateLifeEventCategory())->execute($request);

        $this->assertDatabaseHas('life_event_categories', [
            'id' => $category->id,
            'vault_id' => $vault->id,
            'label' => 'type name',
            'can_be_deleted' => true,
        ]);
    }
}
