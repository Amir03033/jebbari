<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Pages\Pages\EditPage;
use App\Models\Page;
use Database\Seeders\CmsSeeder;
use Livewire\Livewire;
use Tests\Support\AdminTestCase;

/**
 * Rich text is edited as a nested document (lists inside lists inside blocks),
 * so the form sends deep property paths. Livewire's default limit of 10 levels
 * broke saving the privacy page in production.
 */
class DeepRichTextTest extends AdminTestCase
{
    public function test_a_deeply_nested_rich_text_property_can_be_updated(): void
    {
        $this->seed(CmsSeeder::class);
        $this->actingAs($this->owner());
        $page = Page::where('key', 'privacy')->first();

        $component = Livewire::test(EditPage::class, ['record' => $page->getKey()]);
        $uuid = array_key_first($component->get('data.blocks'));

        // data.blocks.<uuid>.data.body.nl.content.3.content.0.content.0.content.0.content.0.text: 18 levels.
        $component->set("data.blocks.$uuid.data.body.nl.content.3.content.0.content.0.content.0.content.0.text", 'Professor W.H. Keesomlaan 12')
            ->assertHasNoErrors();
    }
}
