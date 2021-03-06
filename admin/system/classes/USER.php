<?php
/***
 * Author: Mehmet Hazar Artuner
 * Contact: hazar.artuner@gmail.com
 */

namespace com\admin\system\classes;

use com\admin\system\objects\UserObject;

class User extends \PA_USER_TICKET {
    public $user;
    protected $table;

    function __construct() {
        parent::PA_USER_TICKET();
        $this->table = $this->tables->user;
    }

    function completeRegistration($user_id, $username, $password) {
        global $secretKey;

        $pass_key = randomString(20);
        $encryptedPassword = sha1($secretKey . $password . $pass_key);

        return $this->execute("UPDATE {$this->table} SET username=?, password=?, pass_key=?, status='active' WHERE user_id=?", array($username, $encryptedPassword, $pass_key, $user_id));
    }

    /**
     * @param UserObject $user
     * @param null $roles
     * @param null $groups
     * @return bool | UserObject
     */
    function inviteUser(UserObject $user, $roles = null, $groups = null) {
        global $ADMIN;

        $user->status = "invited";


        if ($user->insert()) {
            // Kullanıcının rollerini ekle
            $this->setUserRoles($user->user_id, $roles);

            // Kullanıcının guruplarını ekle
//            $this->setUserGroups($user->user_id, $groups);

            if($this->sendInvitationMail($user->user_id)){
                return $user;
            }
            else{
                $this->error[] = "* Hata: Kullanıcıya davet maili gönderilemedi! Dosya:" . __FILE__ . " Satır:" . __LINE__;
                return false;
            }
        }
        else{
            $this->error[] = "* Hata: Kullanıcı oluşturulamadı! Dosya:" . __FILE__ . " Satır:" . __LINE__;
            return false;
        }
    }

    /**
     * @param UserObject $user
     * @param null $roles
     * @param null $groups
     * @return bool|UserObject $user
     */
    function addUser(UserObject $user, $roles = null, $groups = null) {
        global $ADMIN;

        if ($this->getUserCount() <= 0 || checkAccessStatus("ADMIN_add_user", false)) {

            global $secretKey;

            $user->pass_key = randomString(20);
            $user->password = sha1($secretKey . $user->password . $user->pass_key);
            $user->register_time = "NOW()";

            if($user->insert()){
                $this->setUserRoles($user->user_id, $roles);

//                $this->setUserGroups($user->user_id, $groups);

                return $user;
            }
            else{
                return false;
            }
        }
    }

    /**
     * Kullanıcı rollerini atar
     *
     * @param integer $user_id
     * @param array $roles role_id dizisi
     * @return bool
     */
    function setUserRoles($user_id, $roles = array()){
        global $ADMIN;

        // Kullanıcının rollerini ekle
        if (($roles != null) && is_array($roles)) {
            $role_count = sizeof($roles);
            for ($i = 0; $i < $role_count; $i++) {
                $ADMIN->USER_ROLE->addUserRole($user_id, $roles[$i]);
            }
        }

        return true;
    }

    /**
     * Kullanıcı guruplarını atar
     *
     * @param integer $user_id
     * @param array $roles role_id dizisi
     * @return bool
     */
    function setUserGroups($user_id, $groups = array()){
        global $ADMIN;

        // Kullanıcının rollerini ekle
        if (($groups != null) && is_array($groups)) {
            $group_count = sizeof($groups);
            for ($i = 0; $i < $group_count; $i++) {
                $ADMIN->USER_GROUP->addUserGroup($user_id, $groups[$i]);
            }
        }

        return true;
    }

