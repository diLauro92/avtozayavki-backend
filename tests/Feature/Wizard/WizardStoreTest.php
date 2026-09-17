<?php

namespace Tests\Feature\Wizard;

use App\Enums\RequestSource;
use App\Wizard\WizardState;
use App\Wizard\WizardStore;
use Tests\TestCase;

class WizardStoreTest extends TestCase
{
    public function test_keeps_state_between_calls(): void
    {
        $state = (new WizardState('phone'))->withAnswer('client_name', 'Пётр')->withPhoto('file-1');

        app(WizardStore::class)->put(RequestSource::Telegram, 100, $state);

        $this->assertEquals($state, app(WizardStore::class)->get(RequestSource::Telegram, 100));
    }

    public function test_unknown_chat_has_no_state(): void
    {
        $this->assertNull(app(WizardStore::class)->get(RequestSource::Telegram, 100));
    }

    public function test_chats_and_channels_do_not_mix(): void
    {
        $store = app(WizardStore::class);
        $store->put(RequestSource::Telegram, 100, new WizardState('phone'));

        $this->assertNull($store->get(RequestSource::Telegram, 200));
        // Другой канал с тем же номером чата; когда появится MAX — заменить на него
        $this->assertNull($store->get(RequestSource::Manual, 100));
    }

    public function test_forget_removes_state(): void
    {
        $store = app(WizardStore::class);
        $store->put(RequestSource::Telegram, 100, new WizardState('phone'));

        $store->forget(RequestSource::Telegram, 100);

        $this->assertNull($store->get(RequestSource::Telegram, 100));
    }

    public function test_abandoned_state_expires_after_twelve_hours(): void
    {
        $store = app(WizardStore::class);
        $store->put(RequestSource::Telegram, 100, new WizardState('phone'));

        $this->travel(11)->hours();
        $this->assertNotNull($store->get(RequestSource::Telegram, 100));

        $this->travel(2)->hours();
        $this->assertNull($store->get(RequestSource::Telegram, 100));
    }
}
