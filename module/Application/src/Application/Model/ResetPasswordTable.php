<?php

/**
 * @copyright  2015 (c) Stanimir Dimitrov.
 * @license    http://www.opensource.org/licenses/mit-license.php  MIT License
 *
 * @version    0.0.17
 *
 * @link       TBA
 */

namespace Application\Model;

use Admin\Model\AbstractModelTable;
use Application\Exception\RuntimeException;

final class ResetPasswordTable extends AbstractModelTable
{
    /**
     * @method __construct
     *
     * @param \Zend\Db\Adapter\Adapter $adapter
     */
    public function __construct($adapter)
    {
        parent::__construct('resetpassword', 'ResetPassword', $adapter);
    }

    /**
     * This method returns a single row which verifies that this is the user that needs to reset his password.
     *
     * @param int $id password id
     * @param int $id user id
     *
     * @throws RuntimeException If row is not found
     *
     * @return ResetPassword
     */
    public function getResetPassword($id = 0, $user = 0)
    {
        $rowset = $this->tableGateway->select(['id' => (int) $id, 'user' => (int) $user]);
        if (!$rowset->current()) {
            throw new RuntimeException("Couldn't find row");
        }
        return $rowset->current();
    }

    /**
     * Save or update password based on the provided id.
     *
     * @param  ResetPassword $resetpassword
     *
     * @return ResetPassword
     */
    public function saveResetPassword(ResetPassword $resetpw)
    {
        $data = [
            'ip'    => (string) $resetpw->getIp(),
            'user'  => (int) $resetpw->getUser(),
            'date'  => (string) $resetpw->getDate(),
            'token' => (string) $resetpw->getToken(),
        ];
        $id = (int) $resetpw->getId();
        $user = (int) $resetpw->getUser();
        if (!$id) {
            $this->tableGateway->insert($data);
        } else {
            if ($this->getResetPassword($id, $user)) {
                $this->tableGateway->update($data, ['id' => (int) $id, 'user' => (int) $user]);
            }
        }
        unset($id, $user, $data);
        return $resetpw;
    }
}
