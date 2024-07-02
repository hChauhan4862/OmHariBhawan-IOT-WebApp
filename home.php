<?php
date_default_timezone_set("Asia/Kolkata");
$telegram_token="1334842917:AAEc9xtzXrkxeAQeQzKS2pGT2ZQ3mkgP-pA";
$telegram_group="931877070";    //"-475852204";
$thinger_token="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiJzdXBlcl9hZG1pbiIsInVzciI6ImhjIn0.ce3kquLFNLyJj99Cq-nkGSaE0iAS8-0HmRak0nrUOso";
function url_c($url){
    $ch = curl_init();  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    curl_setopt($ch, CURLOPT_URL, $url); 
    return curl_exec($ch); 
}
function tele_send($method, $data)
{
    $url = "https://api.telegram.org/bot1334842917:AAEc9xtzXrkxeAQeQzKS2pGT2ZQ3mkgP-pA/" . $method;

    $curld = curl_init();
    curl_setopt($curld, CURLOPT_POST, true);
    curl_setopt($curld, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curld, CURLOPT_URL, $url);
    curl_setopt($curld, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($curld);
    curl_close($curld);
    return $output;
}
function thinger_get($led_name,$data=''){
    global $thinger_token;
    $a=($data=='') ? '/api':'';
    $url='https://backend.thinger.io/v3/users/hc/devices/hcmain/resources/'.$led_name.$a;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
    
    $headers = array();
    $headers[] = 'Content-Type: application/json;charset=UTF-8';
    $headers[] = 'Authorization: Bearer '.$thinger_token;
    $headers[] = 'Accept: application/json, text/plain, */*';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);
    return $result;
}

function icon($a){
    $i['back'] ='U+21A9';
    $i['back0']='U+1F519';
    $i['tv']   ='U+1F4FA';
    $i['drop'] ='U+1F4A7';
    $i['bulb'] ='U+1F4A1';
    $i['torch']='U+1F526';
    $i['power']='U+23FB';
    $i['refresh']='U+1F504';
    $b=$i[$a];
    if(!$b || $b==''){return NULL;}
    return utf8(hexdec(str_replace("U+","", $b)));
}
function utf8($num)
{
    if($num<=0x7F)       return chr($num);
    if($num<=0x7FF)      return chr(($num>>6)+192).chr(($num&63)+128);
    if($num<=0xFFFF)     return chr(($num>>12)+224).chr((($num>>6)&63)+128).chr(($num&63)+128);
    if($num<=0x1FFFFF)   return chr(($num>>18)+240).chr((($num>>12)&63)+128).chr((($num>>6)&63)+128).chr(($num&63)+128);
    return '';
}

class db {
    protected $connection;
	protected $query;
    protected $show_errors  =  TRUE;
    protected $query_closed =  TRUE;
	public    $query_count  =  0;

	public function __construct($dbhost='localhost',$dbuser='hcblogge_iot',$dbpass='Hps202132@',$dbname='hcblogge_iot',$charset='utf8')
	{
		$this->connection = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if ($this->connection->connect_error) {
			$this->error('Failed to connect to MySQL - ' . $this->connection->connect_error);
		}
		$this->connection->set_charset($charset);
	}

    public function query($query) {
        if (!$this->query_closed) {
            $this->query->close();
        }
		if ($this->query = $this->connection->prepare($query)) {
            if (func_num_args() > 1) {
                $x = func_get_args();
                $args = array_slice($x, 1);
				$types = '';
                $args_ref = array();
                foreach ($args as $k => &$arg) {
					if (is_array($args[$k])) {
						foreach ($args[$k] as $j => &$a) {
							$types .= $this->_gettype($args[$k][$j]);
							$args_ref[] = &$a;
						}
					} else {
	                	$types .= $this->_gettype($args[$k]);
	                    $args_ref[] = &$arg;
					}
                }
				array_unshift($args_ref, $types);
                call_user_func_array(array($this->query, 'bind_param'), $args_ref);
            }
            $this->query->execute();
           	if ($this->query->errno) {
           	    $bt = debug_backtrace();$caller = array_shift($bt);
				$this->error('Unable to process MySQL query (check your params) - ' . $this->query->error.' - ON:- '.$caller['file'].':'.$caller['line']);
           	}
            $this->query_closed = FALSE;
			$this->query_count++;
        } else {
            $bt = debug_backtrace();$caller = array_shift($bt);
            $this->error('Unable to prepare MySQL statement (check your syntax) - ' . $this->query->error.' - ON:- '.$caller['file'].':'.$caller['line']);
        }
		return $this;
    }
	
