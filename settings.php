<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_aitutor', get_string('pluginname', 'local_aitutor'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configcheckbox(
        'local_aitutor/enabled',
        get_string('enabled', 'local_aitutor'),
        get_string('enabled_desc', 'local_aitutor'),
        1
    ));

    $settings->add(new admin_setting_configselect(
        'local_aitutor/provider',
        get_string('provider', 'local_aitutor'),
        get_string('provider_desc', 'local_aitutor'),
        'zenmux',
        [
            'zenmux'   => 'ZenMux (GLM, free)',
            'gemini'   => 'Google Gemini',
            'openai'   => 'OpenAI',
            'groq'     => 'Groq',
            'claude'   => 'Anthropic Claude',
            'deepseek' => 'DeepSeek',
            'mistral'  => 'Mistral',
            'cerebras' => 'Cerebras',
        ]
    ));

    $settings->add(new admin_setting_configtext(
        'local_aitutor/model',
        get_string('model', 'local_aitutor'),
        get_string('model_desc', 'local_aitutor'),
        'z-ai/glm-5.2-free'
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_aitutor/apikey',
        get_string('apikey', 'local_aitutor'),
        get_string('apikey_desc', 'local_aitutor'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_aitutor/maxhints',
        get_string('maxhints', 'local_aitutor'),
        get_string('maxhints_desc', 'local_aitutor'),
        '3',
        PARAM_INT
    ));
}
