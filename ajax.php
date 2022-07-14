<?php
    require_once('database.php');

    class ajaxFunctions {

        protected $connection;
        protected $db;

        public function __construct($connection){
            $this->database = $connection;
            $this->db = $connection->connect();
        }
        
        function execute_request($request){
            if(is_callable([$this, $request])){
                $this->$request();
            }else{
                echo '<h3>Unable to call "' . $request . '" method: method missing</h3>';
            }

        }

        function load_more(){
            $db = $this->db;
            $offset = $_POST['offset'];
            $get_report_sql = "SELECT * FROM caloricValueData JOIN areas ON caloricValueData.area = areas.id ORDER BY `applicable_for` DESC LIMIT 50 OFFSET $offset";

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

                echo json_encode($report);
            } else {
                echo "Error: " . $db->error; 
            }  
        }
    }

    $db = new database;

    $ajaxRequest = new ajaxFunctions($db);

    $ajaxRequest->execute_request($_POST['method']);
?>


