<?php
namespace TrevorBice\Component\Mothership\Site\View\Projects;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
    protected $projects;

    public function display($tpl = null)
    {
        // Data is scoped to the user's own client(s) in the model, so no global
        // ACL gate is needed here.
        $this->projects = $this->getModel()->getItems();
        parent::display($tpl);
    }
}