$(function() {
    $('#creer').click(function(ev) {
        ev.preventDefault();
        var url = $(this).attr('href');
        $.get(url, {}, function(data) {
            var pkmn = data['pokemon'];
            $('.pokemons').append('<div class="pokemon"><a href="'+pkmn.url+'">'+pkmn.nom+'</a></div>');
        });
    });
});
