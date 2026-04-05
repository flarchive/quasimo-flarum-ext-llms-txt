import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import Alert from 'flarum/common/components/Alert';
import Button from 'flarum/common/components/Button';
import Switch from 'flarum/common/components/Switch';
import Select from 'flarum/common/components/Select';

export default class LlmsTxtSettingsPage extends ExtensionPage {
  oninit(vnode) {
    super.oninit(vnode);
    this.saving = false;
  }

  content() {
    const t = (key, params) => app.translator.trans(`quasimo-llms-txt.admin.settings.${key}`, params);
    const baseUrl = app.forum.attribute('baseUrl') || window.location.origin;

    return (
      <div className="LlmsTxtSettingsPage">
        {/* ---- URL Info ---- */}
        <div className="Form-group">
          <label>{t('urls_label')}</label>
          <div className="helpText">{t('urls_help')}</div>
          <div className="LlmsTxtSettingsPage-urls">
            <div className="LlmsTxtSettingsPage-url">
              <code>{baseUrl}/llms.txt</code>
              <a
                href={`${baseUrl}/llms.txt`}
                target="_blank"
                rel="noopener noreferrer"
                className="Button Button--icon"
                title={t('open_label')}
              >
                <i className="fas fa-external-link-alt" />
              </a>
            </div>
            <div className="LlmsTxtSettingsPage-url">
              <code>{baseUrl}/llms-full.txt</code>
              <a
                href={`${baseUrl}/llms-full.txt`}
                target="_blank"
                rel="noopener noreferrer"
                className="Button Button--icon"
                title={t('open_label')}
              >
                <i className="fas fa-external-link-alt" />
              </a>
            </div>
          </div>
        </div>

        <hr />

        {/* ---- Enable llms.txt ---- */}
        <div className="Form-group">
          <Switch
            state={this.setting('llms_txt.enabled', '1')() === '1'}
            onchange={(value) => this.setting('llms_txt.enabled')( value ? '1' : '0')}
          >
            {t('enabled_label')}
          </Switch>
          <div className="helpText">{t('enabled_help', { url: `${baseUrl}/llms.txt` })}</div>
        </div>

        {/* ---- Enable llms-full.txt ---- */}
        <div className="Form-group">
          <Switch
            state={this.setting('llms_txt.full_enabled', '1')() === '1'}
            onchange={(value) => this.setting('llms_txt.full_enabled')(value ? '1' : '0')}
          >
            {t('full_enabled_label')}
          </Switch>
          <div className="helpText">{t('full_enabled_help', { url: `${baseUrl}/llms-full.txt` })}</div>
        </div>

        <hr />

        {/* ---- Sort order ---- */}
        <div className="Form-group">
          <label>{t('sort_label')}</label>
          <Select
            value={this.setting('llms_txt.sort', 'latest')()}
            onchange={this.setting('llms_txt.sort')}
            options={{
              latest: t('sort_latest'),
              top: t('sort_top'),
            }}
          />
          <div className="helpText">{t('sort_help')}</div>
        </div>

        {/* ---- Max discussions ---- */}
        <div className="Form-group">
          <label>{t('max_discussions_label')}</label>
          <input
            className="FormControl"
            type="number"
            min="1"
            max="1000"
            bidi={this.setting('llms_txt.max_discussions', '100')}
          />
          <div className="helpText">{t('max_discussions_help')}</div>
        </div>

        {/* ---- Max posts per discussion (full only) ---- */}
        <div className="Form-group">
          <label>{t('max_posts_label')}</label>
          <input
            className="FormControl"
            type="number"
            min="1"
            max="500"
            bidi={this.setting('llms_txt.max_posts_per_discussion', '50')}
          />
          <div className="helpText">{t('max_posts_help')}</div>
        </div>

        {/* ---- Custom introduction text ---- */}
        <div className="Form-group">
          <label>{t('custom_intro_label')}</label>
          <textarea
            className="FormControl"
            rows="4"
            placeholder={t('custom_intro_placeholder')}
            bidi={this.setting('llms_txt.custom_intro', '')}
          />
          <div className="helpText">{t('custom_intro_help')}</div>
        </div>

        {/* ---- Save ---- */}
        <div className="Form-group">
          {this.submitButton()}
        </div>
      </div>
    );
  }
}
