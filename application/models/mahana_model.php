<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Mahana_model extends CI_Model
{

    function send_new_message($sender_id, $recipients, $subject, $body, $priority){
    
            $this->db->trans_start();

            $thread_id = $this->_insert_thread($subject);
            if ($this->db->trans_status() === false)
            {
                $this->db->trans_rollback();
                return false;
            }

            $msg_id = $this->_insert_message($thread_id, $sender_id,  $body, $priority);
            if ($this->db->trans_status() === false)
            {
                $this->db->trans_rollback();
                return false;
            }

            //create batch inserts
            $participants[] = array('thread_id'=>$thread_id,'user_id'=> $sender_id);
            $statuses[]     = array('message_id'=>$msg_id, 'user_id'=> $sender_id,'status'=> MSG_STATUS_READ);

            if (!is_array($recipients))
            {
                $participants[] = array('thread_id'=>$thread_id,'user_id'=>$recipients);
                $statuses[]     = array('message_id'=>$msg_id, 'user_id'=>$recipients, 'status'=>MSG_STATUS_UNREAD);
            }
            else
            {
                foreach ($recipients as $recipient)
                {
                    $participants[] = array('thread_id'=>$thread_id,'user_id'=>$recipient);
                    $statuses[]     = array('message_id'=>$msg_id, 'user_id'=>$recipient, 'status'=>MSG_STATUS_UNREAD);
                }
            }
            $this->_insert_participants($participants);
            if ($this->db->trans_status() === false)
            {
                $this->db->trans_rollback();
                return false;
            }

            $this->_insert_statuses($statuses);
            if ($this->db->trans_status() === false)
            {
                $this->db->trans_rollback();
                return false;
            }

            $this->db->trans_complete();
            return true;
    }

    //reply to message
    function reply_to_message($reply_msg_id, $sender_id, $body, $priority)
    {
            $this->db->trans_start();

            //get the thread id to keep messages together
            if (!($thread_id = $this->_get_thread_id_from_message($reply_msg_id)))
            {
                return false;
            }

            //add this message
            $msg_id = $this->_insert_message($thread_id, $sender_id, $body, $priority);
            if ($this->db->trans_status() === false)
            {
                $this->db->trans_rollback();
                return false;
            }

            if ($recipients = $this->_get_thread_participants($thread_id, $sender_id))
            {
                $statuses[]     = array('message_id'=>$msg_id, 'user_id'=> $sender_id,'status'=> MSG_STATUS_READ);
                foreach ($recipients as $recipient)
                {
                    $statuses[]     = array('message_id'=>$msg_id, 'user_id'=>$recipient['user_id'], 'status'=>MSG_STATUS_UNREAD);
                }
                $this->_insert_statuses($statuses);
                if ($this->db->trans_status() === false)
                {
                    $this->db->trans_rollback();
                    return false;
                }
            }

            $this->db->trans_complete();
            return true;    
    }

    //get a single message
    function get_message($msg_id, $user_id)
    {
           $sql = 'SELECT m.*, s.status, t.subject, '.USER_TABLE_USERNAME .
                ' FROM msg_messages m ' .
                ' JOIN msg_threads t ON (m.thread_id = t.id) ' .
                ' JOIN ' .USER_TABLE_TABLENAME. ' ON ('.USER_TABLE_ID.' = m.sender_id) '.
                ' JOIN msg_status s ON (s.message_id = m.id AND s.user_id = ? ) ' .
                ' WHERE m.id = ? ' ;

            $query = $this->db->query($sql, array($user_id, $msg_id)); 
            return $query->result_array();
    }

    //get full thread
    function get_full_thread($thread_id, $user_id, $full_thread = false, $order_by='asc'){
            $sql = 'SELECT m.*, s.status, t.subject, '.USER_TABLE_USERNAME .
                ' FROM msg_participants p ' .
                ' JOIN msg_threads t ON (t.id = p.thread_id) ' .
                ' JOIN msg_messages m ON (m.thread_id = t.id) ' .
                ' JOIN ' .USER_TABLE_TABLENAME. ' ON ('.USER_TABLE_ID.' = m.sender_id) '.
                ' JOIN msg_status s ON (s.message_id = m.id AND s.user_id = ? ) ' .
                ' WHERE p.user_id = ? ' .
                ' AND p.thread_id = ? ';
            if (!$full_thread)
            {
                $sql .= ' AND m.cdate >= p.cdate';
            }
            $sql .= ' ORDER BY m.cdate '.$order_by;

            $query = $this->db->query($sql, array($user_id, $user_id, $thread_id)); //echo $this->db->last_query();
            return $query->result_array();
    }

    //get all threads
    function get_all_threads($user_id, $full_thread = false, $order_by='asc'){
            $sql = 'SELECT m.*, s.status, t.subject, '.USER_TABLE_USERNAME .
                ' FROM msg_participants p ' .
                ' JOIN msg_threads t ON (t.id = p.thread_id) ' .
                ' JOIN msg_messages m ON (m.thread_id = t.id) ' .
                ' JOIN ' .USER_TABLE_TABLENAME. ' ON ('.USER_TABLE_ID.' = m.sender_id) '.
                ' JOIN msg_status s ON (s.message_id = m.id AND s.user_id = ? ) ' .
                ' WHERE p.user_id = ? ' ;
            if (!$full_thread)
            {
                $sql .= ' AND m.cdate >= p.cdate';
            }
            $sql .= ' ORDER BY t.id '.$order_by. ', m.cdate '.$order_by;

            $query = $this->db->query($sql, array($user_id, $user_id));
            return $query->result_array();
    }


    //change message status
    function update_message_status($msg_id, $user_id, $status_id  )
    {
            $this->db->where(array('message_id'=>$msg_id, 'user_id'=>$user_id ));
            $this->db->update('msg_status', array('status'=>$status_id ));
            return $this->db->affected_rows();
    }


    //add participant
    function add_participant($thread_id, $user_id)
    {
            $this->db->trans_start();

            $participants[] = array('thread_id'=>$thread_id,'user_id'=>$user_id);
            $this->_insert_participants($participants);
            if ($this->db->trans_status() === false)
            {
                $this->db->trans_rollback();
                return false;
            }

            //get messages by thread
            $messages = $this->_get_messages_by_thread_id($thread_id);

            foreach ($messages as $message)
            {
                $statuses[]     = array('message_id'=>$message['id'], 'user_id'=>$user_id, 'status'=>MSG_STATUS_UNREAD);
            }
           
            $this->_insert_statuses($statuses);
            if ($this->db->trans_status() === false)
            {
                $this->db->trans_rollback();
                return false;
            }

            $this->db->trans_complete();
            return true; 
    }

    function remove_participant($thread_id, $user_id)
    {
            $this->db->trans_start();

            $return = $this->_delete_participant($thread_id, $user_id);
            if (($return === false)  || $this->db->trans_status() === false)
            {
                $this->db->trans_rollback();
                return false;
            }

            $this->_delete_statuses($thread_id, $user_id);
            if ($this->db->trans_status() === false)
            {
                $this->db->trans_rollback();
                return false;
            }

            $this->db->trans_complete();
            return true; 
    }

    // because of CodeIgniter's DB Class return style, it is safer to check for uniqueness first 
    function valid_new_participant($thread_id, $user_id)
    {
            $sql = 'SELECT COUNT(*) AS count ' .
                ' FROM msg_participants p ' .
                ' WHERE p.thread_id = ? ' .
                ' AND p.user_id = ? ';
            $query = $this->db->query($sql, array($thread_id, $user_id));
            if ($query->row()->count)
            {
                return false;
            }
            return true;
    }

    function application_user($user_id)
    {
            $sql = 'SELECT COUNT(*) AS count ' .
                ' FROM ' . USER_TABLE_TABLENAME .
                ' WHERE ' . USER_TABLE_ID . ' = ?' ;
            $query = $this->db->query($sql, array($user_id));
            if ($query->row()->count)
            {
                return true;
            }
            return false;
     }

    function get_participant_list($thread_id, $sender_id =0)
    {
        if ($results = $this->_get_thread_participants($thread_id, $sender_id)) {
            return $results;
        }
        return false;
    }


    //                                              
    //***** private functions *****//
    //

    private function _insert_thread($subject)
    {
            $insert_id = $this->db->insert('msg_threads', array('subject'=>$subject));
            return $this->db->insert_id();
    }

    private function _insert_message($thread_id, $sender_id, $body, $priority)
    {
            $insert['thread_id'] = $thread_id;
            $insert['sender_id'] = $sender_id;
            $insert['body']      = $body;
            $insert['priority']  = $priority;

            $insert_id = $this->db->insert('msg_messages', $insert);
            return $this->db->insert_id();
    }

    private function _insert_participants($participants)
    {
            return $this->db->insert_batch('msg_participants', $participants);
    }

    private function _insert_statuses($statuses)
    {
            return $this->db->insert_batch('msg_status', $statuses);
    }

    private function _get_thread_id_from_message($msg_id){
            $query = $this->db->select('thread_id')->get_where('msg_messages', array('id' => $msg_id));
            if ($query->num_rows()){
                return $query->row()->thread_id;
            }
            return 0;
    }

    private function _get_messages_by_thread_id($thread_id)
    {
            $query = $this->db->get_where('msg_messages', array('thread_id' => $thread_id));  
            return $query->result_array();
    }


    private function _get_thread_participants($thread_id, $sender_id=0) 
    {
            $array['thread_id'] = $thread_id;
            if ($sender_id)  //if $sender_id  0, no one to exclude 
            {
                $array['user_id != '] = $sender_id;
            }
            
            $this->db->select('user_id, '.USER_TABLE_USERNAME, false);            
            $this->db->join(USER_TABLE_TABLENAME,'msg_participants.user_id ='.USER_TABLE_ID);
            $query = $this->db->get_where('msg_participants', $array);
            
            return $query->result_array();
    }

    private function _delete_participant($thread_id, $user_id)
    {
            $this->db->delete('msg_participants', array('thread_id'=>$thread_id, 'user_id'=>$user_id));
            if ($this->db->affected_rows() > 0)
            {    
                return true;
            }
            return false;
    }

    private function _delete_statuses($thread_id, $user_id)
    {
            $sql = 'DELETE s FROM msg_status s ' .
                   ' JOIN msg_messages m ON (m.id = s.message_id) ' .
                   ' WHERE m.thread_id = ?  ' .
                   ' AND s.user_id = ? ';
            $query = $this->db->query($sql, array($thread_id, $user_id));
            return true;
    }
}

/* end of file mahana_model.php */