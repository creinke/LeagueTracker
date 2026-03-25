import client from '../api/client';
import {Game, ScoreEntryDetails} from '../types';

const getGamesByEvent = async (eventId: number): Promise<Game[]> => {
    const response = await client.get<Game[]>(`/game/list/${eventId}`);
    return response.data;
};

const getGameScores = async (gameId: number): Promise<ScoreEntryDetails> => {
    const response = await client.get<ScoreEntryDetails>(`/game/scores/${gameId}`);
    return response.data;
};

const saveGameScores = async (gameId: number, data: any): Promise<any> => {
    const response = await client.post(`/game/scores/${gameId}`, data);
    return response.data;
};

const getRoster = async (gameId: number): Promise<{
    currentGamePlayers: { id: number, name: string }[],
    leagueRoster: { id: number, name: string }[]
}> => {
    const response = await client.get(`/game/roster/${gameId}`);
    return response.data;
};

const substitutePlayers = async (gameId: number, playerIds: number[]): Promise<{
    success: boolean,
    message: string
}> => {
    const response = await client.post(`/game/substitute/${gameId}`, {playerIds});
    return response.data;
};

export default {
    getGamesByEvent,
    getGameScores,
    saveGameScores,
    getRoster,
    substitutePlayers,
};
