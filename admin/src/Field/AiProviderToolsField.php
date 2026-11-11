<?php

namespace TrevorBice\Component\Mothership\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

\defined('_JEXEC') or die;

/**
 * Renders, on the component AI options page, the tools for the selected
 * provider: a deep link into that provider's connector plugin (where its key
 * and model live) and a "Test connection" button that runs a live check.
 *
 * Provider-agnostic: it resolves whatever provider is selected in ai_provider
 * and looks its connector plugin up in the mothership-ai group.
 */
class AiProviderToolsField extends FormField
{
    protected $type = 'AiProviderTools';

    protected function getInput()
    {
        // Saved provider selection (default claude). Changing the dropdown takes
        // effect here after a save, which is fine: there is one provider today.
        $provider = strtolower(
            (string) \Joomla\CMS\Component\ComponentHelper::getParams('com_mothership')->get('ai_provider', 'claude')
        );
        $providerLabel = ucfirst($provider);

        $plugin = $this->lookupConnector($provider);

        $id    = $this->id;
        $token = Session::getFormToken();
        $testUrl = 'index.php?option=com_mothership&task=ai.test&provider=' . urlencode($provider);

        $out = '<div class="ms-ai-tools" id="' . $id . '">';

        if (!$plugin) {
            // Connector not installed yet.
            $out .= '<div class="alert alert-warning" style="margin:0 0 12px;">'
                . htmlspecialchars(Text::sprintf('COM_MOTHERSHIP_OPTIONS_AI_TOOLS_MISSING', $providerLabel), ENT_QUOTES, 'UTF-8')
                . '</div>';
            $out .= '</div>';
            return $out;
        }

        // Deep link straight into the connector plugin's edit screen.
        $configureUrl = Route::_('index.php?option=com_plugins&task=plugin.edit&extension_id=' . (int) $plugin->extension_id);

        $out .= '<div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">';
        $out .= '<a class="btn btn-primary" href="' . $configureUrl . '">'
            . htmlspecialchars(Text::sprintf('COM_MOTHERSHIP_OPTIONS_AI_TOOLS_CONFIGURE', $providerLabel), ENT_QUOTES, 'UTF-8')
            . ' &rarr;</a>';
        $out .= '<button type="button" class="btn btn-secondary ms-ai-test-btn">'
            . htmlspecialchars(Text::_('COM_MOTHERSHIP_OPTIONS_AI_TOOLS_TEST'), ENT_QUOTES, 'UTF-8')
            . '</button>';
        $out .= '<span class="ms-ai-test-result" role="status" aria-live="polite" style="font-weight:600;"></span>';
        $out .= '</div>';

        if ((int) $plugin->enabled !== 1) {
            $out .= '<div class="alert alert-info" style="margin:12px 0 0;">'
                . htmlspecialchars(Text::_('COM_MOTHERSHIP_OPTIONS_AI_TOOLS_DISABLED'), ENT_QUOTES, 'UTF-8')
                . '</div>';
        }

        $out .= '</div>';

        $out .= $this->script($id, $testUrl, $token);

        return $out;
    }

    /**
     * Look up the connector plugin row for a provider in the mothership-ai group.
     */
    private function lookupConnector(string $provider)
    {
        $db    = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName(['extension_id', 'enabled', 'name']))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('mothership-ai'))
            ->where($db->quoteName('element') . ' = ' . $db->quote($provider));
        $db->setQuery($query);

        return $db->loadObject();
    }

    /**
     * Inline behaviour for the Test button. Scoped to this field's id.
     */
    private function script(string $id, string $testUrl, string $token): string
    {
        $testing = json_encode(Text::_('COM_MOTHERSHIP_OPTIONS_AI_TOOLS_TESTING'));
        $failed  = json_encode(Text::_('COM_MOTHERSHIP_OPTIONS_AI_TOOLS_FAILED'));
        $url     = json_encode($testUrl);
        $csrf    = json_encode($token);

        return <<<JS
<script>
(function () {
    var root = document.getElementById('$id');
    if (!root) { return; }
    var btn = root.querySelector('.ms-ai-test-btn');
    var out = root.querySelector('.ms-ai-test-result');
    if (!btn || !out) { return; }

    btn.addEventListener('click', function () {
        btn.disabled = true;
        out.style.color = '#6b7280';
        out.textContent = $testing;

        fetch($url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': $csrf },
            credentials: 'same-origin'
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data && data.ok) {
                out.style.color = '#2f7d32';
                var extra = [];
                if (data.model)      { extra.push(data.model); }
                if (data.latency_ms) { extra.push(data.latency_ms + ' ms'); }
                out.textContent = '✓ ' + data.message + (extra.length ? ' (' + extra.join(', ') + ')' : '');
            } else {
                out.style.color = '#c81e1e';
                out.textContent = '✗ ' + ((data && data.message) ? data.message : $failed);
            }
        })
        .catch(function () {
            out.style.color = '#c81e1e';
            out.textContent = '✗ ' + $failed;
        })
        .finally(function () { btn.disabled = false; });
    });
})();
</script>
JS;
    }
}
