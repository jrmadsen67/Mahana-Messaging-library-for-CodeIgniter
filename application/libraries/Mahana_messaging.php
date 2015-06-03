<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
* Name:        Mahana Messaging Library for CodeIgniter
*
* Author:      Jeff Madsen
*              jrmadsen67@gmail.com
*              http://www.codebyjeff.com
*
* Location:    will be on github shortly
*
* Description: CI library for linking to application's existing user table and
*              creating basis of an internal messaging system. No views or controllers
*              included.
*
*              DO CHECK the README.txt for setup instructions and notes!
*
*/

class Mahana_messaging
{
    public function __construct()
    {
        $this->ci =& get_instance();
        // ------------------------------------------------------------------------
        // @TODO: There must be a better way than this to specify a file
        // path that works in both standard CodeIgniter and HMVC modules.
        // ------------------------------------------------------------------------
        require_once dirname(__FILE__).'/../config/mahana.php';

        $this->ci->load->model('mahana_model');
        $this->ci->load->helper('language');
        $this->ci->lang->load('mahana');
    }

    // ------------------------------------------------------------------------

    /**
     * get_message() - will return a single message, including the status for specified user.
     *
     * @param   integer  $msg_id   EQUIRED
     * @param   integer  $user_id  REQUIRED
     * @return  array
     */
    function get_message($msg_id, $user_id)
    {
        if (empty($msg_id))
        {
            return $this->_invalid_id(MSG_ERR_INVALID_MSG_ID);
        }

        if (empty($user_id))
        {
            return $this->_invalid_id(MSG_ERR_INVALID_USER_ID);
        }

        if ($message = $this->ci->mahana_model->get_message($msg_id, $user_id))
        {
            return $this->_success($message);
        }

        // General Error Occurred
        return $this->_general_error();
    }

    // ------------------------------------------------------------------------

    /**
     * get_full_thread() - will return a entire thread, including the status for specified user.
     *
     * @param   integer  $thread_id    REQUIRED
     * @param   integer  $user_id      REQUIRED
     * @param   boolean  $full_thread  OPTIONAL - If true, user will also see messages from thread posted BEFORE user became participant
     * @param   string   $order_by     OPTIONAL
     * @return  array
     */
    function get_full_thread($thread_id, $user_id, $full_thread = FALSE, $order_by = 'ASC')
    {
        if (empty($thread_id))
        {
            return $this->_invalid_id(MSG_ERR_INVALID_THREAD_ID);
        }

        if (empty($user_id))
        {
            return $this->_invalid_id(MSG_ERR_INVALID_USER_ID);
        }

        if ($message = $this->ci->mahana_model->get_full_thread($thread_id, $user_id, $full_thread, $order_by))
        {
            return $this->_success($message);
        }

        // General Error Occurred
        return $this->_general_error();
    }

    // ------------------------------------------------------------------------

    /**
     * get_all_threads() - will return all threads for user, including the status for specified user.
     *
     * @param   integer  $user_id      REQUIRED
     * @param   boolean  $full_thread  OPTIONAL - If true, user will also see messages from thread posted BEFORE user became participant
     * @param   string   $order_by     OPTIONAL
     * @return  array
     */
    function get_all_threads($user_id, $full_thread = FALSE, $order_by = 'ASC')
    {
        if (empty($user_id))
        {
            return $this->_invalid_id(MSG_ERR_INVALID_USER_ID);
        }

		$message = $this->ci->mahana_model->get_all_threads($user_id, $full_thread, $order_by);
        if (is_array($message))
        {
            return $this->_success($message);
        }

        // General Error Occurred
        return $this->_general_error();
    }

    // ------------------------------------------------------------------------

