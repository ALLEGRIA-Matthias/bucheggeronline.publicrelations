$('#dice').click(function(){
    require(['TYPO3/CMS/Core/Ajax/AjaxRequest'], function (AjaxRequest) {
        // Generate a random number between 1 and 32
        const randomNumber = Math.ceil(Math.random() * 32);
        new AjaxRequest(TYPO3.settings.ajaxUrls.example_dosomething)
        .withQueryArguments({input: randomNumber})
        .get()
        .then(async function (response) {
        const resolved = await response.resolve();
        console.log(resolved.result);
        if(resolved.result) {
            require(['TYPO3/CMS/Backend/Notification'], function (Notification) {
                Notification.success(
                        'Die Würfel sind gefallen…',
                        'Resultat: ' + resolved.result,
                        10
                );
            });
        }
        });
    });
});