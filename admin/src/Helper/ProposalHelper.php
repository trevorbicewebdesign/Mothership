<?php
/**
 * Proposal Helper for Mothership Proposal Plugins
 *
 * Provides methods to update an proposal record, insert payment data, 
 * and allocate the payment to the corresponding proposal.
 *
 * @package     Mothership
 * @subpackage  Helper
 * @copyright   (C) 2025 Trevor Bice
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;
use TrevorBice\Component\Mothership\Administrator\Service\EmailService;
use TrevorBice\Component\Mothership\Administrator\Helper\LogHelper; 

class ProposalHelper
{
    /**
     * Returns the proposal status as a string based on the provided status ID.
     *
     * @param int $status_id The status ID of the proposal.
     *                      1 = Draft
     *                      2 = Opened
     *                      3 = Cancelled
     *                      4 = Closed
     * @return string The corresponding status as a string. Returns 'Unknown' if the status ID does not match any known status.
     */
    public static function getStatus($status_id)
    {
        // Transform the status from integer to string
        switch ($status_id) {
            case 1:
                $status = 'Draft';
                break;
            case 2:
                $status = 'Pending';
                break;
            case 3:
                $status = 'Approved';
                break;
            case 4:
                $status = 'Declined';
                break;
            case 5:
                $status = 'Cancelled';
                break;
            case 5:
                $status = 'Expired';
                break;
            
            default:
                $status = 'Unknown';
                break;
        }

        return $status;
    }


    public static function setProposalClosed($proposalId)
    {
        self::updateProposalStatus($proposalId, 4);
    }
    

    /**
     * Updates the status of an proposal in the database.
     *
     * @param int $proposalId The ID of the proposal to update.
     * @param int $status The new status to set for the proposal.
     * 
     * @return bool Returns true if the update was successful, false otherwise.
     * 
     * @throws \Exception If there is an error during the database operation.
     * 
     * Logs an error message if the update fails.
     */
    public static function updateProposalStatus($proposal, $status): bool
    {
        $paidDate = null;

        try {
            $proposal = self::getProposal($proposal->id);
        } catch (\RuntimeException $e) {
            throw new \RuntimeException($e->getMessage());
        }

        switch ($status) {
            case 1: // Draft
            case 2: // Opened
            case 3: // Cancelled
                break;
            case 4: // Closed
                $paidDate = date('Y-m-d H:i:s');
                break;
            default:
                throw new \InvalidArgumentException("Invalid status: $status");
        }

        $db = Factory::getContainer()->get(DatabaseDriver::class);
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__mothership_proposals'))
            ->set($db->quoteName('status') . ' = ' . (int) $status);

        if ($paidDate !== null) {
            $query->set($db->quoteName('paid_date') . ' = ' . $db->quote($paidDate));
        }

        $query->where($db->quoteName('id') . ' = ' . (int) $proposal->id);

        $db->transactionStart();

        try {
            $db->setQuery($query)->execute();

            // Update object & run hooks
            $proposal->status = $status;

            if ($status === 4) {
                self::onProposalClosed($proposal, $status);
            } elseif ($status === 2) {
                self::onProposalOpened($proposal, $status);
            }

            $db->transactionCommit();
        } catch (\Exception $e) {
            $db->transactionRollback();
            throw $e;
        }

        return true;
    }


    /**
     * Retrieves an proposal object from the database by its ID.
     *
     * @param  int  $proposal_id  The ID of the proposal to retrieve.
     * @return object             The proposal object.
     * @throws \RuntimeException  If the proposal with the given ID is not found.
     */
    public static function getProposal($proposal_id)
    {
        $db = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__mothership_proposals'))
            ->where($db->quoteName('id') . ' = ' . $db->quote($proposal_id));

        $db->setQuery($query);
        $proposal = $db->loadObject();

        if (!$proposal) {
            throw new \RuntimeException("Proposal ID {$proposal_id} not found.");
        }

        return $proposal;
    }
}
