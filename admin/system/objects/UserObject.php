<?php namespace com\admin\system\objects;


/**
 * Class UserObject
 * @package com\admin\system\objects
 *
 * @property integer user_id
 * @property integer image_id
 * @property string username
 * @property string displayname
 * @property string birthday
 * @property string first_name
 * @property string last_name
 * @property string gender
 * @property string about
 * @property string email
 * @property string phone
 * @property string password
 * @property string pass_key
 * @property string register_time
 * @property integer captcha_limit
 * @property string status
 */
class UserObject extends AbstractObject{
    function __construct($data = null){
        global $DB;

        $this->_setTableName($DB->tables->user);
        $this->_setMap(array(
            "user_id",
            "image_id",
            "username",
            "displayname",
            "birthday",
            "first_name",
            "last_name",
            "gender",
            "about",
            "email",
            "phone",
            "password",
            "pass_key",
            "register_time",
            "captcha_limit",
            "visible_in_admin",
            "status"
        ));
        $this->_setPrimaryKey("user_id");

        parent::__construct($DB, $data);
    }

    public function __set($key, $val){
        if($key == "password"){
            if(empty($val)){
                unset($this->password);
                return null;
            }
        }

        return parent::__set($key, $val);
    }
}