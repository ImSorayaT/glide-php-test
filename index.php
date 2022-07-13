<?php
class caloricValue {

    function get_report(){
        
        $current_day = new DateTime();
        // Get current month and turn it into an int
        $current_month = intval( date_format($current_day, 'n') );
        $current_year = intval( date_format($current_day, 'Y') );
        
    
        // echo  $current_month;
        $content = '';
        $i = $current_month;
        while($i > 0){
            $month = $i;
            $last_day = cal_days_in_month(CAL_GREGORIAN, $month, $current_year);
            
            $first_day_month = new DateTime($current_year.'/'.$month.'/01');
            $last_day_month = new DateTime($current_year.'/'.$month.'/'.$last_day);

            // vars should return ex. 2022-01-01T00:00:00
            $first_day_month_formatted = date_format($first_day_month, 'Y-m-d\TH:i:s');
            $last_day_month_formatted = date_format($last_day_month, 'Y-m-d\TH:i:s');

            // echo $first_day_month_formatted.'<br>';
            // echo $last_day_month_formatted.'<br>';

            $content .= file_get_contents('http://mip-prd-web.azurewebsites.net/CustomDataDownload?LatestValue=false&Applicable=applicableFor&FromUtcDatetime='.$first_day_month_formatted.'.000Z&ToUtcDateTime='.$last_day_month_formatted.'.000Z&PublicationObjectStagingIds=PUBOBJ1660,PUBOB4507,PUBOB4508,PUBOB4510,PUBOB4509,PUBOB4511,PUBOB4512,PUBOB4513,PUBOB4514,PUBOB4515,PUBOB4516,PUBOB4517,PUBOB4518,PUBOB4519,PUBOB4521,PUBOB4520,PUBOB4522,PUBOBJ1661,PUBOBJ1662');

            $i--;
        }

        var_dump($content);

    }

}

$caloric_value = new caloricValue;

$caloric_value->get_report();

// $current_month = MONTH($current_day);



?>