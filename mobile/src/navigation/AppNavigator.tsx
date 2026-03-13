import React from 'react';
import {createStackNavigator} from '@react-navigation/stack';
import {useAuth} from '../context/AuthContext';
import {View, ActivityIndicator} from 'react-native';

import LoginScreen from '../screens/Login/LoginScreen';
import HomeScreen from '../screens/Home/HomeScreen';
import PlayerListScreen from '../screens/PlayerList/PlayerListScreen';
import PlayerDetailScreen from '../screens/PlayerList/PlayerDetailScreen';
import SeasonListScreen from '../screens/SeasonList/SeasonListScreen';
import SeasonDetailScreen from '../screens/SeasonList/SeasonDetailScreen';
import EventListScreen from '../screens/EventList/EventListScreen';
import EventDetailScreen from '../screens/EventDetail/EventDetailScreen';
import GameListScreen from '../screens/GameList/GameListScreen';
import ScoreEntryScreen from '../screens/GameList/ScoreEntryScreen';
import SubstitutionScreen from '../screens/GameList/SubstitutionScreen';

const Stack = createStackNavigator();

export default function AppNavigator() {
    const {user, loading} = useAuth();

    if (loading) {
        return (
            <View style={{flex: 1, justifyContent: 'center', alignItems: 'center'}}>
                <ActivityIndicator size="large"/>
            </View>
        );
    }

    return (
        <Stack.Navigator>
            {user ? (
                <>
                    <Stack.Screen name="Home" component={HomeScreen} options={{title: 'League Tracker Home'}}/>
                    <Stack.Screen name="PlayerList" component={PlayerListScreen} options={{title: 'Players'}}/>
                    <Stack.Screen name="PlayerDetail" component={PlayerDetailScreen}
                                  options={{title: 'Player Detail'}}/>
                    <Stack.Screen name="SeasonList" component={SeasonListScreen} options={{title: 'Seasons'}}/>
                    <Stack.Screen name="SeasonDetail" component={SeasonDetailScreen}
                                  options={{title: 'Season Detail'}}/>
                    <Stack.Screen name="EventList" component={EventListScreen} options={{title: 'Events'}}/>
                    <Stack.Screen name="EventDetail" component={EventDetailScreen} options={{title: 'Event Detail'}}/>
                    <Stack.Screen name="GameList" component={GameListScreen} options={{title: 'Games'}}/>
                    <Stack.Screen name="ScoreEntry" component={ScoreEntryScreen}/>
                    <Stack.Screen name="Substitution" component={SubstitutionScreen}
                                  options={{title: 'Substitute Players'}}/>
                </>
            ) : (
                <Stack.Screen name="Login" component={LoginScreen} options={{headerShown: false}}/>
            )}
        </Stack.Navigator>
    );
}
