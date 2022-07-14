<?php 
require_once('database.php');

    class caloricValue {
        protected $connection;
        protected $db;

        public function __construct($connection){
            $this->database = $connection;
            $this->db = $connection->connect();
        }

        function get_report(){
            
            $current_day = new DateTime();

            // Get current month and year and turn it into an int
            $current_month = intval( date_format($current_day, 'n') );
            $current_year = intval( date_format($current_day, 'Y') );
            
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
            $keys = array('applicable_at', 'applicable_for', 'data_item', 'value', 'generated_time', 'quality_indicator');

            foreach ($elements as $element) {
                if($element->tagName == 'td'){
                    $rowArray[] = $element->nodeValue;
                }
            }
            
            $rowArray_with_keys = array_combine($keys, $rowArray);
            return $rowArray_with_keys;
        }

        function init_db(){
            $db = $this->db;

            $create_areas_table = "CREATE TABLE areas (
                id INT(6) AUTO_INCREMENT PRIMARY KEY,
                area_code VARCHAR(300) NOT NULL  
                )ENGINE INNODB";

            $create_caloricValues_table = "CREATE TABLE caloricValueData (
                    applicable_for DATETIME NOT NULL,
                    cal_value FLOAT (10.4),
                    area INT (6),
                    FOREIGN KEY (area) REFERENCES areas(id)
                    
                )ENGINE INNODB";
                    

                
                if ($db->query($create_areas_table) === TRUE && $db->query($create_caloricValues_table) === TRUE) {
                    $content = $this->get_report();

                    $this->fill_area_table($db, $content);
                    $this->fill_caloricValueData_table($db, $content);


                }else{
                    echo "Error creating table: " . $db->error;
                }
        }

        function fill_area_table($db, $content ){

            $area_array = $this->getAreas($content);

            foreach($area_array as $area){

                $fill_dataItems_table_sql = "INSERT INTO areas(`area_code`) VALUES ('$area')";
                if ($db->query($fill_dataItems_table_sql) === TRUE) {              
                } else {
                    echo "Error creating table: " . $db->error;
                }        

            }
        }

        function fill_caloricValueData_table($db, $content ){

            $area_array = $this->getAreas($content);
            
            foreach($content as $row){
                if(!empty($row)){
                    if($row["data_item"]){
                        $area = $this->getArea($row['data_item']);
                        $area_id = array_search($area, $area_array) +1;

                        
                    }

                    if($row["value"]){
                        $value = floatval($row["value"]);
                    }else{
                        // var_dump($row);
                        $value = NULL;
                    }



                    if($row['applicable_for']){

                        $parsed_date = date_parse_from_format("d/m/Y", $row['applicable_for']);

                        $month = str_pad(intval($parsed_date['month']), 2, '0', STR_PAD_LEFT);
                        $day = str_pad(intval($parsed_date['day']), 2, '0', STR_PAD_LEFT);

                        $applicable_for = $parsed_date['year'].'-'.$month.'-'.$day;                    

                    }else{
                        $applicable_for = NULL;
                    }
                    

                    $fill_dataItems_table_sql = "INSERT INTO caloricValueData(`applicable_for`, `cal_value`, `area`) VALUES ( '$applicable_for','$value', '$area_id')";
                    if ($db->query($fill_dataItems_table_sql) === TRUE) {              
                    } else {
                        echo "Error creating table: " . $db->error;
                    }        

                }
            }
        }

        function getAreas( array $content){

            foreach($content as $row){ 
                $area_array[] = $this->getArea($row['data_item']); 
            }

            $area_array_no_empty = array_filter($area_array);
            $area_array_unique = array_unique($area_array_no_empty);


            return array_values($area_array_unique);

        }

        function getArea($area){
            
            $remove_first = explode(',', $area);

            if(strpos($remove_first[1], '(') !== false){
                $first_parenth = explode('(', $remove_first[1]);
                $second_parenth = explode(')', $first_parenth[1]);

                return $second_parenth[0];
                
            }else{
                return $remove_first[1];
            }

    
            

            
        }
        
        function init_table(){
            $db = $this->db;
            $get_report_sql = "SELECT * FROM caloricValueData JOIN areas ON caloricValueData.area = areas.id ORDER BY `applicable_for` DESC LIMIT 50";

            $result = $db->query($get_report_sql);
            if($result->num_rows > 0) {     

                while($row = $result->fetch_assoc()) {
                    $date = strtotime($row['applicable_for']);

                    $report[] = array(
                        'applicable_for' => date('d/M/Y', $date),
                        'cal_value' => $row['cal_value'],
                        'area' => $row['area_code'],
                    );
                    
                }

                return $report;
            } else {
                echo "Error: " . $db->error; 
            }     
        
        }


    }

    $db = new database;
    $caloric_value = new caloricValue($db); ?>