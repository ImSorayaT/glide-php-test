<!DOCTYPE html>

    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="css/UI-Table-master/table.min.css">
        <link rel="stylesheet" href="css/UI-Grid-master/grid.min.css">
        <link rel="stylesheet" href="css/UI-Button-master/button.min.css">
        <link rel="stylesheet" href="css/style.css">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Open+Sans&family=Rubik:wght@400;500&family=Share:wght@400;700&display=swap" rel="stylesheet"> 

        <script
                src="https://code.jquery.com/jquery-3.6.0.min.js"
                integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4="
                crossorigin="anonymous"></script>

        <script src="js/script.js"></script>
    </head>

<body>
    <?php
    require_once('caloricValue.php');


        $table = $caloric_value->init_table(); ?>

        <div class="ui grid">

            <div class="ui inverted segment wrapper">
                <div class="row">
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

                    <button id="load-more" class="ui button inverted">load more</button>
                </div>
            </div>
        </div>
    </body>
</html>

