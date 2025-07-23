document.addEventListener('DOMContentLoaded', function(){
    // news teaser content toggle functionality
    if(document.querySelector('.block-news .node--type-news-article.node--view-mode-teaser') !== null) {
        let newsTeaserTitles = document.querySelectorAll('.block-news .node--type-news-article.node--view-mode-teaser .title');

        for(let i = 0; i < newsTeaserTitles.length; i++ ) {
             newsTeaserTitles[i].addEventListener('click', function (e) {
                newsTeaserTitles[i].classList.toggle('content-open');
                newsTeaserTitles[i].nextElementSibling.classList.toggle('hide');
            });
        }
       
    }
});