<html !DOCTYPE>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/UI-Table-master/table.min.css">
    <link rel="stylesheet" href="css/UI-Form-master/form.min.css">
    <link rel="stylesheet" href="css/UI-Grid-master/grid.min.css">
    <link rel="stylesheet" href="css/style.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans&family=Rubik:wght@400;500&family=Share:wght@400;700&display=swap" rel="stylesheet"> 
</head>

<body>

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
                        var_dump($row);
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


            return $area_array_unique;

        }

        function getArea($area){
            $first_half = explode('(', $area);
            $second_half = explode(')', $first_half[1]);

            return $second_half[0];
        }
        
        function init_table(){
            $db = $this->db;
            $get_report_sql = "SELECT * FROM caloricValueData JOIN areas ON caloricValueData.area = areas.id ORDER BY `applicable_for` DESC";

            $result = $db->query($get_report_sql);
            if($result->num_rows > 0) {     

                while($row = $result->fetch_assoc()) {

                    $report[] = array(
                        'applicable_for' => $row['applicable_for'],
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
    $caloric_value = new caloricValue($db);

    // $caloric_value->init_db();

    $table = $caloric_value->init_table(); ?>

<div class="ui grid">

    <div class="ui inverted segment wrapper">
    <div class="row">

  <div class="ui inverted form">
    <div class="two fields">
      <div class="field">
        <label>First Name</label>
        <input placeholder="First Name" type="text">
      </div>
      <div class="field">
        <label>Last Name</label>
        <input placeholder="Last Name" type="text">
      </div>
    </div>
    <div class="inline field">
      <div class="ui checkbox">
        <input type="checkbox" tabindex="0" class="hidden">
        <label>I agree to the terms and conditions</label>
      </div>
    </div>
    <div class="ui submit button">Submit</div>
  </div>


<table class="ui selectable inverted table">
    <thead>
        <tr>
            <th>Applicable for</th>
            <th>Caloric Value</th>
            <th>Area</th>
        </tr>
    </thead>

<?php
foreach($table as $row){
    echo "<tr>
            <td>".$row['applicable_for']."</td>
            <td>".$row['cal_value']." </td>
            <td>".$row['area']." </td>
        </tr>";
} ?>

</table>
</div>
</div>
</div>
</body>
</html>

