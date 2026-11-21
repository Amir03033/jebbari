<?php

namespace Tests\Feature\Leads;

use App\Jobs\PruneOldRecords;
use App\Models\Inquiry;
use App\Models\Lead;
use App\Models\Proposal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** What the privacy statement promises about deleting contact details is what happens. */
class RetentionTest extends TestCase
{
    use RefreshDatabase;

    private function lead(array $o, string $age): Lead
    {
        $lead = Lead::create($o + ['source' => 'wizard']);
        Lead::whereKey($lead->id)->update(['updated_at' => now()->modify($age)]);

        return $lead;
    }

    public function test_leads_go_twelve_months_after_the_last_contact_unless_they_led_to_an_agreement(): void
    {
        $old = $this->lead(['email' => 'oud@example.test'], '-13 months');
        $recent = $this->lead(['email' => 'recent@example.test'], '-11 months');
        $won = $this->lead(['email' => 'klant@example.test', 'status' => 'won'], '-13 months');
        $accepted = $this->lead(['email' => 'akkoord@example.test'], '-13 months');
        Proposal::create(['lead_id' => $accepted->id, 'client_name' => 'A', 'client_email' => 'akkoord@example.test', 'title' => 'X', 'accepted_at' => now()->subYear()]);
        Lead::whereKey($accepted->id)->update(['updated_at' => now()->subMonths(13)]);
        $draft = $this->lead([], '-31 days');

        (new PruneOldRecords)->handle();

        $this->assertNull(Lead::find($old->id));
        $this->assertNull(Lead::find($draft->id));
        $this->assertNotNull(Lead::find($recent->id));
        $this->assertNotNull(Lead::find($won->id));
        $this->assertNotNull(Lead::find($accepted->id));
    }

    public function test_contact_messages_go_twelve_months_after_the_last_activity(): void
    {
        $base = ['name' => 'A', 'email' => 'a@example.test', 'message' => 'Hallo', 'locale' => 'nl'];
        $old = Inquiry::create($base);
        $noted = Inquiry::create($base);
        $new = Inquiry::create($base);
        Inquiry::whereKey([$old->id, $noted->id])->update(['updated_at' => now()->subMonths(13), 'created_at' => now()->subMonths(13)]);
        $noted->notes()->create(['body' => 'Teruggebeld', 'user_id' => null]);

        (new PruneOldRecords)->handle();

        $this->assertNull(Inquiry::find($old->id));
        $this->assertNotNull(Inquiry::find($noted->id));
        $this->assertNotNull(Inquiry::find($new->id));
    }
}