    function sendInvitationMail($user_id, $end_date = "0000-00-00 00:00:00") {
        global $ADMIN;

        $user = $this->getUserById($user_id);
        $this->closeTicketsByTicketType($user_id, "invitation");
        $ticket_id = $this->openTicket($user_id, "invitation", $end_date);
        $ticket = $this->selectTicket($ticket_id);
        $site_title = get_option("admin_site_title");
        $register_link = get_option("admin_site_address") . "/admin/complete_registration.php?type=invitation&user={$user_id}&key={$ticket->ticket_key}";
        $invitation_sender = $ADMIN->AUTHENTICATION->authenticated_user; // Davetiyeyi gönderen kullanıcı

        $mesaj = "Sayın  <b>{$user->displayname}</b>, <br /> ";
        $mesaj .= "<b>{$invitation_sender->displayname}</b> kullanıcısı ";
        $mesaj .= "<b>{$site_title}</b> sitesine üye olmanız için size bir davetiye gönderdi.";
        $mesaj .= "Daveti kabul edip üyelik işleminizi gerçekleştirmek için aşağıdaki linki kullanın.";
        $mesaj .= '<a href="' . $register_link . '" target="_blank" style="margin-top:22px;  background: #c4eef5; width:113px; ';
        $mesaj .= 'height:23px; text-align: center; font:bold 13px Segoe UI; color:#227eac; display:block; ';
        $mesaj .= 'border:solid 1px #95c1d7; text-decoration: none; line-height: 23px;">Üye Ol</a>';

        return sendMail($site_title, "Üyelik Davetiyesi", $mesaj, $user->email);
    }

    function reSendInvitationMail($email) {
        $user = $this->getUserByEmail($email);
        return $this->sendInvitationMail($user->user_id);
    }

    function changePassword($user_id, $password) {
        global $secretKey;

        $pass_key = randomString(20);
        $encryptedPassword = sha1($secretKey . $password . $pass_key);

        return $this->execute("UPDATE {$this->table} SET password=?, pass_key=? WHERE user_id=?", array($encryptedPassword, $pass_key, $user_id));
    }

    function openResetPasswordTicket($email_or_username, $reset_password_page = "/admin/newpassword.php") {
        if ($user = $this->getUserByEmail_OR_Username($email_or_username)) {
            $ticket_type = "resetpassword";
            // Daha önce açık olan ticket ları kapat
            $this->closeTicketsByTicketType($user->user_id, $ticket_type);

            // Yeni bir ticket aç ve mail gönder
            $ticket_id = $this->openTicket($user->user_id, $ticket_type);
            if ($ticket = $this->selectTicket($ticket_id)) {
                $site_title = get_option("admin_site_title");
                $reset_password_link = get_option("admin_site_address") . $reset_password_page . "?type=resetpassword&user={$user->user_id}&key={$ticket->ticket_key}";

                $mesaj = "Sayın  <b>{$user->displayname},</b><br />";
                $mesaj .= "Talebiniz üzerine parola değiştirme işleminizi gerçekleştirmek için aşağıda bulunan \"Parolamı Değiştir\" ";
                $mesaj .= "butonunu kullanarak, ilgili sayfaya yönlendirildikten sonra parolanızı değiştirebilirsiniz. <br />";
                $mesaj .= '<a href="' . $reset_password_link . '" target="_blank" style="margin-top:22px;  background: #c4eef5; width:145px; ';
                $mesaj .= 'height:23px; text-align: center; font:bold 13px Segoe UI; color:#227eac; display:block; ';
                $mesaj .= 'border:solid 1px #95c1d7; text-decoration: none; line-height: 23px;">Parolamı Değiştir</a>';

                if (sendMail($site_title, "Parola Değiştirme", $mesaj, $user->email))
                    return true;
                else {
                    $this->error[] = "Parola sıfırlama maili gönderilemedi!";
                    return false;
                }
            } else {
                $this->error[] = "Parola sıfırlama işlemi için izin alınamadı, lütfen tekrar deneyin!";
                return false;
            }
        } else {
            $this->error[] = "Kullanıcı adı veya mail adresiniz doğru değil!";
            return false;
        }
    }

