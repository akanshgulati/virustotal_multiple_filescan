$(document).ready(function(){
    $(".show_report").on('click',function(){
        var id=$(this).attr('id');
        $(".mc").remove();
        $.ajax({
            url: 'result.php?id=' + id,
            method: 'GET',
            dataType:'json'
        }).success(function(data){
            var tag=null;
            $.each(data,function(index,value){
                tag+="<tr class='mc'>";
                tag+="<td>"+index+"</td>";
                tag+="<td class='red'>"+value+"</td>";
                tag+="</tr>";
            });
            console.log(tag);
            $("#modal_table").append(tag);
            $('a.reveal-link').trigger('click');


        });
    });
});