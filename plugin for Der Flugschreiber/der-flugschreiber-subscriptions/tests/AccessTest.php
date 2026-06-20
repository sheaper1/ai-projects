<?php

use PHPUnit\Framework\TestCase;

final class AccessTest extends TestCase
{
    private $access;

    protected function setUp(): void
    {
        $GLOBALS['df_test_users'] = array(
            1 => (object) array('roles' => array(DF_Subscriptions::ROLE)),
            2 => (object) array('roles' => array('subscriber')),
        );
        $GLOBALS['df_test_meta'] = array();
        $this->access = new DF_Subscriptions_Access();
    }

    public function test_legacy_status_remains_active(): void
    {
        $GLOBALS['df_test_meta'][1][DF_Subscriptions::EXPIRES_META] = '2026-06-30';

        $this->assertSame('active', $this->access->get_subscription_status(1));
        $this->assertTrue($this->access->user_can_read_paid_content(1));
    }

    public function test_trial_can_read_paid_content(): void
    {
        $GLOBALS['df_test_meta'][1] = array(
            DF_Subscriptions::EXPIRES_META => '2026-06-30',
            DF_Subscriptions::STATUS_META => 'trial',
        );

        $this->assertTrue($this->access->user_can_read_paid_content(1));
    }

    public function test_paused_and_cancelled_cannot_read(): void
    {
        foreach (array('paused', 'cancelled') as $status) {
            $GLOBALS['df_test_meta'][1] = array(
                DF_Subscriptions::EXPIRES_META => '2026-06-30',
                DF_Subscriptions::STATUS_META => $status,
            );

            $this->assertFalse($this->access->user_can_read_paid_content(1));
        }
    }

    public function test_expiration_uses_end_of_wordpress_day(): void
    {
        $GLOBALS['df_test_meta'][1][DF_Subscriptions::EXPIRES_META] = '2026-06-11';
        $this->assertTrue($this->access->user_can_read_paid_content(1));

        $GLOBALS['df_test_meta'][1][DF_Subscriptions::EXPIRES_META] = '2026-06-10';
        $this->assertFalse($this->access->user_can_read_paid_content(1));
    }

    public function test_wrong_role_and_invalid_date_are_denied(): void
    {
        $GLOBALS['df_test_meta'][2][DF_Subscriptions::EXPIRES_META] = '2026-06-30';
        $this->assertFalse($this->access->user_can_read_paid_content(2));

        $GLOBALS['df_test_meta'][1][DF_Subscriptions::EXPIRES_META] = 'not-a-date';
        $this->assertFalse($this->access->user_can_read_paid_content(1));
    }
}
