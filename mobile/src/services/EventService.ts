import client from '../api/client';
import {SessionWithEvents, EventDetail} from '../types';

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

export default {
    getEventsBySeason,
    getEventDetail,
    toggleRegistration,
};
