define(['core/ajax'], function(Ajax) {
    return {
        init: function(rowid) {
            var slide = document.querySelector('#id_slide' + rowid);
            slide.addEventListener('click', function(){
                var request = {
                    methodname: 'block_carousel_record_interaction',
                    args: {
                        rowid: rowid
                    }
                };
                Ajax.call([request])[0].fail();
            });
        }
    };
});