	public function fetchAll($callback = null) {
	    $params = array();
        $row = array();
	    $meta = $this->query->result_metadata();
	    while ($field = $meta->fetch_field()) {
	        $params[] = &$row[$field->name];
	    }
	    call_user_func_array(array($this->query, 'bind_result'), $params);
        $result = array();
        while ($this->query->fetch()) {
            $r = array();
            foreach ($row as $key => $val) {
                $r[$key] = $val;
            }
            if ($callback != null && is_callable($callback)) {
                $value = call_user_func($callback, $r);
                if ($value == 'break') break;
            } else {
                $result[] = $r;
            }
        }
        $this->query->close();
        $this->query_closed = TRUE;
		return $result;
	}

	public function fetchArray() {
	    $params = array();
        $row = array();
	    $meta = $this->query->result_metadata();
	    while ($field = $meta->fetch_field()) {
	        $params[] = &$row[$field->name];
	    }
	    call_user_func_array(array($this->query, 'bind_result'), $params);
        $result = array();
		while ($this->query->fetch()) {
			foreach ($row as $key => $val) {
				$result[$key] = $val;
			}
		}
        $this->query->close();
        $this->query_closed = TRUE;
		return $result;
	}

	public function close() {
		return $this->connection->close();
	}

    public function numRows() {
		$this->query->store_result();
		return $this->query->num_rows;
	}

	public function affectedRows() {
		return $this->query->affected_rows;
	}

    public function lastInsertID() {
    	return $this->connection->insert_id;
    }

    public function error($error) {
        if ($this->show_errors) {
            //header('Content-Type: application/json');
            $output["error"]=true;
            $output["status"]=500;
            $output["data"]=array();
                global $developer_mode;if($developer_mode!=1){$error='Server Error';}       // Set Default Error for Non Developer Mode
            $output["message"]=$error;
            echo json_encode($output, $json_type);
            exit();
        }
		else
		{
		    header('HTTP/1.1 500 Internal Server Error');
			exit();
		}
    }

	private function _gettype($var) {
	    if (is_string($var)) return 's';
	    if (is_float($var)) return 'd';
	    if (is_int($var)) return 'i';
	    return 'b';
	}
}
$db = new db();

$path=trim(explode('?',$_SERVER['REQUEST_URI'])[0],'/');
$token=$_GET['auth'];

$op=array(); 

