<?php

namespace Tests\Unit\Modules\Infrastructure\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @preserveGlobalState disabled
 */
class ServerBootstrapperTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_applies_phase_6_hardening_steps(): void
    {
        $script = file_get_contents(app_path('Modules/Infrastructure/Services/ServerBootstrapper.php'));

        $this->assertStringContainsString('apt-get install -y -qq curl wget unzip ufw fail2ban unattended-upgrades ca-certificates', $script);
        $this->assertStringContainsString('useradd --create-home --shell /bin/bash --user-group helm', $script);
        $this->assertStringContainsString('chmod 600 /etc/helm/agent.conf', $script);
        $this->assertStringContainsString('PasswordAuthentication no', $script);
        $this->assertStringContainsString('PermitRootLogin no', $script);
        $this->assertStringContainsString('$ports[] = 80;', $script);
        $this->assertStringContainsString('$ports[] = 443;', $script);
        $this->assertStringContainsString('bind-address = %s', $script);
    }

    public function test_it_preserves_operator_access_before_disabling_password_login(): void
    {
        $script = file_get_contents(app_path('Modules/Infrastructure/Services/ServerBootstrapper.php'));
        $authorizedKeysIndex = strpos($script, 'if [ -f /root/.ssh/authorized_keys ]; then cp /root/.ssh/authorized_keys /home/helm/.ssh/authorized_keys');
        $disablePasswordIndex = strpos($script, 'PasswordAuthentication no');

        $this->assertIsInt($authorizedKeysIndex);
        $this->assertIsInt($disablePasswordIndex);
        $this->assertLessThan($disablePasswordIndex, $authorizedKeysIndex);
    }
}
