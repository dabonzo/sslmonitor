<?php

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AdvancedFeaturesTest extends DuskTestCase
{
    /**
     * Test Analytics Dashboard functionality
     */
    public function test_analytics_dashboard_loads_and_functions()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->testUser())
                    ->visit('/analytics')
                    ->waitForText('Analytics & Insights', 10)
                    ->assertSee('Performance Analytics')
                    ->assertSee('Historical Trends')
                    ->assertSee('Security Insights')
                    ->assertSee('Reports');

            // Test tab navigation
            $browser->click('button:contains("Historical Trends")')
                    ->waitForText('Historical Trends', 5)
                    ->assertSee('Long-term SSL certificate and performance analysis');

            $browser->click('button:contains("Security Insights")')
                    ->waitForText('Security Insights', 5)
                    ->assertSee('SSL security analysis and recommendations');

            // Test refresh functionality
            $browser->click('button:contains("Refresh All")')
                    ->pause(2000);

            $browser->screenshot('analytics-dashboard-complete');
        });
    }

    /**
     * Test Bulk Operations functionality
     */
    public function test_bulk_operations_interface()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->testUser())
                    ->visit('/ssl/bulk-operations')
                    ->waitForText('Bulk Certificate Operations', 10)
                    ->assertSee('Quick Actions')
                    ->assertSee('Active Operations')
                    ->assertSee('Operation History');

            // Test quick action execution
            $browser->click('div:contains("Check All Certificates")')
                    ->pause(3000)
                    ->assertSee('Certificate Status Check');

            // Test new bulk operation creation
            $browser->click('button:contains("New Bulk Operation")')
                    ->waitForText('Select Websites', 5)
                    ->assertSee('Choose which websites to include');

            $browser->screenshot('bulk-operations-interface');
        });
    }

    /**
     * Test Reports Dashboard functionality
     */
    public function test_reports_dashboard_comprehensive()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->testUser())
                    ->visit('/reports')
                    ->waitForText('Advanced Reporting Dashboard', 10)
                    ->assertSee('Quick Reports')
                    ->assertSee('Recent Reports')
                    ->assertSee('Report Usage');

            // Test quick report generation
            $browser->click('div:contains("SSL Status Summary")')
                    ->pause(5000); // Wait for report generation simulation

            // Test report filtering
            $browser->select('select[data-testid="reports-filter"]', 'ssl')
                    ->pause(1000)
                    ->assertSee('SSL Reports');

            // Test custom report builder
            $browser->click('button:contains("Create Report")')
                    ->waitForText('Advanced Report Builder', 5)
                    ->assertSee('Report Information')
                    ->assertSee('Data Sources');

            $browser->screenshot('reports-dashboard-complete');
        });
    }

    /**
     * Test Advanced Alert System
     */
    public function test_advanced_alerting_system()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->testUser())
                    ->visit('/settings/alerts')
                    ->waitForText('Alert Configuration', 10)
                    ->assertSee('Alert Dashboard')
                    ->assertSee('Alert Rules');

            // Test alert rule creation
            $browser->click('button:contains("Create New Rule")')
                    ->waitForText('Advanced Alert Rule Builder', 5)
                    ->type('input[name="rule_name"]', 'Test SSL Expiry Alert')
                    ->select('select[name="priority"]', 'high')
                    ->click('button:contains("Add Condition")');

            // Test notification settings
            $browser->scrollIntoView('h3:contains("Notification Settings")')
                    ->assertSee('Email Notifications')
                    ->assertSee('Slack Integration')
                    ->assertSee('Webhook Integration');

            $browser->screenshot('advanced-alerting-system');
        });
    }

    /**
     * Test Certificate Management Advanced Features
     */
    public function test_certificate_management_features()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->testUser())
                    ->visit('/ssl/websites')
                    ->waitForText('SSL Websites', 10);

            // Find and click a Certificate button
            $browser->whenAvailable('button:contains("Certificate")', function ($button) {
                $button->click();
            })
            ->waitForText('Certificate Details', 5)
            ->assertSee('Security Analysis')
            ->assertSee('Certificate Information')
            ->assertSee('Security Score');

            // Test bulk certificate actions
            $browser->click('button[aria-label="Close"]') // Close modal
                    ->pause(1000);

            // Select multiple websites for bulk actions
            $browser->click('button[title="Select website"]')
                    ->pause(500)
                    ->click('button[title="Select website"]') // Select second website
                    ->pause(500);

            // Verify bulk actions appear
            $browser->whenAvailable('div:contains("Bulk Actions")', function ($div) use ($browser) {
                $browser->assertSee('Transfer to Team')
                        ->assertSee('Check Certificates');
            });

            $browser->screenshot('certificate-management-features');
        });
    }

    /**
     * Test Performance Analytics Deep Dive
     */
    public function test_performance_analytics_detailed()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->testUser())
                    ->visit('/analytics')
                    ->waitForText('Performance Analytics', 10)
                    ->assertSee('Avg Response Time')
                    ->assertSee('Uptime')
                    ->assertSee('SSL Health Score')
                    ->assertSee('Critical Alerts');

            // Test time range selection
            $browser->select('select:contains("Last 30 days")', '7d')
                    ->pause(2000)
                    ->assertSee('Loading chart data...');

            // Test website performance details
            $browser->scrollIntoView('table')
                    ->assertSee('Website Performance Details')
                    ->assertSee('Office Manager Pro')
                    ->assertSee('Red Gas Austria');

            $browser->screenshot('performance-analytics-detailed');
        });
    }

    /**
     * Test Mobile Responsiveness
     */
    public function test_mobile_responsiveness()
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 812) // iPhone X dimensions
                    ->loginAs($this->testUser())
                    ->visit('/analytics')
                    ->waitForText('Analytics & Insights', 10)
                    ->assertSee('Performance Analytics');

            // Test mobile navigation
            $browser->click('button:contains("Performance")')
                    ->pause(1000)
                    ->assertSee('Performance metrics');

            // Test mobile reports interface
            $browser->visit('/reports')
                    ->waitForText('Advanced Reporting Dashboard', 10)
                    ->assertSee('Quick Reports');

            // Test mobile bulk operations
            $browser->visit('/ssl/bulk-operations')
                    ->waitForText('Bulk Certificate Operations', 10)
                    ->assertSee('Quick Actions');

            $browser->screenshot('mobile-responsive-features');
        });
    }

    /**
     * Test Error Handling and Loading States
     */
    public function test_loading_states_and_error_handling()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->testUser())
                    ->visit('/analytics')
                    ->waitForText('Analytics & Insights', 10);

            // Test refresh with loading states
            $browser->click('button:contains("Refresh All")')
                    ->pause(500)
                    ->assertSee('Refreshing...'); // Should show loading state

            // Test report generation loading
            $browser->visit('/reports')
                    ->waitForText('Advanced Reporting Dashboard', 10)
                    ->click('div:contains("Performance Dashboard")')
                    ->pause(1000)
                    ->assertSee('Generating...'); // Should show generation loading

            $browser->screenshot('loading-states-error-handling');
        });
    }

    /**
     * Test Dark Mode Functionality
     */
    public function test_dark_mode_advanced_features()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->testUser())
                    ->visit('/analytics')
                    ->waitForText('Analytics & Insights', 10);

            // Toggle dark mode
            $browser->script('document.documentElement.classList.add("dark")');
            $browser->pause(1000)
                    ->screenshot('analytics-dark-mode');

            // Test reports in dark mode
            $browser->visit('/reports')
                    ->waitForText('Advanced Reporting Dashboard', 10)
                    ->screenshot('reports-dark-mode');

            // Test bulk operations in dark mode
            $browser->visit('/ssl/bulk-operations')
                    ->waitForText('Bulk Certificate Operations', 10)
                    ->screenshot('bulk-operations-dark-mode');

            // Switch back to light mode
            $browser->script('document.documentElement.classList.remove("dark")');
            $browser->pause(1000)
                    ->screenshot('features-light-mode-restored');
        });
    }

    /**
     * Test Integration Between Features
     */
    public function test_feature_integration()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->testUser())
                    ->visit('/ssl/websites')
                    ->waitForText('SSL Websites', 10);

            // Navigate from websites to analytics
            $browser->click('a[href="/analytics"]')
                    ->waitForText('Analytics & Insights', 10)
                    ->assertSee('Performance Analytics');

            // Navigate to reports
            $browser->click('a[href="/reports"]')
                    ->waitForText('Advanced Reporting Dashboard', 10)
                    ->assertSee('SSL Status Summary');

            // Generate a quick SSL report
            $browser->click('div:contains("SSL Status Summary")')
                    ->pause(3000); // Wait for generation

            // Navigate to bulk operations
            $browser->visit('/ssl/bulk-operations')
                    ->waitForText('Bulk Certificate Operations', 10)
                    ->assertSee('Check All Certificates');

            // Test navigation consistency
            $browser->assertSee('Bulk Certificate Operations')
                    ->assertPresent('nav'); // Ensure navigation is present

            $browser->screenshot('feature-integration-complete');
        });
    }

    /**
     * Helper method to get test user
     */
    private function testUser()
    {
        return \App\Models\User::where('email', 'bonzo@konjscina.com')->first()
            ?? \App\Models\User::factory()->create([
                'email' => 'bonzo@konjscina.com',
                'name' => 'Test User'
            ]);
    }
}