    /**
     * get_all_threads_grouped() - will return all threads for user, including the status for specified user.
     *                           - messages are grouped in threads.
     *
     * @param   integer  $user_id      REQUIRED
     * @param   boolean  $full_thread  OPTIONAL - If true, user will also see messages from thread posted BEFORE user became participant
     * @param   string   $order_by     OPTIONAL
     * @return  array
     */
    function get_all_threads_grouped($user_id, $full_thread = FALSE, $order_by = 'ASC')
    {
        if (empty($user_id))
        {
            return $this->_invalid_id(MSG_ERR_INVALID_USER_ID);
        }

		$message = $this->ci->mahana_model->get_all_threads($user_id, $full_thread, $order_by);
        if (is_array($message))
        {
            $threads = array();

            foreach ($message as $msg)
            {
                if ( ! isset($threads[$msg['thread_id']]))
                {
                    $threads[$msg['thread_id']]['thread_id'] = $msg['thread_id'];
                    $threads[$msg['thread_id']]['messages']  = array($msg);
                }
                else
                {
                    $threads[$msg['thread_id']]['messages'][] = $msg;
                }
            }

            return $this->_success($threads);
        }

        // General Error Occurred
        return $this->_general_error();
    }

    // ------------------------------------------------------------------------

    /**
     * update_message_status() - will change status on message for particular user
     *
     * @param   integer  $msg_id     REQUIRED
     * @param   integer  $user_id    REQUIRED
     * @param   integer  $status_id  REQUIRED - should come from config/mahana.php list of constants
     * @return  array
     */
    function update_message_status($msg_id, $user_id, $status_id )
    {
        if (empty($msg_id))
        {
            return $this->_invalid_id(MSG_ERR_INVALID_MSG_ID);
        }

        if (empty($user_id))
        {
            return $this->_invalid_id(MSG_ERR_INVALID_USER_ID);
        }

        if (empty($status_id))
        {
            return $this->_invalid_id(MSG_ERR_INVALID_STATUS_ID);
        }

        if ($this->ci->mahana_model->update_message_status($msg_id, $user_id, $status_id))
        {
            return $this->_success(NULL, MSG_STATUS_UPDATE);
        }

        // General Error Occurred
        return $this->_general_error();

    }

    // ------------------------------------------------------------------------

    /**
     * add_participant() - adds user to existing thread
     *
     * @param   integer  $thread_id  REQUIRED
     * @param   integer  $user_id    REQUIRED
     * @return  array
     */
    function add_participant($thread_id, $user_id)
    {
        if (empty($thread_id))
        {
            return $this->_invalid_id(MSG_ERR_INVALID_THREAD_ID);
        }

        if (empty($user_id))
        {
            return $this->_invalid_id(MSG_ERR_INVALID_USER_ID);
        }

        if ( ! $this->ci->mahana_model->valid_new_participant($thread_id, $user_id))
        {
            $this->_particpant_error(MSG_ERR_PARTICIPANT_EXISTS);
        }

        if ( ! $this->ci->mahana_model->application_user($user_id))
        {
            $this->_particpant_error(MSG_ERR_PARTICIPANT_NONSYSTEM);
        }

        if ($this->ci->mahana_model->add_participant($thread_id, $user_id ))
        {
            return $this->_success(NULL, MSG_PARTICIPANT_ADDED);
        }

        // General Error Occurred
        return $this->_general_error();
    }

    // ------------------------------------------------------------------------

    /**
     * remove_participant() - removes user from existing thread
     *
     * @param   integer  $thread_id  REQUIRED
     * @param   integer  $user_id    REQUIRED
     * @return  array
     */
    function remove_participant($thread_id, $user_id)
    {
        if (empty($thread_id))
        {
            return $this->_invalid_id(MSG_ERR_INVALID_THREAD_ID);
        }

        if (empty($user_id))
        {
            return $this->_invalid_id(MSG_ERR_INVALID_USER_ID);
        }

        if ($this->ci->mahana_model->remove_participant($thread_id, $user_id))
        {
            return $this->_success(NULL, MSG_PARTICIPANT_REMOVED);
        }

        // General Error Occurred
        return $this->_general_error();
    }

    // ------------------------------------------------------------------------

    /**
     * send_new_message() - sends new internal message. This function will create a new thread
     *
     * @param   integer  $sender_id   REQUIRED
     * @param   mixed    $recipients  REQUIRED - a single integer or an array of integers, representing user_ids
     * @param   string   $subject
     * @param   string   $body
     * @param   integer  $priority
     * @return  array
     */
    function send_new_message($sender_id, $recipients, $subject = '', $body = '', $priority = PRIORITY_NORMAL)
    {
        if (empty($sender_id))
        {
            return $this->_invalid_id(MSG_ERR_INVALID_SENDER_ID);
        }

        if (empty($recipients))
        {
            return array(
                'err'  => 1,
                'code' => MSG_ERR_INVALID_RECIPIENTS,
                'msg'  => lang('mahana_'.MSG_ERR_INVALID_RECIPIENTS)
            );
        }

        if ($thread_id = $this->ci->mahana_model->send_new_message($sender_id, $recipients, $subject, $body, $priority))
        {
            return $this->_success($thread_id, MSG_MESSAGE_SENT);
        }

        // General Error Occurred
        return $this->_general_error();
    }

