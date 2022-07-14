
$( document ).ready(function() {
    let resultCount = 50;

    $('#load-more').click(function() {

        $.ajax({
            url: "ajax.php",
            data: {
                method: 'load_more',
                dataType: "json",
                offset: resultCount
            },
            method: 'POST',
            }).done(function(result) {

            addToTable(JSON.parse(result));
            resultCount = resultCount + 500;
        });
    });

    function addToTable(results){

        $.each( results, function( index, result ){
    
            $('tbody').append(`
                <tr>
                    <td>${result.applicable_for}</td>
                    <td>${result.cal_value}</td>
                    <td>${result.area}</td>
                </tr>
            `);
        });

    }
});