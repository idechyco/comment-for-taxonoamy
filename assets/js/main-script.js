jQuery(document).ready(function ($) {
    $('.comment-reply-link').click(function () { 
        let currentCommentID = $(this).attr('data-comment-id');
        $('input[name=comment_parent]').val(currentCommentID);
        
    });
});