if($path=='icon'){

    echo icon('power');
    exit;
}
else if($path=='TELEGRAM_BOT'){
    $callback=0;
    $path = "https://api.telegram.org/bot".$telegram_token;
    $receied=json_decode(file_get_contents("php://input"),TRUE);
    file_put_contents("test.txt",file_get_contents("php://input"));
    
    $default_keyboard = array(
        "inline_keyboard" => array(
            array(
                array("text" => "POWER", "callback_data" => "/check power ".rand(1,99999)),
                array("text" => "TV LIGHTS", "callback_data" => "/check LED_1 ".rand(1,99999)),
                array("text" => "FAN", "callback_data" => "/check LED_2 ".rand(1,99999))
            ),
            array(
                array("text" => "OUTSIDE BULB", "callback_data" => "/check LED_3 ".rand(1,99999)),
                array("text" => "CONCEALED 1", "callback_data" => "/check LED_4 ".rand(1,99999)),
                array("text" => "CONCEALED 2", "callback_data" => "/check LED_5 ".rand(1,99999))
            ),
            array(
                array("text" => "INSIDE BULB", "callback_data" => "/check LED_6 ".rand(1,99999)),
                array("text" => "CONCEALED 3", "callback_data" => "/check LED_7 ".rand(1,99999)),
                array("text" => "CONCEALED 4", "callback_data" => "/check LED_8 ".rand(1,99999))
            ),
            array(
                array("text" => icon('drop')."  WATER STATUS ".icon('drop'), "callback_data" => "/water ".rand(1,99999)),
                array("text" => "SCHEDULE", "callback_data" => "/schedule ".rand(1,99999))
            ),
            array(
                array("text" => icon('refresh')."  REFRESH MENU", "callback_data" => "/refresh ".rand(1,99999)),
            )
        ),
        "resize_keyboard"=>true,
        "one_time_keyboard"=>true
    );
    $led_names=array(
        "power"=>"DEVICE POWER",
        "LED_1"=>"TV LIGHT",
        "LED_2"=>"FAN",
        "LED_3"=>"OUTSIDE BULB",
        "LED_4"=>"CONCEALED LIGHT 1",
        "LED_5"=>"CONCEALED LIGHT 2",
        "LED_6"=>"INSIDE BULB",
        "LED_7"=>"CONCEALED LIGHT 3",
        "LED_8"=>"CONCEALED LIGHT 4"
    );
    
    if(isset($receied['callback_query'])) {$callback=1;$update=$receied['callback_query'];} else {$update=$receied;}
    
    $chatId = $update["message"]["chat"]["id"];
    if($callback){
        $command=$update['data'];
    }
    else{
        $command = $update["message"]["text"];
    }
    $parameters=array();
    
    
    if (strpos($command, "/start") === 0) {
        $text="Welcome To Om Hari Bhawan";
    }
    else if (strpos($command, "/refresh") === 0) {
        $text="Welcome To Om Hari Bhawan

Refresh On ".date("h:i:sa");
    }
    else if (strpos($command, "/schedule") === 0) {
        $text="Sorry dear, scheduling system not availble on telegram
        Please use app for this service";
        $default_keyboard = array(
            "inline_keyboard" => array(
                array(
                    array("text" => " Go Back  ".icon('back'), "callback_data" => "/start ".rand(1,99999))
                ),
            ),
            "resize_keyboard"=>true,
            "one_time_keyboard"=>false
        );
    }
    else if (strpos($command, "/water") === 0) {
        $status=json_decode(thinger_get('water'),true);
        
        if(array_key_exists("error",$status)){
                $text="Seems Device is offline";
        }
        else{
            $c_status=($status['in']) ? "ON":"OFF";
            $text="WATER LEVEL IS :- ".$status['out'];
            
            $default_keyboard = array(
                "inline_keyboard" => array(
                    array(
                        array("text" => " Go Back  ".icon('back'), "callback_data" => "/start ".rand(1,99999))
                    ),
                ),
                "resize_keyboard"=>true,
                "one_time_keyboard"=>false
            );
        }
    }
    else if(strpos($command, "/check") === 0){
        $l=explode(" ",$command)[1];
        $name=$led_names[$l];
        if($name!=''){
            $temp=thinger_get($l);
            $status=json_decode($temp,true);
            if(array_key_exists("error",$status)){
                $text="Seems Device is offline";
            }
            else{
                $c_status=($status['in']) ? "ON":"OFF";
                $text=$name." IS CURRENTLY ".$c_status;
                
                $new_s=($status['in']) ? 0:1;
                $new_s_text=$new_s ? "ON":"OFF";
                
                $default_keyboard = array(
                    "inline_keyboard" => array(
                        array(
                            array("text" => "TURN ".$new_s_text, "callback_data" => "/switch ".$l." ".$new_s_text." ".rand(1,99999)),
                            array("text" => "Go Back", "callback_data" => "/start ".rand(1,99999))
                        ),
                    ),
                    "resize_keyboard"=>true,
                    "one_time_keyboard"=>false
                );
            }
        }
    }
    else if(strpos($command, "/switch") === 0){
        $l=explode(" ",$command)[1];
        $s=explode(" ",$command)[2];
        $name=$led_names[$l];
        
        if($name!='' && ($s=="ON" || $s=="OFF")){
            $n= ($s=="ON") ? "1":"0";
            $temp=thinger_get($l,$n);
            file_put_contents("test.txt",$l);
            $status=json_decode($temp,true);

            if(array_key_exists("error",$status)){
                $text="Seems Device is offline";
            }
            else{
                $text=$name." IS NOW TURN ".$s;
            }
        }
    }
    else{
        $text="Command not found, Please use these buttons";
    }
    
    
    if($callback){
        $mid=$update['message']['message_id'];
        $encodedKeyboard = json_encode($default_keyboard);
        $parameters = array('chat_id' => $chatId,'message_id' => $mid, 'text' => $text, 'reply_markup' => $encodedKeyboard);
        $r=tele_send('editMessageText', $parameters);
    }
    else{
        $encodedKeyboard = json_encode($default_keyboard);
        $parameters = array('chat_id' => $chatId, 'text' => $text, 'reply_markup' => $encodedKeyboard);
        $r=tele_send('sendMessage', $parameters);
    }
    //file_put_contents("test.txt",$r);
    exit;
}
else if($path=='user_login'){
    $u=$_POST['u'];
    $p=$_POST['p'];
    if($u!='' && $p!=''){
        $r=$db->query('SELECT * FROM users WHERE userid = ? AND password = ?',$u,$p)->fetchArray();
        if($r['userid']==$u){
            if($r['status']==1){
                unset($r['password']);
                $op['code']=100;
                $op['user']=$r;
            }
            else{
                $op['code']=2;
            }
        }
        else{
            $op['code']=1;
        }
    }
    else{
        $op['code']=0;
    }
}
else if($path=='water_level_api'){
    
    $per=$_GET['per'];
    $water_level=$_GET['level'];
    $test=(int) ($per/10)+1;
    
         if ($test == 1 && $water_level>$test) {
      $op["message"] = "Water Level is in Danger, Please immediately Turn On Motor.";
    }
    else if ($test == 2 && $water_level>$test) {
      $op["message"] = "Water Level is too much Low, Please Turn On Motor.";
    }
    else if ($test == 3 && $water_level>$test) {
      $op["message"] = "Please Turn On Motor.";
    }
    else if ($test == 9 && $water_level<$test) {
      $op["message"] = "Water Tank is going to be Full, Please be ready to turn off motor";
    }
    else if ($test == 10 && $water_level<$test) {
      $op["message"] = "Water Tank is going to be Full, you can turn of motor";
    }
    else if ($test == 11 && $water_level<$test) {
      $op["message"] = "Water Tank is going to be Full, I already told you to turn of motor, now water and Electricity both are wasting";
    }
    else if ($water_level<$test){
      $op["message"] = "Seems Water moter is on.";
    }
    else if ($water_level==$test){
      $op["message"] = "Can't Be Send";
    }
    else{
      $op["message"] = "Seems Water moter is off.";  
    }
    $op["STATUS"]="OK";
    $text="WATER TANK :- ".$per." \n".$op["message"];
    $url="https://api.telegram.org/bot".$telegram_token."/sendMessage?chat_id=".$telegram_group."&parse_mod=markdown&text=".urlencode($text);
    $op['TELE_RES']=url_c($url);
}
else if($path=='IFTT'){
    $r['SERVER']=$_SERVER;
    $r['REQUEST']=$_REQUEST;
    $r['POST']=$_POST;
    $r['GET']=$_GET;
    $r['ENV']=$_ENV;
    $r['COOKIE']=$_COOKIE;
    $r['SESSION']=$_SESSION;
    $a=json_encode($r,JSON_PRETTY_PRINT);
    file_put_contents("iftt.txt",$a, FILE_APPEND);
    echo "success";
    exit;
}
header('Content-Type: application/json');
echo json_encode($op,JSON_PRETTY_PRINT);