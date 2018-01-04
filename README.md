




Name:  Mahana Messaging Library for CodeIgniter

Author: Jeff Madsen
        jrmadsen67@gmail.com
        http://www.codebyjeff.com

Location: - git@github.com:jrmadsen67/Mahana-Messaging-library-for-CodeIgniter.git

Description:  CI library for linking to application's existing user table and creating basis of an internal messaging system
           No views or controllers included - DO CHECK the README for setup instructions and notes


Welcome to the Mahana Messaging Library for CodeIgniter

This library is intended as a starting off point for building an internal messaging system for your CodeIgniter application. It does NOT include any controllers or views. To use this library:

1) download from github at the above url

2) there are 5 files (not including this README):

    - mahana.sql  -- run this sql script in your database. Note that these tables are all InnoDB - Mahana uses transactions

    - config/mahana.php -- you will need to set up your existing users table information here, following the sample data style

    - language/english/mahana_lang.php -- all error and success messages can be changed here, or multilingual support can be added

    -- models/mahana_model.php -- the database model

    -- libraries/Mahana_messaging -- the main library file

3) from your controller, load the library as either:

    (Recommended)
    $this->load->library('mahana_messaging');
    $msg = $this->mahana_messaging->get_message($msg_id, $sender_id);

    or

    $this->load->library('mahana_messaging');
        $mahana = new Mahana_messaging();
    $msg = $mahana->get_message($msg_id, $sender_id);

4) All functions return the array:

    $status['err']      1= error, 0 = no error
    $status['code']     a specific code for that return value, found in config/mahana.php
    $status['msg']      a configurable message, found in language/english/mahana_lang.php
    $status['retval']   (optional) returned array of data

5) Features

    Mahana Messaging has a couple of small features you should be aware of:

    1) using the config/mahana.php constants USER_TABLE_TABLENAME, USER_TABLE_ID, USER_TABLE_USERNAME you may quickly and easily integrate the messaging library with your exisiting user table

    2) all return messages are configurable and can be made multi-lingual

    3) return array $status makes for easy conversion to json format for ajax-based systems

    4) get_full_thread() and get_all_threads() have a unique parameter - $full_thread. If set to true, a newly added participant to a thread can see all messages dating BEFORE he was added, allowing him to "catch up" on the conversation. Ideal for adding a manager or a new salesperson to a conversation.


Thank you for using Mahana Messaging! Please be sure to leave the author's credits in the library file.