    // ------------------------------------------------------------------------

    /**
     * reply_to_message() - replies to internal message. This function will NOT create a new thread or participant list
     *
     * @param   integer  $msg_id     REQUIRED
     * @param   integer  $sender_id  REQUIRED
     * @param   string   $subject
     * @param   string   $body
     * @param   integer  $priority
     * @return  array
     */
    function reply_to_message($msg_id, $sender_id, $subject = '', $body = '', $priority = PRIORITY_NORMAL)
    {
        if (empty($sender_id))
        {
            return $this->_invalid_id(MSG_ERR_INVALID_SENDER_ID);
        }

        if (empty($msg_id))
        {
            return $this->_invalid_id(MSG_ERR_INVALID_MSG_ID);
        }

        if ($new_msg_id = $this->ci->mahana_model->reply_to_message($msg_id, $sender_id, $body, $priority))
        {
            return $this->_success($new_msg_id, MSG_MESSAGE_SENT);
        }

        // General Error Occurred
        return $this->_general_error();
    }

    // ------------------------------------------------------------------------

    /**
     * get_participant_list() - returns list of participants on given thread. If sender_id set, sender_id will be left off list
     *
     * @param   integer  $thread_id  REQUIRED
     * @param   integer  $sender_id  REQUIRED
     * @return  array
     */
    function get_participant_list($thread_id, $sender_id = 0)
    {
        if (empty($thread_id))
        {
            return $this->_invalid_id(MSG_ERR_INVALID_THREAD_ID);
        }

        if ($participants = $this->ci->mahana_model-> get_participant_list($thread_id, $sender_id))
        {
            return $this->_success($participants);
        }

        // General Error Occurred
        return $this->_general_error();
    }

    // ------------------------------------------------------------------------

    /**
     * get_msg_count() - returns integer with count of message for user, by status. defaults to new messages
     *
     * @param   integer  $user_id    REQUIRED
     * @param   integer  $status_id  OPTIONAL - defaults to "Unread"
     * @return  array
     */
    function get_msg_count($user_id, $status_id = MSG_STATUS_UNREAD)
    {
        if (empty($user_id))
        {
            return $this->_invalid_id(MSG_ERR_INVALID_USER_ID);
        }

        if (is_numeric($message = $this->ci->mahana_model->get_msg_count($user_id, $status_id)))
        {
            return $this->_success($message);
        }

        // General Error Occurred
        return $this->_general_error();
    }

    // ------------------------------------------------------------------------
    // Private Functions from here out!
    // ------------------------------------------------------------------------

    /**
     * Success
     *
     * @param   mixed  $retval
     * @return  array
     */
    private function _success($retval = '', $message = MSG_SUCCESS)
    {
        return array(
            'err'    => 0,
            'code'   => MSG_SUCCESS,
            'msg'    => lang('mahana_' . $message),
            'retval' => $retval
        );
    }

    // ------------------------------------------------------------------------

    /**
     * Invalid ID
     *
     * @param   integer  config.php error code numbers
     * @return  array
     */
    private function _invalid_id($error = '')
    {
        return array(
            'err'  => 1,
            'code' => $error,
            'msg'  => lang('mahana_'.$error)
        );
    }

    // ------------------------------------------------------------------------

    /**
     * Error Particpant Exists
     *
     * @return  array
     */
    private function _participant_error($error = '')
    {
        return array(
            'err'  => 1,
            'code' => 1,
            'msg'  => lang('mahana_' . $error)
        );
    }


    // ------------------------------------------------------------------------

    /**
     * General Error
     *
     * @return  array
     */
    private function _general_error()
    {
        return array(
            'err'  => 1,
            'code' => MSG_ERR_GENERAL,
            'msg'  => lang('mahana_'.MSG_ERR_GENERAL)
        );
    }
}
