import React, {createContext, useState, useEffect, useContext, ReactNode} from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import {User, League} from '../types';
import {setApiToken} from '../api/client';

interface AuthContextData {
    user: User | null;
    league: League | null;
    token: string | null;
    loading: boolean;
    signIn: (token: string, user: User, league: League) => Promise<void>;
    signOut: () => Promise<void>;
}

const AuthContext = createContext<AuthContextData>({} as AuthContextData);

export const AuthProvider: React.FC<{ children: ReactNode }> = ({children}) => {
    const [user, setUser] = useState<User | null>(null);
    const [league, setLeague] = useState<League | null>(null);
    const [token, setToken] = useState<string | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        async function loadStorageData() {
            const storagedUser = await AsyncStorage.getItem('user');
            const storagedToken = await AsyncStorage.getItem('apiToken');
            const storagedLeague = await AsyncStorage.getItem('league');

            if (storagedUser && storagedToken && storagedLeague) {
                setUser(JSON.parse(storagedUser));
                setToken(storagedToken);
                setLeague(JSON.parse(storagedLeague));
                setApiToken(storagedToken);
            }
            setLoading(false);
        }

        loadStorageData();
    }, []);

    async function signIn(apiToken: string, userData: User, leagueData: League) {
        setUser(userData);
        setToken(apiToken);
        setLeague(leagueData);
        setApiToken(apiToken);

        await AsyncStorage.setItem('user', JSON.stringify(userData));
        await AsyncStorage.setItem('apiToken', apiToken);
        await AsyncStorage.setItem('league', JSON.stringify(leagueData));
    }

    async function signOut() {
        await AsyncStorage.multiRemove(['user', 'apiToken', 'league']);
        setUser(null);
        setToken(null);
        setLeague(null);
        setApiToken(null);
    }

    return (
        <AuthContext.Provider value={{user, league, token, loading, signIn, signOut}}>
            {children}
        </AuthContext.Provider>
    );
};

export function useAuth() {
    const context = useContext(AuthContext);
    return context;
}
