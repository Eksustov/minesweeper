window.initRoomListeners = (roomId) => {
    if (!roomId) return;

    Echo.channel(`room.${roomId}`)
        .listen('.RoomClosed', () => {
            alert('Host left, room closed');
            window.location.href = '/';
        });
};