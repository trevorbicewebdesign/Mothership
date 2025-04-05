<?php

namespace TrevorBice\Component\Mothership\Administrator\Field;

use Joomla\CMS\Form\Field\ListField;
use TrevorBice\Component\Mothership\Administrator\Helper\DomainHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

\defined('_JEXEC') or die;

class RegistrarField extends ListField
{
    protected $type = 'Registrar';

    public function getOptions()
    {
        $options = [
            HTMLHelper::_('select.option', 'godaddy', 'GoDaddy'),
            HTMLHelper::_('select.option', 'namecheap', 'Namecheap'),
            HTMLHelper::_('select.option', 'bluehost', 'Bluehost'),
            HTMLHelper::_('select.option', 'hostgator', 'HostGator'),
            HTMLHelper::_('select.option', 'google_domains', 'Google Domains'),
            HTMLHelper::_('select.option', 'network_solutions', 'Network Solutions'),
            HTMLHelper::_('select.option', 'ionos', 'IONOS'),
            HTMLHelper::_('select.option', 'hover', 'Hover'),
            HTMLHelper::_('select.option', 'dreamhost', 'DreamHost'),
            HTMLHelper::_('select.option', 'dynadot', 'Dynadot'),
            HTMLHelper::_('select.option', 'name_silo', 'NameSilo'),
            HTMLHelper::_('select.option', 'porkbun', 'Porkbun'),
            HTMLHelper::_('select.option', 'cloudflare', 'Cloudflare'),
            HTMLHelper::_('select.option', 'register_com', 'Register.com'),
            HTMLHelper::_('select.option', 'enom', 'eNom'),
            HTMLHelper::_('select.option', 'tucows', 'Tucows'),
            HTMLHelper::_('select.option', 'gandi', 'Gandi'),
            HTMLHelper::_('select.option', 'scalahosting', 'ScalaHosting'),
            HTMLHelper::_('select.option', 'bigrock', 'BigRock'),
            HTMLHelper::_('select.option', 'resellerclub', 'ResellerClub'),
        ];

        array_unshift($options, HTMLHelper::_('select.option', '', Text::_('COM_MOTHERSHIP_SELECT_DOMAIN_REGISTRAR')));
        return array_merge(parent::getOptions(), is_array($options) ? $options : []);
    }
}
