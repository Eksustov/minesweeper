window.initRoomListeners = ({ roomId, userId, redirectUrl }) => {
    if (!roomId) return;

    const channelName = `room.${roomId}`;
    window.Echo.leave(channelName);

    const ch = Echo.channel(channelName);

    ch.listen('.RoomClosed', () => {
        alert('Host left, room closed');
        window.location.href = redirectUrl;
    });

    ch.listen('.GameStarted', (e) => {
        if (e?.url && window.location.href !== e.url) {
            window.location.href = e.url;
        }
    });

    ch.listen('.RoomUpdated', (e) => {
        if (Array.isArray(e.players)) {
            window.rebuildPlayersList?.(e.players);
        }
    });

    ch.listen('.PlayerJoined', (e) => {
        if (Array.isArray(e.players)) {
            window.rebuildPlayersList?.(e.players);
        }
    });

    ch.listen('.PlayerLeft', (e) => {
        if (Array.isArray(e.players)) {
            window.rebuildPlayersList?.(e.players);
        }
    });

    ch.listen('.PlayerKicked', (e) => {
        if (userId && e.playerId === userId) {
            window.location.href = redirectUrl;
            return;
        }

        if (Array.isArray(e.players)) {
            window.rebuildPlayersList?.(e.players);
        } else {
            const current = document.querySelectorAll('#playersList > li').length;
            window.updateCapacity?.(current);
        }
    });
};