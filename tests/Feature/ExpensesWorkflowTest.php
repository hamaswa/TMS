<?php

namespace Tests\Feature;

use App\Models\DaliyExpenses;
use App\Models\Expenses;
use App\Models\User;
use App\Models\Workers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExpensesWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_expenses_are_added_edited_and_filtered_from_the_index_page(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->get(route('admin.dailyexpense.create'))
            ->assertRedirect(route('admin.dailyexpense.index', ['modal' => 'create']));

        $this->actingAs($owner)->post(route('admin.dailyexpense.insert'), [
            'name' => ['Tea', 'Rickshaw'],
            'rupee' => [150, 300],
        ])->assertRedirect(route('admin.dailyexpense.index'))->assertSessionHas('insert');

        $this->assertDatabaseCount('daliy_expenses', 2);
        $tea = DaliyExpenses::where('Expense_name', 'Tea')->firstOrFail();

        $this->actingAs($owner)->from(route('admin.dailyexpense.index'))
            ->post(route('admin.dailyexpense.insert'), ['name' => ['Bad'], 'rupee' => ['abc']])
            ->assertSessionHasErrors('rupee.0');

        $this->actingAs($owner)->get(route('admin.dailyexpense.edit', $tea->id))
            ->assertRedirect(route('admin.dailyexpense.index', ['edit' => $tea->id]));

        $this->actingAs($owner)->post(route('admin.dailyexpense.update', $tea->id), [
            'name' => 'Green tea',
            'rupee' => 200,
        ])->assertRedirect(route('admin.dailyexpense.index'));
        $this->assertDatabaseHas('daliy_expenses', ['id' => $tea->id, 'Expense_name' => 'Green tea', 'Expense_payment' => 200]);

        $this->actingAs($owner)->get(route('admin.dailyexpense.index'))
            ->assertOk()
            ->assertSee('id="dailyCreateModal"', false)
            ->assertSee('id="dailyEditModal"', false)
            ->assertSeeText('Green tea')
            ->assertSee('Rs. 500.00');

        $this->actingAs($owner)->get(route('admin.dailyexpense.index', ['from' => now()->addDay()->toDateString()]))
            ->assertOk()
            ->assertDontSeeText('Green tea');
    }

    public function test_daily_expenses_are_paginated_ten_per_page_with_a_full_list_total(): void
    {
        $owner = $this->owner();
        foreach (range(1, 12) as $i) {
            DaliyExpenses::create(['user_id' => $owner->id, 'Expense_name' => 'Item '.$i, 'Expense_payment' => 100]);
        }
        $oldest = DaliyExpenses::where('Expense_name', 'Item 1')->firstOrFail();
        $oldest->forceFill(['created_at' => now()->subDay()])->save();

        $this->actingAs($owner)->get(route('admin.dailyexpense.index'))
            ->assertOk()
            ->assertViewHas('expense', fn ($page) => $page->count() === 10 && $page->total() === 12)
            ->assertSee('Rs. 1,200.00')
            ->assertDontSeeText('Item 1 ');

        $this->actingAs($owner)->get(route('admin.dailyexpense.index', ['page' => 2]))
            ->assertOk()
            ->assertViewHas('expense', fn ($page) => $page->count() === 2);

        // Editing a record that is not on the first page still opens its modal.
        $this->actingAs($owner)->get(route('admin.dailyexpense.index', ['edit' => $oldest->id]))
            ->assertOk()
            ->assertViewHas('editTarget', fn ($target) => ($target['id'] ?? null) === $oldest->id)
            ->assertSee(route('admin.dailyexpense.update', ['id' => $oldest->id]), false);
    }

    public function test_monthly_bills_and_salaries_are_managed_through_modals(): void
    {
        $owner = $this->owner();
        $other = $this->owner();

        $this->actingAs($owner)->post(route('admin.expense.insert'), [
            'expense_date' => now()->toDateString(),
            'rent' => 20000,
            'bill' => 3500,
        ])->assertRedirect(route('admin.expense.index'));

        $this->actingAs($owner)->post(route('admin.expense.insert'), [
            'workers' => [
                ['name' => 'Ali', 'salary' => 15000],
                ['name' => 'Bilal', 'salary' => 12000],
            ],
        ])->assertRedirect(route('admin.expense.index'));

        // Staff-only submit must not create an empty rent/bill row.
        $this->assertSame(1, Expenses::where('user_id', $owner->id)->count());
        $this->assertSame(2, Workers::where('user_id', $owner->id)->count());

        $this->actingAs($owner)->from(route('admin.expense.index'))
            ->post(route('admin.expense.insert'), [])
            ->assertSessionHasErrors('rent');

        $bill = Expenses::where('user_id', $owner->id)->firstOrFail();
        $this->actingAs($owner)->post(route('admin.expense.update', $bill->id), [
            'rent' => 21000, 'bill' => 3000, 'expense_date' => now()->toDateString(),
        ])->assertRedirect(route('admin.expense.index'));
        $this->assertEquals(21000, (float) $bill->fresh()->Monthly_Rent);

        $ali = Workers::where('Worker_Name', 'Ali')->firstOrFail();
        $this->actingAs($owner)->post(route('admin.worker.update', $ali->id), ['name' => 'Ali Raza', 'salary' => 16000])
            ->assertRedirect(route('admin.expense.index'));
        $this->assertDatabaseHas('workers', ['id' => $ali->id, 'Worker_Name' => 'Ali Raza', 'Worker_salary' => 16000]);

        $this->actingAs($owner)->get(route('admin.expense.index'))
            ->assertOk()
            ->assertSee('id="billModal"', false)
            ->assertSee('id="staffCreateModal"', false)
            ->assertSeeText('Ali Raza')
            ->assertSee('Rs. 52,000.00');

        // Another shop cannot open or edit these records.
        $this->actingAs($other)->get(route('admin.expense.edit', $bill->id))->assertNotFound();
        $this->actingAs($other)->post(route('admin.worker.update', $ali->id), ['name' => 'X', 'salary' => 1])->assertNotFound();
    }

    private function owner(): User
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
