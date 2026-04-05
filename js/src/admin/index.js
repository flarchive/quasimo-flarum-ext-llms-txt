import app from 'flarum/common/app';

app.initializers.add('quasimo-llms-txt', () => {
  const baseUrl = window.location.origin;

  app.extensionData
    .for('quasimo-llms-txt')
    .registerSetting({
      setting: 'llms_txt.enabled',
      label: app.translator.trans('quasimo-llms-txt.admin.settings.enabled_label'),
      help: app.translator.trans('quasimo-llms-txt.admin.settings.enabled_help', {
        url: baseUrl + '/llms.txt',
      }),
      type: 'boolean',
    })
    .registerSetting({
      setting: 'llms_txt.full_enabled',
      label: app.translator.trans('quasimo-llms-txt.admin.settings.full_enabled_label'),
      help: app.translator.trans('quasimo-llms-txt.admin.settings.full_enabled_help', {
        url: baseUrl + '/llms-full.txt',
      }),
      type: 'boolean',
    })
    .registerSetting({
      setting: 'llms_txt.sort',
      label: app.translator.trans('quasimo-llms-txt.admin.settings.sort_label'),
      help: app.translator.trans('quasimo-llms-txt.admin.settings.sort_help'),
      type: 'select',
      options: {
        latest: app.translator.trans('quasimo-llms-txt.admin.settings.sort_latest'),
        top: app.translator.trans('quasimo-llms-txt.admin.settings.sort_top'),
      },
      default: 'latest',
    })
    .registerSetting({
      setting: 'llms_txt.max_discussions',
      label: app.translator.trans('quasimo-llms-txt.admin.settings.max_discussions_label'),
      help: app.translator.trans('quasimo-llms-txt.admin.settings.max_discussions_help'),
      type: 'number',
      min: 1,
      max: 1000,
    })
    .registerSetting({
      setting: 'llms_txt.max_posts_per_discussion',
      label: app.translator.trans('quasimo-llms-txt.admin.settings.max_posts_label'),
      help: app.translator.trans('quasimo-llms-txt.admin.settings.max_posts_help'),
      type: 'number',
      min: 1,
      max: 500,
    })
    .registerSetting({
      setting: 'llms_txt.custom_intro',
      label: app.translator.trans('quasimo-llms-txt.admin.settings.custom_intro_label'),
      help: app.translator.trans('quasimo-llms-txt.admin.settings.custom_intro_help'),
      type: 'text',
      placeholder: app.translator.trans('quasimo-llms-txt.admin.settings.custom_intro_placeholder'),
    });
});
