$(document).ready(function(){
	// rating - click
	$(document).on('click','.rating a',function(){
		var $thisA = $(this);
		var this_id = $thisA.data('id');
		$thisA.parents('.rating').find('a').each(function(index){
			$(this).removeClass('selected');
			if( $(this).data('id')<=this_id )
			{
				$(this).addClass('selected');
			}
		});
		$thisA.parents('.rating').find('input[name="REVIEW_TEXT_rate"]').val( this_id );
		return false;
	});

	// rating - hover
	$(document).on('mouseenter','.rating a',function(){
		var $thisA = $(this);
		var this_id = $thisA.data('id');
		$thisA.parents('.rating').find('a').removeClass('hover').each(function(index){
			if( $(this).data('id')<=this_id )
			{
				$(this).addClass('hover');
			}
		});
	}).on('mouseleave','.rating a',function(){
		$(this).parents('.rating').find('a').removeClass('hover');
	});

    $("#send_review").on("click",function(){
        RSGoPro_SummComment($(this))
        $("#reviewForm").submit()
    })

    var RSGoPro_Separator = ":SEPARATOR:";

    function RSGoPro_SummComment(forma)
    {
        console.log('Sending')
        var $reviewform = $(forma);
        var newVal = $reviewform.find('input[name="REVIEW_TEXT_rate"]').val() + RSGoPro_Separator +
            $reviewform.find('textarea[name="REVIEW_TEXT_plus"]').val() + RSGoPro_Separator +
            $reviewform.find('textarea[name="REVIEW_TEXT_minus"]').val() + RSGoPro_Separator +
            $reviewform.find('textarea[name="REVIEW_TEXT_comment"]').val();
        if($reviewform.find('textarea[id="REVIEW_TEXT_comment"]').val()=="")
            newVal = '';

        $reviewform.find('textarea[name="REVIEW_TEXT"]').val( newVal );

        if( newVal=='' )
        {
            $reviewform.find('textarea[name="REVIEW_TEXT_comment"]').css('border','1px solid red');

            setTimeout(function(){
                $reviewform.find('textarea[name="REVIEW_TEXT_comment"]').css('border','');
            },1200);

            return false;
        } else {
            showMath()
            return false;
        }

        return false;
    }

    function getRandomNumber(min, max) {
        min = Math.ceil(min)
        max = Math.floor(max)
        return Math.floor(Math.random() * (max - min) + min)
    }

    function showMath() {
        while ( true )
        {
            var number1 = getRandomNumber(1,10)
            var number2 = getRandomNumber(1,10)

            while( number1 === number2 ) {
                number2 = getRandomNumber(1,10)
            }

            var number3 = 0
            var result = number1 + number2;
            var action = '+'

            if (getRandomNumber(1,100) <= 42) {
                action = '-'
                if (number2 >= number1) {
                    number3 = number1
                    number1 = number2
                    number2 = number3
                }
                result = number1 - number2;
            }

            var variable = prompt("Решите пример: " + number1 + action + number2 + '=');
            if ( Number(variable) === result ) break;
            alert( "Не правильно" );
        }

        while ( true )
        {
            number1 = getRandomNumber(1,10)
            number2 = getRandomNumber(1,10)

            while( number1 === number2 ) {
                number2 = getRandomNumber(1,10)
            }

            result = number1 + number2;
            action = '+'

            if (getRandomNumber(1,100) <= 38) {
                if (number2 >= number1) {
                    number3 = number1
                    number1 = number2
                    number2 = number3
                }
                action = '-'
                result = number1 - number2;
            }

            variable = prompt("Еще один пример: " + number1 + action + number2 + '=');
            if ( Number(variable) === result ) break;
            alert( "Не правильно" );
        }
    }
});
