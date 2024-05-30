jQuery(document).ready(function ($) {
    $('.adminReplyComment').click(function (e) { 
        e.preventDefault();
        let commentReplyID = $(this).attr('data-comment-id');
        $('tr[data-reply-form='+commentReplyID+']').fadeIn(300);
    });
    $('.termCommentsButtons .cancel').click(function (e) { 
        e.preventDefault();
        $(this).closest('tr').fadeOut(300);
    });
});