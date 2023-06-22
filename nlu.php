<?php
namespace Google\Cloud\Samples\Dialogflow;
require_once 'vendor/autoload.php';




use Google\Cloud\Dialogflow\V2\SessionsClient;
use Google\Cloud\Dialogflow\V2\TextInput;
use Google\Cloud\Dialogflow\V2\QueryInput;

function getParameterValue ($result, $key, $default) {
    $fields = $result->getParameters()->getFields();
    if (!$fields->offsetExists($key)) return $default; // not found

    $offset = $fields->offsetGet($key);
    switch ($offset->getKind()) {
        case 'string_value': return $offset->getStringValue();
        case 'number_value': return $offset->getNumberValue();
        case 'bool_value': return $offset->getBoolValue();
        case 'null_value': return $offset->getNullValue();
        default: return $default; // kind not supported
    }
}

function detect_intent ($text, $projectId='e-bot-ipbf', $sessionId = '123456', $languageCode = 'zh-TW') {
  
  $link = mysqli_connect("192.168.2.200", "slionf26_app_2023", "root123456", "slionf26_app_2023") // 建立MySQL的資料庫連結
    or die("無法開啟MySQL資料庫連結!<br>");

  // 送出編碼的MySQL指令
  mysqli_query($link, 'SET CHARACTER SET utf8');
  mysqli_query($link, "SET collation_connection = 'utf8_unicode_ci'");
  
    $key = array('credentials' => 'e-bot.json');
    $sessionsClient = new SessionsClient($key);
    $session = $sessionsClient->sessionName($projectId, $sessionId);

    $textInput = new TextInput();
    $textInput->setText($text);
    $textInput->setLanguageCode($languageCode);

    $queryInput = new QueryInput();
    $queryInput->setText($textInput);

    $response = $sessionsClient->detectIntent($session, $queryInput);
    $queryResult = $response->getQueryResult();
    $queryText = $queryResult->getQueryText();
    $intent = $queryResult->getIntent();
    $displayName = $intent->getDisplayName();
    $confidence = $queryResult->getIntentDetectionConfidence();
    $fulfilmentText = $queryResult->getFulfillmentText();

  /* 要做的事情*/
  	
  
/*-- 1. Product_content Intent start --*/  
  if ($displayName == "Product_content"){
      $p_name = getParameterValue($queryResult, "items_description_entity", "");
    	
      $sql_del = "DELETE FROM `nlu`";
      mysqli_query($link, $sql_del);
      $sql_nlu = "INSERT INTO `nlu`(`p_name`) VALUES ('$p_name');";
      mysqli_query($link, $sql_nlu); 
        	
      $sql_p = "SELECT * FROM  `product` WHERE `p_name` = '$p_name' ";
      if($result_p = mysqli_query($link, $sql_p)){  
       
          $row = mysqli_fetch_assoc($result_p);
          $price =  $row["price"];
          $content = str_replace("</br>","", str_replace("\xE2\x81\xA3","", $row["content"]));
          $fulfilmentText = $p_name . "，一份" . $price . "元。\n" . $content . "。\n\n需要幫您加入購物車嗎？"; 

      }else{  
          $fulfilmentText =  "(no results)";   
      } 
    mysqli_free_result($result_p);
  }
/*-- 1. Product_content Intent end --*/

/*-- 2. Product_new Intent start --*/  
  if ($displayName == "Product_new"){
      $p_id = rand(1,24);
      do {
        if ($p_id >= 24) $p_pd = 1; else $p_id += 1;
        $sql_c = "SELECT * FROM  `cart` WHERE `p_id` = '$p_id' ";
        $x = mysqli_fetch_assoc(mysqli_query($link, $sql_c));       
 	  } while ($x);
      
      $sql_p = "SELECT * FROM  `product` WHERE `ID` = '$p_id' ";
      if($result_p = mysqli_query($link, $sql_p)){         
          $row = mysqli_fetch_assoc($result_p);
          $price =  $row["price"];
          $p_name = $row["p_name"]; 
          $content = str_replace("</br>","", str_replace("\xE2\x81\xA3","", $row["content"]));
          $fulfilmentText = "近期新品有：\n" .$p_name . "！\n一份價格為" . $price . "元。\n\n" . $content . "。\n\n需要幫您加入購物車嗎？"; 
        
          $sql_del = "DELETE FROM `nlu`";
      	  mysqli_query($link, $sql_del);
     	  $sql_nlu = "INSERT INTO `nlu`(`p_name`) VALUES ('$p_name');";
      	  mysqli_query($link, $sql_nlu); 

      }else{  
          $fulfilmentText =  "(no results)";   
      } 
    //mysqli_free_result($result_c);
    mysqli_free_result($result_p);
  }
/*-- 2. Product_new Intent end --*/

/*-- 3. Product_recommend Intent start --*/  
  if ($displayName == "Product_recommend"){
     
      $p_id = rand(1,24);
      do {
        if ($p_id >= 24) $p_pd = 1; else $p_id += 1;
        $sql_c = "SELECT * FROM  `cart` WHERE `p_id` = '$p_id' ";
        $x = mysqli_fetch_assoc(mysqli_query($link, $sql_c));       
 	  } while ($x);
      
      $sql_p = "SELECT * FROM  `product` WHERE `ID` = '$p_id' ";
      if($result_p = mysqli_query($link, $sql_p)){  
       
          $row = mysqli_fetch_assoc($result_p);
          $price =  $row["price"];
          $p_name = $row["p_name"];  
          $content = str_replace("</br>","", str_replace("\xE2\x81\xA3","", $row["content"]));
          $fulfilmentText = "向您推薦：\n" .$p_name . "！\n一份價格為" . $price . "元。\n\n" . $content . "。\n\n需要幫您加入購物車嗎？"; 
        
          $sql_del = "DELETE FROM `nlu`";
      	  mysqli_query($link, $sql_del);
     	  $sql_nlu = "INSERT INTO `nlu`(`p_name`) VALUES ('$p_name');";
      	  mysqli_query($link, $sql_nlu); 

      }else{  
          $fulfilmentText =  "(no results)";   
      } 
    
    mysqli_free_result($result_p);
  }
/*-- 3. Product_recommend Intent end --*/
  

/*-- 4. Add_yes Intent start --*/
  if ($displayName == "Add_yes"){
    $p_num = getParameterValue($queryResult, "quantity", "");
    $sql_nlu = "SELECT * FROM  `nlu` ";
    $row = mysqli_fetch_assoc(mysqli_query($link, $sql_nlu));
    $p_name = $row["p_name"];
	
    if($p_num){
      $sql_c = "
          INSERT INTO `cart`(`p_id`, `p_num`, `p_name`, `p_price`, `p_img`)
          VALUES ((SELECT ID FROM product WHERE p_name = '$p_name'),
          '$p_num',
          '$p_name',
          (SELECT price FROM product WHERE p_name = '$p_name'),
          CONCAT('https://s0854006.lionfree.net/app/img/', 
          (SELECT picture FROM product WHERE p_name = '$p_name'))) 
          ON DUPLICATE KEY UPDATE `p_num` = `p_num` + '$p_num';
        ";

        if(mysqli_query($link, $sql_c)){  
          $fulfilmentText = "好的，幫您將" .$p_num. "份" .$p_name. "加入購物車。\n還有其他需求嗎？";
        }else{  
          $fulfilmentText =  "(no results)";  
        }     
    }
  }
/*-- 4. Add_yes Intent end --*/
  
/*-- 6.	Add_to_cart Intent start --*/
  if ($displayName == "Add_to_cart"){
      $p_name = getParameterValue($queryResult, "items_description_entity", "");
      $p_num = getParameterValue($queryResult, "quantity", "");
    
      if($p_num && $p_name){
        $sql_c = "
        INSERT INTO `cart`(`p_id`, `p_num`, `p_name`, `p_price`, `p_img`)
          VALUES ((SELECT ID FROM product WHERE p_name = '$p_name'),
          '$p_num',
          '$p_name',
          (SELECT price FROM product WHERE p_name = '$p_name'),
          CONCAT('https://s0854006.lionfree.net/app/img/', 
          (SELECT picture FROM product WHERE p_name = '$p_name'))) 
          ON DUPLICATE KEY UPDATE `p_num` = `p_num` + '$p_num';
        ";

        if(mysqli_query($link, $sql_c)){  
          $fulfilmentText = "好的，幫您將" .$p_num. "份" .$p_name. "加入購物車。\n還有其他需求嗎？";
        }else{  
          $fulfilmentText =  mysqli_error();  
        }       
      }
      /*else if($p_name){
        $fulfilmentText =  "好的，您要幾份呢？";  
      }
      else{
        $fulfilmentText =  "好的，您要什麼食物品項呢？";
      }*/
      
  }
/*-- 6.	Add_to_cart Intent end --*/
  
/*-- 7. Add_to_cart_end start --*/
  if ($displayName == "Add_to_cart_end"){
    
    $amountarray=array();
    $pricearray = array();
	
    $sql_c = "SELECT * FROM `cart` ";
    
    $result_c = mysqli_query($link, $sql_c);
    if ($result_c->num_rows > 0) {
		  // output data of each row
      while($row = $result_c->fetch_assoc()) {
            $amountarray[] = $row["p_num"];
            $pricearray[] = $row["p_price"];
      }
	} 

  	$cnt = count($amountarray);
    $total = 0;
    for ($j = 0; $j < $cnt; $j++) {
      $total += $amountarray[$j] * $pricearray[$j];
	  }



    if($total >0){  
      $fulfilmentText = "好的，總共 $total 元，要核對詳細資料嗎？";
    }else if($total == 0){  
      $fulfilmentText =  "購物車為空，使用AI助手，立即開始選購商品！";
    }    
    else{
      $fulfilmentText =  mysqli_error();
    }
    mysqli_free_result($result_c);   
  }
/*-- 7. Add_to_cart_end end --*/

/*-- 8. Verify_no start --*/
  if ($displayName == "Verify_no"){

    $sql_c = "SELECT * FROM `cart` ";
    $result_c = mysqli_query($link, $sql_c);
     
    $messageArr = array();
    $dataarray= array();
  
    $IDarray = array();
    $namearray=array();
    $amountarray = array();
    $pricearray = array();
  
  
    //data
    date_default_timezone_set('Asia/Taipei');
    $year = date('Y');
    $month = date('m');
    $day = date('d');
    $date = $year . "-" . $month . "-" . $day;
    $a=rand(0,2147483647);
    
    if ($result_c->num_rows > 0) {
      // output data of each row
      while($row = $result_c->fetch_assoc()) {
            $IDarray[] = $row["p_id"];
            $namearray[] = $row["p_name"];
            $amountarray[] = $row["p_num"];
            $pricearray[] = $row["p_price"];
                 
       $dataarray[] = $row;//將資料一筆一筆丟進dataarray
        
      }
    } 
  
    $amount = "";
    $product = "";
  
    $cnt = count($IDarray);
    $total = 0;
    for ($j = 0; $j < $cnt; $j++) {
        $total += $amountarray[$j] * $pricearray[$j];
        if ($product == "") {
              $product .= $namearray[$j];
              $amount .= $amountarray[$j];
          } else {
              $product .= "," . $namearray[$j];
              $amount .= "," . $amountarray[$j];
          }
      }
  
      if($product != ""){
        $sql = "INSERT INTO `checkout`(`checkout_id`, `account`, `product_name`, `product_amount`, `total`, `date`) 
        VALUES ('$a','appuser','$product','$amount','$total','$date');";
        mysqli_query($link, $sql);
        $sql = "DELETE FROM `cart` ;";
        mysqli_query($link, $sql);
    	  $fulfilmentText = "好的，訂單已經送出，謝謝購買。";
        }
        else{
          $fulfilmentText = "購物車為空，請先選購商品後再送出訂單。";
        }
      
      mysqli_free_result($result_c);   

  }
/*-- 8. Verify_no end --*/
  
/*-- 9. Verify_yes start --*/
if ($displayName == "Verify_yes" || $displayName == "Verify") {
 $sql_c = "SELECT * FROM `cart` ";
  $result_c = mysqli_query($link, $sql_c);

  $amountarray = array();
  $pricearray = array();

  $output="";
  
  if ($result_c->num_rows > 0) {
    // output data of each row
    while($row = $result_c->fetch_assoc()) {
          $ID= $row["p_id"];
          $name = $row["p_name"];
          $amount  = $row["p_num"];
          $price  = $row["p_price"];

          $amountarray[] = $row["p_num"];
          $pricearray[] = $row["p_price"];
               
          $dataarray[] = $row;//將資料一筆一筆丟進dataarray
          $t_price = $amount*$price;
          $output .= "$name $amount 份, 小計 $t_price 元\n";
      
    }

    $total = 0;
    for ($j = 0; $j < count($amountarray); $j++) {
        $total += $amountarray[$j] * $pricearray[$j];
    }

    $fulfilmentText = "明細如下:\n\n$output \n總計 $total 元，是否送出訂單？";
  } 
  else{
    $fulfilmentText =  mysqli_error();
  }
}
/*-- 9. Verify_yes end --*/
 

/*-- 10. Send_yes start --*/
if ($displayName == "Send_yes"){

  $sql_c = "SELECT * FROM `cart` ";
  $result_c = mysqli_query($link, $sql_c);
   
  $messageArr = array();
  $dataarray= array();

  $IDarray = array();
  $namearray=array();
  $amountarray = array();
  $pricearray = array();


  //data
  date_default_timezone_set('Asia/Taipei');
  $year = date('Y');
  $month = date('m');
  $day = date('d');
  $date = $year . "-" . $month . "-" . $day;
  $a=rand(0,2147483647);
  
  if ($result_c->num_rows > 0) {
    // output data of each row
    while($row = $result_c->fetch_assoc()) {
          $IDarray[] = $row["p_id"];
          $namearray[] = $row["p_name"];
          $amountarray[] = $row["p_num"];
          $pricearray[] = $row["p_price"];
               
     $dataarray[] = $row;//將資料一筆一筆丟進dataarray
      
    }
  } 

  $amount = "";
  $product = "";

  $cnt = count($IDarray);
  $total = 0;
  for ($j = 0; $j < $cnt; $j++) {
      $total += $amountarray[$j] * $pricearray[$j];
      if ($product == "") {
            $product .= $namearray[$j];
            $amount .= $amountarray[$j];
        } else {
            $product .= "," . $namearray[$j];
            $amount .= "," . $amountarray[$j];
        }
    }

    if($product != ""){
      $sql = "INSERT INTO `checkout`(`checkout_id`, `account`, `product_name`, `product_amount`, `total`, `date`) 
      VALUES ('$a','appuser','$product','$amount','$total','$date');";
      mysqli_query($link, $sql);
      $sql = "DELETE FROM `cart` ;";
      mysqli_query($link, $sql);
	  $fulfilmentText = "好的，訂單已經送出，謝謝購買。";
    }
  	else{
      $fulfilmentText = "購物車為空，請先選購商品後再送出訂單。";
    }
    
    mysqli_free_result($result_c);   

}
/*-- 10. Send_yes end --*/

/*-- 12. Check_checkout start --*/
if ($displayName == "Check_checkout") {
  $sql_c = "SELECT * FROM `checkout` WHERE `account`= 'appuser' ";
   $result_c = mysqli_query($link, $sql_c);
 
   $output="";
   $cnt = 1;
   
   if ($result_c->num_rows > 0) {
     // output data of each row
     while($row = $result_c->fetch_assoc()) {
           
          
          $product_name = $row["product_name"];
          $total = $row["total"];
                
           
           $output .= "第 $cnt 筆：\n$product_name\n計 $total 元\n\n";
           $cnt += 1;
       
     }
 
     $fulfilmentText = "您的訂單如下:\n\n$output";
   } 
   else{
     $fulfilmentText =  mysqli_error();
   }
 }
/*-- 12. Check_checkout end --*/
  
    $sessionsClient->close();
    if (empty($fulfilmentText)) $fulfilmentText = '(no response)';
    return ($fulfilmentText);
  
    mysqli_close($link); // 關閉資料庫連結
}

//echo substr(urldecode($_SERVER['REQUEST_URI']),13) . " -> ";
echo detect_intent(substr(urldecode($_SERVER['REQUEST_URI']),13));
?>
