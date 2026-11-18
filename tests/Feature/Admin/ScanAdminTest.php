<?php

namespace Tests\Feature\Admin;

use App\Models\Scan;
use Tests\Support\AdminTestCase;

class ScanAdminTest extends AdminTestCase
{
    public function test_the_owner_sees_scans_and_leads_and_others_do_not(): void
    {
        Scan::create(['url' => 'https://lead.example', 'domain' => 'lead.example', 'email' => 'lead@example.com', 'locale' => 'nl', 'ip_hash' => 'x', 'contact_consent' => true, 'status' => Scan::DONE, 'scores' => ['security' => 80, 'seo' => 60, 'speed' => 90, 'accessibility' => 70]]);

        $this->actingAs($this->owner())->get('/admin/scans')->assertOk()->assertSee('lead.example')->assertSee('lead@example.com');
        $this->actingAs($this->editor())->get('/admin/scans')->assertForbidden();
        $this->actingAs($this->support())->get('/admin/scans')->assertForbidden();
    }
}
