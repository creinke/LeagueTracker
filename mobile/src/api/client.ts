import axios from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';
import Constants from 'expo-constants';

// Get local development IP from Expo Constants or fallback to local domain
const isWeb = typeof window !== 'undefined' && window.location && window.location.origin;
const origin = isWeb ? window.location.origin : '';

// Function to get the current host without port, for the fallback
const getFallbackBaseUrl = () => {
    if (isWeb && window.location.hostname) {
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
             return `http://leaguetracker.local/api`;
        }
        return `http://${window.location.hostname}/api`;
    }
    return 'http://192.168.4.76/api';
};

const API_BASE_URL = isWeb
    ? (origin.includes('localhost') || origin.includes('192.168.')
        ? getFallbackBaseUrl() // Use the same hostname but drop the port (assuming backend is on 80)
        : `${origin}/api`) // Use relative URL for production PWA
    : (Constants.expoConfig?.extra?.apiUrl || 'http://192.168.4.76/api');

const client = axios.create({
    baseURL: API_BASE_URL,
    headers: {
        'Content-Type': 'application/json',
    },
});

let inMemoryToken: string | null = null;

export const setApiToken = (token: string | null) => {
    inMemoryToken = token;
};

client.interceptors.request.use(async (config) => {
    const token = inMemoryToken || await AsyncStorage.getItem('apiToken');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
}, (error) => {
    return Promise.reject(error);
});

client.interceptors.response.use((response) => {
    return response;
}, (error) => {
    return Promise.reject(error);
});

export default client;