    // TODO: username değerini değiştirme özelliği ekle
    function updateUser(UserObject $user) {
        $password = $user->password;

        if (!empty($password)) {
            global $secretKey;

            $data = $this->getUserById($user->user_id);

            $user->password = sha1($secretKey . $password . $data->pass_key);
        }

        return $user->update();
    }

    // TODO: Silinen kullanıcı silinmeden önce sisteme giriş yapmışsa ve hala sistemdeyse onu sistemden de çıkarmanın yolunu bul.
    function deleteUser($user_id) {
        global $ADMIN;

        return $this->deleteUsersAllTickets($user_id) && $ADMIN->USER_ROLE->deleteUserRolesByUser($user_id) && $this->deleteTracksByUserId($user_id) &&
        $ADMIN->USER_GROUP->deleteUserGroupsByUser($user_id) && $this->execute("DELETE FROM {$this->table} WHERE user_id=?", array($user_id));
    }

    function getUserCount(UserObject $filters = null) {
        $variables = array();
        $query = "SELECT COUNT(*) FROM {$this->table} ";

        if(($filters !== null) && sizeof($filters->toArray()) > 0){
            $stringArr = array();

            foreach($filters->toArray() as $key=>$val){
                $stringArr[] = "{$key}=? ";
                $variables[] = $val;
            }

            $query .= "WHERE " . join(" AND ", $stringArr);
        }

        return $this->get_value($query, $variables);
    }

    /**
     * @param $user_id
     * @return UserObject|null
     */
    function getUserById($user_id) {
        if($user = $this->get_row("SELECT * FROM {$this->table} WHERE user_id=?", array($user_id))){
            return new UserObject($user);
        }
        else{
            return null;
        }
    }

    /**
     * @param $username
     * @return UserObject|null
     */
    function getUserByUsername($username) {
        if($user = $this->get_row("SELECT * FROM {$this->table} WHERE username=?", array($username))){
            return new UserObject($user);
        }
        else{
            return null;
        }
    }

    /**
     * @param $email
     * @return UserObject|null
     */
    function getUserByEmail($email) {
        if($user = $this->get_row("SELECT * FROM {$this->table} WHERE email=?", array($email))){
            return new UserObject($user);
        }
        else{
            return null;
        }
    }

    /**
     * @param $email_or_username
     * @return UserObject|null
     */
    function getUserByEmail_OR_Username($email_or_username) {
        if($user = $this->get_row("SELECT * FROM {$this->table} WHERE email=? OR username=?", array($email_or_username, $email_or_username))){
            return new UserObject($user);
        }
        else{
            return null;
        }
    }

    /**
     * @param UserObject $filters
     * @return UserObject|null
     */
    function getUser(UserObject $filters = null) {
        $variables = array();

        if($filters === null){
            return null;
        }
        else if(sizeof($filters->toArray()) <= 0){
            return null;
        }
        else{
            $stringArr = array();

            foreach($filters->toArray() as $key=>$val){
                $stringArr[] = "{$key}=? ";
                $variables[] = $val;
            }

            $query = "SELECT * FROM {$this->table} WHERE " . join(" AND ", $stringArr);
        }

        if($user = $this->get_row($query, $variables)){
            return new UserObject($user);
        }
        else{
            return null;
        }
    }

    /**
     * @param UserObject $filters
     * @return null|static[]
     */
    function listUsers(UserObject $filters = null) {
        $variables = array();

        $query = "SELECT * FROM {$this->table} ";

        if(($filters !== null) && sizeof($filters->toArray()) > 0){
            $stringArr = array();

            foreach($filters->toArray() as $key=>$val){
                $stringArr[] = "{$key}=? ";
                $variables[] = $val;
            }

            $query .= "WHERE " . join(" AND ", $stringArr);
        }

        if($users = $this->get_rows($query, $variables)){
            return UserObject::convertToObjectCollection($users);
        }
        else{
            return null;
        }
    }
}