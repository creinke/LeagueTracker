import client from '../api/client';
import {Season, SeasonDetail} from '../types';

const SeasonService = {
    getSeasons: async (): Promise<Season[]> => {
        const response = await client.get<Season[]>('/season/list');
        return response.data;
    },

    getSeason: async (id: number): Promise<SeasonDetail> => {
        const response = await client.get<SeasonDetail>(`/season/view/${id}`);
        return response.data;
    },
};

export default SeasonService;
