import client from '../api/client';
import {Player, PlayerDetail} from '../types';

const PlayerService = {
    getPlayers: async (): Promise<Player[]> => {
        const response = await client.get<Player[]>('/player/list');
        return response.data;
    },

    getPlayer: async (id: number): Promise<PlayerDetail> => {
        const response = await client.get<PlayerDetail>(`/player/view/${id}`);
        return response.data;
    },

    createPlayer: async (playerData: any): Promise<{ id: number; success: boolean }> => {
        const response = await client.post('/player/create', playerData);
        return response.data;
    },

    updatePlayer: async (id: number, playerData: any): Promise<{ success: boolean }> => {
        const response = await client.put(`/player/update/${id}`, playerData);
        return response.data;
    },
};

export default PlayerService;
