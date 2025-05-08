<?php
namespace TrevorBice\Component\Mothership\Administrator\Rule;

use Joomla\CMS\Form\FormRule; 

class CustomRule extends FormRule
{
    protected $regex = '^[0-9\*\#]+$';
}