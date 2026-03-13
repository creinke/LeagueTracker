import client from '../api/client';
import { Player, PlayerDetail } from '../types';

const PlayerService = {
  getPlayers: async (): Promise<Player[]> => {
    const response = await client.get<Player[]>('/player/list');
    return response.data;
  },

  getPlayer: async (id: number): Promise<PlayerDetail> => {
    const response = await client.get<PlayerDetail>(`/player/view/${id}`);
    return response.data;
  },
};

export default PlayerService;
