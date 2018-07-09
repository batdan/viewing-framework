function executeQuery()
{
    var periode = 3 * 60 * 1000;
    var uri = window.location.pathname + window.location.search;

    $.post("/vendor/vw/framework/lib/core/ajax/ajax_ping.php",
	{
		action : 'ping',
        uri : uri
	},
	function success(data)
	{
		// console.log(data);
	}, 'json');

    setTimeout( function () {
        executeQuery();
    }, periode);
}

setTimeout( function () {
    executeQuery();
}, (3 * 60 * 1000));
