@local @local_aitutor
Feature: AI Tutor administration settings
  In order to enable the Socratic AI tutor
  As an administrator
  I need to reach its configuration page

  Scenario: An administrator can view the AI Tutor settings page
    Given I log in as "admin"
    And I visit "/admin/settings.php?section=local_aitutor"
    Then I should see "Enable the AI tutor"
    And I should see "AI provider"
    And I should see "AI API key"
