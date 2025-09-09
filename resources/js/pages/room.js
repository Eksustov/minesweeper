document.addEventListener('DOMContentLoaded', () => {
    const meta = document.getElementById('room-meta');
    if (!meta) return;
  
    const roomId = meta.dataset.roomId;
  
    window.Echo.channel(`room.${roomId}`)
    .listen('.PlayerJoined', (e) => { // <-- add the dot here
        const list = document.getElementById('playersList');
        if (!list) return;
        list.innerHTML = '';
        e.players.forEach(player => {
        list.insertAdjacentHTML('beforeend', `<li>${player.name}</li>`);
        });
    });
  });
  