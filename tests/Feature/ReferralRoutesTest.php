<?php

namespace Tests\Feature;

use App\Models\Master;
use App\Models\Payment;
use App\Models\Referral;
use App\Models\ReferralEarning;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;

class ReferralRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_referrals_and_earnings_follow_existing_payment_rules(): void
    {
        $this->seed();

        $this->withHeader('X-Master-Id', '1')
            ->getJson('/api/referrals/my')
            ->assertOk()
            ->assertJsonCount(4, 'referrals')
            ->assertJsonStructure(['referrals' => [['attached_at']]])
            ->assertJsonPath('referrals.0.master_id', 3)
            ->assertJsonPath('referrals.0.name', 'Ира')
            ->assertJsonPath('referrals.0.rewarded', true)
            ->assertJsonPath('referrals.0.earned', 30000)
            ->assertJsonPath('referrals.1.rewarded', false)
            ->assertJsonPath('referrals.2.rewarded', false)
            ->assertJsonPath('referrals.3.name', 'Даша')
            ->assertJsonPath('referrals.3.rewarded', false)
            ->assertJsonPath('referrals.3.earned', 0);

        $this->withHeader('X-Master-Id', '1')
            ->getJson('/api/referrals/earnings')
            ->assertOk()
            ->assertExactJson([
                'total' => 30000,
                'pending' => 30000,
                'paid' => 0,
                'rewarded_referrals' => 1,
            ]);
    }

    public function test_earnings_separate_pending_and_paid_amounts(): void
    {
        $this->seed();
        $newMaster = Master::create(['name' => 'Новый мастер', 'referral_code' => 'NEW42']);

        $this->withHeader('X-Master-Id', (string) $newMaster->id)
            ->postJson('/api/referrals/attach', ['code' => 'MASHA10'])
            ->assertCreated();

        Payment::create(['master_id' => $newMaster->id, 'amount' => 500, 'type' => Payment::TYPE_SBP]);
        ReferralEarning::where('referred_master_id', 3)->update(['status' => ReferralEarning::STATUS_PAID]);

        $this->withHeader('X-Master-Id', '1')
            ->getJson('/api/referrals/earnings')
            ->assertOk()
            ->assertExactJson([
                'total' => 35000,
                'pending' => 5000,
                'paid' => 30000,
                'rewarded_referrals' => 2,
            ]);
    }

    public function test_attach_is_idempotent_and_does_not_change_referrer(): void
    {
        $this->seed();
        $newMaster = Master::create(['name' => 'Новый мастер', 'referral_code' => 'NEW42']);

        $this->withHeader('X-Master-Id', (string) $newMaster->id)
            ->postJson('/api/referrals/attach', ['code' => 'MASHA10'])
            ->assertCreated()
            ->assertJsonPath('referrer_master_id', 1);

        $this->withHeader('X-Master-Id', (string) $newMaster->id)
            ->postJson('/api/referrals/attach', ['code' => 'LENA77'])
            ->assertOk()
            ->assertJsonPath('referrer_master_id', 1);

        $this->assertSame(1, Referral::where('referred_master_id', $newMaster->id)->count());
    }

    public function test_attach_rejects_invalid_and_own_codes(): void
    {
        $this->seed();

        $this->withHeader('X-Master-Id', '2')
            ->postJson('/api/referrals/attach')
            ->assertUnprocessable();

        $this->withHeader('X-Master-Id', '2')
            ->post('/api/referrals/attach', ['code' => 123])
            ->assertUnprocessable()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonValidationErrors('code');

        $this->withHeader('X-Master-Id', '2')
            ->postJson('/api/referrals/attach', ['code' => 'UNKNOWN'])
            ->assertUnprocessable();

        $this->withHeader('X-Master-Id', '2')
            ->postJson('/api/referrals/attach', ['code' => 'LENA77'])
            ->assertUnprocessable();

        $this->assertDatabaseMissing('referrals', ['referred_master_id' => 2]);
    }

    public function test_database_rejects_a_second_referral_for_the_same_master(): void
    {
        $this->seed();

        $this->expectException(QueryException::class);

        Referral::create([
            'referrer_master_id' => 2,
            'referred_master_id' => 3,
            'status' => Referral::STATUS_PENDING,
        ]);
    }

    public function test_attach_then_first_payment_rewards_the_referrer_once(): void
    {
        $this->seed();
        $newMaster = Master::create(['name' => 'Новый мастер', 'referral_code' => 'NEW42']);

        $this->withHeader('X-Master-Id', (string) $newMaster->id)
            ->postJson('/api/referrals/attach', ['code' => 'MASHA10'])
            ->assertCreated();

        Payment::create(['master_id' => $newMaster->id, 'amount' => 500, 'type' => Payment::TYPE_SBP]);
        Payment::create(['master_id' => $newMaster->id, 'amount' => 500, 'type' => Payment::TYPE_SBP]);

        $this->assertDatabaseHas('referrals', [
            'referred_master_id' => $newMaster->id,
            'status' => Referral::STATUS_REWARDED,
        ]);
        $this->assertSame(1, ReferralEarning::where('referred_master_id', $newMaster->id)->count());

        $this->withHeader('X-Master-Id', '1')
            ->getJson('/api/referrals/earnings')
            ->assertOk()
            ->assertJsonPath('total', 35000)
            ->assertJsonPath('pending', 35000)
            ->assertJsonPath('rewarded_referrals', 2);
    }

    public function test_a_master_without_referrals_has_empty_reports(): void
    {
        $this->seed();

        $this->withHeader('X-Master-Id', '2')
            ->getJson('/api/referrals/my')
            ->assertOk()
            ->assertExactJson(['referrals' => []]);

        $this->withHeader('X-Master-Id', '2')
            ->getJson('/api/referrals/earnings')
            ->assertOk()
            ->assertExactJson([
                'total' => 0,
                'pending' => 0,
                'paid' => 0,
                'rewarded_referrals' => 0,
            ]);
    }

    public function test_routes_require_an_existing_master(): void
    {
        $this->seed();

        $this->postJson('/api/referrals/attach', ['code' => 'MASHA10'])->assertUnauthorized();
        $this->getJson('/api/referrals/my')->assertUnauthorized();
        $this->getJson('/api/referrals/earnings')->assertUnauthorized();
        $this->withHeader('X-Master-Id', '999')->getJson('/api/referrals/my')->assertUnauthorized();
    }
}
