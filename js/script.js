
$( document ).ready(function() {
    let resultCount = 50;
    console.log();
    console.log($(document).height() - $(window).height());

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
            resultCount = resultCount + 50;
            console.log(resultCount);
        });
    });

    function addToTable(results){
        console.log(results);

        $.each( results, function( index, result ){
            console.log( result.cal_value );
    
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