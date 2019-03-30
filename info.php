elseif(isset($_REQUEST['sendsms'])) {
$type = 'sms';
$userMY_userd_system=$userID;
$country=0;
foreach ($_REQUEST as $key => $value ){
if($value !='userID')
${$key} = $value = strip_tags($value);
}
$to = correctRecipient($to);

$userID=$userMY_userd_system;
//$country=0;
$file='';
$duration=0;
$scheduledate='';
//check recipients
$message=$text;
//check recipients
$tempTo = $to;
$tempTo = correctRecipient($tempTo);
$tempTo = alterPhone($tempTo,countryPhoneCode($country));
$tempTo = removeDuplicate($tempTo);

$chars = countCharacters($message);
$numbers = $to_count = $numbers = countRecipient($tempTo);
$pages = countPage($message,$type,$duration);
$cost = smsCost($tempTo,$type,$country,$pages,$userID);

$now = date('Y-m-d H', time());
$sent_from = 'API';
$gateway_id = 0;
$ip = getUserIP();
$notice = '';

if(empty($text)) { echo '{"request": "sendsms","status": "error","message": "Message Field Is Empty"}'; die(); }
if(empty($from)) { echo '{"request": "sendsms","status": "error","message": "No Valid Recipients Supplied"}'; die(); }
$forbiden_id = forbiddenSenderID($from,$userID);
//if($forbiden_id && (!isAdmin($userID))) { echo '{"request": "sendsms","status": "error","message": "Sender ID Not Allowed"}'; die();}
if($forbiden_id) { echo '{"request": "sendsms","status": "error","message": "Sender ID Not Allowed"}'; die();}
$who_is="admin";
if(isAdmin($userID) == false){
$CheckSenderID = CheckSenderID($userID,$from,'sms');
if($CheckSenderID == false) { echo '{"request": "sendsms","status": "error","message": "Sender ID was not Request"}'; die();}
$who_is="client";

}

$forbiden_word = forbiddenWord($text,$userID);
//if($forbiden_word && (!isAdmin($userID))) { echo '{"request": "sendsms","status": "error","message": "Message Contains Forbidden Words"}'; die();}
if($forbiden_word) { echo '{"request": "sendsms","status": "error","message": "Message Contains Forbidden Words"}'; die();}
if($numbers < 1) { echo '{"request": "sendsms","status": "error","message": "No Valid Recipients Supplied"}' ; die(); } //if((userWalletBalance($userID) < 1) && (!isAdmin($userID)) ) { echo '{"request": "sendsms","status": "error","message": "Insufficient Balance"}' ; die();} if(userWalletBalance($userID) < $cost ) { echo '{"request": "sendsms","status": "error","message": "Insufficient Balance"}' ; die();} //set admin user if(isAdmin($userID)) { //$userID=0; $user_time=date('Y-m-d H:i:s',time()); } else { $user_time=date('Y-m-d H:i:s',time()); //getUserTime($userID); } if(strtotime($scheduledate)> strtotime($user_time)) {
    $schedule_date = date('Y-m-d H:i:s',strtotime($scheduledate));
    //save to DB for scheduling
    $Registered->runQuerySever( "INSERT INTO scheduledmessages (`message`,`recipients`, `customer_id`, `type`, `media`, `date`, `sender_id`, `phonebook_id`, `marketinglist_id`, `duration`, `file`,`country`,`status`,`schedule_date`, `gateway_id`, `to_count`) VALUES ('$text','$to', '$userID', '$type', '$file','$now','$from','0','0', '$duration', '','0','queued', '$schedule_date', '0', '$to_count');") ;
    logEvent($userID,'Scheduled a message for '.$schedule_date);
    echo '{"request": "sendsms","status": "queued","group_id": "0","date": "'.date('Y-m-d H:i:s').'"}'; die();
    }
    //save to DB for queuing

    $query = "INSERT INTO sentmessages
    (`message`,`recipients`, `customer_id`, `type`, `media`, `date`, `sender_id`, `phonebook_id`, `marketinglist_id`, `duration`, `file`,`country`,`status`,`sent_from`, `gateway_id`, `ip`, `notice`, `to_count`) VALUES
    (:text, :to, :userID, :type, :file, :now, :from, '0','0', :duration, '','0','completed', :sent_from, '0', :ip, '', :to_count)" ;
    $stmt = $Registered->runQuery($query);
    $stmt->bindparam(":text", $text);
    $stmt->bindparam(":to", $tempTo);
    $stmt->bindparam(":userID", $userID);
    $stmt->bindparam(":type", $type);
    $stmt->bindparam(":file", $file);
    $stmt->bindparam(":now", $now);
    $stmt->bindparam(":from", $from);
    $stmt->bindparam(":duration", $duration);
    $stmt->bindparam(":sent_from", $sent_from);
    $stmt->bindparam(":ip", $ip);
    $stmt->bindparam(":to_count", $to_count);
    $stmt->execute();
    $group_id = $GLOBALS['DB_con']->lastInsertId();


    $to_list = explode(",",$to);
    $c=1;

    foreach ($to_list as $to_singal ){
    $to = $to_singal ;
    $listmsg="";
    $now = time();
    $datetime = date('Y-m-d H:i:s');
    $country = 'Unknown'; //getDestinationCountry($to);
    $gateway_id = getDestinationRoute('sms',$to,$userID,'0');

    $Registered->runQuerySever( "INSERT INTO messagedetails (`message_id`, `message`,`recipient`, `customer_id`, `type`, `media`, `date`, `sender_id`, `marketinglist_id`, `duration`, `datetime`,`country_id`, `operator`, `status`, `gateway_id`, `notice`, `cost`) VALUES
    ('$group_id', '$text','$to', '$userID', '$type', '$file', '$now', '$from', '0', '0', '$datetime','$country', 'Unknown', 'sending', '$gateway_id', '', '0');");
    $single_message_id = $GLOBALS['DB_con']->lastInsertId();

    sendSMS($single_message_id);
    $status_s = singleMessageData($single_message_id,'status');
    $notice = singleMessageData($single_message_id,'notice');


    $listmsg .= '{"message_id": "'.$single_message_id.'","status": "'.$status_s.'","date": "'.date('Y-m-d H:i:s').'"}';
    if(count($to_list) != $c)
    $listmsg .= ",";

    $c++;
    }



    logEvent($userID,'Initiated an '.$type.' message to '.$numbers.' recipients');
    $msg_blc="";
    if($who_is=="client"){
    //$balance = userWalletBalance($userID);
    //$msg_blc= '","last_balance":"'.$balance.'"';
    }
    $notice_msg='';
    if($status_s =="failed"){
    $notice_msg= ',"notice":"'.$notice.'"';
    }

    echo '{"request": "sendsms","status": "'.$status_s.'"'.$notice_msg.',"group_id": "'.$group_id.'","messages":[ '.$listmsg.$msg_blc.' ],"who_is": "'.$who_is.'","date": "'.date('Y-m-d H:i:s').'"}'; die();
     