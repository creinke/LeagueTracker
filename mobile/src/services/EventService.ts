import client from '../api/client';
import {SessionWithEvents, EventDetail, EventResults} from '../types';

interface RegisterResponse {
    success: boolean;
    isRegistered: boolean;
    message: string;
}

const getEventsBySeason = async (seasonId: number): Promise<SessionWithEvents[]> => {
    const response = await client.get<SessionWithEvents[]>(`/event/list/${seasonId}`);
    return response.data;
};

const getEventDetail = async (id: number): Promise<EventDetail> => {
    const response = await client.get<EventDetail>(`/event/view/${id}`);
    return response.data;
};

const toggleRegistration = async (id: number): Promise<RegisterResponse> => {
    const response = await client.post<RegisterResponse>(`/event/register/${id}`);
    return response.data;
};

const getEventResults = async (id: number): Promise<EventResults> => {
    const response = await client.get<EventResults>(`/event/results/${id}`);
    return response.data;
};

const getShortcuts = async (): Promise<{
    nextEvent: { id: number; eventNumber: number; description: string } | null;
    lastEvent: { id: number; eventNumber: number; description: string } | null;
}> => {
    const response = await client.get(`/event/shortcuts`);
    return response.data;
};

export default {
    getEventsBySeason,
    getEventDetail,
    toggleRegistration,
    getEventResults,
    getShortcuts,
};
