jQuery(function($){
    $('#pco-groups-search, #pco-groups-type, #pco-groups-day, #pco-groups-location').on('input change', function() {
        var search = $('#pco-groups-search').val().toLowerCase();
        var type = $('#pco-groups-type').val();
        var day = $('#pco-groups-day').val();
        var location = $('#pco-groups-location').val();
        $('.pco-group-card').each(function() {
            var $card = $(this);
            var match = true;
            if (search && !$card.find('h3').text().toLowerCase().includes(search)) match = false;
            if (type && $card.data('type') != type) match = false;
            if (day && $card.data('day') != day) match = false;
            if (location && $card.data('location') != location) match = false;
            $card.toggle(match);
        });
    });
});