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

            $content .= file_get_contents('http://mip-prd-web.azurewebsites.net/CustomDataDownload?LatestValue=false&Applicable=applicableFor&FromUtcDatetime='.$first_day_month_formatted.'.000Z&ToUtcDateTime='.$last_day_month_formatted.'.000Z&PublicationObjectStagingIds=PUBOBJ1660,PUBOB4507,PUBOB4508,PUBOB4510,PUBOB4509,PUBOB4511,PUBOB4512,PUBOB4513,PUBOB4514,PUBOB4515,PUBOB4516,PUBOB4517,PUBOB4518,PUBOB4519,PUBOB4521,PUBOB4520,PUBOB4522,PUBOBJ1661,PUBOBJ1662');

            $i--;
        }


        return $this->stringResultsToArray($content);

    }


    // turns the string recieved from the site into an array
    function stringResultsToArray($content){

        $dom = new DOMDocument;
        @$dom->loadHTML($content);

        $rows = $dom->getElementsByTagName('tr');
    
        foreach ($rows as $row){
            $results[] = $this->tdrows($row->childNodes);

        }

        $header = array_shift($results);

        return $results;
    }

    // processes each row from the table and turns it into an array
    function tdrows($elements){

        $keys = array('applicable_at', 'applicable_for', 'data_item', 'value', 'generated_time');
        
        foreach ($elements as $element) {

            if($element->tagName == 'td'){
                $rowArray[] = $element->nodeValue;
            }
            $rowArray_with_keys = array_fill_keys($keys, $rowArray);

        }
        $rowArray_with_keys = array_fill_keys($keys, $rowArray[1]);

        return $rowArray_with_keys;
    }
    


}

$caloric_value = new caloricValue;

var_dump($caloric_value->get_report());

