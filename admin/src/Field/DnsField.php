<?php

namespace TrevorBice\Component\Mothership\Administrator\Field;

use Joomla\CMS\Form\Field\ListField;
use TrevorBice\Component\Mothership\Administrator\Helper\DomainHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

\defined('_JEXEC') or die;

class DnsField extends ListField
{
    protected $type = 'Dns';

    public function getOptions()
    {
        $options = [
            HTMLHelper::_('select.option', 'cloudflare', 'Cloudflare'),
            HTMLHelper::_('select.option', 'godaddy', 'GoDaddy'),
            HTMLHelper::_('select.option', 'namecheap', 'Namecheap'),
            HTMLHelper::_('select.option', 'google_domains', 'Google Domains'),
            HTMLHelper::_('select.option', 'aws_route53', 'AWS Route 53'),
            HTMLHelper::_('select.option', 'azure_dns', 'Azure DNS'),
            HTMLHelper::_('select.option', 'dyn', 'Dyn'),
            HTMLHelper::_('select.option', 'hover', 'Hover'),
            HTMLHelper::_('select.option', 'bluehost', 'Bluehost'),
            HTMLHelper::_('select.option', 'hostgator', 'HostGator'),
            HTMLHelper::_('select.option', 'dreamhost', 'DreamHost'),
            HTMLHelper::_('select.option', '1and1', '1&1 IONOS'),
            HTMLHelper::_('select.option', 'rackspace', 'Rackspace'),
            HTMLHelper::_('select.option', 'digitalocean', 'DigitalOcean'),
            HTMLHelper::_('select.option', 'linode', 'Linode'),
            HTMLHelper::_('select.option', 'vultr', 'Vultr'),
            HTMLHelper::_('select.option', 'porkbun', 'Porkbun'),
            HTMLHelper::_('select.option', 'gandi', 'Gandi'),
            HTMLHelper::_('select.option', 'cloudns', 'ClouDNS'),
            HTMLHelper::_('select.option', 'freedns', 'FreeDNS'),
        ];

        array_unshift($options, HTMLHelper::_('select.option', '', Text::_('COM_MOTHERSHIP_SELECT_DOMAIN_DNS')));
        return array_merge(parent::getOptions(), is_array($options) ? $options : []);
    }
}
