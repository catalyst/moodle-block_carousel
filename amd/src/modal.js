define(['core/modal_factory', 'jquery'], function(ModalFactory, $) {
    return {
        init: function(rowid, modalContent) {
            var slide = document.querySelector('#id_slide' + rowid);
            slide.addEventListener('click', function(){
                ModalFactory.create(
                    {
                        type: ModalFactory.types.CANCEL,
                        title: '',
                        body: modalContent
                    }
                ).then($.proxy(function(modal) {
                    modal.setLarge();
                    modal.show();
                }));
            });
        }
    };
});