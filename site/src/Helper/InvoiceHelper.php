<?php
namespace TrevorBice\Component\Mothership\Site\Helper;

\defined('_JEXEC') or die;

class InvoiceHelper
{
    public static function getStatus($status_id)
    {
        $statuses = [
            1 => 'Draft',
            2 => 'Opened',
            3 => 'Canceled',
            4 => 'Closed',
        ];

        return $statuses[$status_id] ?? 'Unknown';
    }
}